<?php

namespace Tests\Feature;

use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QueryCOPTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_the_latest_trm_converted_using_the_stored_usdt_value(): void
    {
        Currency::create(['asset' => 'USDT', 'value' => 36.50]);

        Http::fake([
            'https://www.datos.gov.co/resource/32sa-8pi3.json*' => Http::response([
                ['valor' => '4000.25', 'vigenciadesde' => '2026-09-03T00:00:00.000'],
            ]),
        ]);

        $this->artisan('app:query-c-o-p')
            ->assertExitCode(0);

        $this->assertDatabaseHas('currencies', [
            'asset' => 'COP',
            'value' => '146009.13',
        ]);

        Http::assertSent(fn ($request) => $request->url() === 'https://www.datos.gov.co/resource/32sa-8pi3.json'
            && $request['$limit'] === 1
            && $request['$order'] === 'vigenciadesde DESC');
    }
}