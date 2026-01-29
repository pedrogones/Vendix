
<script>
    function cpfOrEmailMask(input) {
    input.addEventListener('input', function () {
        let value = input.value;

        if (/[a-zA-Z@]/.test(value)) {
            return;
        }

        let numbers = value.replace(/\D/g, '');

        if (!numbers.length) {
            input.value = '';
            return;
        }

        numbers = numbers.substring(0, 11);

        if (numbers.length > 3) {
            numbers = numbers.replace(/^(\d{3})(\d)/, '$1.$2');
        }
        if (numbers.length > 6) {
            numbers = numbers.replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3');
        }
        if (numbers.length > 9) {
            numbers = numbers.replace(/^(\d{3})\.(\d{3})\.(\d{3})(\d{1,2})$/, '$1.$2.$3-$4');
        }

        input.value = numbers;
    });
}

</script>
