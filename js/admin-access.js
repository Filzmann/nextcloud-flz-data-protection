(function () {
    'use strict';

    const shared = window.OCA.FlzDataProtection?.Shared?.ui;
    if (!shared?.AdminAccessOverlay) return;

    const root = document.getElementById('data-protection-full-access');
    const canManage = root?.dataset.canManage === 'true';

    const request = async (path, options = {}) => {
        const headers = { Accept: 'application/json', ...(options.headers || {}) };
        if (options.method && options.method !== 'GET') headers.requesttoken = OC.requestToken;
        const response = await fetch(OC.generateUrl('/apps/flz_data_protection' + path), {
            credentials: 'same-origin',
            ...options,
            headers,
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.message || 'Anfrage fehlgeschlagen.');
        return payload;
    };

    new shared.AdminAccessOverlay({
        overlayId: 'data-protection-admin-access-overlay',
        triggerId: 'data-protection-admin-grant-warning',
        closeId: 'data-protection-admin-access-overlay-close',
        formId: 'data-protection-full-access-form',
        historyId: 'data-protection-full-access-history',
        statusId: 'data-protection-full-access-status',
        canManage,
        api: {
            getState: () => request('/api/v1/admin/full-access'),
            activate: (targetUid, durationMinutes) => request('/api/v1/admin/full-access', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ targetUid, durationMinutes }),
            }),
            revoke: (targetUid) => request('/api/v1/admin/full-access/' + encodeURIComponent(targetUid), { method: 'DELETE' }),
        },
    });
})();
