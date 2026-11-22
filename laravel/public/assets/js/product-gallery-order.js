(function () {
    'use strict';
    function init() {
        var input = document.querySelector('input[name="photos"].selected-files');
        if (!input) return;
        var picker = input.closest('[data-toggle="aizuploader"]') || input.parentElement;
        var preview = picker.nextElementSibling;
        if (!preview || !preview.classList.contains('file-preview')) return;
        var help = document.createElement('p');
        help.className = 'text-muted small mt-2';
        help.textContent = 'ছবি টেনে বা ↑ ↓ বাটনে আগে–পরে সাজান। প্রথম ছবিটি আগে দেখাবে। / Drag or use arrows to reorder.';
        // AIZ uploader requires picker.nextElementSibling to be the preview.
        preview.parentNode.insertBefore(help, preview.nextSibling);
        preview.classList.add('product-gallery-preview');
        var dragged = null;
        function rows() { return Array.from(preview.querySelectorAll('.file-preview-item')); }
        function save() {
            input.value = rows().map(function (r) { return r.dataset.id; }).join(',');
            rows().forEach(function (r, i, all) {
                r.querySelector('.gallery-position').textContent = (i + 1) + ' / ' + all.length;
                r.querySelector('[data-move="-1"]').disabled = i === 0;
                r.querySelector('[data-move="1"]').disabled = i === all.length - 1;
            });
        }
        function decorate() {
            observer.disconnect();
            var order = input.value.split(',');
            rows().sort(function (a,b) { return order.indexOf(a.dataset.id)-order.indexOf(b.dataset.id); }).forEach(function (r) {
                preview.appendChild(r);
                if (r.querySelector('.gallery-order-controls')) return;
                r.draggable = true;
                r.style.cursor = 'grab';
                var controls = document.createElement('div');
                controls.className = 'gallery-order-controls d-flex align-items-center';
                controls.innerHTML = '<span class="gallery-position small mr-2"></span><button type="button" class="btn btn-sm btn-light" data-move="-1" aria-label="Move image earlier">↑</button><button type="button" class="btn btn-sm btn-light ml-1" data-move="1" aria-label="Move image later">↓</button>';
                r.insertBefore(controls, r.querySelector('.remove'));
            });
            if (rows().length) save();
            observer.observe(preview, {childList:true});
        }
        preview.addEventListener('click', function (e) {
            var button = e.target.closest('[data-move]');
            if (!button) return;
            var row = button.closest('.file-preview-item');
            if (button.dataset.move === '-1' && row.previousElementSibling) preview.insertBefore(row,row.previousElementSibling);
            if (button.dataset.move === '1' && row.nextElementSibling) preview.insertBefore(row.nextElementSibling,row);
            save();
        });
        preview.addEventListener('dragstart', function (e) {
            dragged = e.target.closest('.file-preview-item');
            if (!dragged) return;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', dragged.dataset.id);
        });
        preview.addEventListener('dragover', function (e) { if (dragged) e.preventDefault(); });
        preview.addEventListener('drop', function (e) {
            var target = e.target.closest('.file-preview-item');
            if (!dragged || !target || dragged === target) return;
            e.preventDefault();
            var rect = target.getBoundingClientRect();
            preview.insertBefore(dragged, e.clientY < rect.top+rect.height/2 ? target : target.nextSibling);
            save();
            dragged = null;
        });
        preview.addEventListener('dragend', function () { dragged = null; });
        var observer = new MutationObserver(decorate);
        decorate();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
}());
