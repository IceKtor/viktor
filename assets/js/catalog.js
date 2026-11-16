// фильтры и сортировка в каталоге. всё на data-атрибутах карточек

(function () {
    var spisok = document.getElementById('spisok');
    if (!spisok) return;

    var fSort = document.getElementById('f-sort');
    var fFloors = document.getElementById('f-floors');
    var fMaterial = document.getElementById('f-material');
    var fFoundation = document.getElementById('f-foundation');
    var fBedrooms = document.getElementById('f-bedrooms');
    var fPrice = document.getElementById('f-price');
    var fGarage = document.getElementById('f-garage');
    var fTerrace = document.getElementById('f-terrace');
    var naideno = document.getElementById('naideno');
    var pusto = document.getElementById('pusto');

    var karto4ki = Array.prototype.slice.call(spisok.querySelectorAll('.dom'));
    // запоминаем исходный порядок, чтоб "по умолчанию" возвращало как было
    karto4ki.forEach(function (k, i) { k.setAttribute('data-poryadok', i); });

    function primenit() {
        var floors = fFloors.value;
        var material = fMaterial.value;
        var foundation = fFoundation.value;
        var sort = fSort.value;
        var bedrooms = fBedrooms.value;
        var priceTo = fPrice.value === '' ? 0 : Number(fPrice.value);

        var vidno = 0;
        karto4ki.forEach(function (k) {
            var ok = true;
            if (floors !== '' && k.getAttribute('data-floors') !== floors) ok = false;
            if (material !== '' && k.getAttribute('data-material') !== material) ok = false;
            if (foundation !== '' && k.getAttribute('data-foundation') !== foundation) ok = false;
            if (bedrooms !== '' && k.getAttribute('data-bedrooms') !== bedrooms) ok = false;
            if (priceTo && Number(k.getAttribute('data-price')) > priceTo) ok = false;
            if (fGarage.checked && k.getAttribute('data-garage') !== '1') ok = false;
            if (fTerrace.checked && k.getAttribute('data-terrace') !== '1') ok = false;

            if (ok) {
                k.classList.remove('skryt');
                vidno++;
            } else {
                k.classList.add('skryt');
            }
        });

        // сортировка
        var kopiya = karto4ki.slice();
        kopiya.sort(function (a, b) {
            var ra = Number(a.getAttribute('data-poryadok'));
            var rb = Number(b.getAttribute('data-poryadok'));
            var pa = Number(a.getAttribute('data-price'));
            var pb = Number(b.getAttribute('data-price'));
            var sa = Number(a.getAttribute('data-area'));
            var sb = Number(b.getAttribute('data-area'));

            if (sort === 'area-up') return sa - sb || ra - rb;
            if (sort === 'area-down') return sb - sa || ra - rb;
            if (sort === 'price-up') return pa - pb || ra - rb;
            if (sort === 'price-down') return pb - pa || ra - rb;
            return ra - rb;
        });
        kopiya.forEach(function (k) { spisok.appendChild(k); });

        naideno.textContent = vidno;
        pusto.hidden = vidno !== 0;
        spisok.hidden = vidno === 0;
    }

    function sbros() {
        fSort.value = '';
        fFloors.value = '';
        fMaterial.value = '';
        fFoundation.value = '';
        fBedrooms.value = '';
        fPrice.value = '';
        fGarage.checked = false;
        fTerrace.checked = false;
        primenit();
    }

    [fSort, fFloors, fMaterial, fFoundation, fBedrooms, fPrice, fGarage, fTerrace].forEach(function (f) {
        f.addEventListener('change', primenit);
    });
    document.getElementById('f-sbros').addEventListener('click', sbros);
    document.getElementById('pusto-sbros').addEventListener('click', sbros);
})();
