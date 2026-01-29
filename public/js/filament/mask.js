function applyMask() {
    if (typeof Inputmask === 'undefined') return;

    const masks = {
        cpf: '999.999.999-99',
        phone: '(99) 99999-9999',
    };

    Object.entries(masks).forEach(([id, mask]) => {
        document
            .querySelectorAll(`input#${id}:not([data-masked])`)
            .forEach(input => {
                Inputmask({
                    mask,
                    clearIncomplete: true,
                    showMaskOnHover: false,
                    showMaskOnFocus: false,
                }).mask(input);

                input.dataset.masked = 'true';
            });
    });
}

const scheduleMask = () => setTimeout(applyMask, 0);

document.addEventListener('livewire:init', () => {
    scheduleMask();

    if (window.Livewire?.hook) {
        Livewire.hook('commit', ({ succeed }) => {
            succeed(() => scheduleMask());
        });
    }
});

document.addEventListener('livewire:navigated', scheduleMask);
