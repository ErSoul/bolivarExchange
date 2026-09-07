<?php

namespace Tests\Feature;

use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QueryBinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_the_average_of_the_top_ten_buy_and_sell_prices(): void
    {
        Http::fakeSequence()
            ->push(['data' => array_map(fn ($price) => ['adv' => ['price' => $price]], range(1, 11))])
            ->push(['data' => array_map(fn ($price) => ['adv' => ['price' => $price]], range(21, 31))]);

        $this->artisan('app:queryBinance')
            ->assertExitCode(0);

        $this->assertDatabaseHas('currencies', [
            'base_asset' => 'VES',
            'target_asset' => 'USDT',
            'value' => '15.50',
        ]);

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request['tradeType'] === 'BUY' && $request['rows'] === 10);
        Http::assertSent(fn ($request) => $request['tradeType'] === 'SELL' && $request['rows'] === 10);
        $this->assertEquals(15.5, Currency::where('target_asset', 'USDT')->value('value'));
    }
}