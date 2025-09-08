document.addEventListener('DOMContentLoaded', function () {
    var openBtn = document.getElementById('offer-form-open');
    var closeBtn = document.getElementById('offer-form-close');
    var panel = document.getElementById('offer-form-panel');
    var form = panel ? panel.querySelector('form') : null;
    if (openBtn && closeBtn && panel) {
        openBtn.addEventListener('click', function () {
            panel.classList.add('active');
        });
        closeBtn.addEventListener('click', function () {
            panel.classList.remove('active');
        });
    }
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var formData = new FormData(form);
            formData.append('offer_form_nonce', offerFormData.nonce);
            fetch(offerFormData.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
            .then(function (resp) { return resp.json(); })
            .then(function (data) {
                if (data.success) {
                    alert('Thank you for your offer!');
                    form.reset();
                    panel.classList.remove('active');
                } else {
                    alert(data.data && data.data.message ? data.data.message : 'Error submitting offer');
                }
            })
            .catch(function () {
                alert('Error submitting offer');
            });
        });
    }
});
