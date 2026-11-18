<?php

namespace Tests\Feature;

use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QueryBCVTest extends TestCase
{
    use RefreshDatabase;

    private function bcvPage(?string $euro, ?string $dolar): string
    {
        $euroBlock = $euro === null ? '' : <<<HTML
            <div id="euro" class="col-sm-12 col-xs-12 "><div class="field-content"><div class="row recuadrotsmc">
              <div class="col-sm-6 col-xs-6"><span> EUR </span></div>
              <div class="col-sm-6 col-xs-6 centrado textp"><strong class="strong-tb"> {$euro}</strong> </div>
            </div></div></div>
            HTML;

        $dolarBlock = $dolar === null ? '' : <<<HTML
            <div id="dolar" class="col-sm-12 col-xs-12 "><div class="field-content"><div class="row recuadrotsmc">
              <div class="col-sm-6 col-xs-6"><span> USD</span></div>
              <div class="col-sm-6 col-xs-6 centrado textp"><strong class="strong-tb">{$dolar}</strong>  </div>
            </div></div></div>
            HTML;

        return "<html><body>{$euroBlock}{$dolarBlock}</body></html>";
    }

    public function test_it_stores_rates_with_thousands_separators(): void
    {
        Http::fake(['https://bcv.org.ve*' => Http::response($this->bcvPage('1.007,38665582', '916,64770000'))]);

        $this->artisan('app:queryBCV')->assertExitCode(0);

        $this->assertEqualsWithDelta(1007.38665582, (float) Currency::where('target_asset', 'EUR')->value('value'), 1e-6);
        $this->assertEqualsWithDelta(916.6477, (float) Currency::where('target_asset', 'USD')->value('value'), 1e-6);
    }

    public function test_it_parses_rates_with_several_thousands_groups(): void
    {
        Http::fake(['https://bcv.org.ve*' => Http::response($this->bcvPage('1.234.567,5', '12.000,25'))]);

        $this->artisan('app:queryBCV')->assertExitCode(0);

        $this->assertEqualsWithDelta(1234567.5, (float) Currency::where('target_asset', 'EUR')->value('value'), 1e-6);
        $this->assertEqualsWithDelta(12000.25, (float) Currency::where('target_asset', 'USD')->value('value'), 1e-6);
    }

    public function test_it_does_not_store_zero_when_a_rate_is_missing(): void
    {
        Http::fake(['https://bcv.org.ve*' => Http::response($this->bcvPage(null, '916,64770000'))]);

        $this->artisan('app:queryBCV')->assertExitCode(1);

        $this->assertDatabaseMissing('currencies', ['target_asset' => 'EUR']);
        $this->assertDatabaseHas('currencies', ['target_asset' => 'USD', 'base_asset' => 'VES']);
    }

    public function test_it_fails_without_storing_when_the_request_fails(): void
    {
        Http::fake(['https://bcv.org.ve*' => Http::response('error', 500)]);

        $this->artisan('app:queryBCV')->assertExitCode(1);

        $this->assertDatabaseCount('currencies', 0);
    }

    public function test_it_fails_without_storing_when_the_request_throws(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->artisan('app:queryBCV')->assertExitCode(1);

        $this->assertDatabaseCount('currencies', 0);
    }
}
