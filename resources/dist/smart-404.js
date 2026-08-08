(function () {
    'use strict';

    var shell = document.getElementById('capell-smart-404');
    if (!shell) return;

    var list = shell.querySelector('.capell-smart-404__list');
    var endpoint = shell.getAttribute('data-endpoint');
    var path = window.location.pathname;
    if (!list || !endpoint || !window.fetch) return;

    var controller = typeof AbortController === 'function' ? new AbortController() : null;
    var timeout = window.setTimeout(function () {
        if (controller) controller.abort();
        shell.hidden = true;
    }, 2000);

    window.fetch(endpoint + '?path=' + encodeURIComponent(path), {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
        signal: controller ? controller.signal : undefined
    }).then(function (response) {
        if (!response.ok) throw new Error('Smart 404 request failed');
        return response.json();
    }).then(function (payload) {
        window.clearTimeout(timeout);
        var suggestions = payload && Array.isArray(payload.suggestions) ? payload.suggestions : [];
        list.textContent = '';
        suggestions.forEach(function (suggestion) {
            if (!suggestion || typeof suggestion.title !== 'string' || typeof suggestion.url !== 'string') return;
            var item = document.createElement('li');
            var link = document.createElement('a');
            link.textContent = suggestion.title;
            link.href = suggestion.url;
            item.appendChild(link);
            list.appendChild(item);
        });
        shell.hidden = suggestions.length === 0;
    }).catch(function () {
        window.clearTimeout(timeout);
        shell.hidden = true;
    });
}());
