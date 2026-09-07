import './bootstrap';

const converter = document.querySelector('[data-currency-converter]');

if (converter) {
	const form = converter.querySelector('[data-converter-form]');
	const amountInput = converter.querySelector('[data-converter-amount]');
	const fromInput = converter.querySelector('[data-converter-from]');
	const toInput = converter.querySelector('[data-converter-to]');
	const result = converter.querySelector('[data-converter-result]');
	const submit = converter.querySelector('[data-converter-submit]');
	const formatter = new Intl.NumberFormat('es-VE', { maximumFractionDigits: 2 });

	const renderResult = (conversion) => {
		if (conversion.value === null) {
			result.innerHTML = '<p class="font-mono text-xs uppercase tracking-[0.12em] text-[#f29a72]">No hay una cotización disponible para esta conversión.</p>';
			return;
		}

		result.innerHTML = `<p class="mb-2 font-mono text-[10px] uppercase tracking-[0.15em] text-white/50">Resultado</p><p class="font-mono text-3xl font-medium text-white">${formatter.format(conversion.value)} <span class="text-base text-white/50">${conversion.to}</span></p>`;
	};

	const convert = async () => {
		const amount = Number(amountInput.value);

		if (!Number.isFinite(amount) || amount < 0) {
			result.innerHTML = '<p class="font-mono text-xs uppercase tracking-[0.12em] text-[#f29a72]">Introduce una cantidad válida.</p>';
			return;
		}

		submit.disabled = true;
		submit.textContent = 'Consultando...';

		try {
			const params = new URLSearchParams({
				amount: String(amount),
				from: fromInput.value,
				to: toInput.value,
			});
			const response = await fetch(`${converter.dataset.endpoint}?${params}`);

			if (!response.ok) {
				throw new Error('Conversion request failed');
			}

			const payload = await response.json();
			renderResult(payload.conversion);
		} catch {
			result.innerHTML = '<p class="font-mono text-xs uppercase tracking-[0.12em] text-[#f29a72]">No fue posible consultar el mercado.</p>';
		} finally {
			submit.disabled = false;
			submit.textContent = 'Convertir';
		}
	};

	form.addEventListener('submit', (event) => {
		event.preventDefault();
		convert();
	});
}
