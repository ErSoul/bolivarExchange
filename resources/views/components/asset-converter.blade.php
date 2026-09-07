<section id="asset-converter" class="mt-4 border border-[#1d2925] bg-[#1d2925] p-6 text-[#fbfaf7] sm:p-8" data-currency-converter data-endpoint="{{ route('currencies.api') }}">
    <div class="mb-8 flex flex-col gap-3 border-b border-white/15 pb-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-2 font-mono text-[10px] uppercase tracking-[0.2em] text-[#f29a72]">Conversor</p>
            <h2 class="text-2xl font-bold tracking-tight">Calcula el valor de tus activos</h2>
        </div>
        <span class="font-mono text-xs uppercase tracking-[0.12em] text-white/45">Datos del mercado</span>
    </div>

    <form class="grid gap-4 lg:grid-cols-[1fr_auto_1fr_auto] lg:items-end" data-converter-form>
        <label class="block">
            <span class="mb-2 block font-mono text-[10px] uppercase tracking-[0.15em] text-white/55">Cantidad y origen</span>
            <div class="grid grid-cols-[1fr_7rem] gap-2">
                <input class="min-w-0 border border-white/15 bg-white/10 px-4 py-3 font-mono text-lg text-white outline-none transition focus:border-[#f29a72]" type="number" name="amount" min="0" step="any" value="1" inputmode="decimal" data-converter-amount>
                <select class="border border-white/15 bg-[#293934] px-3 py-3 font-mono text-sm text-white outline-none transition focus:border-[#f29a72]" name="from" data-converter-from>
                    @foreach (['USDT', 'EUR', 'USD', 'COP', 'VES'] as $asset)
                        <option value="{{ $asset }}" @selected($asset === 'USD')>{{ $asset }}</option>
                    @endforeach
                </select>
            </div>
        </label>

        <span class="hidden pb-3 text-center font-mono text-xl text-[#f29a72] lg:block" aria-hidden="true">→</span>

        <label class="block">
            <span class="mb-2 block font-mono text-[10px] uppercase tracking-[0.15em] text-white/55">Destino</span>
            <select class="w-full border border-white/15 bg-[#293934] px-3 py-3 font-mono text-sm text-white outline-none transition focus:border-[#f29a72]" name="to" data-converter-to>
                @foreach (['USDT', 'EUR', 'USD', 'COP', 'VES'] as $asset)
                    <option value="{{ $asset }}" @selected($asset === 'VES')>{{ $asset }}</option>
                @endforeach
            </select>
        </label>

        <button class="border border-[#f29a72] bg-[#f29a72] px-5 py-3 font-mono text-xs font-medium uppercase tracking-[0.12em] text-[#1d2925] transition hover:bg-[#ffc0a5]" type="submit" data-converter-submit>
            Convertir
        </button>
    </form>

    <div class="mt-8 min-h-16 border-t border-white/15 pt-5" aria-live="polite" data-converter-result>
        <p class="font-mono text-xs uppercase tracking-[0.12em] text-white/50">Introduce una cantidad para consultar el valor.</p>
    </div>
</section>