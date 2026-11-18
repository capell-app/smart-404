;(function () {
    'use strict'

    var shell = document.getElementById('capell-smart-404')
    if (!shell) return

    var list = shell.querySelector('.capell-smart-404__list')
    var endpoint = shell.getAttribute('data-endpoint')
    var path = window.location.pathname
    if (!list || !endpoint || !window.fetch) return

    // The server already resolved suggestions for this address: keep them rather than resolving a second time.
    if (list.children.length > 0) return

    var timeoutMs = parseInt(shell.getAttribute('data-timeout'), 10)
    if (!(timeoutMs > 0)) timeoutMs = 2000

    var controller =
        typeof AbortController === 'function' ? new AbortController() : null
    var timeout = window.setTimeout(function () {
        if (controller) controller.abort()
    }, timeoutMs)

    window
        .fetch(endpoint + '?path=' + encodeURIComponent(path), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
            signal: controller ? controller.signal : undefined,
        })
        .then(function (response) {
            if (!response.ok) throw new Error('Smart 404 request failed')
            return response.json()
        })
        .then(function (payload) {
            window.clearTimeout(timeout)
            var suggestions =
                payload && Array.isArray(payload.suggestions)
                    ? payload.suggestions
                    : []
            list.textContent = ''
            suggestions.forEach(function (suggestion) {
                if (
                    !suggestion ||
                    typeof suggestion.title !== 'string' ||
                    typeof suggestion.url !== 'string'
                )
                    return
                var item = document.createElement('li')
                var link = document.createElement('a')
                link.textContent = suggestion.title
                link.href = suggestion.url
                item.appendChild(link)
                list.appendChild(item)
            })
            shell.hidden = suggestions.length === 0
        })
        .catch(function () {
            // A failed or slow request leaves whatever the page already shows.
            window.clearTimeout(timeout)
        })
})()
