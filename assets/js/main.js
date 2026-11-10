// общий скрипт: шапка, меню, появление блоков, шаги на главной

(function () {
    window.sergeOk = true; // сигнал для страховки в header.php
    var shapka = document.getElementById('shapka');
    var gamburger = document.getElementById('gamburger');
    var menyu = document.getElementById('menyu');

    // шапка меняется когда прокрутили вниз
    function skroll() {
        if (!shapka) return;
        if (window.scrollY > 20) {
            shapka.classList.add('shapka--prokrutka');
        } else {
            shapka.classList.remove('shapka--prokrutka');
        }
    }
    window.addEventListener('scroll', skroll);
    skroll();

    // гамбургер
    function zakryt() {
        document.body.classList.remove('menyu-otkryto');
        if (gamburger) {
            gamburger.setAttribute('aria-expanded', 'false');
            gamburger.setAttribute('aria-label', 'Открыть меню');
        }
    }

    if (gamburger && menyu) {
        gamburger.addEventListener('click', function () {
            var otkryto = document.body.classList.toggle('menyu-otkryto');
            gamburger.setAttribute('aria-expanded', otkryto ? 'true' : 'false');
            gamburger.setAttribute('aria-label', otkryto ? 'Закрыть меню' : 'Открыть меню');
        });

        // по ссылке в меню - закрываем
        var ssylki = menyu.getElementsByTagName('a');
        for (var i = 0; i < ssylki.length; i++) {
            ssylki[i].addEventListener('click', zakryt);
        }
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') zakryt();
    });

    // если растянули окно до десктопа, меню надо закрыть, а то body останется заблокированным
    window.addEventListener('resize', function () {
        if (window.innerWidth > 1024) zakryt();
    });


    // стрелка в конце кнопок и ссылок оборачивается в span, чтобы при наведении она чуть сдвигалась (стиль .strelka)
    document.querySelectorAll('.knopka, .ssylka, .nazad').forEach(function (el) {
        var t = el.lastChild;
        if (!t || t.nodeType !== 3) return;
        var m = t.nodeValue.match(/^([\s\S]*?)\s*(→)\s*$/);
        if (!m) return;
        t.nodeValue = m[1] + '\u00a0';
        var s = document.createElement('span');
        s.className = 'strelka';
        s.setAttribute('aria-hidden', 'true');
        s.textContent = '→';
        el.appendChild(s);
    });

    // тема: светлая / тёмная. Выбор хранится в браузере; без выбора - как в системе
    var html = document.documentElement;
    var tema = document.getElementById('tema');
    function podpisTemy() {
        if (!tema) return;
        var temnaya = html.getAttribute('data-theme') === 'dark';
        var tekst = tema.querySelector('.tema__tekst');
        if (tekst) tekst.textContent = temnaya ? 'Светлая тема' : 'Тёмная тема';
        tema.setAttribute('aria-pressed', temnaya ? 'true' : 'false');
    }
    podpisTemy();
    if (tema) {
        tema.addEventListener('click', function () {
            var nov = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', nov);
            try { localStorage.setItem('serge-tema', nov); } catch (e) {}
            podpisTemy();
        });
    }
    if (window.matchMedia) {
        var sist = window.matchMedia('(prefers-color-scheme: dark)');
        var sledit = function (e) {
            var vybor = null;
            try { vybor = localStorage.getItem('serge-tema'); } catch (er) {}
            if (vybor === 'light' || vybor === 'dark') return;   // посетитель выбрал сам - систему не слушаем
            html.setAttribute('data-theme', e.matches ? 'dark' : 'light');
            podpisTemy();
        };
        if (sist.addEventListener) sist.addEventListener('change', sledit); else if (sist.addListener) sist.addListener(sledit);
    }

    // кнопка «Наверх»: появляется, когда пролистали больше полутора экранов
    var naverh = document.getElementById('naverh');
    if (naverh) {
        var pokazat = function () {
            naverh.classList.toggle('vidno', window.scrollY > window.innerHeight * 1.5);
        };
        window.addEventListener('scroll', pokazat, { passive: true });
        pokazat();
        naverh.addEventListener('click', function () {
            var plavno = !(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
            window.scrollTo({ top: 0, behavior: plavno ? 'smooth' : 'auto' });
            var logo = document.querySelector('.logo');
            if (logo) logo.focus({ preventScroll: true });   // фокус клавиатуры тоже возвращаем наверх
        });
    }

    // шаги на главной: активный один
    var shagi = document.querySelectorAll('.shag');
    function sdelatAktivnym(shag) {
        shagi.forEach(function (s) { s.classList.remove('aktiv'); });
        shag.classList.add('aktiv');
    }
    shagi.forEach(function (shag) {
        shag.addEventListener('click', function () { sdelatAktivnym(shag); });
        shag.addEventListener('mouseenter', function () { sdelatAktivnym(shag); });
        shag.addEventListener('focus', function () { sdelatAktivnym(shag); });
        shag.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                sdelatAktivnym(shag);
            }
        });
    });
})();
