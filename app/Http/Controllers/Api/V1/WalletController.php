<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Elliptic\EC;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use kornrunner\Keccak;

class WalletController extends Controller
{
    private const NONCE_TTL_MINUTES = 5;

    private const SECP256K1_HALF_N = '7FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF5D576E7357A4501DDFE92F46681B20A0';

    /**
     * Get nonce for wallet connection (authenticated users only).
     */
    public function getLinkNonce(Request $request)
    {
        $request->validate([
            'address' => ['required', 'string', 'regex:/^0x[a-fA-F0-9]{40}$/'],
        ]);

        $user = $request->user();
        $address = strtolower($request->address);

        if ($user->wallet_address) {
            return response()->json([
                'message' => 'Wallet already connected to this account.',
            ], 422);
        }

        if (User::where('wallet_address', $address)->exists()) {
            return response()->json([
                'message' => 'This wallet is already linked to another account.',
            ], 422);
        }

        $nonce = $this->buildLinkMessage($user->id, $address);

        Cache::put(
            $this->linkNonceCacheKey($user->id, $address),
            $nonce,
            now()->addMinutes(self::NONCE_TTL_MINUTES)
        );

        return response()->json(['nonce' => $nonce]);
    }

    /**
     * Link wallet to authenticated user account.
     */
    public function linkWallet(Request $request)
    {
        $request->validate([
            'address' => ['required', 'string', 'regex:/^0x[a-fA-F0-9]{40}$/'],
            'signature' => ['required', 'string', 'regex:/^0x[a-fA-F0-9]{130}$/'],
        ]);

        $address = strtolower($request->address);
        $user = $request->user();
        $nonce = Cache::pull($this->linkNonceCacheKey($user->id, $address));

        if (!$nonce) {
            Log::warning('Wallet link rejected: nonce missing or expired', [
                'user_id' => $user->id,
                'address' => $address,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Nonce expired or not found. Please try again.',
            ], 422);
        }

        if (!$this->isValidWalletSignature($address, $request->signature, $nonce)) {
            Log::warning('Wallet link rejected: invalid signature', [
                'user_id' => $user->id,
                'address' => $address,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Signature verification failed',
            ], 401);
        }

        $result = DB::transaction(function () use ($user, $address) {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($lockedUser->wallet_address) {
                return 'already_connected';
            }

            if (User::where('wallet_address', $address)->lockForUpdate()->exists()) {
                return 'already_linked';
            }

            $lockedUser->wallet_address = $address;
            $lockedUser->save();

            return 'success';
        });

        return match ($result) {
            'already_connected' => response()->json([
                'message' => 'Wallet already connected to this account.',
            ], 422),
            'already_linked' => response()->json([
                'message' => 'This wallet is already linked to another account.',
            ], 422),
            default => response()->json([
                'message' => 'Wallet connected successfully',
                'wallet_address' => $address,
            ]),
        };
    }

    /**
     * Get nonce for wallet signature (account security bypass).
     */
    public function getSecurityNonce(Request $request)
    {
        $request->validate([
            'address' => ['required', 'string', 'regex:/^0x[a-fA-F0-9]{40}$/'],
        ]);

        $user = $request->user();
        $address = strtolower($request->address);

        if (!$user->wallet_address || $user->wallet_address !== $address) {
            return response()->json([
                'message' => 'This wallet is not connected to your account.',
            ], 403);
        }

        $nonce = $this->buildSecurityMessage($user->id, $address);

        Cache::put(
            $this->securityNonceCacheKey($user->id, $address),
            $nonce,
            now()->addMinutes(self::NONCE_TTL_MINUTES)
        );

        return response()->json(['nonce' => $nonce]);
    }

