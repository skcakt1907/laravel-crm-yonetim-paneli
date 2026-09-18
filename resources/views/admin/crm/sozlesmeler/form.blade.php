@extends('admin._layout')

@section('title', $sozlesme ? 'Sözleşme Düzenle' : 'Yeni Sözleşme')

@section('content')
@php $duzenle = (bool) $sozlesme; @endphp

<div class="page-head" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:18px">
    <h1 style="font-size:22px;font-weight:800;margin:0">{{ $duzenle ? '✏️ Sözleşme Düzenle' : '➕ Yeni Sözleşme' }}</h1>
    <a href="{{ route('admin.crm.sozlesmeler.index') }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i> <span>Listeye Dön</span></a>
</div>

<form action="{{ $duzenle ? route('admin.crm.sozlesmeler.update', $sozlesme->id) : route('admin.crm.sozlesmeler.store') }}"
      method="POST" onsubmit="if(window.tinymce)tinymce.triggerSave()">
    @csrf
    @if($duzenle) @method('PUT') @endif

    <div class="section" style="padding:20px;margin-bottom:18px">
        <div class="form-grid form-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div class="form-group" style="grid-column:1/-1">
                <label class="form-label">Başlık <span class="required">*</span></label>
                <input type="text" name="baslik" class="form-input" required value="{{ old('baslik', $sozlesme->baslik ?? '') }}" placeholder="Örn: Kurumsal Web Sitesi Tasarım Sözleşmesi">
            </div>

            <div class="form-group">
                <label class="form-label">Kategori</label>
                <select name="kategori_id" class="form-select">
                    <option value="">— Kategori seç —</option>
                    @foreach($kategoriler as $kat)
                        <option value="{{ $kat->id }}" {{ (string)old('kategori_id', $sozlesme->kategori_id ?? '') === (string)$kat->id ? 'selected' : '' }}>{{ $kat->ad }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Müşteri (CRM)</label>
                @php
                    $secMusteriId = (string) old('musteri_id', $sozlesme->musteri_id ?? ($onSeciliMusteri ?? ''));
                    $secMusteriAd = '';
                    if ($secMusteriId !== '') {
                        foreach ($musteriler as $mm) {
                            if ((string) $mm->id === $secMusteriId) { $secMusteriAd = $mm->adi; break; }
                        }
                    }
                @endphp
                <div class="musteri-secici" style="position:relative">
                    <input type="hidden" name="musteri_id" id="musteriIdInput" value="{{ $secMusteriId }}">
                    <input type="text" id="musteriAra" class="form-input" autocomplete="off"
                           placeholder="Müşteri ara (ad / firma) — opsiyonel"
                           value="{{ $secMusteriAd }}"
                           oninput="musteriFiltre()" onfocus="musteriFiltre()">
                    <button type="button" id="musteriTemizle" onclick="musteriSecimTemizle()"
                            title="Seçimi temizle"
                            style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);{{ $secMusteriId==='' ? 'display:none' : '' }}">✕</button>
                    <div id="musteriListe" class="musteri-liste" style="display:none;position:absolute;z-index:30;left:0;right:0;top:100%;margin-top:4px;max-height:240px;overflow:auto;background:var(--card,#fff);border:1px solid var(--border,#e5e7eb);border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12)"></div>
                </div>
                <div class="form-help">Müşteri seçmek zorunda değilsin; seçmezsen "Karşı Taraf" alanına elle yaz.</div>
            </div>

            <div class="form-group">
                <label class="form-label">Karşı Taraf / Ünvan</label>
                <input type="text" name="taraf_adi" class="form-input" value="{{ old('taraf_adi', $sozlesme->taraf_adi ?? '') }}" placeholder="Müşteri seçmediysen elle yaz">
            </div>

            <div class="form-group">
                <label class="form-label">Tutar (₺)</label>
                <input type="number" step="0.01" min="0" name="tutar" class="form-input" value="{{ old('tutar', $sozlesme->tutar ?? '') }}" placeholder="Opsiyonel">
            </div>

            <div class="form-group">
                <label class="form-label">Durum <span class="required">*</span></label>
                @php $d = old('durum', $sozlesme->durum ?? 'taslak'); @endphp
                <select name="durum" class="form-select" required>
                    <option value="taslak"    {{ $d=='taslak' ? 'selected':'' }}>Taslak</option>
                    <option value="aktif"     {{ $d=='aktif' ? 'selected':'' }}>Aktif</option>
                    <option value="imzalandi" {{ $d=='imzalandi' ? 'selected':'' }}>İmzalandı</option>
                    <option value="iptal"     {{ $d=='iptal' ? 'selected':'' }}>İptal</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Sözleşme Tarihi</label>
                <input type="date" name="tarih" class="form-input" value="{{ old('tarih', isset($sozlesme->tarih) && $sozlesme->tarih ? \Carbon\Carbon::parse($sozlesme->tarih)->format('Y-m-d') : date('Y-m-d')) }}">
            </div>

            {{-- SÜRE (04.08.2026)
                 Kolonlar ve doğrulama zaten vardı, formda giriş alanı yoktu —
                 bu yüzden bitiş tarihi hiç dolmuyor, hatırlatma da çalışmıyordu. --}}
            <div class="form-group">
                <label class="form-label">Başlangıç Tarihi</label>
                <input type="date" name="baslangic_tarihi" class="form-input"
                       value="{{ old('baslangic_tarihi', isset($sozlesme->baslangic_tarihi) && $sozlesme->baslangic_tarihi ? \Carbon\Carbon::parse($sozlesme->baslangic_tarihi)->format('Y-m-d') : '') }}">
                <div class="form-help">Sözleşmenin yürürlüğe girdiği tarih</div>
            </div>

            <div class="form-group">
                <label class="form-label">Bitiş Tarihi</label>
                <input type="date" name="bitis_tarihi" class="form-input"
                       value="{{ old('bitis_tarihi', isset($sozlesme->bitis_tarihi) && $sozlesme->bitis_tarihi ? \Carbon\Carbon::parse($sozlesme->bitis_tarihi)->format('Y-m-d') : '') }}">
                <div class="form-help">
                    Bitişe <strong>1 hafta kala</strong> otomatik hatırlatma e-postası gönderilir.
                    Boş bırakırsan hatırlatma yapılmaz.
                </div>
            </div>
        </div>
    </div>

    <div class="section" style="padding:20px;margin-bottom:18px">
        <label class="form-label">Sözleşme Metni</label>
        <textarea name="icerik" class="rich-full">{{ old('icerik', $sozlesme->icerik ?? '') }}</textarea>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:8px">
        <a href="{{ route('admin.crm.sozlesmeler.index') }}" class="btn btn-secondary">Vazgeç</a>
        <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> <span>{{ $duzenle ? 'Güncelle' : 'Kaydet' }}</span></button>
    </div>
