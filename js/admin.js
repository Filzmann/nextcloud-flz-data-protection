(function () {
    'use strict';
    const form = document.getElementById('data-protection-permission-settings-form');
    const notice = document.getElementById('data-protection-admin-notice');
    if (!form || !notice) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        try {
            const body = new URLSearchParams();
            form.querySelectorAll('select[data-permission]').forEach((select) => {
                if (select.disabled) return;
                const key = `assignments[${select.dataset.permission}][]`;
                const selected = Array.from(select.selectedOptions);
                if (selected.length === 0) body.append(key, '');
                selected.forEach((option) => body.append(key, option.value));
            });
            const response = await fetch(OC.generateUrl('/apps/flz_data_protection/api/v1/retention-settings'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: { requesttoken: OC.requestToken, Accept: 'application/json' },
                body,
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Speichern fehlgeschlagen.');
            notice.textContent = 'Gespeichert.';
            notice.setAttribute('role', 'status');
        } catch (error) {
            notice.textContent = error.message || 'Speichern fehlgeschlagen.';
            notice.setAttribute('role', 'alert');
        }
        notice.hidden = false;
    });
})();
