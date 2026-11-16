// карусель на странице дома: стрелки, точки, свайп, клавиши

(function () {
    var karusel = document.getElementById('gallery');
    if (!karusel) return;

    var lenta = document.getElementById('lenta');
    var slaidy = karusel.querySelectorAll('.karusel__slaid');
    var tochki = karusel.querySelectorAll('.karusel__tochka');
    var podpis = document.getElementById('k-podpis');
    var nazad = document.getElementById('k-nazad');
    var vpered = document.getElementById('k-vpered');
    var podpisi = ['Фасад', 'Интерьер', 'Планировка'];

    var tekushij = 0;
    var kol = slaidy.length;

    function pokazat(n) {
        // по кругу: после последнего снова первый
        if (n < 0) n = kol - 1;
        if (n >= kol) n = 0;
        tekushij = n;

        lenta.style.transform = 'translateX(-' + (n * 100) + '%)';
        for (var i = 0; i < tochki.length; i++) {
            tochki[i].classList.toggle('aktiv', i === n);
        }
        if (podpis) podpis.textContent = podpisi[n] || '';
    }

    nazad.addEventListener('click', function () { pokazat(tekushij - 1); });
    vpered.addEventListener('click', function () { pokazat(tekushij + 1); });

    for (var i = 0; i < tochki.length; i++) {
        (function (k) {
            tochki[k].addEventListener('click', function () { pokazat(k); });
        })(i);
    }

    // стрелки на клавиатуре, когда карусель в фокусе
    karusel.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowLeft') { e.preventDefault(); pokazat(tekushij - 1); }
        if (e.key === 'ArrowRight') { e.preventDefault(); pokazat(tekushij + 1); }
    });

    // свайп пальцем
    var startX = null;
    var startY = null;
    lenta.addEventListener('touchstart', function (e) {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
    }, { passive: true });
    lenta.addEventListener('touchend', function (e) {
        if (startX === null) return;
        var dx = e.changedTouches[0].clientX - startX;
        var dy = e.changedTouches[0].clientY - startY;
        // если больше по вертикали - человек скроллит страницу, не трогаем
        if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy)) {
            pokazat(dx < 0 ? tekushij + 1 : tekushij - 1);
        }
        startX = null;
    }, { passive: true });

    // кнопка "Получить планировку" открывает третий слайд
    var knopki = document.querySelectorAll('[data-slide]');
    for (var j = 0; j < knopki.length; j++) {
        knopki[j].addEventListener('click', function (e) {
            e.preventDefault();
            pokazat(Number(this.getAttribute('data-slide')));
            karusel.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    }

    pokazat(0);
})();
