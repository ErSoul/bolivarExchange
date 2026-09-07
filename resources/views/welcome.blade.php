<x-layout>
    <main class="mx-auto min-h-screen max-w-6xl px-6 py-8 sm:px-10 lg:px-16 lg:py-12">
        <header class="flex items-center justify-between border-b border-[#d8d6ce] pb-6">
            <a href="{{ route('currencies.index') }}" class="font-mono text-sm font-medium tracking-[0.15em] text-[#1d2925]">CV<span class="text-[#e06a3a]">/</span>24</a>
            <span class="rounded-full border border-[#c8d2ca] bg-[#e6eee7] px-3 py-1 font-mono text-[10px] uppercase tracking-[0.15em] text-[#427158]">Mercado VES</span>
        </header>

        <section class="grid gap-12 py-16 lg:grid-cols-[1.1fr_0.9fr] lg:items-end lg:py-24">
            <div>
                <p class="mb-5 font-mono text-xs uppercase tracking-[0.25em] text-[#e06a3a]">Cotizaciones en tiempo real</p>
                <h1 class="max-w-2xl text-5xl font-extrabold leading-[0.98] tracking-[-0.04em] sm:text-7xl">El valor de tu dinero, <span class="text-[#e06a3a]">claro.</span></h1>
            </div>
            <div class="max-w-sm lg:justify-self-end">
                <p class="text-lg leading-relaxed text-[#68736d]">Consulta las principales monedas convertidas a bolívares venezolanos en un solo lugar.</p>
                @if ($updatedAt)
                    <p class="mt-5 font-mono text-xs uppercase tracking-[0.12em] text-[#8a918c]">Actualizado {{ $updatedAt->diffForHumans() }}</p>
                @endif
            </div>
        </section>

        <section aria-labelledby="rates-title">
            <div class="mb-5 flex items-end justify-between border-b border-[#1d2925] pb-3">
                <h2 id="rates-title" class="text-xl font-bold">Cotizaciones</h2>
                <span class="font-mono text-xs text-[#8a918c]">BASE / VES</span>
            </div>

            @if ($currencies->isEmpty())
                <div class="border border-dashed border-[#b9beb8] bg-[#fbfaf7] px-6 py-12 text-center text-[#68736d]">
                    Aún no hay cotizaciones disponibles.
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($currencies as $currency)
                        <x-currency-card :code="$currency['code']" :value="$currency['value']" :base="$currency['base']" :quote="$currency['quote']" />
                    @endforeach
                </div>
            @endif
        </section>

        <x-asset-converter />

        @if (! empty($historicalSeries))
            <section class="mt-16">
                <div class="mb-6 flex items-end justify-between border-b border-[#1d2925] pb-3">
                    <h2 class="text-xl font-bold">Evolución histórica</h2>
                    <span class="font-mono text-xs text-[#8a918c]">ÚLTIMOS REGISTROS</span>
                </div>

                @php
                    $chartColors = ['USDT' => '#e06a3a', 'EUR' => '#427158', 'USD' => '#1d2925', 'COP' => '#8a918c'];
                    $chartMin = null;
                    $chartMax = null;
                    foreach ($historicalSeries as $series) {
                        foreach ($series as $point) {
                            $chartMin = $chartMin === null ? $point['value'] : min($chartMin, $point['value']);
                            $chartMax = $chartMax === null ? $point['value'] : max($chartMax, $point['value']);
                        }
                    }
                    $chartMin = $chartMin === null ? 0 : max(0, $chartMin * 0.95);
                    $chartMax = $chartMax === null ? 1 : $chartMax * 1.05;
                    $chartHeight = 320;
                    $chartWidth = 960;
                    $plotLeft = 76;
                    $plotRight = 24;
                    $plotTop = 24;
                    $plotBottom = 58;
                    $plotWidth = $chartWidth - $plotLeft - $plotRight;
                    $plotHeight = $chartHeight - $plotTop - $plotBottom;
                    $chartLabels = collect($historicalSeries)->first() ?? [];
                    $xLabelStep = max(1, (int) ceil(count($chartLabels) / 6));
                @endphp

                <div class="overflow-hidden rounded-3xl border border-[#d8d6ce] bg-[#fbfaf7] p-4 sm:p-6">
                    <svg id="currency-history-chart" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" class="h-80 w-full" role="img" aria-label="Evolución del valor de los activos en VES a través del tiempo">
                        @for ($tick = 0; $tick <= 4; $tick++)
                            @php
                                $tickRatio = $tick / 4;
                                $tickY = $plotTop + ($tickRatio * $plotHeight);
                                $tickValue = $chartMax - ($tickRatio * ($chartMax - $chartMin));
                            @endphp
                            <line x1="{{ $plotLeft }}" y1="{{ $tickY }}" x2="{{ $chartWidth - $plotRight }}" y2="{{ $tickY }}" stroke="#d8d6ce" stroke-width="1"></line>
                            <text x="{{ $plotLeft - 12 }}" y="{{ $tickY + 4 }}" text-anchor="end" fill="#8a918c" font-family="DM Mono, monospace" font-size="11">{{ number_format($tickValue, 0, ',', '.') }}</text>
                        @endfor

                        <line x1="{{ $plotLeft }}" y1="{{ $plotTop }}" x2="{{ $plotLeft }}" y2="{{ $chartHeight - $plotBottom }}" stroke="#8a918c" stroke-width="1.5"></line>
                        <line x1="{{ $plotLeft }}" y1="{{ $chartHeight - $plotBottom }}" x2="{{ $chartWidth - $plotRight }}" y2="{{ $chartHeight - $plotBottom }}" stroke="#8a918c" stroke-width="1.5"></line>
                        <text x="16" y="{{ $plotTop + ($plotHeight / 2) }}" transform="rotate(-90 16 {{ $plotTop + ($plotHeight / 2) }})" text-anchor="middle" fill="#68736d" font-family="DM Mono, monospace" font-size="11">Valor en VES</text>
                        <text x="{{ $plotLeft + ($plotWidth / 2) }}" y="{{ $chartHeight - 12 }}" text-anchor="middle" fill="#68736d" font-family="DM Mono, monospace" font-size="11">Fecha de registro</text>

                        @foreach ($chartLabels as $index => $point)
                            @if ($index % $xLabelStep === 0 || $index === count($chartLabels) - 1)
                                @php
                                    $labelX = $plotLeft + (($index / max(1, count($chartLabels) - 1)) * $plotWidth);
                                @endphp
                                <text x="{{ $labelX }}" y="{{ $chartHeight - $plotBottom + 22 }}" text-anchor="middle" fill="#8a918c" font-family="DM Mono, monospace" font-size="11">{{ $point['label'] }}</text>
                            @endif
                        @endforeach

                        @foreach ($historicalSeries as $code => $series)
                            @php
                                $points = [];
                                foreach ($series as $index => $point) {
                                    $x = $plotLeft + (($index / max(1, count($series) - 1)) * $plotWidth);
                                    $y = $plotTop + $plotHeight - (($point['value'] - $chartMin) / max(0.01, $chartMax - $chartMin)) * $plotHeight;
                                    $points[] = $x . ',' . $y;
                                }
                                $pointsText = implode(' ', $points);
                            @endphp

                            <polyline
                                fill="none"
                                stroke="{{ $chartColors[$code] ?? '#e06a3a' }}"
                                stroke-width="3"
                                points="{{ $pointsText }}"
                                class="transition-all duration-300"
                            ></polyline>

                            @foreach ($series as $index => $point)
                                @php
                                    $x = $plotLeft + (($index / max(1, count($series) - 1)) * $plotWidth);
                                    $y = $plotTop + $plotHeight - (($point['value'] - $chartMin) / max(0.01, $chartMax - $chartMin)) * $plotHeight;
                                @endphp
                                <circle cx="{{ $x }}" cy="{{ $y }}" r="3.5" fill="{{ $chartColors[$code] ?? '#e06a3a' }}"></circle>
                            @endforeach
                        @endforeach
                    </svg>

                    <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-3">
                        <span class="font-mono text-[10px] uppercase tracking-[0.12em] text-[#8a918c]">Series:</span>
                        @foreach ($historicalSeries as $code => $series)
                            <span class="inline-flex items-center gap-2 text-xs font-medium text-[#1d2925]">
                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $chartColors[$code] ?? '#e06a3a' }};"></span>
                                1 {{ $code }} / VES
                            </span>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if (! empty($historicalCopSeries))
            <section class="mt-16" aria-labelledby="cop-history-title">
                <div class="mb-5 flex items-end justify-between border-b border-[#1d2925] pb-3">
                    <h2 id="cop-history-title" class="text-xl font-bold">Histórico COP</h2>
                    <span class="font-mono text-xs text-[#8a918c]">RELACIÓN VES / USDT</span>
                </div>

                <div class="overflow-x-auto border border-[#d8d6ce] bg-[#fbfaf7]">
                    <table id="cop-history-table" class="w-full min-w-[620px] text-left">
                        <caption class="sr-only">Evolución diaria de COP calculada mediante la relación VES a USDT</caption>
                        <thead class="border-b border-[#d8d6ce] bg-[#e6eee7] font-mono text-[10px] uppercase tracking-[0.12em] text-[#427158]">
                            <tr>
                                <th class="px-5 py-4 font-medium">Fecha</th>
                                <th class="px-5 py-4 text-right font-medium">VES / USDT</th>
                                <th class="px-5 py-4 text-right font-medium">COP / USD</th>
                                <th class="px-5 py-4 text-right font-medium">1 VES en COP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#d8d6ce] font-mono text-sm text-[#1d2925]">
                            @foreach ($historicalCopSeries as $point)
                                <tr>
                                    <td class="px-5 py-4 text-[#68736d]">{{ $point['label'] }}</td>
                                    <td class="px-5 py-4 text-right">{{ number_format($point['usdt_to_ves'], 2, ',', '.') }}</td>
                                    <td class="px-5 py-4 text-right">{{ number_format($point['cop_to_usd'], 2, ',', '.') }}</td>
                                    <td class="px-5 py-4 text-right font-medium text-[#e06a3a]">{{ number_format($point['ves_to_cop'], 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <footer class="mt-20 flex flex-col gap-2 border-t border-[#d8d6ce] pt-5 text-xs text-[#8a918c] sm:flex-row sm:items-center sm:justify-between">
            <span>Datos consolidados para una lectura sencilla.</span>
            <span class="font-mono">VES · Venezuela</span>
        </footer>
    </main>
</x-layout>
