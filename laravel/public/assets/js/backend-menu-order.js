(function () {
    'use strict';
    var root = document.getElementById('menu-order-workspace');
    if (!root) return;
    var form = document.getElementById('menu-order-form');
    var input = document.getElementById('menu-order-value');
    var initial = input.value;
    var defaults = JSON.parse(document.getElementById('menu-order-defaults').textContent);
    var lists = Array.from(root.querySelectorAll('[data-order-group]'));
    var dragging = null, submitted = false;
    function children(list) { return Array.from(list.children).filter(function (node) { return node.hasAttribute('data-order-id'); }); }
    function serialize() {
        var order = {};
        lists.forEach(function (list) {
            var rows = children(list);
            order[list.dataset.orderGroup] = rows.map(function (row, index) {
                row.querySelector('.menu-order-position').textContent = index + 1;
                row.querySelector('[data-move="up"]').disabled = index === 0;
                row.querySelector('[data-move="down"]').disabled = index === rows.length - 1;
                return row.dataset.orderId;
            });
        });
        return order;
    }
    function equal(a, b) { var keys = Object.keys(a); return keys.length === Object.keys(b).length && keys.every(function (key) { return JSON.stringify(a[key]) === JSON.stringify(b[key]); }); }
    function update() {
        var order = serialize(); input.value = JSON.stringify(order);
        var changed = !equal(order, JSON.parse(initial));
        form.dataset.dirty = changed ? '1' : '0';
        document.getElementById('menu-order-changes').textContent = changed ? 'Menu order changed / সেভ করা হয়নি' : 'Menu order saved / পরিবর্তন সেভ আছে';
        root.querySelector('.menu-order-savebar').classList.toggle('has-changes', changed);
    }
    root.querySelectorAll('[data-order-id]').forEach(function (row) {
        var head = row.querySelector('.menu-order-row-head');
        head.querySelectorAll('[data-move]').forEach(function (button) {
            button.addEventListener('click', function () {
                var list = row.parentNode, rows = children(list), index = rows.indexOf(row);
                if (button.dataset.move === 'up' && index > 0) list.insertBefore(row, rows[index - 1]);
                if (button.dataset.move === 'down' && index < rows.length - 1) list.insertBefore(rows[index + 1], row);
                update();
            });
        });
        var expand = head.querySelector('.menu-order-expand');
        if (expand) expand.addEventListener('click', function () {
            var list = Array.from(row.children).filter(function (node) { return node.hasAttribute('data-order-group'); })[0];
            list.hidden = !list.hidden; expand.setAttribute('aria-expanded', String(!list.hidden));
        });
        row.addEventListener('dragstart', function (event) {
            event.stopPropagation(); dragging = row; row.classList.add('is-dragging');
            event.dataTransfer.effectAllowed = 'move'; event.dataTransfer.setData('text/plain', row.dataset.orderId);
        });
        row.addEventListener('dragover', function (event) {
            event.stopPropagation();
            if (!dragging || dragging === row || dragging.parentNode !== row.parentNode) return;
            event.preventDefault(); row.classList.add('drop-target'); event.dataTransfer.dropEffect = 'move';
        });
        row.addEventListener('dragleave', function () { row.classList.remove('drop-target'); });
        row.addEventListener('drop', function (event) {
            event.stopPropagation(); row.classList.remove('drop-target');
            if (!dragging || dragging === row || dragging.parentNode !== row.parentNode) return;
            event.preventDefault(); var rect = row.getBoundingClientRect();
            row.parentNode.insertBefore(dragging, event.clientY < rect.top + rect.height / 2 ? row : row.nextSibling); update();
        });
        row.addEventListener('dragend', function (event) {
            event.stopPropagation(); dragging = null;
            root.querySelectorAll('.is-dragging,.drop-target').forEach(function (node) { node.classList.remove('is-dragging', 'drop-target'); });
        });
    });
    document.getElementById('menu-order-reset').addEventListener('click', function () {
        if (!window.confirm('Default menu order ফিরিয়ে আনবেন? Save করার পরে কার্যকর হবে।')) return;
        lists.forEach(function (list) { var map = {}; children(list).forEach(function (row) { map[row.dataset.orderId] = row; }); (defaults[list.dataset.orderGroup] || []).forEach(function (id) { if (map[id]) list.appendChild(map[id]); }); }); update();
    });
    form.addEventListener('submit', function () { update(); submitted = true; document.getElementById('menu-order-save').disabled = true; });
    window.addEventListener('beforeunload', function (event) { if (!submitted && form.dataset.dirty === '1') { event.preventDefault(); event.returnValue = ''; } });
    update();
}());
