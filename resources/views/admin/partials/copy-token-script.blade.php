<script>
    document.addEventListener('click', function (event) {
        const button = event.target.closest('[data-copy-token]');
        if (!button) return;
        const token = button.getAttribute('data-copy-token');
        const done = function () {
            const original = button.textContent;
            button.textContent = 'Tersalin';
            setTimeout(function () { button.textContent = original; }, 1500);
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(token).then(done);
        } else {
            const field = document.createElement('textarea');
            field.value = token;
            document.body.appendChild(field);
            field.select();
            document.execCommand('copy');
            field.remove();
            done();
        }
    });
    document.querySelectorAll('form[data-single-submit]').forEach(function (form) {
        form.addEventListener('submit', function () {
            const submit = form.querySelector('[type=submit]');
            if (submit) { submit.disabled = true; submit.textContent = 'Menyimpan…'; }
        });
    });
</script>
