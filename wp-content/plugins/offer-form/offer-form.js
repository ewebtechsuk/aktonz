document.addEventListener('DOMContentLoaded', function () {
    var panel = document.getElementById('offer-form-panel');
    var openBtn = document.getElementById('offer-form-open');
    var closeBtn = document.getElementById('offer-form-close');
    var form = panel ? panel.querySelector('form') : null;

    function openPanel() {
        if (panel) {
            panel.classList.add('active');
        }
    }

    function closePanel() {
        if (panel) {
            panel.classList.remove('active');
        }
    }

    window.showOfferForm = openPanel;
    window.closeOfferForm = closePanel;

    if (openBtn) {
        openBtn.addEventListener('click', openPanel);
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', closePanel);
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
                    closePanel();
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
