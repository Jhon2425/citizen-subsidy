<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PsgcService
{
    protected const BASE = 'https://psgc.gitlab.io/api';

    /** PSGC changes a few times a year at most. */
    protected const TTL = 60 * 60 * 24 * 30;

    /**
     * @return array<int, array{code: string, name: string}>
     */
    public function regions(): array
    {
        return $this->fetch('psgc.regions', '/regions/');
    }

    /**
     * NCR and some special areas have no provinces — callers should
     * fall back to citiesInRegion() when this returns empty.
     *
     * @return array<int, array{code: string, name: string}>
     */
    public function provinces(string $regionCode): array
    {
        return $this->fetch(
            "psgc.provinces.{$regionCode}",
            "/regions/{$regionCode}/provinces/"
        );
    }

    /**
     * @return array<int, array{code: string, name: string}>
     */
    public function cities(string $provinceCode): array
    {
        return $this->fetch(
            "psgc.cities.{$provinceCode}",
            "/provinces/{$provinceCode}/cities-municipalities/"
        );
    }

    /**
     * @return array<int, array{code: string, name: string}>
     */
    public function citiesInRegion(string $regionCode): array
    {
        return $this->fetch(
            "psgc.cities.region.{$regionCode}",
            "/regions/{$regionCode}/cities-municipalities/"
        );
    }

    /**
     * @return array<int, array{code: string, name: string}>
     */
    public function barangays(string $cityCode): array
    {
        return $this->fetch(
            "psgc.barangays.{$cityCode}",
            "/cities-municipalities/{$cityCode}/barangays/"
        );
    }

    /**
     * Return a cached PSGC endpoint, or fetch and cache it.
     *
     * Only SUCCESSFUL responses are cached (an empty list is a valid
     * success, e.g. NCR has no provinces). A failed request returns []
     * but is never cached, so one network hiccup can't leave the form
     * broken for the whole cache TTL.
     *
     * @return array<int, array{code: string, name: string}>
     */
    protected function fetch(string $cacheKey, string $path): array
    {
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $data = $this->request($path);

        if ($data === null) {
            return [];
        }

        Cache::put($cacheKey, $data, self::TTL);

        return $data;
    }

    /**
     * Call the PSGC API. Returns null on any failure.
     *
     * @return array<int, array{code: string, name: string}>|null
     */
    protected function request(string $path): ?array
    {
        try {
            $response = Http::timeout(10)
                ->retry(2, 200)
                ->acceptJson()
                ->get(self::BASE . $path);

            if ($response->failed()) {
                Log::warning('PSGC request failed', [
                    'path' => $path,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $json = $response->json();

            if (! is_array($json)) {
                Log::warning('PSGC returned an unexpected payload', ['path' => $path]);

                return null;
            }

            return collect($json)
                ->map(fn ($item) => [
                    'code' => $item['code'],
                    'name' => $item['name'],
                ])
                ->sortBy('name', SORT_NATURAL)
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::error('PSGC request errored', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}