<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class PSGCController extends Controller
{
    /**
     * Base URL for the PSGC API
     */
    protected $baseUrl = 'https://psgc.gitlab.io/api';
    
    /**
     * Cache TTL in seconds (24 hours)
     */
    protected $cacheTtl = 86400;
    
    /**
     * Get all provinces
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProvinces()
    {
        return Cache::remember('psgc_provinces', $this->cacheTtl, function () {
            try {
                $response = Http::get("{$this->baseUrl}/provinces");
                
                if ($response->successful()) {
                    $provinces = $response->json();
                    
                    // Sort provinces alphabetically
                    usort($provinces, function ($a, $b) {
                        return strcmp($a['name'], $b['name']);
                    });
                    
                    return response()->json($provinces);
                }
                
                return response()->json(['error' => 'Failed to fetch provinces from PSGC API'], 500);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to connect to PSGC API: ' . $e->getMessage()], 500);
            }
        });
    }
    
    /**
     * Get cities/municipalities by province code
     *
     * @param string $provinceCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCitiesByProvince($provinceCode)
    {
        return Cache::remember("psgc_cities_{$provinceCode}", $this->cacheTtl, function () use ($provinceCode) {
            try {
                // First, check if the province exists
                $provinceResponse = Http::get("{$this->baseUrl}/provinces/{$provinceCode}");
                
                if (!$provinceResponse->successful()) {
                    return response()->json(['error' => 'Province not found'], 404);
                }
                
                // Get municipalities within this province
                $municipalitiesResponse = Http::get("{$this->baseUrl}/provinces/{$provinceCode}/municipalities");
                
                // Get cities within this province
                $citiesResponse = Http::get("{$this->baseUrl}/provinces/{$provinceCode}/cities");
                
                if ($municipalitiesResponse->successful() && $citiesResponse->successful()) {
                    $municipalities = $municipalitiesResponse->json();
                    $cities = $citiesResponse->json();
                    
                    // Combine and sort by name
                    $combined = array_merge($municipalities, $cities);
                    usort($combined, function ($a, $b) {
                        return strcmp($a['name'], $b['name']);
                    });
                    
                    return response()->json($combined);
                }
                
                return response()->json(['error' => 'Failed to fetch cities/municipalities from PSGC API'], 500);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to connect to PSGC API: ' . $e->getMessage()], 500);
            }
        });
    }
    
    /**
     * Get barangays by city/municipality code
     *
     * @param string $cityCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBarangaysByCity($cityCode)
    {
        return Cache::remember("psgc_barangays_{$cityCode}", $this->cacheTtl, function () use ($cityCode) {
            try {
                // Check if this is a city or municipality
                $cityResponse = Http::get("{$this->baseUrl}/cities/{$cityCode}");
                $municipalityResponse = Http::get("{$this->baseUrl}/municipalities/{$cityCode}");
                
                if ($cityResponse->successful()) {
                    $barangaysResponse = Http::get("{$this->baseUrl}/cities/{$cityCode}/barangays");
                } elseif ($municipalityResponse->successful()) {
                    $barangaysResponse = Http::get("{$this->baseUrl}/municipalities/{$cityCode}/barangays");
                } else {
                    return response()->json(['error' => 'City/Municipality not found'], 404);
                }
                
                if ($barangaysResponse->successful()) {
                    $barangays = $barangaysResponse->json();
                    
                    // Sort barangays alphabetically
                    usort($barangays, function ($a, $b) {
                        return strcmp($a['name'], $b['name']);
                    });
                    
                    return response()->json($barangays);
                }
                
                return response()->json(['error' => 'Failed to fetch barangays from PSGC API'], 500);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to connect to PSGC API: ' . $e->getMessage()], 500);
            }
        });
    }
}