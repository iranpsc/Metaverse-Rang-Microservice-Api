<?php

namespace App\Services;

use App\Models\Ip;
use Closure;
use Illuminate\Support\Facades\Cache;

class FilterIpRangeService
{
    public function handle($request, Closure $next)
    {
        $allowedIpRanges = Cache::remember('allowed_ip_ranges', 60 * 60 * 24, function () {
            return Ip::whereType('range')->orderBy('from')->get();
        });

        $ip = ip2long($request->ip());

        $left = 0;
        $right = count($allowedIpRanges) - 1;

        while ($left <= $right) {
            $mid = ($left + $right) >> 1;
            $ipRange = $allowedIpRanges[$mid];

            if ($ip >= $ipRange->from && $ip <= $ipRange->to) {
                return long2ip($ip);
            } elseif ($ip < $ipRange->from) {
                $right = $mid - 1;
            } else {
                $left = $mid + 1;
            }
        }

        return $next($request);
    }
}
