<?php

namespace App\Console\Commands;

use App\Models\Currency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class QueryCOP extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:queryCOP';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Query the latest COP/USD TRM and store its value in VES.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $response = Http::acceptJson()
                ->timeout(30)
                ->get('https://www.datos.gov.co/resource/32sa-8pi3.json', [
                    '$limit' => 1,
                    '$order' => 'vigenciadesde DESC',
                ]);
        } catch (\Throwable $e) {
            $this->error('TRM request error: ' . $e->getMessage());

            return self::FAILURE;
        }

        if (! $response->successful()) {
            $this->error('TRM request failed: ' . $response->status());

            return self::FAILURE;
        }

        $trm = $response->json('0.valor');

        Currency::create(
            ['target_asset' => 'USD',
             'base_asset' => 'COP',
             'value' => $trm ]
        );

        $this->info('COP value stored: ' . number_format($trm, 2, '.', ''). " COP/USD");

        return self::SUCCESS;
    }
}
