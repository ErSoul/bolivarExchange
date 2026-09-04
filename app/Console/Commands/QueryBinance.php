<?php

namespace App\Console\Commands;

use App\Models\Currency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class QueryBinance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:queryBinance {--fiat=VES} {--side=BUY} {--rows=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Query the Binance P2P USDT market for the best offers.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $apiKey = config('binance.api_key');

        $fiat = strtoupper((string) $this->option('fiat'));
        $rows = min(10, max(1, (int) $this->option('rows')));

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if (filled($apiKey)) {
            $headers['X-MBX-APIKEY'] = $apiKey;
        }

        $prices = collect();

        foreach (['BUY', 'SELL'] as $side) {
            try {
                $response = Http::withHeaders($headers)
                    ->timeout(30)
                    ->post('https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/adv/search', [
                        'page' => 1,
                        'rows' => $rows,
                        'payTypes' => [],
                        'countries' => [],
                        'publisherType' => null,
                        'tradeType' => $side,
                        'asset' => 'USDT',
                        'fiat' => $fiat,
                    ]);
            } catch (\Throwable $e) {
                $this->error('Binance request error: ' . $e->getMessage());

                return self::FAILURE;
            }

            if (! $response->successful()) {
                $this->error('Binance P2P ' . $side . ' request failed: ' . $response->status());

                return self::FAILURE;
            }

            $sidePrices = collect($response->json('data', []))
                ->take($rows)
                ->pluck('adv.price')
                ->filter(fn ($price) => is_numeric($price))
                ->map(fn ($price) => (float) $price);

            if ($sidePrices->isEmpty()) {
                $this->warn('No USDT P2P offers were found for ' . $fiat . ' / ' . $side . '.');

                return self::FAILURE;
            }

            $prices = $prices->merge($sidePrices);
        }

        if ($prices->isEmpty()) {
            $this->warn('No USDT P2P offers were found for ' . $fiat . '.');

            return self::FAILURE;
        }

        $average = round($prices->average(), 2);
        Currency::create(
            ['target_asset' => 'USDT',
             'base_asset' => $fiat,
             'value' => $average]
        );

        $this->info('Average USDT price from ' . $prices->count() . ' offers: ' . number_format($average, 2, '.', ''));

        return self::SUCCESS;
    }
}
