(function () {
    'use strict';
    const form = document.getElementById('data-protection-admin-form');
    const notice = document.getElementById('data-protection-admin-notice');
    if (!form || !notice) return;

    const request = async (path, options = {}) => {
        const headers = options.headers || {};
        if (options.method && options.method !== 'GET') headers.requesttoken = OC.requestToken;
        const response = await fetch(OC.generateUrl('/apps/filzmann_data_protection' + path), {
            credentials: 'same-origin',
            ...options,
            headers,
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.message || 'Anfrage fehlgeschlagen.');
        return payload;
    };

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

    const grantForm = document.getElementById('data-protection-full-access-form');
    const grantHistory = document.getElementById('data-protection-full-access-history');
    const grantStatus = document.getElementById('data-protection-full-access-status');
    if (!grantForm || !grantHistory || !grantStatus) return;

    const dateLabel = (value) => value ? new Date(value).toLocaleString() : '—';
    const loadGrants = async () => {
        try {
            const state = await request('/api/v1/admin/full-access');
            grantHistory.replaceChildren();
            if (!state.history?.length) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 6;
                cell.textContent = 'Noch keine Freigaben protokolliert.';
                row.append(cell);
                grantHistory.append(row);
                return;
            }
            state.history.forEach((grant) => {
                const row = document.createElement('tr');
                const active = !grant.revokedAt && new Date(grant.startsAt).getTime() <= Date.now() && new Date(grant.endsAt).getTime() > Date.now();
                [
                    grant.targetUid,
                    grant.grantedBy,
                    dateLabel(grant.startsAt),
                    dateLabel(grant.endsAt),
                    grant.revokedAt ? dateLabel(grant.revokedAt) : (active ? 'Aktiv' : 'Planmäßig beendet'),
                ].forEach((value) => {
                    const cell = document.createElement('td');
                    cell.textContent = value;
                    row.append(cell);
                });
                const action = document.createElement('td');
                if (active) {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.textContent = 'Widerrufen';
                    button.dataset.revokeUid = grant.targetUid;
                    action.append(button);
                }
                row.append(action);
                grantHistory.append(row);
            });
        } catch (error) {
            grantStatus.textContent = error.message || 'Die Vollzugriffshistorie konnte nicht geladen werden.';
        }
    };

    grantForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const data = new FormData(grantForm);
        if (data.get('enabled') !== 'on') return;
        try {
            await request('/api/v1/admin/full-access', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    targetUid: String(data.get('targetUid') || '').trim(),
                    durationMinutes: Number(data.get('durationMinutes')),
                }),
            });
            grantForm.elements.enabled.checked = false;
            grantStatus.textContent = 'Der zeitlich begrenzte Vollzugriff wurde aktiviert.';
            await loadGrants();
        } catch (error) {
            grantStatus.textContent = error.message || 'Der Vollzugriff konnte nicht aktiviert werden.';
        }
    });

    grantHistory.addEventListener('click', async (event) => {
        const button = event.target.closest('button[data-revoke-uid]');
        if (!button) return;
        button.disabled = true;
        try {
            await request('/api/v1/admin/full-access/' + encodeURIComponent(button.dataset.revokeUid), { method: 'DELETE' });
            grantStatus.textContent = 'Der Vollzugriff wurde widerrufen.';
            await loadGrants();
        } catch (error) {
            button.disabled = false;
            grantStatus.textContent = error.message || 'Der Vollzugriff konnte nicht widerrufen werden.';
        }
    });

    loadGrants();
})();
