<?php

namespace Tests\Unit\Console\Commands;

use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QueryBCVTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fetches_the_bcv_rates_and_persists_them(): void
    {
        $fixturePath = base_path('tests/Fixtures/sample.html');
        $html = file_get_contents($fixturePath);

        Http::fake([
            'https://bcv.org.ve' => Http::response($html),
        ]);

        $this->artisan('app:queryBCV')
            ->assertExitCode(0);

        $this->assertDatabaseHas('currencies', [
            'base_asset' => 'VES',
            'target_asset' => 'USD',
            'value' => '801.1752',
        ]);

        $this->assertDatabaseHas('currencies', [
            'base_asset' => 'VES',
            'target_asset' => 'EUR',
            'value' => '929.09083243',
        ]);

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://bcv.org.ve');

        $this->assertEquals(801.1752, (float) Currency::where('target_asset', 'USD')->value('value'));
        $this->assertEquals(929.09083243, (float) Currency::where('target_asset', 'EUR')->value('value'));
    }
}
