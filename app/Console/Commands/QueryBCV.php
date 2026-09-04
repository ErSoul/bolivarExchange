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
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $response = Http::withOptions([
                    'verify' => config('app.ssl_verification'),
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => config('app.ssl_verification'),
                        CURLOPT_SSL_VERIFYHOST => config('app.ssl_verification') ? 2 : 0,
                    ],
                ])->get('https://bcv.org.ve');

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($response->body(), LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $dolarElement = $dom->getElementById('dolar');
        $euroElement = $dom->getElementById('euro');

        $dolarValue = $dolarElement ? $dolarElement->textContent : 'N/A';
        $euroValue = $euroElement ? $euroElement->textContent : 'N/A';

        preg_match('/\d+,\d+/', $dolarValue, $matches);
        $dolarValueFormatted = isset($matches[0]) ? str_replace(',', '.', $matches[0]) : 'N/A';

        preg_match('/\d+,\d+/', $euroValue, $matches);
        $euroValueFormatted = isset($matches[0]) ? str_replace(',', '.', $matches[0]) : 'N/A';

        Currency::create(
            ['target_asset' => 'USD',
             'base_asset' => 'VES',
             'value' => (float)$dolarValueFormatted]
        );

        Currency::create(
            ['target_asset' => 'EUR',
             'base_asset' => 'VES',
             'value' => (float)$euroValueFormatted]
        );

        $this->info('Dólar (formateado): ' . (float)$dolarValueFormatted);
        $this->info('Euro (formateado): ' . (float)$euroValueFormatted);
    }
}
