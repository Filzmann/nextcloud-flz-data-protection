(function () {
    'use strict';

    const form = document.getElementById('data-protection-full-access-form');
    const history = document.getElementById('data-protection-full-access-history');
    const status = document.getElementById('data-protection-full-access-status');
    if (!form || !history || !status) return;

    const request = async (path, options = {}) => {
        const headers = { Accept: 'application/json', ...(options.headers || {}) };
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

    const dateLabel = (value) => value ? new Date(value).toLocaleString() : '—';
    const loadGrants = async () => {
        try {
            const state = await request('/api/v1/admin/full-access');
            history.replaceChildren();
            if (!state.history?.length) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = 6;
                cell.textContent = 'Noch keine Freigaben protokolliert.';
                row.append(cell);
                history.append(row);
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
                history.append(row);
            });
        } catch (error) {
            status.textContent = error.message || 'Die Vollzugriffshistorie konnte nicht geladen werden.';
            status.setAttribute('role', 'alert');
        }
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const data = new FormData(form);
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
            form.elements.enabled.checked = false;
            status.textContent = 'Der zeitlich begrenzte Vollzugriff wurde aktiviert.';
            status.setAttribute('role', 'status');
            await loadGrants();
        } catch (error) {
            status.textContent = error.message || 'Der Vollzugriff konnte nicht aktiviert werden.';
            status.setAttribute('role', 'alert');
        }
    });

    history.addEventListener('click', async (event) => {
        const button = event.target.closest('button[data-revoke-uid]');
        if (!button) return;
        button.disabled = true;
        try {
            await request('/api/v1/admin/full-access/' + encodeURIComponent(button.dataset.revokeUid), { method: 'DELETE' });
            status.textContent = 'Der Vollzugriff wurde widerrufen.';
            status.setAttribute('role', 'status');
            await loadGrants();
        } catch (error) {
            button.disabled = false;
            status.textContent = error.message || 'Der Vollzugriff konnte nicht widerrufen werden.';
            status.setAttribute('role', 'alert');
        }
    });

    void loadGrants();
})();
