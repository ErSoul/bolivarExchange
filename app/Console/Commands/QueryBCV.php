<?php

namespace App\Console\Commands;

use App\Models\Currency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class QueryBCV extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:queryBCV';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Query the latest USD/VES and EUR/VES rates from the BCV website.';

    /**
     * BCV element id => stored target asset.
     *
     * @var array<string, string>
     */
    private const RATES = [
        'dolar' => 'USD',
        'euro' => 'EUR',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $response = Http::withOptions([
                'verify' => config('app.ssl_verification'),
            ])->timeout(30)->get('https://bcv.org.ve');
        } catch (\Throwable $e) {
            $this->error('BCV request error: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! $response->successful()) {
            $this->error('BCV request failed: '.$response->status());

            return self::FAILURE;
        }

        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($response->body(), LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $status = self::SUCCESS;

        foreach (self::RATES as $elementId => $asset) {
            $rate = self::parseRate($dom->getElementById($elementId)?->textContent);

            if ($rate === null) {
                $this->error("Could not read the {$asset} rate from the BCV page.");
                $status = self::FAILURE;

                continue;
            }

            Currency::create([
                'target_asset' => $asset,
                'base_asset' => 'VES',
                'value' => $rate,
            ]);

            $this->info("{$asset}: {$rate} VES");
        }

        return $status;
    }

    /**
     * Parse a BCV-formatted amount such as "1.007,38665582" or "916,6477".
     * The dot is a thousands separator and the comma is the decimal separator.
     */
    public static function parseRate(?string $text): ?float
    {
        if ($text === null || ! preg_match('/\d{1,3}(?:\.\d{3})+,\d+|\d+,\d+/', $text, $matches)) {
            return null;
        }

        $rate = (float) str_replace(['.', ','], ['', '.'], $matches[0]);

        return $rate > 0 ? $rate : null;
    }
}
