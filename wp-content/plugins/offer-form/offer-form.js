document.addEventListener('DOMContentLoaded', function () {
    var openBtn = document.getElementById('offer-form-open');
    var closeBtn = document.getElementById('offer-form-close');
    var panel = document.getElementById('offer-form-panel');
    if (openBtn && closeBtn && panel) {
        openBtn.addEventListener('click', function () {
            panel.classList.add('active');
        });
        closeBtn.addEventListener('click', function () {
            panel.classList.remove('active');
        });
    }
});
