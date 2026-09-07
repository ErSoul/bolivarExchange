<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\General\Currency as CurrencyAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_currency_values_are_rendered_in_ves(): void
    {
        Currency::create(['base_asset' => CurrencyAsset::VES->value, 'target_asset' => CurrencyAsset::USD->value, 'value' => 125.50]);

        $this->get('/')->assertOk()
            ->assertSee('1 USD equivale a')
            ->assertSee('125,50')
            ->assertSee(CurrencyAsset::VES->value);
    }

    public function test_cop_value_is_derived_from_usdt_and_usd_quotes(): void
    {
        Currency::create(['base_asset' => CurrencyAsset::VES->value, 'target_asset' => CurrencyAsset::USDT->value, 'value' => 971]);
        Currency::create(['base_asset' => CurrencyAsset::VES->value, 'target_asset' => CurrencyAsset::USD->value, 'value' => 971]);
        Currency::create(['base_asset' => CurrencyAsset::COP->value, 'target_asset' => CurrencyAsset::USD->value, 'value' => 3100]);

        $this->get('/')->assertOk()
            ->assertSee('1 VES equivale a')
            ->assertSee('3,19')
            ->assertSee(CurrencyAsset::COP->value);
    }

    public function test_currency_cards_follow_the_requested_asset_order(): void
    {
        Currency::create(['base_asset' => CurrencyAsset::VES->value, 'target_asset' => CurrencyAsset::USDT->value, 'value' => 971]);
        Currency::create(['base_asset' => CurrencyAsset::VES->value, 'target_asset' => CurrencyAsset::EUR->value, 'value' => 1200]);
        Currency::create(['base_asset' => CurrencyAsset::VES->value, 'target_asset' => CurrencyAsset::USD->value, 'value' => 1250]);
        Currency::create(['base_asset' => CurrencyAsset::COP->value, 'target_asset' => CurrencyAsset::USD->value, 'value' => 3100]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder([CurrencyAsset::USDT->value, CurrencyAsset::EUR->value, CurrencyAsset::USD->value, CurrencyAsset::COP->value]);
    }

    public function test_cop_value_is_derived_explicitly_from_latest_cop_and_usdt_rates(): void
    {
        Currency::create(['base_asset' => CurrencyAsset::VES->value, 'target_asset' => CurrencyAsset::COP->value, 'value' => 100]);
        Currency::create(['base_asset' => CurrencyAsset::VES->value, 'target_asset' => CurrencyAsset::USDT->value, 'value' => 971]);
        Currency::create(['base_asset' => CurrencyAsset::COP->value, 'target_asset' => CurrencyAsset::USD->value, 'value' => 3100]);

        $this->get('/')
            ->assertOk()
            ->assertSee('3,19')
            ->assertSee(CurrencyAsset::COP->value);
    }

    public function test_api_allows_filtering_and_conversion_by_query_params(): void
    {
        Currency::create(['base_asset' => CurrencyAsset::VES->value, 'target_asset' => CurrencyAsset::USDT->value, 'value' => 971]);
        Currency::create(['base_asset' => CurrencyAsset::VES->value, 'target_asset' => CurrencyAsset::EUR->value, 'value' => 1200]);
        Currency::create(['base_asset' => CurrencyAsset::VES->value, 'target_asset' => CurrencyAsset::USD->value, 'value' => 1250]);
        Currency::create(['base_asset' => CurrencyAsset::COP->value, 'target_asset' => CurrencyAsset::USD->value, 'value' => 3100]);

        $this->getJson('/api/currencies?assets=USDT,EUR&from=USD&to=VES&amount=2')
            ->assertOk()
            ->assertJsonPath('currencies.0.code', CurrencyAsset::USDT->value)
            ->assertJsonPath('currencies.1.code', CurrencyAsset::EUR->value)
            ->assertJsonPath('conversion.from', CurrencyAsset::USD->value)
            ->assertJsonPath('conversion.to', CurrencyAsset::VES->value)
            ->assertJsonPath('conversion.amount', 2.0)
            ->assertJsonPath('conversion.value', 2500.0);
    }

    public function test_api_inverts_cop_quotes_for_cop_conversions(): void
    {
        Currency::create(['base_asset' => CurrencyAsset::VES->value, 'target_asset' => CurrencyAsset::USD->value, 'value' => 1000]);
        Currency::create(['base_asset' => CurrencyAsset::COP->value, 'target_asset' => CurrencyAsset::USD->value, 'value' => 3100]);

        $this->getJson('/api/currencies?from=COP&to=USD&amount=1000')
            ->assertOk()
            ->assertJsonPath('conversion.value', 1000 / 3100);

        $this->getJson('/api/currencies?from=COP&to=VES&amount=1000')
            ->assertOk()
            ->assertJsonPath('conversion.value', 1000 * 1000 / 3100);
    }

    public function test_api_converts_from_ves_using_shared_quote_rates(): void
    {
        Currency::create(['base_asset' => CurrencyAsset::VES->value, 'target_asset' => CurrencyAsset::USD->value, 'value' => 1000]);
        Currency::create(['base_asset' => CurrencyAsset::COP->value, 'target_asset' => CurrencyAsset::USD->value, 'value' => 3100]);

        $this->getJson('/api/currencies?from=VES&to=USD&amount=1000')
            ->assertOk()
            ->assertJsonPath('conversion.value', 1.0);

        $this->getJson('/api/currencies?from=VES&to=COP&amount=1000')
            ->assertOk()
            ->assertJsonPath('conversion.value', 3100.0);
    }

    public function test_ves_to_cop_uses_the_usdt_anchor_rate(): void
    {
        Currency::create(['base_asset' => CurrencyAsset::VES->value, 'target_asset' => CurrencyAsset::USDT->value, 'value' => 971]);
        Currency::create(['base_asset' => CurrencyAsset::COP->value, 'target_asset' => CurrencyAsset::USD->value, 'value' => 3100]);

        $this->getJson('/api/currencies?from=VES&to=COP&amount=1000')
            ->assertOk()
            ->assertJsonPath('conversion.value', 1000 * 3100 / 971);
    }

    public function test_dashboard_exposes_historical_asset_trends(): void
    {
        Currency::create(['base_asset' => CurrencyAsset::VES->value, 'target_asset' => CurrencyAsset::USD->value, 'value' => 1250, 'created_at' => now()->subHour()]);
        Currency::create(['base_asset' => CurrencyAsset::VES->value, 'target_asset' => CurrencyAsset::USD->value, 'value' => 1300, 'created_at' => now()]);
        Currency::create(['base_asset' => CurrencyAsset::VES->value, 'target_asset' => CurrencyAsset::USDT->value, 'value' => 971, 'created_at' => now()]);

        $this->get('/')
            ->assertOk()
            ->assertSee('currency-history-chart')
            ->assertSee('USD')
            ->assertSee('USDT');
    }

    public function test_dashboard_history_uses_one_value_per_day(): void
    {
        $yesterday = now()->subDay();

        Currency::create(['base_asset' => CurrencyAsset::VES->value, 'target_asset' => CurrencyAsset::USD->value, 'value' => 1200, 'created_at' => $yesterday->copy()->setTime(9, 0)]);
        Currency::create(['base_asset' => CurrencyAsset::VES->value, 'target_asset' => CurrencyAsset::USD->value, 'value' => 1250, 'created_at' => $yesterday->copy()->setTime(17, 0)]);
        Currency::create(['base_asset' => CurrencyAsset::VES->value, 'target_asset' => CurrencyAsset::USD->value, 'value' => 1300, 'created_at' => now()->setTime(9, 0)]);

        $this->get('/')
            ->assertOk()
            ->assertSee($yesterday->format('d/m'));
    }

    public function test_dashboard_shows_cop_history_separately_using_the_usdt_anchor(): void
    {
        $date = now()->subDay()->setTime(12, 0);

        Currency::create(['base_asset' => CurrencyAsset::VES->value, 'target_asset' => CurrencyAsset::USDT->value, 'value' => 971, 'created_at' => $date]);
        Currency::create(['base_asset' => CurrencyAsset::COP->value, 'target_asset' => CurrencyAsset::USD->value, 'value' => 3100, 'created_at' => $date]);

        $this->get('/')
            ->assertOk()
            ->assertSee('cop-history-table')
            ->assertSee('VES / USDT')
            ->assertSee('3,19');
    }

    public function test_dashboard_exposes_the_asset_converter(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('asset-converter')
            ->assertSee('data-currency-converter')
            ->assertSee(route('currencies.api'), false)
            ->assertSee('Convertir');
    }
}
