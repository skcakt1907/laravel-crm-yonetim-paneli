@extends('admin._layout')

@section('title', 'Toplu Tutar Girişi')

@section('content')
@php $f = fn ($x) => number_format((float) $x, 2, ',', '.'); @endphp

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.domains.index') }}">Domain / Hosting</a>
    <span class="sep">/</span>
    <span class="current">Toplu Tutar Girişi</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">Toplu Tutar Girişi</h1>
        <div class="page-subtitle">
            Satış tutarı boş kalan domain kayıtlarını tek ekranda doldur — kâr raporu tahminden çıkıp gerçeğe dönsün
        </div>
    </div>
    <div class="page-actions">
        @if(Route::has('admin.crm.domains.kar-raporu'))
            <a href="{{ route('admin.crm.domains.kar-raporu') }}" class="btn btn-secondary">
                <i data-lucide="trending-up"></i> <span>Kâr Raporu</span>
            </a>
        @endif
        <a href="{{ route('admin.crm.domains.index') }}" class="btn btn-ghost">
            <i data-lucide="arrow-left"></i> <span>Domain Listesi</span>
        </a>
    </div>
</div>

{{-- ═══ Özet ═══ --}}
<div class="mini-stat-grid">
    <div class="mini-stat">
        <div class="lbl">Tutarı Girilmiş</div>
        <div class="val" style="color:var(--success)">{{ $dolduruldu }}</div>
        <div class="sub">kayıt hazır</div>
    </div>
    <div class="mini-stat warning">
        <div class="lbl">Bu Listede</div>
        <div class="val" style="color:var(--warning)">{{ $kayitlar->count() }}</div>
        <div class="sub">tutarı boş kayıt</div>
    </div>
    <div class="mini-stat info">
        <div class="lbl">Önerilerin Toplamı</div>
        <div class="val">{{ $f($oneriToplam) }} ₺</div>
        <div class="sub">liste fiyatıyla doldurulursa</div>
    </div>
    <div class="mini-stat">
        <div class="lbl">Şu An Girilen</div>
        <div class="val" id="sayacDeger">0</div>
        <div class="sub" id="sayacTutar">0,00 ₺</div>
    </div>
</div>

{{-- ═══ Filtre ═══ --}}
<form method="GET" class="section" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;margin-top:16px;margin-bottom:14px">
    <div class="form-group" style="flex:2;min-width:220px;margin-bottom:0">
        <label class="form-label" style="font-size:11px">Ara</label>
        <input type="text" name="ara" value="{{ $ara }}" class="form-input" placeholder="domain veya müşteri adı...">
    </div>
    <div class="form-group" style="flex:1;min-width:170px;margin-bottom:0">
        <label class="form-label" style="font-size:11px">Uzantı</label>
        <select name="uzanti" class="form-select">
            <option value="">Tümü</option>
            @foreach($dagilim as $u => $adet)
                @if($u !== 'bilinmiyor')
                    <option value="{{ $u }}" {{ $uzanti === $u ? 'selected' : '' }}>.{{ $u }} ({{ $adet }})</option>
                @endif
            @endforeach
        </select>
    </div>
    <div class="form-group" style="flex:1;min-width:150px;margin-bottom:0">
        <label class="form-label" style="font-size:11px">Sıralama</label>
        <select name="sira" class="form-select">
            <option value="tarih"  {{ $sira === 'tarih'  ? 'selected' : '' }}>Tarihe göre (yeni önce)</option>
            <option value="domain" {{ $sira === 'domain' ? 'selected' : '' }}>Domain adına göre</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary"><i data-lucide="filter"></i> <span>Filtrele</span></button>
    @if($ara || $uzanti || $sira !== 'tarih')
        <a href="{{ route('admin.crm.domains.toplu-tutar') }}" class="btn btn-ghost btn-sm">
            <i data-lucide="x"></i> <span>Temizle</span>
        </a>
    @endif
</form>

@if($kayitlar->isEmpty())
    <div class="table-wrap">
        <div class="empty-state" style="padding:48px 0">
            <i data-lucide="check-circle-2" class="empty-state-icon"></i>
            <h4>Tutarı boş kayıt yok</h4>
            <p>Bu filtreye uyan, tutarı girilmemiş domain kaydı bulunmuyor.</p>
        </div>
    </div>
@else

