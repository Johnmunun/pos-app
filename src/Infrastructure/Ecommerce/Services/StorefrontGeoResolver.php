<?php

namespace Src\Infrastructure\Ecommerce\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class StorefrontGeoResolver
{
    /**
     * @return array{country_code: ?string, region: ?string, city: ?string}
     */
    public static function forRequest(Request $request): array
    {
        $cf = strtoupper(trim((string) $request->header('CF-IPCountry', '')));
        if ($cf !== '' && $cf !== 'XX' && strlen($cf) === 2) {
            return ['country_code' => $cf, 'region' => null, 'city' => null];
        }

        $ip = (string) $request->ip();
        if ($ip === '' || self::isPrivateOrReservedIp($ip)) {
            return ['country_code' => null, 'region' => null, 'city' => null];
        }

        try {
            $response = Http::timeout(2)->get('http://ip-api.com/json/'.$ip, [
                'fields' => 'status,countryCode,regionName,city',
            ]);
            if (!$response->successful()) {
                return ['country_code' => null, 'region' => null, 'city' => null];
            }
            $data = $response->json();
            if (!is_array($data) || ($data['status'] ?? '') !== 'success') {
                return ['country_code' => null, 'region' => null, 'city' => null];
            }
            $code = isset($data['countryCode']) ? strtoupper(substr((string) $data['countryCode'], 0, 2)) : null;
            $region = isset($data['regionName']) ? mb_substr((string) $data['regionName'], 0, 120) : null;
            $city = isset($data['city']) ? mb_substr((string) $data['city'], 0, 120) : null;

            return [
                'country_code' => $code !== '' ? $code : null,
                'region' => $region !== '' ? $region : null,
                'city' => $city !== '' ? $city : null,
            ];
        } catch (\Throwable) {
            return ['country_code' => null, 'region' => null, 'city' => null];
        }
    }

    private static function isPrivateOrReservedIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
