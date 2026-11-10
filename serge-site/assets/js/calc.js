// калькулятор: проверка полей, запрос на api/calc.php, вывод результата, сохранение в localStorage
// в статической выгрузке (GitHub Pages, PHP нет) форма содержит data-rates и расчёт делается тут же, по тем же формулам, что в includes/functions.php

(function () {
    var forma = document.getElementById('calc-form');
    if (!forma) return;

    var KLUCH = 'serge_calc';
    var areaMin = Number(forma.getAttribute('data-area-min'));
    var areaMax = Number(forma.getAttribute('data-area-max'));
    var TEKST_KNOPKI = 'Рассчитать';
    var TEKST_GOTOVO = 'Рассчитано ✓';

    var polya = ['area', 'floors', 'material', 'foundation'];
    var rezultat = document.getElementById('rezultat');
    var pustoBlok = document.getElementById('rezultat-pusto');
    var knopka = document.getElementById('btn-raschet');
    var obshaya = document.getElementById('err-obshaya');
    var gotovo = false;
    var stavki = null;
    try { stavki = JSON.parse(forma.getAttribute('data-rates') || 'null'); } catch (e) { stavki = null; }

    // расчёт по ТЗ 8.3 (копия raschet() из includes/functions.php) - только для статической выгрузки
    function raschetLokalno(d) {
        var area = Number(String(d.area).replace(',', '.'));
        var floors = parseInt(d.floors, 10);
        var mat = stavki.materials[d.material];
        var fnd = stavki.foundations[d.foundation];
        var sZas = area / floors;
        var cSt = area * mat.price;
        var cF = sZas * fnd.price;
        var c0 = cSt + cF;
        var kEt = (floors === 1) ? 1 : stavki.k_floors_many;
        var c1 = c0 * kEt * stavki.k_reg;
        var step = stavki.round_step;
        var itog = Math.round(c1 / step) * step;
        var dl = stavki.delta / 100;
        function r2(x) { return Math.round(x * 100) / 100; }
        return {
            area: area, floors: floors, material: mat.name, material_price: mat.price,
            foundation: fnd.name, foundation_price: fnd.price, s_zas: r2(sZas),
            c_st: Math.round(cSt), c_f: Math.round(cF), c0: Math.round(c0), k_et: kEt, k_reg: stavki.k_reg,
            c1: Math.round(c1), total: itog,
            low: Math.round(r2(itog * (1 - dl)) / step) * step,
            high: Math.round(r2(itog * (1 + dl)) / step) * step,
            delta: stavki.delta, step: step, date: stavki.date
        };
    }

    // параметры из адреса (?area=120&floors=2...): в PHP-версии их подставляет сервер, в статической - этот код
    function izAdresa() {
        var q = window.location.search.replace(/^\?/, '');
        if (!q) return false;
        var nashli = false;
        q.split('&').forEach(function (par) {
            var kv = par.split('=');
            var k = decodeURIComponent(kv[0] || '');
            var v = decodeURIComponent((kv[1] || '').replace(/\+/g, ' '));
            if (polya.indexOf(k) !== -1 && v !== '') {
                var el = document.getElementById(k);
                if (el && el.value === '') { el.value = v; nashli = true; }
            }
        });
        var podskazka = document.getElementById('podskazka-karto4ka');
        if (nashli && podskazka) podskazka.hidden = false;
        return nashli;
    }

    // ---------- localStorage (в try, потому что в приватном режиме может падать) ----------
    function sohranit() {
        try {
            var d = {};
            polya.forEach(function (p) { d[p] = document.getElementById(p).value; });
            localStorage.setItem(KLUCH, JSON.stringify(d));
        } catch (e) { /* ну и ладно */ }
    }

    function zagruzit() {
        try {
            var s = localStorage.getItem(KLUCH);
            if (!s) return;
            var d = JSON.parse(s);
            polya.forEach(function (p) {
                var el = document.getElementById(p);
                // подставляем только если поле пустое (из карточки дома параметры важнее)
                if (el.value === '' && d[p]) el.value = d[p];
            });
        } catch (e) { /* ничего */ }
    }

    function ochistitSohranenie() {
        try { localStorage.removeItem(KLUCH); } catch (e) { }
    }

    // ---------- ошибки ----------
    function pokazatOshibku(pole, tekst) {
        var blok = document.getElementById('pole-' + pole);
        var err = document.getElementById('err-' + pole);
        blok.classList.add('est-oshibka');
        err.textContent = tekst;
        err.hidden = false;
        document.getElementById(pole).setAttribute('aria-invalid', 'true');
    }

    function ubratOshibki() {
        polya.forEach(function (p) {
            document.getElementById('pole-' + p).classList.remove('est-oshibka');
            var err = document.getElementById('err-' + p);
            err.textContent = '';
            err.hidden = true;
            document.getElementById(p).removeAttribute('aria-invalid');
        });
        obshaya.textContent = '';
        obshaya.hidden = true;
    }

    // проверка на клиенте, тексты как в ТЗ
    function proverit() {
        var oshibki = {};

        var area = document.getElementById('area').value.trim().replace(',', '.');
        var chislo = Number(area);
        if (!/^\d+(\.\d+)?$/.test(area) || chislo < areaMin || chislo > areaMax) {
            oshibki.area = 'Укажите площадь числом от ' + areaMin + ' до ' + areaMax + ' м²';
        }
        if (document.getElementById('floors').value === '') {
            oshibki.floors = 'Выберите этажность из списка';
        }
        if (document.getElementById('material').value === '') {
            oshibki.material = 'Выберите материал стен';
        }
        if (document.getElementById('foundation').value === '') {
            oshibki.foundation = 'Выберите тип фундамента';
        }
        return oshibki;
    }

    function pokazatOshibki(oshibki) {
        ubratOshibki();
        var pervoe = null;
        polya.forEach(function (p) {
            if (oshibki[p]) {
                pokazatOshibku(p, oshibki[p]);
                if (!pervoe) pervoe = p;
            }
        });
        if (pervoe) document.getElementById(pervoe).focus();
    }

    // ---------- вывод результата ----------
    function rub(n) {
        return Number(n).toLocaleString('ru-RU').replace(/\s/g, ' ') + ' ₽';
    }

    // число по-русски: 1.15 -> 1,15
    function ruChislo(n) {
        return String(n).replace('.', ',');
    }

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = String(s);
        return d.innerHTML;
    }

    function pokazatRezultat(r) {
        var html = '';
        html += '<div class="rez-metka">Предварительная стоимость</div>';
        html += '<div class="rez-summa">≈ ' + rub(r.total) + '</div>';
        html += '<div class="rez-diapazon">Диапазон: ' + rub(r.low) + ' — ' + rub(r.high) + ' (±' + r.delta + '%)</div>';
        html += '<table class="rez-tablica">';
        html += '<tr><td>Стены (' + esc(r.material) + '): ' + ruChislo(r.area) + ' м² × ' + rub(r.material_price) + '</td><td>' + rub(r.c_st) + '</td></tr>';
        html += '<tr><td>Фундамент (' + esc(r.foundation).toLowerCase() + '): ' + ruChislo(r.s_zas) + ' м² × ' + rub(r.foundation_price) + '</td><td>' + rub(r.c_f) + '</td></tr>';
        html += '<tr><td>Базовая стоимость</td><td>' + rub(r.c0) + '</td></tr>';
        html += '<tr><td>Коэффициент этажности</td><td>×' + ruChislo(r.k_et) + '</td></tr>';
        html += '<tr><td>Региональный коэффициент</td><td>×' + ruChislo(r.k_reg) + '</td></tr>';
        html += '<tr class="rez-itog"><td>Итого (округлено до ' + rub(r.step) + ')</td><td>' + rub(r.total) + '</td></tr>';
        html += '</table>';
        html += '<div class="rez-data">Расценки актуальны на ' + esc(r.date) + ' · Демо-данные</div>';
        html += '<p class="rez-ogovorka">Расчёт предварительный и не является сметой или публичной офертой. Точная стоимость определяется после проектирования и осмотра участка.</p>';
        // если на сайте включены заявки (PHP + база), предлагаем отправить расчёт менеджеру
        var zayavka = forma.getAttribute('data-zayavka');
        if (zayavka) {
            var q = polya.map(function (p) { return p + '=' + encodeURIComponent(document.getElementById(p).value); }).join('&');
            html += '<a class="knopka knopka--derevo rez-ssylka" href="' + zayavka + '?' + q + '">Получить точный расчёт</a> ';
        }
        html += '<a class="knopka knopka--kontur rez-ssylka" href="catalog.php">Сравнить с другими проектами</a>';

        rezultat.innerHTML = html;
        rezultat.hidden = false;
        pustoBlok.hidden = true;
        rezultat.focus();
        // на телефоне результат под формой, надо к нему прокрутить
        if (window.innerWidth <= 1024) {
            rezultat.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // ---------- отправка ----------
    forma.addEventListener('submit', function (e) {
        e.preventDefault();

        var oshibki = proverit();
        if (Object.keys(oshibki).length > 0) {
            pokazatOshibki(oshibki);
            rezultat.hidden = true;
            pustoBlok.hidden = false;
            return;
        }
        ubratOshibki();
        sohranit();

        var dannye = new FormData(forma);
        var staryjTekst = TEKST_KNOPKI;
        knopka.disabled = true;
        gotovo = false;
        knopka.textContent = 'Считаем…';

        var zapros;
        if (stavki) {
            var ob = {};
            polya.forEach(function (p) { ob[p] = document.getElementById(p).value; });
            zapros = Promise.resolve({ ok: true, result: raschetLokalno(ob) });
        } else {
            zapros = fetch(forma.getAttribute('action'), { method: 'POST', body: dannye })
                .then(function (otvet) { return otvet.json(); });
        }
        zapros
            .then(function (j) {
                if (j.ok) {
                    pokazatRezultat(j.result);
                    gotovo = true;
                } else {
                    pokazatOshibki(j.errors || {});
                    rezultat.hidden = true;
                    pustoBlok.hidden = false;
                }
            })
            .catch(function () {
                obshaya.textContent = 'Не получилось выполнить расчёт. Проверьте соединение и попробуйте ещё раз.';
                obshaya.hidden = false;
            })
            .then(function () {
                knopka.disabled = false;
                knopka.textContent = gotovo ? TEKST_GOTOVO : staryjTekst;
            });
    });

    // ошибка у поля пропадает когда начали править
    polya.forEach(function (p) {
        var el = document.getElementById(p);
        var sobytie = (el.tagName === 'SELECT') ? 'change' : 'input';
        el.addEventListener(sobytie, function () {
            document.getElementById('pole-' + p).classList.remove('est-oshibka');
            var err = document.getElementById('err-' + p);
            err.hidden = true;
            el.removeAttribute('aria-invalid');
            knopka.textContent = TEKST_KNOPKI;
            sohranit();
        });
    });

    // сброс
    document.getElementById('btn-sbros').addEventListener('click', function () {
        forma.reset();
        // reset возвращает значения, которые были в html (из карточки), поэтому чистим руками
        polya.forEach(function (p) { document.getElementById(p).value = ''; });
        ubratOshibki();
        ochistitSohranenie();
        knopka.textContent = TEKST_KNOPKI;
        rezultat.innerHTML = '';
        rezultat.hidden = true;
        pustoBlok.hidden = false;
        document.getElementById('area').focus();
    });

    izAdresa();
    zagruzit();
})();
