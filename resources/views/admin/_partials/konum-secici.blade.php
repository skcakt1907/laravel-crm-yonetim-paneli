{{--
    Yeniden kullanılabilir KADEMELİ KONUM SEÇİCİ: Ülke → İl → İlçe → Mahalle
    Kullanım:
        @include('admin._partials.konum-secici', [
            'pfx'    => 'musteri',            // benzersiz önek (zorunlu)
            'isimler'=> ['ulke'=>'ulke','il'=>'il','ilce'=>'ilce','mahalle'=>'mahalle'], // form alan adları (ops.)
            'secili' => ['il'=>'Muğla','ilce'=>'Bodrum','mahalle'=>'Gümbet'],            // düzenleme için (ops.)
            'mahalleGoster' => true,          // mahalle dropdown'ı gösterilsin mi (ops, varsayılan true)
        ])
--}}
@php
    $pfx = $pfx ?? 'k';
    $isimler = array_merge(['ulke'=>'ulke','il'=>'il','ilce'=>'ilce','mahalle'=>'mahalle'], $isimler ?? []);
    $secili  = array_merge(['ulke'=>'Türkiye','il'=>'','ilce'=>'','mahalle'=>''], $secili ?? []);
    $mahalleGoster = $mahalleGoster ?? true;
@endphp

<div class="konum-secici" data-pfx="{{ $pfx }}"
     data-sel-il="{{ $secili['il'] }}" data-sel-ilce="{{ $secili['ilce'] }}" data-sel-mahalle="{{ $secili['mahalle'] }}"
     data-url-iller="{{ route('admin.geo.iller') }}"
     data-url-ilceler="{{ route('admin.geo.ilceler') }}"
     data-url-mahalleler="{{ route('admin.geo.mahalleler') }}"
     style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px">

    <select name="{{ $isimler['ulke'] }}" class="form-input ks-ulke">
        <option value="Türkiye" selected>🌍 Türkiye</option>
    </select>

    <select name="{{ $isimler['il'] }}" class="form-input ks-il">
        <option value="">🏙️ İl seçin…</option>
    </select>

    <select name="{{ $isimler['ilce'] }}" class="form-input ks-ilce" disabled>
        <option value="">📍 Önce il seçin</option>
    </select>

    @if($mahalleGoster)
    <select name="{{ $isimler['mahalle'] }}" class="form-input ks-mahalle" disabled>
        <option value="">🏘️ Önce ilçe seçin</option>
    </select>
    @endif
</div>

@once
@push('scripts')
<script>
(function () {
    function doldur(sel, list, placeholder, secili) {
        sel.innerHTML = '<option value="">' + placeholder + '</option>';
        list.forEach(function (i) {
            var o = document.createElement('option');
            o.value = i.ad; o.textContent = i.ad;
            if (i.id) o.setAttribute('data-id', i.id);
            if (secili && i.ad === secili) o.selected = true;
            sel.appendChild(o);
        });
    }
    function selId(sel) {
        var o = sel.options[sel.selectedIndex];
        return o ? o.getAttribute('data-id') : null;
    }
    function init(box) {
        var ilSel = box.querySelector('.ks-il'),
            ilceSel = box.querySelector('.ks-ilce'),
            mahSel = box.querySelector('.ks-mahalle');
        var u = {
            iller: box.dataset.urlIller,
            ilceler: box.dataset.urlIlceler,
            mahalleler: box.dataset.urlMahalleler
        };
        var sel = { il: box.dataset.selIl, ilce: box.dataset.selIlce, mahalle: box.dataset.selMahalle };

        // İlleri yükle
        fetch(u.iller, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (list) {
                doldur(ilSel, list, '🏙️ İl seçin…', sel.il);
                if (ilSel.value) ilSel.dispatchEvent(new Event('change'));
            });

        ilSel.addEventListener('change', function () {
            var id = selId(ilSel);
            ilceSel.innerHTML = '<option value="">📍 Tüm İlçeler</option>';
            ilceSel.disabled = !id;
            if (mahSel) { mahSel.innerHTML = '<option value="">🏘️ Önce ilçe seçin</option>'; mahSel.disabled = true; }
            if (!id) return;
            fetch(u.ilceler + '?il_id=' + id, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (list) {
                    doldur(ilceSel, list, '📍 İlçe seçin…', sel.ilce);
                    if (ilceSel.value) ilceSel.dispatchEvent(new Event('change'));
                });
        });

        if (mahSel) {
            ilceSel.addEventListener('change', function () {
                var id = selId(ilceSel);
                mahSel.innerHTML = '<option value="">🏘️ Tüm Mahalleler</option>';
                mahSel.disabled = !id;
                if (!id) return;
                fetch(u.mahalleler + '?ilce_id=' + id, { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (list) { doldur(mahSel, list, '🏘️ Mahalle seçin…', sel.mahalle); });
            });
        }
    }
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.konum-secici').forEach(init);
    });
})();
</script>
@endpush
@endonce