<form method="POST" action="{{ route('admin.crm.domains.toplu-tutar.kaydet') }}" id="tutarForm">
    @csrf

    {{-- ═══ Toplu doldurma yardımcıları ═══ --}}
    <div class="section" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;margin-bottom:14px">
        <button type="button" class="btn btn-secondary btn-sm" id="btnOneri">
            <i data-lucide="wand-2"></i> <span>Boş olanlara önerilen fiyatı yaz</span>
        </button>
        <div class="form-group" style="min-width:170px;margin-bottom:0">
            <label class="form-label" style="font-size:11px">Hepsine aynı tutarı yaz</label>
            <input type="number" step="0.01" min="0" class="form-input" id="hepsiDeger" placeholder="örn. 1500">
        </div>
        <button type="button" class="btn btn-secondary btn-sm" id="btnHepsi">
            <i data-lucide="copy"></i> <span>Uygula</span>
        </button>
        <button type="button" class="btn btn-ghost btn-sm" id="btnTemizle">
            <i data-lucide="eraser"></i> <span>Girilenleri sil</span>
        </button>
        <div class="form-help" style="flex:1;min-width:220px;margin-bottom:0">
            Boş bıraktığın satıra <strong>dokunulmaz</strong>. Yalnızca doldurduğun kayıtlar güncellenir.
        </div>
    </div>

    <div class="table-wrap">
        <div class="card-header" style="padding:16px 18px;margin-bottom:0">
            <div class="card-title">Tutarı Boş Domain Kayıtları</div>
            <span class="badge badge-warning">{{ $kayitlar->count() }}</span>
        </div>
        <div class="table-scroll">
            <table class="data-table" style="min-width:900px">
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th style="width:200px">Müşteri</th>
                        <th style="width:105px">Tarih</th>
                        <th style="width:95px">Uzantı</th>
                        <th style="width:120px" class="text-right">Öneri</th>
                        <th style="width:150px" class="text-right">Satış Tutarı (₺)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kayitlar as $k)
                    <tr>
                        <td>
                            <strong>{{ \Illuminate\Support\Str::limit($k->domain, 44) }}</strong>
                            @if($k->bitis_tarih)
                                <div style="font-size:11px;color:var(--text-muted)">
                                    bitiş: {{ \Carbon\Carbon::parse($k->bitis_tarih)->format('d.m.Y') }}
                                </div>
                            @endif
                        </td>
                        <td style="font-size:12.5px">{{ \Illuminate\Support\Str::limit(trim($k->musteri) ?: '—', 26) }}</td>
                        <td style="font-size:12px">
                            {{ $k->tarih ? \Carbon\Carbon::parse($k->tarih)->format('d.m.Y') : '—' }}
                        </td>
                        <td>
                            @if($k->uzanti)
                                <span class="badge badge-neutral">.{{ $k->uzanti }}</span>
                            @else
                                <span class="badge badge-warning" title="Uzantı fiyat listesinde yok">bilinmiyor</span>
                            @endif
                        </td>
                        <td class="text-right" style="font-size:12px;color:var(--text-muted)">
                            {{ $k->oneri > 0 ? $f($k->oneri) : '—' }}
                        </td>
                        <td class="text-right">
                            <input type="number" step="0.01" min="0"
                                   name="tutar[{{ $k->id }}]"
                                   class="form-input tutar-girdi"
                                   data-oneri="{{ $k->oneri > 0 ? $k->oneri : '' }}"
                                   style="text-align:right;padding:6px 8px;font-size:13px"
                                   placeholder="{{ $k->oneri > 0 ? $f($k->oneri) : '0,00' }}">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{--
        Kaydet çubuğu: sayfa uzun (221 satır) olduğu için ekranda kalmalı.
        position:fixed KULLANILMIYOR — sol menünün ve sağ alttaki destek balonunun
        altında kalıyordu. sticky ile içerik sütununa hapsoluyor; sağ boşluk balona yer açar.
    --}}
    <div id="kaydetCubugu" style="position:sticky;bottom:0;z-index:20;margin-top:14px;
         background:var(--surface);border:1px solid var(--border);border-radius:12px;
         padding:12px 16px;padding-right:76px;display:flex;gap:14px;flex-wrap:wrap;
         align-items:center;justify-content:flex-end;box-shadow:0 -4px 16px rgba(0,0,0,.08)">
        <div class="form-help" style="margin:0;margin-right:auto">
            <strong id="cubukSayi">0</strong> kayıt dolduruldu ·
            toplam <strong id="cubukTutar">0,00 ₺</strong>
        </div>
        <button type="submit" class="btn btn-primary" id="btnKaydet" disabled>
            <i data-lucide="save"></i> <span>Girilenleri Kaydet</span>
        </button>
    </div>
    <div style="height:18px"></div>
</form>

<script>
(function () {
    var girdiler = Array.prototype.slice.call(document.querySelectorAll('.tutar-girdi'));
    var btnKaydet = document.getElementById('btnKaydet');

    function bicim(n) {
        return n.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺';
    }

    function say() {
        var adet = 0, toplam = 0;
        girdiler.forEach(function (g) {
            var v = parseFloat(g.value);
            if (!isNaN(v) && v > 0) { adet++; toplam += v; }
        });
        document.getElementById('cubukSayi').textContent  = adet;
        document.getElementById('cubukTutar').textContent = bicim(toplam);
        document.getElementById('sayacDeger').textContent = adet;
        document.getElementById('sayacTutar').textContent = bicim(toplam);
        btnKaydet.disabled = adet === 0;
    }

    girdiler.forEach(function (g) { g.addEventListener('input', say); });

    document.getElementById('btnOneri').addEventListener('click', function () {
        girdiler.forEach(function (g) {
            if (!g.value && g.dataset.oneri) g.value = g.dataset.oneri;
        });
        say();
    });

    document.getElementById('btnHepsi').addEventListener('click', function () {
        var v = document.getElementById('hepsiDeger').value;
        if (!v) return;
        girdiler.forEach(function (g) { g.value = v; });
        say();
    });

    document.getElementById('btnTemizle').addEventListener('click', function () {
        girdiler.forEach(function (g) { g.value = ''; });
        say();
    });

    // Enter'a basınca formu yollamak yerine bir alt satıra geç
    girdiler.forEach(function (g, i) {
        g.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); if (girdiler[i + 1]) girdiler[i + 1].focus(); }
        });
    });

    say();
})();
</script>
@endif

@endsection
