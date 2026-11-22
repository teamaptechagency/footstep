(function () {
    'use strict';
    var input = document.getElementById('menu-search');
    var menu = document.getElementById('main-menu');
    var results = document.getElementById('search-menu');
    if (!input || !menu || !results) return;
    function label(link) {
        var span = link.querySelector('.aiz-side-nav-text');
        return (span ? span.textContent : link.textContent).replace(/\s+/g, ' ').trim();
    }
    function search() {
        var query = input.value.trim().toLocaleLowerCase();
        results.replaceChildren();
        menu.classList.toggle('d-none', !!query);
        menu.hidden = !!query;
        if (!query) return;
        var tokens = query.split(/\s+/);
        var seen = new Set();
        menu.querySelectorAll('a[href]').forEach(function (link) {
            var href = link.getAttribute('href');
            if (!href || href.charAt(0) === '#') return;
            var url;
            try { url = new URL(href, location.href); } catch (e) { return; }
            if (url.origin !== location.origin || !/^https?:$/.test(url.protocol)) return;
            var text = label(link);
            if (!text) return;
            var parents = [], item = link.closest('li');
            while (item && item.parentElement !== menu) {
                item = item.parentElement.closest('li');
                if (item) {
                    var parentLink = Array.prototype.find.call(item.children, function (child) { return child.tagName === 'A'; });
                    if (parentLink) parents.unshift(label(parentLink));
                }
            }
            var searchable = parents.concat(text).join(' ').toLocaleLowerCase();
            if (!tokens.every(function (token) { return searchable.indexOf(token) !== -1; }) || seen.has(url.href)) return;
            seen.add(url.href);
            var li = document.createElement('li');
            li.className = 'aiz-side-nav-item';
            var a = document.createElement('a');
            a.className = 'aiz-side-nav-link';
            a.href = url.href;
            var icon = document.createElement('i');
            icon.className = 'las la-search aiz-side-nav-icon';
            icon.setAttribute('aria-hidden', 'true');
            var content = document.createElement('span');
            content.className = 'aiz-side-nav-text';
            content.textContent = text;
            if (parents.length) {
                var context = document.createElement('small');
                context.style.cssText = 'display:block;opacity:.75;font-size:10px;line-height:1.5';
                context.textContent = parents.join(' / ');
                content.appendChild(context);
            }
            a.append(icon, content);
            li.appendChild(a);
            results.appendChild(li);
        });
        if (!results.children.length) {
            var empty = document.createElement('li');
            empty.className = 'aiz-side-nav-item';
            empty.style.cssText = 'padding:12px 20px;color:inherit';
            empty.textContent = 'No matching menu / কোনো মেনু পাওয়া যায়নি';
            results.appendChild(empty);
        }
    }
    input.removeAttribute('onkeyup');
    input.setAttribute('aria-label', 'Search admin menus');
    input.setAttribute('aria-controls', 'search-menu');
    results.setAttribute('aria-live', 'polite');
    input.addEventListener('input', search);
    input.addEventListener('search', search);
    input.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') { input.value = ''; search(); }
        if (event.key === 'Enter' && input.value.trim()) {
            var first = results.querySelector('a');
            if (first) { event.preventDefault(); first.click(); }
        }
    });
    window.menuSearch = search;
    search();
})();
