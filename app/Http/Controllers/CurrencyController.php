<?php

namespace App\Http\Controllers;

use App\General\Currency as CurrencyAsset;
use App\Models\Currency;
use App\Services\CurrencyMarketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CurrencyController extends Controller
{
    public function __construct(
        protected CurrencyMarketService $currencyMarketService
    ) {}

    public function index()
    {
        $quotes = $this->latestQuotes();
        $historicalQuotes = Currency::query()->oldest('created_at')->get();
        $currencies = $this->currencyMarketService->getCurrenciesForDisplay($quotes);
        $historicalSeries = $this->currencyMarketService->getHistoricalAssetSeries($historicalQuotes);
        $historicalCopSeries = $this->currencyMarketService->getHistoricalCopSeries($historicalQuotes);

        return view('welcome', [
            'currencies' => $currencies,
            'updatedAt' => $quotes->max('created_at'),
            'historicalSeries' => $historicalSeries,
            'historicalCopSeries' => $historicalCopSeries,
        ]);
    }

    public function apiIndex(Request $request): JsonResponse
    {
        $quotes = $this->latestQuotes();
        $assets = $this->parseAssets($request->query('assets'));
        $from = strtoupper((string) $request->query('from', CurrencyAsset::USD->value));
        $to = strtoupper((string) $request->query('to', CurrencyAsset::VES->value));
        $amount = (float) $request->query('amount', 1);

        $currencies = $this->currencyMarketService->getCurrenciesForDisplay($quotes);

        if ($assets !== []) {
            $currencies = $currencies->filter(fn (array $currency) => in_array($currency['code'], $assets, true));
        }

        $conversion = $this->buildConversion($quotes, $from, $to, $amount);

        return response()->json([
            'updated_at' => $quotes->max('created_at')?->toISOString(),
            'currencies' => $currencies->values()->all(),
            'conversion' => $conversion,
        ], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    protected function latestQuotes()
    {
        return Currency::query()->latest('created_at')->get()
            ->unique(fn (Currency $quote) => $quote->base_asset . ':' . $quote->target_asset)
            ->values();
    }

    protected function parseAssets(?string $assets): array
    {
        if (blank($assets)) {
            return [];
        }

        return collect(explode(',', $assets))
            ->map(fn (string $asset) => strtoupper(trim($asset)))
            ->filter()
            ->values()
            ->all();
    }

    protected function buildConversion(Collection $quotes, string $from, string $to, float $amount): array
    {
        if ($from === $to) {
            return [
                'from' => $from,
                'to' => $to,
                'amount' => $amount,
                'value' => $amount,
            ];
        }

        $rate = $this->currencyMarketService->resolveRate($quotes, $from, $to);

        return [
            'from' => $from,
            'to' => $to,
            'amount' => $amount,
            'value' => $rate === null ? null : $amount * $rate,
        ];
    }

}

