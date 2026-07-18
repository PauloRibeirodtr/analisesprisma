const initializeReactivationForm = (form) => {
    const toggle = form.querySelector('[data-payment-toggle]');
    const fieldsContainer = form.querySelector('[data-payment-fields]');

    if (!toggle || !fieldsContainer) {
        return;
    }

    const paymentFields = fieldsContainer.querySelectorAll('input, select');

    const updatePaymentFields = () => {
        paymentFields.forEach((field) => {
            field.disabled = !toggle.checked;
            field.required = toggle.checked;
        });

        fieldsContainer.classList.toggle(
            'prisma-reactivation-payment--disabled',
            !toggle.checked,
        );
    };

    toggle.addEventListener('change', updatePaymentFields);
    updatePaymentFields();
};

document.addEventListener('DOMContentLoaded', () => {
    document
        .querySelectorAll('[data-reactivation-form]')
        .forEach(initializeReactivationForm);
});
