(function (window, document) {
    'use strict';

    const statusLabels = {
        complete: 'Vollständig',
        partial: 'Teilweise',
        not_applicable: 'Keine Daten vorhanden',
        failed: 'Fehlgeschlagen',
        missing: 'Fehlt',
    };

    const element = (tagName, text, className) => {
        const node = document.createElement(tagName);
        if (text !== undefined && text !== null) {
            node.textContent = String(text);
        }
        if (className) {
            node.className = className;
        }
        return node;
    };

    const definition = (label, value) => {
        const fragment = document.createElement('div');
        fragment.className = 'data-protection-definition';
        fragment.append(element('dt', label), element('dd', value));
        return fragment;
    };

    const attributesTable = (attributes) => {
        const wrapper = element('div', null, 'data-protection-table-wrapper');
        const table = document.createElement('table');
        table.className = 'data-protection-table';
        const head = document.createElement('thead');
        const headRow = document.createElement('tr');
        const nameHeader = element('th', 'Datenfeld');
        const valueHeader = element('th', 'Gespeicherter Wert');
        nameHeader.setAttribute('scope', 'col');
        valueHeader.setAttribute('scope', 'col');
        headRow.append(nameHeader, valueHeader);
        head.append(headRow);

        const body = document.createElement('tbody');
        Object.entries(attributes || {}).forEach(([name, value]) => {
            const row = document.createElement('tr');
            const nameCell = element('th', name);
            nameCell.setAttribute('scope', 'row');
            row.append(nameCell, element('td', value === null ? '—' : value));
            body.append(row);
        });
        table.append(head, body);
        wrapper.append(table);
        return wrapper;
    };

    const entryView = (entry) => {
        const section = element('section', null, 'data-protection-entry');
        section.append(element('h3', entry.categoryLabel || 'Datensatz'));
        section.append(element('p', entry.summary || 'Keine Zusammenfassung vorhanden.'));

        const metadata = document.createElement('dl');
        metadata.className = 'data-protection-metadata';
        metadata.append(
            definition('Referenz', entry.reference),
            definition('Zweck', entry.purpose),
            definition('Herkunft', entry.source),
            definition('Empfängerkategorien', (entry.recipientCategories || []).join(', ')),
            definition('Aufbewahrung', entry.retention),
            definition('Drittlandübermittlung', entry.thirdCountryTransfer),
            definition('Automatisierte Entscheidung', entry.automatedDecision),
        );
        if (entry.thirdPartyContentNotice) {
            metadata.append(definition('Drittpersonenhinweis', entry.thirdPartyContentNotice));
        }
        section.append(metadata, attributesTable(entry.attributes));
        return section;
    };

    const providerView = (appId, provider) => {
        const article = element('article', null, 'data-protection-provider');
        article.append(element('h2', provider.displayName || appId));
        article.append(element('p', statusLabels[provider.status] || provider.status, 'data-protection-provider-status'));

        if ((provider.restrictions || []).length > 0) {
            const restrictions = document.createElement('ul');
            restrictions.className = 'data-protection-restrictions';
            provider.restrictions.forEach((restriction) => restrictions.append(element('li', restriction)));
            article.append(restrictions);
        }
        (provider.entries || []).forEach((entry) => article.append(entryView(entry)));
        return article;
    };

    const render = (container, report) => {
        container.replaceChildren();
        const status = element(
            'p',
            report.coverageComplete
                ? 'Die konfigurierte Providerabdeckung ist vollständig.'
                : 'Die appübergreifende Auskunft ist derzeit nicht vollständig.',
            'data-protection-summary',
        );
        status.setAttribute('role', 'status');
        container.append(status);

        const providers = Object.entries(report.providers || {});
        if (providers.length === 0) {
            container.append(element('p', 'Noch sind keine Datenschutzprovider registriert.'));
            return;
        }
        providers.forEach(([appId, provider]) => container.append(providerView(appId, provider)));
    };

    window.FilzmannDataProtection = window.FilzmannDataProtection || {};
    window.FilzmannDataProtection.reportView = { render };
})(window, document);
