<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Currency Service
 * 
 * Handles multi-currency support including exchange rate management,
 * currency conversion, and localized pricing display
 */
class CurrencyService
{
    private const DEFAULT_CURRENCY = 'USD';
    private const CACHE_TTL = 3600; // 1 hour
    private const EXCHANGE_RATE_API = 'https://api.exchangerate-api.com/v4/latest/';

    private const SUPPORTED_CURRENCIES = [
        'USD' => ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2],
        'UGX' => ['name' => 'Ugandan Shilling', 'symbol' => 'UGX', 'decimal_places' => 0],
        'EUR' => ['name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2],
        'GBP' => ['name' => 'British Pound', 'symbol' => '£', 'decimal_places' => 2],
        'JPY' => ['name' => 'Japanese Yen', 'symbol' => '¥', 'decimal_places' => 0],
        'AED' => ['name' => 'UAE Dirham', 'symbol' => 'AED', 'decimal_places' => 2],
    ];

    /**
     * Get current exchange rates for all supported currencies
     */
    public function getExchangeRates(string $baseCurrency = self::DEFAULT_CURRENCY): array
    {
        $cacheKey = "exchange_rates.{$baseCurrency}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($baseCurrency) {
            try {
                $response = Http::timeout(10)->get(self::EXCHANGE_RATE_API . $baseCurrency);
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Filter to only supported currencies
                    $rates = [];
                    foreach (array_keys(self::SUPPORTED_CURRENCIES) as $currency) {
                        $rates[$currency] = $data['rates'][$currency] ?? 1;
                    }
                    
                    $rates['last_updated'] = $data['date'] ?? now()->toDateString();
                    $rates['base_currency'] = $baseCurrency;
                    
                    Log::info('Exchange rates updated successfully', [
                        'base_currency' => $baseCurrency,
                        'rates_count' => count($rates) - 2, // Exclude metadata
                    ]);
                    
                    return $rates;
                }
                
                throw new Exception('Failed to fetch exchange rates: ' . $response->status());
                
            } catch (Exception $e) {
                Log::error('Failed to fetch exchange rates', [
                    'base_currency' => $baseCurrency,
                    'error' => $e->getMessage(),
                ]);
                
                // Return fallback rates
                return $this->getFallbackRates($baseCurrency);
            }
        });
    }

    /**
     * Convert amount from one currency to another
     */
    public function convertCurrency(float $amount, string $fromCurrency, string $toCurrency): array
    {
        if ($fromCurrency === $toCurrency) {
            return [
                'original_amount' => $amount,
                'converted_amount' => $amount,
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'exchange_rate' => 1,
                'conversion_date' => now()->toISOString(),
            ];
        }

        try {
            $rates = $this->getExchangeRates($fromCurrency);
            
            if (!isset($rates[$toCurrency])) {
                throw new Exception("Exchange rate not available for {$toCurrency}");
            }
            
            $exchangeRate = $rates[$toCurrency];
            $convertedAmount = $amount * $exchangeRate;
            
            // Round to appropriate decimal places
            $decimalPlaces = self::SUPPORTED_CURRENCIES[$toCurrency]['decimal_places'];
            $convertedAmount = round($convertedAmount, $decimalPlaces);
            
            return [
                'original_amount' => $amount,
                'converted_amount' => $convertedAmount,
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'exchange_rate' => $exchangeRate,
                'conversion_date' => now()->toISOString(),
                'last_rate_update' => $rates['last_updated'],
            ];
            
        } catch (Exception $e) {
            Log::error('Currency conversion failed', [
                'amount' => $amount,
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Format amount with currency symbol and localization
     */
    public function formatCurrency(float $amount, string $currency, ?string $locale = null): string
    {
        if (!isset(self::SUPPORTED_CURRENCIES[$currency])) {
            throw new Exception("Unsupported currency: {$currency}");
        }
        
        $currencyInfo = self::SUPPORTED_CURRENCIES[$currency];
        $decimalPlaces = $currencyInfo['decimal_places'];
        $symbol = $currencyInfo['symbol'];
        
        // Format number with appropriate decimal places
        $formattedAmount = number_format($amount, $decimalPlaces);
        
        // Apply currency symbol based on currency type
        switch ($currency) {
            case 'USD':
            case 'EUR':
            case 'GBP':
                return $symbol . $formattedAmount;
            case 'JPY':
            case 'UGX':
            case 'AED':
                return $formattedAmount . ' ' . $symbol;
            default:
                return $formattedAmount . ' ' . $currency;
        }
    }

    /**
     * Get localized pricing for multiple currencies
     */
    public function getLocalizedPricing(float $baseAmount, string $baseCurrency = self::DEFAULT_CURRENCY): array
    {
        $pricing = [];
        
        foreach (array_keys(self::SUPPORTED_CURRENCIES) as $currency) {
            try {
                if ($currency === $baseCurrency) {
                    $pricing[$currency] = [
                        'amount' => $baseAmount,
                        'formatted' => $this->formatCurrency($baseAmount, $currency),
                        'is_base' => true,
                    ];
                } else {
                    $conversion = $this->convertCurrency($baseAmount, $baseCurrency, $currency);
                    $pricing[$currency] = [
                        'amount' => $conversion['converted_amount'],
                        'formatted' => $this->formatCurrency($conversion['converted_amount'], $currency),
                        'exchange_rate' => $conversion['exchange_rate'],
                        'is_base' => false,
                    ];
                }
            } catch (Exception $e) {
                Log::warning('Failed to get pricing for currency', [
                    'currency' => $currency,
                    'base_amount' => $baseAmount,
                    'base_currency' => $baseCurrency,
                    'error' => $e->getMessage(),
                ]);
                
                // Skip this currency if conversion fails
                continue;
            }
        }
        
        return [
            'base_amount' => $baseAmount,
            'base_currency' => $baseCurrency,
            'pricing' => $pricing,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get supported currencies list
     */
    public function getSupportedCurrencies(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }

    /**
     * Validate currency code
     */
    public function isValidCurrency(string $currency): bool
    {
        return isset(self::SUPPORTED_CURRENCIES[$currency]);
    }

    /**
     * Get currency information
     */
    public function getCurrencyInfo(string $currency): ?array
    {
        return self::SUPPORTED_CURRENCIES[$currency] ?? null;
    }

    /**
     * Get customer's preferred currency based on location
     */
    public function getPreferredCurrency(?string $country = null): string
    {
        if (!$country) {
            return self::DEFAULT_CURRENCY;
        }
        
        $countryToCurrency = [
            'Uganda' => 'UGX',
            'United States' => 'USD',
            'United Kingdom' => 'GBP',
            'Japan' => 'JPY',
            'UAE' => 'AED',
            'Germany' => 'EUR',
            'France' => 'EUR',
            'Italy' => 'EUR',
            'Spain' => 'EUR',
        ];
        
        return $countryToCurrency[$country] ?? self::DEFAULT_CURRENCY;
    }

    /**
     * Calculate shipping costs with currency conversion
     */
    public function calculateShippingCosts(array $baseRates, string $targetCurrency = self::DEFAULT_CURRENCY): array
    {
        $costs = [];
        
        foreach ($baseRates as $service => $rate) {
            try {
                if ($rate['currency'] === $targetCurrency) {
                    $costs[$service] = $rate;
                } else {
                    $conversion = $this->convertCurrency(
                        $rate['amount'], 
                        $rate['currency'], 
                        $targetCurrency
                    );
                    
                    $costs[$service] = [
                        'amount' => $conversion['converted_amount'],
                        'currency' => $targetCurrency,
                        'formatted' => $this->formatCurrency($conversion['converted_amount'], $targetCurrency),
                        'original_amount' => $rate['amount'],
                        'original_currency' => $rate['currency'],
                        'exchange_rate' => $conversion['exchange_rate'],
                    ];
                }
            } catch (Exception $e) {
                Log::warning('Failed to convert shipping cost', [
                    'service' => $service,
                    'rate' => $rate,
                    'target_currency' => $targetCurrency,
                    'error' => $e->getMessage(),
                ]);
                
                // Keep original rate if conversion fails
                $costs[$service] = $rate;
            }
        }
        
        return $costs;
    }

    /**
     * Get historical exchange rates for analytics
     */
    public function getHistoricalRates(string $currency, int $days = 30): array
    {
        // This would typically fetch from a historical rates API
        // For now, return a placeholder structure
        
        $rates = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $rates[] = [
                'date' => $date->toDateString(),
                'rate' => 1 + (rand(-100, 100) / 10000), // Simulated rate variation
                'currency' => $currency,
            ];
        }
        
        return $rates;
    }

    /**
     * Clear exchange rate cache
     */
    public function clearExchangeRateCache(): void
    {
        foreach (array_keys(self::SUPPORTED_CURRENCIES) as $currency) {
            Cache::forget("exchange_rates.{$currency}");
        }
        
        Log::info('Exchange rate cache cleared');
    }

    /**
     * Get fallback exchange rates when API is unavailable
     */
    private function getFallbackRates(string $baseCurrency): array
    {
        // Static fallback rates (should be updated periodically)
        $fallbackRates = [
            'USD' => [
                'USD' => 1,
                'UGX' => 3700,
                'EUR' => 0.85,
                'GBP' => 0.73,
                'JPY' => 110,
                'AED' => 3.67,
            ],
            'UGX' => [
                'USD' => 0.00027,
                'UGX' => 1,
                'EUR' => 0.00023,
                'GBP' => 0.0002,
                'JPY' => 0.03,
                'AED' => 0.001,
            ],
        ];
        
        $rates = $fallbackRates[$baseCurrency] ?? $fallbackRates['USD'];
        $rates['last_updated'] = 'fallback';
        $rates['base_currency'] = $baseCurrency;
        
        Log::warning('Using fallback exchange rates', [
            'base_currency' => $baseCurrency,
        ]);
        
        return $rates;
    }
}