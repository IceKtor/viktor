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

    // плавное появление блоков
    var bloki = document.querySelectorAll('.poyavlenie');
    if ('IntersectionObserver' in window) {
        var sled = new IntersectionObserver(function (zapisi) {
            zapisi.forEach(function (z) {
                if (z.isIntersecting) {
                    z.target.classList.add('vid');
                    sled.unobserve(z.target);
                }
            });
        }, { threshold: 0.12 });
        bloki.forEach(function (b) { sled.observe(b); });
    } else {
        // старый браузер - просто показываем всё
        bloki.forEach(function (b) { b.classList.add('vid'); });
    }

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
