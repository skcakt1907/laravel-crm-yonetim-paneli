@extends('admin._layout')

@section('title', 'Sistem Limitleri')

@push('head')
@include('admin.ayarlar._partials.styles')
<style>
    .limit-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 18px;
        margin-bottom: 12px;
    }
    .limit-head {
        display: flex; align-items: center; gap: 12px;
        margin-bottom: 14px;
    }
    .limit-icon {
        width: 40px; height: 40px;
        border-radius: var(--radius-md);
        background: var(--brand-soft);
        color: var(--brand-dark);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    body.theme-dark .limit-icon { color: var(--brand); }
    .limit-name { font-weight: 600; font-size: 15px; }
    .limit-desc { font-size: 12.5px; color: var(--text-muted); margin-top: 2px; }

    .range-wrap {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 12px;
        align-items: center;
    }
    .range-value {
        background: var(--brand);
        color: #000;
        padding: 6px 14px;
        border-radius: var(--radius-md);
        font-weight: 700;
        font-size: 14px;
        min-width: 80px;
        text-align: center;
    }
    input[type="range"] {
        -webkit-appearance: none;
        appearance: none;
        height: 6px;
        background: var(--bg-subtle);
        border-radius: 99px;
        outline: none;
    }
    input[type="range"]::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 20px; height: 20px;
        border-radius: 50%;
        background: var(--brand);
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    }
    input[type="range"]::-moz-range-thumb {
        width: 20px; height: 20px;
        border-radius: 50%;
        background: var(--brand);
        cursor: pointer;
        border: none;
    }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.ayarlar.index') }}">Ayarlar</a>
    <span class="sep">/</span>
    <span class="current">Limitler</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="gauge"></i>
            Sistem Limitleri
        </h1>
        <div class="page-subtitle">Dosya yükleme, oturum ve diğer sistem limitleri</div>
    </div>
</div>

<div class="ayarlar-layout">
    @include('admin.ayarlar._partials.nav', ['active' => 'limit'])

    <div class="ayarlar-content">
        <form action="{{ route('admin.ayarlar.limit.post') }}" method="POST">
            @csrf

            <div class="info-card">
                <div class="ic"><i data-lucide="alert-triangle"></i></div>
                <div class="body">
                    Sunucu PHP konfigürasyonu (<code>upload_max_filesize</code>) bu değerleri kısıtlayabilir.
                </div>
            </div>

            <div class="section">
                <div class="section-title">
                    <i data-lucide="upload-cloud"></i>
                    <span>Yükleme Limitleri</span>
                </div>

                {{-- Max Upload Size --}}
                @php $uploadSize = (int)($ayarlar->max_upload_size ?? 10); @endphp
                <div class="limit-card">
                    <div class="limit-head">
                        <div class="limit-icon"><i data-lucide="file-up"></i></div>
                        <div style="flex:1">
                            <div class="limit-name">Maksimum Dosya Boyutu</div>
                            <div class="limit-desc">Yüklenebilen tek dosya boyutu (MB)</div>
                        </div>
                    </div>
                    <div class="range-wrap">
                        <input type="range" name="max_upload_size"
                               min="1" max="100" step="1"
                               value="{{ $uploadSize }}"
                               oninput="document.getElementById('uploadSizeVal').textContent = this.value + ' MB'">
                        <div class="range-value" id="uploadSizeVal">{{ $uploadSize }} MB</div>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text-muted);margin-top:4px">
                        <span>1 MB</span>
                        <span>100 MB</span>
                    </div>
                </div>

                {{-- Max File Count --}}
                @php $fileCount = (int)($ayarlar->max_file_count ?? 10); @endphp
                <div class="limit-card">
                    <div class="limit-head">
                        <div class="limit-icon"><i data-lucide="files"></i></div>
                        <div style="flex:1">
                            <div class="limit-name">Maksimum Dosya Sayısı</div>
                            <div class="limit-desc">Tek seferde yüklenebilecek dosya sayısı</div>
                        </div>
                    </div>
                    <div class="range-wrap">
                        <input type="range" name="max_file_count"
                               min="1" max="50" step="1"
                               value="{{ $fileCount }}"
                               oninput="document.getElementById('fileCountVal').textContent = this.value + ' adet'">
                        <div class="range-value" id="fileCountVal">{{ $fileCount }} adet</div>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text-muted);margin-top:4px">
                        <span>1</span>
                        <span>50</span>
                    </div>
                </div>
            </div>

            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="clock"></i>
                    <span>Oturum / Güvenlik</span>
                </div>

                {{-- Session Timeout --}}
                @php $sessionTimeout = (int)($ayarlar->session_timeout ?? 120); @endphp
                <div class="limit-card">
                    <div class="limit-head">
                        <div class="limit-icon"><i data-lucide="timer"></i></div>
                        <div style="flex:1">
                            <div class="limit-name">Oturum Süresi</div>
                            <div class="limit-desc">Kullanıcı oturumunun otomatik kapanma süresi (dakika)</div>
                        </div>
                    </div>
                    <div class="range-wrap">
                        <input type="range" name="session_timeout"
                               min="5" max="1440" step="5"
                               value="{{ $sessionTimeout }}"
                               oninput="document.getElementById('sessionVal').textContent = this.value + ' dk' + (this.value >= 60 ? ' (' + (this.value/60).toFixed(1) + ' saat)' : '')">
                        <div class="range-value" id="sessionVal">
                            {{ $sessionTimeout }} dk{{ $sessionTimeout >= 60 ? ' (' . number_format($sessionTimeout/60, 1) . ' saat)' : '' }}
                        </div>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text-muted);margin-top:4px">
                        <span>5 dk</span>
                        <span>24 saat</span>
                    </div>
                </div>
            </div>

            <div class="sticky-save">
                <div style="font-size:13px;color:var(--text-muted)">
                    <i data-lucide="info" style="width:13px;height:13px;display:inline;vertical-align:middle"></i>
                    Değişiklikler bir sonraki istekten itibaren geçerli
                </div>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i>
                    <span>Limitleri Kaydet</span>
                </button>
            </div>
        </form>
    </div>
</div>

@endsection