    /**
     * Verify wallet signature for account security bypass.
     */
    public function verifySecuritySignature(Request $request)
    {
        $request->validate([
            'address' => ['required', 'string', 'regex:/^0x[a-fA-F0-9]{40}$/'],
            'signature' => ['required', 'string', 'regex:/^0x[a-fA-F0-9]{130}$/'],
            'duration' => ['required', 'integer', 'min:5', 'max:120'],
        ]);

        $user = $request->user();
        $address = strtolower($request->address);
        $nonce = Cache::pull($this->securityNonceCacheKey($user->id, $address));

        if (!$user->wallet_address || $user->wallet_address !== $address) {
            return response()->json([
                'message' => 'This wallet is not connected to your account.',
            ], 403);
        }

        if (!$nonce) {
            return response()->json([
                'message' => 'Nonce expired or not found. Please try again.',
            ], 422);
        }

        if (!$this->isValidWalletSignature($address, $request->signature, $nonce)) {
            Log::warning('Wallet security verification rejected: invalid signature', [
                'user_id' => $user->id,
                'address' => $address,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Signature verification failed',
            ], 401);
        }

        $accountSecurity = $user->accountSecurity;

        if (!$accountSecurity) {
            $accountSecurity = $user->accountSecurity()->create([
                'length' => $request->duration * 60,
            ]);
        }

        $accountSecurity->update([
            'unlocked' => true,
            'until' => time() + ($request->duration * 60),
            'length' => $request->duration * 60,
        ]);

        if (Schema::hasTable('user_events')) {
            $user->events()->create([
                'event' => 'غیر فعال سازی امنیت حساب کاربری (کیف پول)',
                'ip' => $request->ip(),
                'device' => $request->userAgent(),
                'status' => 1,
            ]);
        }

        return response()->json([
            'message' => 'Account security unlocked successfully',
            'until' => $accountSecurity->until,
        ]);
    }

    private function buildLinkMessage(int $userId, string $address): string
    {
        return implode("\n", [
            'Link wallet to your ' . config('app.name') . ' account at ' . $this->applicationDomain() . '.',
            '',
            'Account ID: ' . $userId,
            'Wallet: ' . $address,
            'Nonce: ' . Str::random(32),
        ]);
    }

    private function buildSecurityMessage(int $userId, string $address): string
    {
        return implode("\n", [
            'Unlock account security on ' . config('app.name') . ' at ' . $this->applicationDomain() . '.',
            '',
            'Account ID: ' . $userId,
            'Wallet: ' . $address,
            'Nonce: ' . Str::random(32),
        ]);
    }

    private function applicationDomain(): string
    {
        $host = parse_url(config('app.url'), PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : 'localhost';
    }

    private function linkNonceCacheKey(int $userId, string $address): string
    {
        return 'web3_nonce_link_' . $userId . '_' . $address;
    }

    private function securityNonceCacheKey(int $userId, string $address): string
    {
        return 'web3_nonce_security_' . $userId . '_' . $address;
    }

    private function isValidWalletSignature(string $address, string $signature, string $nonce): bool
    {
        $r = substr($signature, 2, 64);
        $s = substr($signature, 66, 64);
        $v = hexdec(substr($signature, 130, 2));

        if (!ctype_xdigit($r) || !ctype_xdigit($s)) {
            return false;
        }

        if (strcasecmp($s, self::SECP256K1_HALF_N) > 0) {
            return false;
        }

        if ($v < 27) {
            $v += 27;
        }

        $recoveryParam = $v - 27;
        if ($recoveryParam !== 0 && $recoveryParam !== 1) {
            return false;
        }

        $msgLength = strlen($nonce);
        $messagePrefix = "\x19Ethereum Signed Message:\n" . $msgLength . $nonce;
        $msgHash = Keccak::hash($messagePrefix, 256);

        try {
            $ec = new EC('secp256k1');
            $publicKey = $ec->recoverPubKey($msgHash, [
                'r' => $r,
                's' => $s,
            ], $recoveryParam);

            $derivedAddress = '0x' . substr(Keccak::hash(hex2bin(substr($publicKey->encode('hex'), 2)), 256), -40);

            return strtolower($derivedAddress) === $address;
        } catch (\Exception $e) {
            return false;
        }
    }
}
