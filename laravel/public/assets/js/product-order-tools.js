(function () {
    'use strict';
    function init() {
        var root = document.getElementById('footstep-product-tools');
        var actions = document.getElementById('footstep-order-actions');
        var form = document.getElementById('option-choice-form');
        if (!root || !actions || !form) return;
        document.body.classList.add('footstep-product-page');
        var bar = document.getElementById('footstep-sticky-order');
        var checkout = document.getElementById('footstep-sticky-checkout');
        var price = document.getElementById('footstep-sticky-price');
        var chosen = form.querySelector('#chosen_price');
        var flashOrder = document.body.dataset.flashOrder === 'true';
        var messenger = 'https://m.me/' + encodeURIComponent(root.dataset.messenger) + '?ref=product_' + encodeURIComponent(root.dataset.id);
        var framePending = false;
        function currentPrice() { return (chosen && chosen.textContent.trim()) || root.dataset.price; }
        function orderButton() { return actions.querySelector('.buy-now:not(.d-none)'); }
        function visible(element) { return element && element.getClientRects().length > 0 && getComputedStyle(element).visibility !== 'hidden'; }
        function message() {
            var lines = ['আমি এই product সম্পর্কে জানতে চাই।', 'Product: ' + root.dataset.name, 'Product ID: ' + root.dataset.id];
            form.querySelectorAll('input[type="radio"]:checked').forEach(function (input) {
                var label = input.name === 'color' ? 'Color' : (input.name.indexOf('attribute_id_') === 0 ? 'Size / option' : 'Option');
                var row = input.closest('.row');
                var rowLabel = row && row.querySelector('.col-sm-2');
                if (rowLabel && rowLabel.textContent.trim()) label = rowLabel.textContent.trim().replace(/:\s*$/, '');
                lines.push(label + ': ' + input.value);
            });
            var qty = form.querySelector('[name="quantity"]');
            lines.push('Quantity: ' + (qty ? qty.value : '1'));
            lines.push('Total price: ' + currentPrice());
            lines.push('Product link: ' + root.dataset.url);
            return lines.join('\n');
        }
        function update() {
            framePending = false;
            var bottomNav = document.querySelector('body[data-layout-theme=bazar] .bazar-bottom-nav, .aiz-mobile-bottom-nav');
            var bottom = visible(bottomNav) ? Math.ceil(bottomNav.getBoundingClientRect().height) : 0;
            bar.style.setProperty('--footstep-bottom-nav', bottom + 'px');
            var header = document.querySelector('header.sticky-top, header.studio-header, header.bazar-header');
            var top = header ? Math.max(0, header.getBoundingClientRect().bottom) : 0;
            var button = orderButton();
            var target = button || actions.querySelector('.out-of-stock') || actions;
            var rect = target.getBoundingClientRect();
            var buttonInView = visible(target) && rect.bottom > top && rect.top < window.innerHeight - bottom;
            var cod=document.querySelector('[data-direct-checkout]');
            if(visible(cod)){var codRect=cod.getBoundingClientRect();buttonInView=buttonInView||(codRect.bottom>top&&codRect.top<window.innerHeight-bottom);}
            var modalOpen = document.body.classList.contains('modal-open') || !!document.querySelector('.modal.show, #footstep-direct-dialog[open]');
            bar.hidden = modalOpen || buttonInView;
            checkout.disabled = !button;
            checkout.querySelector('span').textContent = button ? 'Order Now' : 'Out of Stock';
            price.textContent = currentPrice();
        }
        function schedule() { if (!framePending) { framePending = true; window.requestAnimationFrame(update); } }
        checkout.addEventListener('click', function () {
            if(window.footstepDirectCheckout)window.footstepDirectCheckout();
        });
        document.querySelectorAll('[data-product-contact]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (button.dataset.productContact === 'whatsapp') {
                    if (!root.dataset.whatsapp) { window.alert('WhatsApp contact number is not configured.'); return; }
                    window.open('https://wa.me/' + root.dataset.whatsapp + '?text=' + encodeURIComponent(message()), '_blank', 'noopener,noreferrer');
                } else {
                    // Messenger supports Page referral context; it does not support a prefilled text message.
                    window.open(messenger, '_blank', 'noopener,noreferrer');
                }
            });
        });
        window.addEventListener('scroll', schedule, { passive: true });
        window.addEventListener('resize', schedule);
        form.addEventListener('change', schedule);
        if ('IntersectionObserver' in window) new IntersectionObserver(schedule).observe(actions);
        var observer = new MutationObserver(schedule);
        observer.observe(actions, { subtree: true, attributes: true, attributeFilter: ['class', 'disabled'] });
        observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
        if (chosen) observer.observe(chosen, { childList: true, subtree: true, characterData: true });
        if (window.jQuery) window.jQuery(document).on('shown.bs.modal hidden.bs.modal', schedule);
        update();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
}());
