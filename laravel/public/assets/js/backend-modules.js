(function () {
    'use strict';
    var root = document.getElementById('modules-workspace');
    if (!root) return;
    var form = document.getElementById('modules-form');
    var cards = Array.from(root.querySelectorAll('[data-module-card]'));
    var switches = Array.from(form.querySelectorAll('input[type="checkbox"]'));
    var initial = switches.map(function (input) { return input.checked; });
    var search = document.getElementById('modules-search');
    var status = document.getElementById('modules-status');
    function update() {
        var enabled = 0, features = 0, visible = 0;
        var term = search.value.trim().toLocaleLowerCase();
        cards.forEach(function (card) {
            var master = card.querySelector('[data-module-master]');
            var children = Array.from(card.querySelectorAll('[data-feature-switch]'));
            card.classList.toggle('is-disabled', !master.checked);
            card.querySelector('.module-state-text').textContent = master.checked ? 'Enabled' : 'Disabled';
            card.querySelector('.module-count').textContent = children.filter(function (input) { return input.checked; }).length + ' / ' + children.length + ' selected';
            if (master.checked) { enabled++; features += children.filter(function (input) { return input.checked; }).length; }
            var titleMatch = card.querySelector('h2').textContent.toLocaleLowerCase().indexOf(term) !== -1;
            var rowMatch = false;
            card.querySelectorAll('[data-feature-label]').forEach(function (row) {
                var match = !term || titleMatch || row.getAttribute('data-feature-label').indexOf(term) !== -1;
                row.hidden = !match; rowMatch = rowMatch || match;
            });
            card.hidden = !(titleMatch || rowMatch) || (status.value === 'on' && !master.checked) || (status.value === 'off' && master.checked);
            if (!card.hidden) visible++;
        });
        var changes = switches.filter(function (input, index) { return input.checked !== initial[index]; }).length;
        document.getElementById('modules-enabled-count').textContent = enabled;
        document.getElementById('features-enabled-count').textContent = features;
        document.getElementById('modules-result-count').textContent = visible + ' / ' + cards.length + ' modules';
        document.getElementById('modules-empty').hidden = visible !== 0;
        document.getElementById('modules-changes').textContent = changes ? changes + ' unsaved changes / সেভ করা হয়নি' : 'All changes saved / পরিবর্তন সেভ আছে';
        root.querySelector('.modules-savebar').classList.toggle('has-changes', changes > 0);
        form.dataset.dirty = changes ? '1' : '0';
    }
    switches.forEach(function (input) { input.addEventListener('change', update); });
    search.addEventListener('input', update); status.addEventListener('change', update);
    document.getElementById('modules-enable-all').addEventListener('click', function () { switches.forEach(function (input) { input.checked = true; }); update(); });
    document.getElementById('modules-disable-all').addEventListener('click', function () {
        if (!window.confirm('সব backend module বন্ধ করতে চান? Modules and Extension চালু থাকবে। Public website একই থাকবে। Save না করা পর্যন্ত কিছু বদলাবে না।')) return;
        switches.forEach(function (input) { input.checked = false; }); update();
    });
    var submitted = false;
    form.addEventListener('submit', function () { submitted = true; document.getElementById('modules-save').disabled = true; });
    window.addEventListener('beforeunload', function (event) { if (!submitted && form.dataset.dirty === '1') { event.preventDefault(); event.returnValue = ''; } });
    update();
}());
