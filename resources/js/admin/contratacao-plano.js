const formatCurrency = (value) => {
    const numericValue = Number.parseFloat(value);

    if (Number.isNaN(numericValue)) {
        return '—';
    }

    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(numericValue);
};

const initializeContractForm = (form) => {
    const planSelect = form.querySelector('[data-contract-plan-select]');
    const durationInput = form.querySelector('[data-contract-duration]');
    const valueInput = form.querySelector('[data-contract-value]');
    const summaryName = form.querySelector('[data-contract-summary-name]');
    const summaryValue = form.querySelector('[data-contract-summary-value]');
    const summaryDuration = form.querySelector('[data-contract-summary-duration]');

    if (!planSelect || !durationInput || !valueInput) {
        return;
    }

    const updateSummary = () => {
        const option = planSelect.selectedOptions[0];
        const hasPlan = option?.value;

        summaryName.textContent = hasPlan
            ? option.dataset.nome
            : 'Nenhum plano selecionado';
        summaryValue.textContent = hasPlan
            ? formatCurrency(option.dataset.valor)
            : '—';
        summaryDuration.textContent = hasPlan
            ? `${option.dataset.duracao} dias`
            : '—';
    };

    planSelect.addEventListener('change', () => {
        const option = planSelect.selectedOptions[0];

        if (option?.value) {
            durationInput.value = option.dataset.duracao;
            valueInput.value = option.dataset.valor;
        } else {
            durationInput.value = '';
            valueInput.value = '';
        }

        updateSummary();
    });

    const selectedOption = planSelect.selectedOptions[0];

    if (selectedOption?.value) {
        if (!durationInput.value) {
            durationInput.value = selectedOption.dataset.duracao;
        }

        if (!valueInput.value) {
            valueInput.value = selectedOption.dataset.valor;
        }
    }

    updateSummary();
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-contract-form]').forEach(initializeContractForm);
});
