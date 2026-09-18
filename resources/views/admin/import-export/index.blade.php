@extends('admin._layout')

@section('title', 'Import / Export')

@push('head')
<style>
    .ie-card {
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 22px;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .ie-head {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .ie-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .ie-title {
        font-weight: 700;
        font-size: 17px;
    }
    .ie-sub {
        font-size: 13px;
        color: var(--text-muted);
        margin-top: 2px;
    }
    .ie-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .ie-row {
        display: flex;
        gap: 8px;
        align-items: center;
        padding: 10px 12px;
        background: var(--bg);
        border-radius: var(--radius-md);
        border: 1px solid var(--border);
    }
    .ie-row-label {
        flex: 1;
        font-size: 13px;
        font-weight: 500;
    }
    .ie-row-actions {
        display: flex;
        gap: 6px;
    }
    .ie-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    @media (max-width: 900px) {
        .ie-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Import / Export</span>
</div>

@if(session('success'))<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger" style="margin-bottom:16px">{{ session('error') }}</div>@endif
@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:16px">
    <ul style="margin:0 0 0 18px">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="arrow-left-right"></i>
            Import / Export
        </h1>
        <div class="page-subtitle">Toplu veri içe ve dışa aktarma araçları</div>
    </div>
</div>

@php
    $uyeSayisi = \DB::table('uyeler')->count();
    $faturaSayisi = \DB::table('faturalar')->count();
    $paketSayisi = \DB::table('yazilimlar')->count();
@endphp

{{-- STAT KARTLARI --}}
<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(184,182,46,.12);color:var(--brand-dark)">
            <i data-lucide="users"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Üye</div>
            <div class="stat-card-value">{{ $uyeSayisi }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,.12);color:#10b981">
            <i data-lucide="receipt"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Fatura</div>
            <div class="stat-card-value">{{ $faturaSayisi }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,.12);color:#3b82f6">
            <i data-lucide="package"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Paket</div>
            <div class="stat-card-value">{{ $paketSayisi }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(245,158,11,.12);color:#f59e0b"
             title="Toplam aktarılabilir kayıt">
            <i data-lucide="database"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam</div>
            <div class="stat-card-value">{{ $uyeSayisi + $faturaSayisi + $paketSayisi }}</div>
        </div>
    </div>
</div>

<div class="ie-grid">
    {{-- EXPORT --}}
    <div class="ie-card">
        <div class="ie-head">
            <div class="ie-icon" style="background:rgba(16,185,129,.15);color:#10b981">
                <i data-lucide="download"></i>
            </div>
            <div>
                <div class="ie-title">Dışa Aktar (Export)</div>
                <div class="ie-sub">Verilerinizi CSV/Excel formatında indir</div>
            </div>
        </div>

        <div class="ie-actions">
            <div class="ie-row">
                <i data-lucide="users" style="color:var(--brand-dark)"></i>
                <div class="ie-row-label">
                    Üyeler
                    <div style="font-size:11px;color:var(--text-muted)">{{ $uyeSayisi }} kayıt</div>
                </div>
                <div class="ie-row-actions">
                    <a href="{{ route('admin.export.uyeler') }}" class="btn btn-primary btn-sm">
                        <i data-lucide="download"></i>
                        <span>İndir</span>
                    </a>
                    <a href="{{ route('admin.import.template.uyeler') }}" class="btn btn-secondary btn-sm">
                        <i data-lucide="file"></i>
                        <span>Şablon</span>
                    </a>
                </div>
            </div>

            <div class="ie-row">
                <i data-lucide="receipt" style="color:#10b981"></i>
                <div class="ie-row-label">
                    Faturalar
                    <div style="font-size:11px;color:var(--text-muted)">{{ $faturaSayisi }} kayıt</div>
                </div>
                <div class="ie-row-actions">
                    <a href="{{ route('admin.export.faturalar') }}" class="btn btn-primary btn-sm">
                        <i data-lucide="download"></i>
                        <span>İndir</span>
                    </a>
                </div>
            </div>

            <div class="ie-row">
                <i data-lucide="package" style="color:#3b82f6"></i>
                <div class="ie-row-label">
                    Paketler
                    <div style="font-size:11px;color:var(--text-muted)">{{ $paketSayisi }} kayıt</div>
                </div>
                <div class="ie-row-actions">
                    <a href="{{ route('admin.export.paketler') }}" class="btn btn-primary btn-sm">
                        <i data-lucide="download"></i>
                        <span>İndir</span>
                    </a>
                </div>
            </div>
        </div>

        <div style="font-size:12px;color:var(--text-muted);padding-top:8px;border-top:1px dashed var(--border)">
            <i data-lucide="info" style="width:12px;height:12px;display:inline-block;vertical-align:-2px"></i>
            Dosyalar UTF-8 (BOM) ve <code>;</code> ayraçlı CSV formatında — Excel ile uyumlu.
        </div>
    </div>

    {{-- IMPORT --}}
    <div class="ie-card">
        <div class="ie-head">
            <div class="ie-icon" style="background:rgba(59,130,246,.15);color:#3b82f6">
                <i data-lucide="upload"></i>
            </div>
            <div>
                <div class="ie-title">İçe Aktar (Import)</div>
                <div class="ie-sub">Excel/CSV dosyası yükleyerek toplu kayıt</div>
            </div>
        </div>

        {{-- Üye Import --}}
        <form action="{{ route('admin.import.uyeler') }}" method="POST" enctype="multipart/form-data"
              class="ie-row" style="flex-direction:column;align-items:stretch;gap:10px"
              onsubmit="return confirm('Üye verileri sisteme eklenecek. Devam edilsin mi?')">
            @csrf
            <div style="display:flex;gap:10px;align-items:center">
                <i data-lucide="users" style="color:var(--brand-dark)"></i>
                <div style="flex:1;font-weight:500;font-size:13px">Üye Import</div>
            </div>
            <input type="file" name="file" accept=".csv,.xlsx,.xls" required class="form-input"
                   style="padding:8px">
            <button type="submit" class="btn btn-primary btn-sm">
                <i data-lucide="upload"></i>
                <span>Yükle</span>
            </button>
        </form>

        {{-- Paket Import --}}
        <form action="{{ route('admin.import.paketler') }}" method="POST" enctype="multipart/form-data"
              class="ie-row" style="flex-direction:column;align-items:stretch;gap:10px"
              onsubmit="return confirm('Paket verileri sisteme eklenecek. Devam edilsin mi?')">
            @csrf
            <div style="display:flex;gap:10px;align-items:center">
                <i data-lucide="package" style="color:#3b82f6"></i>
                <div style="flex:1;font-weight:500;font-size:13px">Paket Import</div>
            </div>
            <input type="file" name="file" accept=".csv,.xlsx,.xls" required class="form-input"
                   style="padding:8px">
            <button type="submit" class="btn btn-primary btn-sm">
                <i data-lucide="upload"></i>
                <span>Yükle</span>
            </button>
        </form>

        <div style="font-size:12px;color:var(--text-muted);padding-top:8px;border-top:1px dashed var(--border)">
            <i data-lucide="alert-triangle" style="width:12px;height:12px;display:inline-block;vertical-align:-2px;color:#f59e0b"></i>
            Önce <strong>"Şablon"</strong> butonundan örnek dosyayı indirip yapıyı kontrol et.
        </div>
    </div>
</div>

{{-- TOPLU SİLME --}}
<div class="ie-card" style="margin-top:16px;border-color:rgba(239,68,68,.2);background:rgba(239,68,68,.02)">
    <div class="ie-head">
        <div class="ie-icon" style="background:rgba(239,68,68,.15);color:#ef4444">
            <i data-lucide="trash-2"></i>
        </div>
        <div>
            <div class="ie-title" style="color:#ef4444">Toplu Silme</div>
            <div class="ie-sub">Birden fazla kaydı tek seferde sil — DİKKATLİ KULLAN!</div>
        </div>
    </div>

    <form action="{{ route('admin.toplu.sil') }}" method="POST"
          onsubmit="return confirm('⚠️ Seçili kayıtlar KALICI olarak silinecek! Devam edilsin mi?')">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Tablo</label>
                <select name="tablo" class="form-select" required>
                    <option value="">— Tablo seç —</option>
                    <option value="uyeler">Üyeler</option>
                    <option value="faturalar">Faturalar</option>
                    <option value="yazilimlar">Paketler</option>
                    <option value="blog">Blog</option>
                    <option value="referanslar">Referanslar</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">ID Listesi (virgülle ayırın)</label>
                <input type="text" id="idsInput" class="form-input"
                       placeholder="Örn: 12, 34, 56, 78">
                <small class="form-help">Virgül veya boşlukla ayrılmış ID'ler</small>
            </div>
        </div>
        <div id="idsHidden"></div>
        <button type="submit" class="btn btn-danger" onclick="prepareIds()" style="margin-top:8px">
            <i data-lucide="trash-2"></i>
            <span>Kayıtları Sil</span>
        </button>
    </form>

    <div style="font-size:12px;color:#ef4444;padding-top:8px;border-top:1px dashed rgba(239,68,68,.3)">
        <i data-lucide="alert-octagon" style="width:12px;height:12px;display:inline-block;vertical-align:-2px"></i>
        Bu işlem <strong>geri alınamaz</strong>. Silmeden önce yedek alın.
    </div>
</div>

<script>
function prepareIds(){
    var raw = (document.getElementById('idsInput').value || '').trim();
    var hiddenWrap = document.getElementById('idsHidden');
    hiddenWrap.innerHTML = '';
    if(!raw) return;
    var ids = raw.split(/[,\s]+/).map(function(s){ return s.trim(); }).filter(Boolean);
    ids.forEach(function(id){
        var h = document.createElement('input');
        h.type = 'hidden';
        h.name = 'ids[]';
        h.value = id;
        hiddenWrap.appendChild(h);
    });
}
</script>

@endsection