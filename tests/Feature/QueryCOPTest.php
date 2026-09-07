<?php

namespace Tests\Feature;

use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QueryCOPTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_the_latest_cop_usd_trm(): void
    {
        Http::fake([
            'https://www.datos.gov.co/resource/32sa-8pi3.json*' => Http::response([
                ['valor' => '4000.25', 'vigenciadesde' => '2026-09-03T00:00:00.000'],
            ]),
        ]);

        $this->artisan('app:queryCOP')
            ->assertExitCode(0);

        $this->assertDatabaseHas('currencies', [
            'base_asset' => 'COP',
            'target_asset' => 'USD',
            'value' => '4000.25',
        ]);

        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://www.datos.gov.co/resource/32sa-8pi3.json')
            && $request['$limit'] === 1
            && $request['$order'] === 'vigenciadesde DESC');
    }
}