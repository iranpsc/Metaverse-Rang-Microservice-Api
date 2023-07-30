<?php

namespace App\Services;

use Closure;
use App\Models\Ip;
use Illuminate\Support\Facades\Cache;

class FilterIpService
{
    public function handle($request, Closure $next)
    {
        $allowedIps = Cache::remember('allowed_api_ips', 60 * 60 * 24, function () {
            return Ip::whereType('api')->get();
        });

        $ip = ip2long($request->ip());

        foreach ($allowedIps as $allowedIp) {
            if($ip === $allowedIp->from) {
                return long2ip($ip);
            }
        }

        return false;
    }
}