</form>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function () {
    if (!window.tinymce) return;
    var isDark = document.body.classList.contains('theme-dark');
    tinymce.init({
        selector: 'textarea.rich-full',
        license_key: 'gpl',
        promotion: false,
        branding: false,
        height: 520,
        menubar: false,
        plugins: 'lists link image table code fullscreen autolink pagebreak',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | link table pagebreak | code fullscreen',
        fontsize_formats: '10px 11px 12px 14px 16px 18px 20px 24px 28px 32px',
        content_style: 'body{font-family:Arial,sans-serif;font-size:14px;line-height:1.6;padding:18px} table{border-collapse:collapse} td,th{border:1px solid #ccc;padding:6px}',
        skin: isDark ? 'oxide-dark' : 'oxide',
        content_css: isDark ? 'dark' : 'default',
        language: 'tr',
        language_url: 'https://cdn.jsdelivr.net/npm/tinymce-i18n@latest/langs7/tr.js'
    });
})();
if (window.lucide) lucide.createIcons();
</script>
<script>
// Aranabilir müşteri seçici
window.__musteriler = [
@foreach($musteriler as $m)
    { id: "{{ $m->id }}", ad: @json($m->adi) },
@endforeach
];
function musteriFiltre() {
    var q = (document.getElementById('musteriAra').value || '').toLocaleLowerCase('tr');
    var liste = document.getElementById('musteriListe');
    var arr = window.__musteriler;
    var html = '';
    var sayac = 0;
    for (var i = 0; i < arr.length; i++) {
        var ad = (arr[i].ad || '').toLocaleLowerCase('tr');
        if (q === '' || ad.indexOf(q) !== -1) {
            html += '<div class="musteri-sec-item" data-id="' + arr[i].id + '" data-ad="' +
                    (arr[i].ad || '').replace(/"/g, '&quot;') + '" ' +
                    'style="padding:9px 12px;cursor:pointer;font-size:13px;border-bottom:1px solid var(--border,#f0f0f0)" ' +
                    'onmousedown="musteriSec(this)">' + (arr[i].ad || '') + '</div>';
            sayac++;
            if (sayac >= 50) { html += '<div style="padding:8px 12px;font-size:12px;color:var(--text-muted)">… daralt</div>'; break; }
        }
    }
    if (html === '') html = '<div style="padding:9px 12px;font-size:13px;color:var(--text-muted)">Eşleşme yok</div>';
    liste.innerHTML = html;
    liste.style.display = '';
}
function musteriSec(el) {
    document.getElementById('musteriIdInput').value = el.getAttribute('data-id');
    document.getElementById('musteriAra').value = el.getAttribute('data-ad');
    document.getElementById('musteriListe').style.display = 'none';
    document.getElementById('musteriTemizle').style.display = '';
}
function musteriSecimTemizle() {
    document.getElementById('musteriIdInput').value = '';
    document.getElementById('musteriAra').value = '';
    document.getElementById('musteriTemizle').style.display = 'none';
    document.getElementById('musteriListe').style.display = 'none';
}
document.addEventListener('click', function (e) {
    var sec = document.querySelector('.musteri-secici');
    if (sec && !sec.contains(e.target)) {
        document.getElementById('musteriListe').style.display = 'none';
    }
});
</script>
@endpush