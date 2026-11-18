<?php

namespace Tests\Feature;

use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepairBCVRatesTest extends TestCase
{
    use RefreshDatabase;

    private function quote(string $asset, float $value, string $at): Currency
    {
        return Currency::create([
            'base_asset' => 'VES',
            'target_asset' => $asset,
            'value' => $value,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    public function test_it_restores_the_thousands_lost_by_the_old_parser(): void
    {
        $good = $this->quote('EUR', 998.12, '2026-11-01 08:00:00');
        $broken1 = $this->quote('EUR', 1.5, '2026-11-02 08:00:00');      // 1.001,5
        $broken2 = $this->quote('EUR', 7.38665582, '2026-11-03 08:00:00'); // 1.007,38665582
        $usd = $this->quote('USD', 916.6477, '2026-11-03 08:00:00');

        $this->artisan('app:repairBCVRates')->assertExitCode(0);

        $this->assertEqualsWithDelta(998.12, (float) $good->fresh()->value, 1e-6);
        $this->assertEqualsWithDelta(1001.5, (float) $broken1->fresh()->value, 1e-6);
        $this->assertEqualsWithDelta(1007.38665582, (float) $broken2->fresh()->value, 1e-6);
        $this->assertEqualsWithDelta(916.6477, (float) $usd->fresh()->value, 1e-6);
    }

    public function test_it_is_idempotent_and_ignores_unrelated_or_zero_rows(): void
    {
        $this->quote('EUR', 990, '2026-11-01 08:00:00');
        $zero = $this->quote('EUR', 0, '2026-11-02 08:00:00');
        $broken = $this->quote('EUR', 10, '2026-11-03 08:00:00');
        $usdt = Currency::create(['base_asset' => 'VES', 'target_asset' => 'USDT', 'value' => 5]);

        $this->artisan('app:repairBCVRates')->assertExitCode(0);
        $this->artisan('app:repairBCVRates')->expectsOutputToContain('0 fila(s)')->assertExitCode(0);

        $this->assertEquals(0, (float) $zero->fresh()->value);
        $this->assertEqualsWithDelta(1010, (float) $broken->fresh()->value, 1e-6);
        $this->assertEquals(5, (float) $usdt->fresh()->value);
    }

    public function test_it_does_not_touch_implausible_jumps(): void
    {
        $this->quote('USD', 600, '2026-11-01 08:00:00');
        $odd = $this->quote('USD', 5, '2026-11-02 08:00:00'); // 1005 would be a +67% jump

        $this->artisan('app:repairBCVRates')->assertExitCode(0);

        $this->assertEquals(5, (float) $odd->fresh()->value);
    }

    public function test_dry_run_reports_without_writing(): void
    {
        $this->quote('EUR', 995, '2026-11-01 08:00:00');
        $broken = $this->quote('EUR', 3.25, '2026-11-02 08:00:00');

        $this->artisan('app:repairBCVRates --dry-run')
            ->expectsOutputToContain('1003.25')
            ->assertExitCode(0);

        $this->assertEqualsWithDelta(3.25, (float) $broken->fresh()->value, 1e-6);
    }
}
