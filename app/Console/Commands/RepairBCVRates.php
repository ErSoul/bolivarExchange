<?php

namespace App\Console\Commands;

use App\Models\Currency;
use Illuminate\Console\Command;

/**
 * Repairs BCV quotes stored while QueryBCV ignored thousands separators.
 *
 * The old parser matched only the last digit group of "1.007,38665582" and
 * stored 7.38665582, so the lost part is always an exact multiple of 1000.
 * Each row is compared with the previous trusted quote of the same asset and
 * the missing thousands are restored when that yields a plausible value.
 */
class RepairBCVRates extends Command
{
    protected $signature = 'app:repairBCVRates {--dry-run : Show the changes without saving them}';

    protected $description = 'Restore USD/EUR BCV quotes truncated by the thousands-separator parsing bug.';

    /** Maximum relative distance from the previous quote for a repair to be accepted. */
    private const TOLERANCE = 0.2;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $repaired = 0;

        foreach (['USD', 'EUR'] as $asset) {
            $previous = null;

            $quotes = Currency::query()
                ->where('base_asset', 'VES')
                ->where('target_asset', $asset)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            foreach ($quotes as $quote) {
                $value = (float) $quote->value;

                if ($value <= 0) {
                    continue;
                }

                if ($previous !== null) {
                    $missingThousands = (int) round(($previous - $value) / 1000);
                    $candidate = round($value + $missingThousands * 1000, 8);

                    if ($missingThousands >= 1 && abs($candidate - $previous) <= $previous * self::TOLERANCE) {
                        $this->line("{$asset} #{$quote->id} ({$quote->created_at}): {$value} -> {$candidate}");

                        if (! $dryRun) {
                            $quote->timestamps = false;
                            $quote->update(['value' => $candidate]);
                        }

                        $value = $candidate;
                        $repaired++;
                    }
                }

                $previous = $value;
            }
        }

        $this->info(($dryRun ? 'Se repararían ' : 'Reparadas ')."{$repaired} fila(s).");

        return self::SUCCESS;
    }
}
