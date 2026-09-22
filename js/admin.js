(function () {
    'use strict';
    const form = document.getElementById('data-protection-admin-form');
    const notice = document.getElementById('data-protection-admin-notice');
    if (!form || !notice) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const data = new FormData(form);
        try {
            const response = await fetch(OC.generateUrl('/apps/filzmann_data_protection/api/v1/retention-settings'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: { requesttoken: OC.requestToken, Accept: 'application/json' },
                body: new URLSearchParams({
                    reviewer_groups: data.get('reviewer_groups') || '',
                }),
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
