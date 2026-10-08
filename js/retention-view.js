(function (window, document) {
    'use strict';
    const element = (tag, text) => {
        const node = document.createElement(tag);
        if (text !== undefined) node.textContent = String(text);
        return node;
    };
    const providerArticle = (appId, provider, onContinue) => {
        const article = element('article');
        article.className = 'data-protection-provider';
        article.dataset.providerAppId = appId;
        article.append(element('h3', provider.displayName || appId));
        const status = element('p', provider.status === 'complete' ? 'Vorschau vollständig' : 'Vorschau nicht vollständig');
        status.className = 'data-protection-retention-status';
        article.append(status);
        const list = element('ul');
        list.className = 'data-protection-retention-candidates';
        (provider.candidates || []).forEach((candidate) => {
            list.append(element('li', `${candidate.reference}: ${candidate.reviewReason} (${candidate.action})`));
        });
        if ((provider.candidates || []).length === 0) {
            const empty = element('li', 'Keine fälligen REVIEW-Kandidaten.');
            empty.className = 'data-protection-retention-empty';
            list.append(empty);
        }
        article.append(list);
        appendContinuations(article, provider.continuations, onContinue);
        return article;
    };
    const appendContinuations = (article, continuations, onContinue) => {
        Object.entries(continuations || {}).forEach(([policyId, continuation]) => {
            const button = element('button', 'Weitere REVIEW-Kandidaten laden');
            button.type = 'button';
            button.dataset.policyId = policyId;
            button.dataset.continuation = continuation;
            button.addEventListener('click', () => onContinue(continuation, button));
            article.append(button);
        });
    };
    const render = (container, report, onContinue = () => {}) => {
        container.replaceChildren();
        const providers = Object.entries(report.providers || {});
        if (providers.length === 0) {
            container.append(element('p', 'Noch sind keine Retention-Provider registriert.'));
            return;
        }
        providers.forEach(([appId, provider]) => {
            container.append(providerArticle(appId, provider, onContinue));
        });
    };
    const append = (container, report, onContinue = () => {}) => {
        Object.entries(report.providers || {}).forEach(([appId, provider]) => {
            const article = Array.from(container.querySelectorAll('.data-protection-provider'))
                .find((candidate) => candidate.dataset.providerAppId === appId);
            if (!article) {
                container.append(providerArticle(appId, provider, onContinue));
                return;
            }
            const list = article.querySelector('.data-protection-retention-candidates');
            const status = article.querySelector('.data-protection-retention-status');
            if (status) status.textContent = provider.status === 'complete' ? 'Vorschau vollständig' : 'Vorschau nicht vollständig';
            if ((provider.candidates || []).length > 0) {
                article.querySelectorAll('.data-protection-retention-empty').forEach((empty) => empty.remove());
            }
            (provider.candidates || []).forEach((candidate) => {
                list.append(element('li', `${candidate.reference}: ${candidate.reviewReason} (${candidate.action})`));
            });
            article.querySelectorAll('button[data-continuation]').forEach((button) => button.remove());
            appendContinuations(article, provider.continuations, onContinue);
        });
    };
    window.FlzDataProtection = window.FlzDataProtection || {};
    window.FlzDataProtection.retentionView = { render, append };
})(window, document);
