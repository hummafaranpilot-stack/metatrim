let btnLoading = false;
let submitBtns = document.querySelectorAll('.submit-order-btn');
submitBtns.forEach(submitBtn => {
    submitBtn.addEventListener('click',
        function(e) {
            e.preventDefault();
            if (btnLoading) {
                return false;
            }
            btnLoading = true;
            submitBtns.forEach(btn => {
                btn.disabled = true;
                btn.removeAttribute('onclick');
                btn.classList.add('disabled');
            });
            let loaders = document.querySelectorAll('.loader-wrap');
            loaders.forEach(loader => {
                loader.style.display = 'block';
                loader.classList.add('disabled');
            });
            this.closest('form').submit();
        })
});