// Модальные окна сайта: сообщения и подтверждения действий.
//  - элемент с атрибутом data-modal="ok|err|info" показывается в окне (без JS остаётся обычным блоком на странице);
//  - форма с атрибутом data-confirm="текст" перед отправкой спрашивает подтверждение в окне;
//  - window.sergeModal(текст, тип) открывает окно из любого скрипта.
(function () {
    var box, body, title, btnOk, btnNo, lastFocus, onOk = null;
    var TITLES = { ok: 'Готово', err: 'Ошибка', info: 'Сообщение', ask: 'Подтвердите действие' };

    function build() {
        if (box) return;
        box = document.createElement('div');
        box.className = 'modal';
        box.hidden = true;
        box.innerHTML = '<div class="modal__fon" data-close="1"></div>' +
            '<div class="modal__okno" role="dialog" aria-modal="true" aria-labelledby="modal-zag" tabindex="-1">' +
            '<h2 class="modal__zag" id="modal-zag"></h2><div class="modal__tekst"></div>' +
            '<div class="modal__knopki"><button type="button" class="knopka knopka--ramka modal__net">Отмена</button>' +
            '<button type="button" class="knopka knopka--cher modal__da">Понятно</button></div></div>';
        document.body.appendChild(box);
        title = box.querySelector('.modal__zag');
        body = box.querySelector('.modal__tekst');
        btnOk = box.querySelector('.modal__da');
        btnNo = box.querySelector('.modal__net');
        box.addEventListener('click', function (e) { if (e.target.getAttribute('data-close')) close(); });
        btnNo.addEventListener('click', close);
        btnOk.addEventListener('click', function () { var f = onOk; close(); if (f) f(); });
        document.addEventListener('keydown', function (e) {
            if (box.hidden) return;
            if (e.key === 'Escape') close();
            if (e.key === 'Tab') { // фокус остаётся внутри окна
                var els = box.querySelectorAll('button:not([hidden]), a[href]');
                if (!els.length) return;
                var first = els[0], last = els[els.length - 1];
                if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
            }
        });
    }

    function close() {
        if (!box || box.hidden) return;
        box.hidden = true;
        onOk = null;
        document.body.classList.remove('modal-otkryto');
        if (lastFocus && lastFocus.focus) lastFocus.focus();
    }

    // html - уже безопасная разметка (берётся из страницы) или обычный текст; ask: окно с двумя кнопками
    function open(html, type, ask, yes) {
        build();
        lastFocus = document.activeElement;
        box.setAttribute('data-type', ask ? 'ask' : (type || 'info'));
        title.textContent = TITLES[ask ? 'ask' : (type || 'info')] || TITLES.info;
        body.innerHTML = html;
        btnNo.hidden = !ask;
        btnOk.textContent = ask ? 'Подтвердить' : 'Понятно';
        onOk = ask ? yes : null;
        box.hidden = false;
        document.body.classList.add('modal-otkryto');
        btnOk.focus();
    }

    function esc(t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
    window.sergeModal = function (text, type) { open(esc(text), type, false); };

    function init() {
        // сообщения со страницы: все собираются в одно окно, тип берётся по самому серьёзному
        var items = document.querySelectorAll('[data-modal]');
        if (items.length) {
            var html = [], type = 'info', rank = { info: 0, ok: 1, err: 2 };
            for (var i = 0; i < items.length; i++) {
                var t = items[i].getAttribute('data-modal') || 'info';
                if ((rank[t] || 0) >= rank[type]) type = t;
                html.push('<p>' + items[i].innerHTML + '</p>');
                items[i].hidden = true;
            }
            open(html.join(''), type, false);
        }
        // подтверждения действий
        var forms = document.querySelectorAll('form[data-confirm]');
        for (var j = 0; j < forms.length; j++) {
            (function (f) {
                f.addEventListener('submit', function (e) {
                    if (f.getAttribute('data-ok')) return;
                    e.preventDefault();
                    open(esc(f.getAttribute('data-confirm')), 'info', true, function () { f.setAttribute('data-ok', '1'); f.submit(); });
                });
            })(forms[j]);
        }
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
