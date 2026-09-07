<?php

namespace App\Services;

use App\General\Currency as CurrencyAsset;
use Illuminate\Support\Collection;

class CurrencyMarketService
{
    /**
     * @return Collection<int, array{code: string, value: float, base: string, quote: string}>
     */
    public function getCurrenciesForDisplay(Collection $quotes): Collection
    {
        $vesRates = $this->resolveVesRates($quotes);
        $copToUsd = $this->latestRate($quotes, CurrencyAsset::COP->value, CurrencyAsset::USD->value);
        $usdtToVes = $this->latestRate($quotes, CurrencyAsset::VES->value, CurrencyAsset::USDT->value);
        $orderedCodes = [
            CurrencyAsset::USDT->value,
            CurrencyAsset::EUR->value,
            CurrencyAsset::USD->value,
            CurrencyAsset::COP->value,
        ];

        return collect($orderedCodes)
            ->filter(fn (string $code) => isset($vesRates[$code]) || $code === CurrencyAsset::COP->value)
            ->map(function (string $code) use ($vesRates, $copToUsd, $usdtToVes) {
                if ($code === CurrencyAsset::COP->value) {
                    $copPerVes = $copToUsd !== null && $usdtToVes !== null
                        ? $copToUsd / $usdtToVes
                        : (isset($vesRates[$code]) ? 1 / $vesRates[$code] : null);

                    if ($copPerVes === null) {
                        return null;
                    }

                    return [
                        'code' => $code,
                        'value' => $copPerVes,
                        'base' => CurrencyAsset::VES->value,
                        'quote' => $code,
                    ];
                }

                return [
                    'code' => $code,
                    'value' => $vesRates[$code],
                    'base' => $code,
                    'quote' => CurrencyAsset::VES->value,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param  array<int, string>  $assetCodes
     * @return array<string, array<int, array{label: string, value: float}>>
     */
    public function getHistoricalAssetSeries(Collection $quotes, array $assetCodes = ['USDT', 'EUR', 'USD']): array
    {
        $series = [];

        foreach ($assetCodes as $code) {
            $points = $quotes
                ->filter(fn ($quote) => $quote->base_asset === CurrencyAsset::VES->value && $quote->target_asset === $code)
                ->groupBy(fn ($quote) => $quote->created_at?->toDateString())
                ->sortKeys()
                ->map(function (Collection $dailyQuotes) {
                    $quote = $dailyQuotes->sortByDesc('created_at')->first();

                    return [
                        'label' => $quote->created_at?->format('d/m'),
                        'value' => (float) $quote->value,
                    ];
                })
                ->values()
                ->all();

            if ($points !== []) {
                $series[$code] = $points;
            }
        }

        return $series;
    }

    /**
     * @return array<int, array{label: string, usdt_to_ves: float, cop_to_usd: float, ves_to_cop: float}>
     */
    public function getHistoricalCopSeries(Collection $quotes): array
    {
        $usdtByDay = $quotes
            ->filter(fn ($quote) => $quote->base_asset === CurrencyAsset::VES->value && $quote->target_asset === CurrencyAsset::USDT->value)
            ->groupBy(fn ($quote) => $quote->created_at?->toDateString());
        $copByDay = $quotes
            ->filter(fn ($quote) => $quote->base_asset === CurrencyAsset::COP->value && $quote->target_asset === CurrencyAsset::USD->value)
            ->groupBy(fn ($quote) => $quote->created_at?->toDateString());

        return $usdtByDay
            ->keys()
            ->intersect($copByDay->keys())
            ->sort()
            ->map(function (string $day) use ($usdtByDay, $copByDay) {
                $usdtToVes = (float) $usdtByDay[$day]->sortByDesc('created_at')->first()->value;
                $copToUsd = (float) $copByDay[$day]->sortByDesc('created_at')->first()->value;

                return [
                    'label' => $usdtByDay[$day]->first()->created_at?->format('d/m'),
                    'usdt_to_ves' => $usdtToVes,
                    'cop_to_usd' => $copToUsd,
                    'ves_to_cop' => $copToUsd / $usdtToVes,
                ];
            })
            ->values()
            ->all();
    }

    public function latestRate(Collection $quotes, string $base, string $target): ?float
    {
        $quote = $quotes
            ->where('base_asset', $base)
            ->where('target_asset', $target)
            ->sortByDesc('created_at')
            ->first();

        if (! $quote) {
            return null;
        }

        return (float) $quote->value;
    }

    public function resolveRate(Collection $quotes, string $from, string $to): ?float
    {
        $usdtToVes = $this->latestRate($quotes, CurrencyAsset::VES->value, CurrencyAsset::USDT->value);
        $copToUsd = $this->latestRate($quotes, CurrencyAsset::COP->value, CurrencyAsset::USD->value);

        if ($usdtToVes !== null && $copToUsd !== null) {
            if ($from === CurrencyAsset::VES->value && $to === CurrencyAsset::COP->value) {
                return $copToUsd / $usdtToVes;
            }

            if ($from === CurrencyAsset::COP->value && $to === CurrencyAsset::VES->value) {
                return $usdtToVes / $copToUsd;
            }

            if ($from === CurrencyAsset::COP->value && in_array($to, [CurrencyAsset::USD->value, CurrencyAsset::USDT->value], true)) {
                return 1 / $copToUsd;
            }

            if (in_array($from, [CurrencyAsset::USD->value, CurrencyAsset::USDT->value], true) && $to === CurrencyAsset::COP->value) {
                return $copToUsd;
            }
        }

        $vesRates = $this->resolveVesRates($quotes);

        if ($from === CurrencyAsset::VES->value) {
            return isset($vesRates[$to]) ? 1 / $vesRates[$to] : null;
        }

        if ($to === CurrencyAsset::VES->value) {
            return isset($vesRates[$from]) ? $vesRates[$from] : null;
        }

        if (isset($vesRates[$from]) && isset($vesRates[$to])) {
            return $vesRates[$from] / $vesRates[$to];
        }

        return null;
    }

    private function resolveVesRates(Collection $quotes): array
    {
        $rates = ['VES' => 1.0];

        foreach ($quotes as $quote) {
            if ($quote->base_asset === CurrencyAsset::VES->value && (float) $quote->value > 0) {
                $rates[$quote->target_asset] = (float) $quote->value;
            }
        }

        for ($iteration = 0; $iteration < $quotes->count(); $iteration++) {
            $changed = false;

            foreach ($quotes as $quote) {
                $value = (float) $quote->value;

                if ($value <= 0) {
                    continue;
                }

                if (isset($rates[$quote->base_asset]) && ! isset($rates[$quote->target_asset])) {
                    $rates[$quote->target_asset] = $rates[$quote->base_asset] * $value;
                    $changed = true;
                } elseif (isset($rates[$quote->target_asset]) && ! isset($rates[$quote->base_asset])) {
                    $rates[$quote->base_asset] = $rates[$quote->target_asset] / $value;
                    $changed = true;
                }
            }

            if (! $changed) {
                break;
            }
        }

        return $rates;
    }
}
