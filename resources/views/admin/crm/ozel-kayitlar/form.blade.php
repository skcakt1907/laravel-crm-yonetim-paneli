@extends('admin._layout')

@section('title', $kayit ? 'Kayıt Düzenle' : 'Yeni Kayıt')

@section('content')
@php $duzenle = (bool) $kayit; @endphp

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.ozel-kayitlar.index') }}">Şifre Kasası</a>
    <span class="sep">/</span>
    <span class="current">{{ $duzenle ? 'Düzenle' : 'Yeni' }}</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $duzenle ? 'Kayıt Düzenle' : 'Yeni Kasa Kaydı' }}</h1>
        <div class="page-subtitle">Müşteri hesap &amp; şifre bilgileri — şifreler veritabanında şifreli saklanır</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.crm.ozel-kayitlar.index') }}" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i> <span>Listeye Dön</span>
        </a>
    </div>
</div>

<form action="{{ $duzenle ? route('admin.crm.ozel-kayitlar.update', $kayit->id) : route('admin.crm.ozel-kayitlar.store') }}" method="POST">
    @csrf
    @if($duzenle) @method('PUT') @endif

    <div class="section">
        <div class="form-grid">

            <div class="form-group" style="grid-column:1/-1">
                <label class="form-label">CRM Müşterisi (opsiyonel — bizde kayıtlıysa)</label>
                @php
                    $secId = (string) old('crm_musteri_id', $kayit->crm_musteri_id ?? ($onSeciliMusteri ?? ''));
                    $secAd = '';
                    if ($secId !== '') {
                        foreach ($musteriler as $mm) {
                            if ((string) $mm->id === $secId) { $secAd = $mm->adi; break; }
                        }
                    }
                @endphp
                <div class="musteri-secici" style="position:relative">
                    <input type="hidden" name="crm_musteri_id" id="musteriIdInput" value="{{ $secId }}">
                    <input type="text" id="musteriAra" class="form-input" autocomplete="off"
                           placeholder="Müşteri ara (ad / firma) — boş bırakırsan elle gir"
                           value="{{ $secAd }}" oninput="musteriFiltre()" onfocus="musteriFiltre()">
                    <button type="button" id="musteriTemizle" onclick="musteriSecimTemizle()" title="Seçimi temizle"
                            style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);{{ $secId==='' ? 'display:none' : '' }}">✕</button>
                    <div id="musteriListe" class="musteri-liste" style="display:none;position:absolute;z-index:30;left:0;right:0;top:100%;margin-top:4px;max-height:240px;overflow:auto;background:var(--surface);color:var(--text);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-md,0 8px 24px rgba(0,0,0,.12))"></div>
                </div>
                <div class="form-help">Müşteri seçince başlık otomatik dolar; istersen aşağıdan değiştir. Müşterimiz değilse boş bırak, başlığı elle yaz.</div>
            </div>

            <div class="form-group">
                <label class="form-label">Başlık / Müşteri Adı <span class="required">*</span></label>
                <input type="text" name="baslik" id="baslikInput" class="form-input" required value="{{ old('baslik', $kayit->baslik ?? '') }}" placeholder="Örn: Ahmet Yılmaz / Kafe Libre">
            </div>

            <div class="form-group">
                <label class="form-label">Gmail</label>
                <input type="text" name="email" class="form-input" value="{{ old('email', $kayit->email ?? '') }}" placeholder="ornek@gmail.com">
            </div>

        </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:8px">
        <a href="{{ route('admin.crm.ozel-kayitlar.index') }}" class="btn btn-secondary"><i data-lucide="x"></i> <span>Vazgeç</span></a>
        <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> <span>{{ $duzenle ? 'Güncelle' : 'Kaydet ve Hesap Ekle' }}</span></button>
    </div>
</form>

@push('scripts')
<script>
// PASİF ve POTANSİYEL müşteriler de listede — web sitesi aktif olmasa da
// giriş bilgileri kasaya girilebilsin diye filtrelenmiyor, sadece etiketleniyor.
window.__musteriler = [
@foreach($musteriler as $m)
    { id: "{{ $m->id }}", ad: @json($m->adi), durum: @json($m->durum ?? 'aktif') },
@endforeach
];

function musteriFiltre() {
    var q = (document.getElementById('musteriAra').value || '').toLocaleLowerCase('tr');
    var liste = document.getElementById('musteriListe');
    var arr = window.__musteriler, html = '', sayac = 0;
    for (var i = 0; i < arr.length; i++) {
        var ad = (arr[i].ad || '').toLocaleLowerCase('tr');
        if (q === '' || ad.indexOf(q) !== -1) {
            // Pasif / potansiyel müşteriye rozet — seçilebilir olduğu belli olsun
            var rozet = '';
            if (arr[i].durum === 'pasif') {
                rozet = ' <span class="badge badge-danger" style="font-size:10.5px">pasif</span>';
            } else if (arr[i].durum === 'potansiyel') {
                rozet = ' <span class="badge badge-warning" style="font-size:10.5px">potansiyel</span>';
            }
            html += '<div data-id="' + arr[i].id + '" data-ad="' + (arr[i].ad || '').replace(/"/g, '&quot;') + '" ' +
                    'style="padding:9px 12px;cursor:pointer;font-size:13px;border-bottom:1px solid var(--border)" ' +
                    'onmousedown="musteriSec(this)">' + (arr[i].ad || '') + rozet + '</div>';
            sayac++;
            if (sayac >= 50) { html += '<div style="padding:8px 12px;font-size:12px;color:var(--text-muted)">… daralt</div>'; break; }
        }
    }
    if (html === '') html = '<div style="padding:9px 12px;font-size:13px;color:var(--text-muted)">Eşleşme yok — başlığı elle yazabilirsin</div>';
    liste.innerHTML = html;
    liste.style.display = '';
}
function musteriSec(el) {
    var id = el.getAttribute('data-id'), ad = el.getAttribute('data-ad');
    document.getElementById('musteriIdInput').value = id;
    document.getElementById('musteriAra').value = ad;
    document.getElementById('musteriListe').style.display = 'none';
    document.getElementById('musteriTemizle').style.display = '';
    var b = document.getElementById('baslikInput');
    if (b && b.value.trim() === '') b.value = ad;
}
function musteriSecimTemizle() {
    document.getElementById('musteriIdInput').value = '';
    document.getElementById('musteriAra').value = '';
    document.getElementById('musteriTemizle').style.display = 'none';
    document.getElementById('musteriListe').style.display = 'none';
}
document.addEventListener('click', function (e) {
    var sec = document.querySelector('.musteri-secici');
    if (sec && !sec.contains(e.target)) document.getElementById('musteriListe').style.display = 'none';
});
if (window.lucide) lucide.createIcons();
</script>
@endpush
@endsection