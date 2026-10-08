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

    const metadataFields = [
        ['purpose', 'Zweck', (entry) => entry.purpose],
        ['source', 'Herkunft', (entry) => entry.source],
        ['recipientCategories', 'Empfängerkategorien', (entry) => (entry.recipientCategories || []).join(', ')],
        ['retention', 'Aufbewahrung', (entry) => entry.retention],
        ['thirdCountryTransfer', 'Drittlandübermittlung', (entry) => entry.thirdCountryTransfer],
        ['automatedDecision', 'Automatisierte Entscheidung', (entry) => entry.automatedDecision],
        ['thirdPartyContentNotice', 'Drittpersonenhinweis', (entry) => entry.thirdPartyContentNotice],
    ];

    const displayValue = (value) => {
        if (value === null || value === undefined || value === '') return '—';
        if (value === true) return 'Ja';
        if (value === false) return 'Nein';
        return String(value);
    };

    const sharedValue = (entries, getter) => {
        if (entries.length === 0) return null;
        const first = displayValue(getter(entries[0]));
        return first !== '—' && entries.every((entry) => displayValue(getter(entry)) === first) ? first : null;
    };

    const commonValues = (entries, fields) => {
        const values = new Map();
        fields.forEach(([key, label, getter]) => {
            const value = sharedValue(entries, getter);
            if (value !== null) values.set(key, { label, value });
        });
        return values;
    };

    const commonBlock = (title, values, detail) => {
        if (values.size === 0) return null;
        const section = element('section', null, 'data-protection-common');
        section.append(element('h3', title));
        if (detail) section.append(element('p', detail));
        const metadata = document.createElement('dl');
        metadata.className = 'data-protection-metadata';
        values.forEach(({ label, value }) => metadata.append(definition(label, value)));
        section.append(metadata);
        return section;
    };

    const humanReference = (entry) => {
        const reference = String(entry.reference || '');
        const separator = reference.lastIndexOf(':');
        const identifier = separator >= 0 ? reference.slice(separator + 1) : reference;
        const label = entry.categoryLabel || 'Datensatz';
        if (/^[0-9]+$/.test(identifier)) return `${label} Nr. ${identifier}`;
        return identifier ? `${label}, interne Kennung ${identifier.replaceAll('_', ' ').replaceAll('-', ' ')}` : label;
    };

    const entriesTable = (entries, columns) => {
        const wrapper = element('div', null, 'data-protection-table-wrapper');
        const table = document.createElement('table');
        table.className = 'data-protection-table';
        const caption = element('caption', `${entries[0]?.categoryLabel || 'Datensätze'}: unterschiedliche gespeicherte Angaben`);
        const head = document.createElement('thead');
        const headRow = document.createElement('tr');
        columns.forEach(({ label }) => {
            const header = element('th', label);
            header.setAttribute('scope', 'col');
            headRow.append(header);
        });
        head.append(headRow);

        const body = document.createElement('tbody');
        entries.forEach((entry) => {
            const row = document.createElement('tr');
            columns.forEach(({ getter }, index) => {
                const cell = element(index === 0 ? 'th' : 'td', displayValue(getter(entry)));
                if (index === 0) cell.setAttribute('scope', 'row');
                row.append(cell);
            });
            body.append(row);
        });
        table.append(caption, head, body);
        wrapper.append(table);
        return wrapper;
    };

    const categoryView = (entries, providerCommon) => {
        const section = element('section', null, 'data-protection-entry');
        const label = entries[0]?.categoryLabel || 'Datensätze';
        section.append(element('h3', `${label} (${entries.length})`));

        const summary = sharedValue(entries, (entry) => entry.summary);
        if (summary !== null) {
            section.append(element('p', entries.length > 1 ? `${summary}: ${entries.length}-mal protokolliert.` : summary, 'data-protection-category-summary'));
        }

        const categoryCommon = commonValues(entries, metadataFields.filter(([key]) => !providerCommon.has(key)));
        const attributeNames = [...new Set(entries.flatMap((entry) => Object.keys(entry.attributes || {})))];
        const commonAttributes = commonValues(entries, attributeNames.map((name) => [name, name, (entry) => entry.attributes?.[name]]));
        const combined = new Map([...categoryCommon, ...commonAttributes]);
        const common = commonBlock('Gilt für diesen Datentyp', combined);
        if (common) section.append(common);

        const columns = [{ label: 'Interne Zuordnung', getter: humanReference }];
        if (summary === null) columns.push({ label: 'Datensatz', getter: (entry) => entry.summary });
        attributeNames.filter((name) => !commonAttributes.has(name)).forEach((name) => {
            columns.push({ label: name, getter: (entry) => entry.attributes?.[name] });
        });
        metadataFields.filter(([key, , getter]) => !providerCommon.has(key) && !categoryCommon.has(key) && entries.some((entry) => displayValue(getter(entry)) !== '—')).forEach(([, labelText, getter]) => {
            columns.push({ label: labelText, getter });
        });
        section.append(entriesTable(entries, columns));
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
        const entries = provider.entries || [];
        if (entries.length === 0) {
            article.append(element('p', provider.status === 'failed' ? 'Diese App konnte für die Auskunft nicht erreicht werden.' : 'Für dich sind in dieser App keine Datensätze vorhanden.'));
            return article;
        }

        const providerCommon = commonValues(entries, metadataFields);
        const shared = commonBlock('Gilt für alle folgenden Daten', providerCommon, `${entries.length} gespeicherte Einträge`);
        if (shared) article.append(shared);

        const categories = new Map();
        entries.forEach((entry) => {
            const key = entry.categoryId || entry.categoryLabel || 'records';
            if (!categories.has(key)) categories.set(key, []);
            categories.get(key).push(entry);
        });
        categories.forEach((categoryEntries) => article.append(categoryView(categoryEntries, providerCommon)));
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
            bindPersistentHorizontalScroll(container);
            return;
        }
        providers.forEach(([appId, provider]) => container.append(providerView(appId, provider)));
        bindPersistentHorizontalScroll(container);
    };

    let horizontalScrollCleanup = () => {};
    const bindPersistentHorizontalScroll = (container) => {
        horizontalScrollCleanup();
        const root = document.getElementById?.('data-protection-app');
        const target = container.querySelector?.('.data-protection-table-wrapper');
        if (!root || !target) return;
        const proxy = document.createElement('div');
        proxy.className = 'app-horizontal-scroll-proxy';
        proxy.tabIndex = 0;
        proxy.setAttribute('role', 'region');
        proxy.setAttribute('aria-label', 'Horizontal durch den Datenschutzbericht scrollen');
        const track = document.createElement('div');
        track.className = 'app-horizontal-scroll-proxy__track';
        track.setAttribute('aria-hidden', 'true');
        proxy.append(track);
        root.append(proxy);
        const update = () => {
            const visible = target.scrollWidth > target.clientWidth;
            proxy.hidden = !visible;
            track.style.width = `${target.scrollWidth}px`;
            if (visible) proxy.scrollLeft = target.scrollLeft;
        };
        const fromProxy = () => { target.scrollLeft = proxy.scrollLeft; };
        const fromTarget = () => { proxy.scrollLeft = target.scrollLeft; };
        proxy.addEventListener('scroll', fromProxy);
        target.addEventListener('scroll', fromTarget);
        const observer = typeof ResizeObserver === 'function' ? new ResizeObserver(update) : null;
        observer?.observe(root);
        observer?.observe(target);
        window.addEventListener('resize', update);
        update();
        horizontalScrollCleanup = () => {
            observer?.disconnect();
            window.removeEventListener('resize', update);
            proxy.removeEventListener('scroll', fromProxy);
            target.removeEventListener('scroll', fromTarget);
            proxy.remove();
            horizontalScrollCleanup = () => {};
        };
    };

    window.FlzDataProtection = window.FlzDataProtection || {};
    window.FlzDataProtection.reportView = { render };
})(window, document);
