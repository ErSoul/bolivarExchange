@props(['code', 'value', 'base', 'quote'])

<article class="group relative overflow-hidden border border-[#d8d6ce] bg-[#fbfaf7] p-5 transition duration-300 hover:-translate-y-1 hover:border-[#e06a3a] hover:shadow-[0_16px_32px_rgba(29,41,37,0.08)]">
    <div class="mb-10 flex items-start justify-between">
        <span class="font-mono text-xs font-medium tracking-[0.2em] text-[#e06a3a]">{{ $code }}</span>
        <span class="text-xs text-[#8a918c]">VES</span>
    </div>
    <p class="mb-1 text-sm text-[#68736d]">1 {{ $base }} equivale a</p>
    <p class="font-mono text-3xl font-medium tracking-tight text-[#1d2925]">
        {{ number_format($value, 2, ',', '.') }} <span class="text-base text-[#8a918c]">{{ $quote }}</span>
    </p>
    <div class="absolute -bottom-8 -right-5 h-24 w-24 rounded-full border-14 border-[#e06a3a]/10 transition duration-300 group-hover:scale-125"></div>
</article>