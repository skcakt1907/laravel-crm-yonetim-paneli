@extends('admin._layout')

@section('title', 'Sayfa Bakım Modu')

@push('head')
@include('admin.ayarlar._partials.styles')
<style>
    .add-form {
        background: linear-gradient(135deg, var(--brand-soft), transparent);
        border: 1px dashed var(--brand-medium);
        border-radius: var(--radius-md);
        padding: 16px;
        margin-bottom: 16px;
    }
    .form-grid-inline {
        display: grid;
        grid-template-columns: 1.2fr 1.5fr 1fr 1fr auto;
        gap: 8px;
        align-items: end;
    }
    @media (max-width: 900px) {
        .form-grid-inline { grid-template-columns: 1fr 1fr; }
    }

    .bakim-row.aktif {
        background: linear-gradient(90deg, rgba(239,68,68,0.05), transparent);
        border-left: 3px solid var(--danger);
    }

    .url-pattern {
        background: var(--bg-subtle);
        padding: 3px 8px;
        border-radius: 6px;
        font-family: monospace;
        font-size: 11.5px;
        color: var(--text-secondary);
    }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.ayarlar.index') }}">Ayarlar</a>
    <span class="sep">/</span>
    <span class="current">Sayfa Bakım</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="file-warning"></i>
            Sayfa Bazlı Bakım
            <span class="badge badge-brand">{{ count($sayfalar ?? []) }}</span>
        </h1>
        <div class="page-subtitle">Belirli sayfaları bakıma alın (tüm site yerine sadece o sayfayı)</div>
    </div>
</div>

<div class="ayarlar-layout">
    @include('admin.ayarlar._partials.nav', ['active' => 'sayfa-bakim'])

    <div class="ayarlar-content">
        <div class="info-card">
            <div class="ic"><i data-lucide="info"></i></div>
            <div class="body">
                URL pattern'larıyla belirli sayfaları bakıma alabilirsiniz. <code>/hizmetler/*</code> tüm hizmet sayfalarını,
                <code>/blog</code> ise sadece blog ana sayfasını kapsar.
            </div>
        </div>

        {{-- YENİ SAYFA EKLEME --}}
        <div class="add-form">
            <div style="font-weight:600;font-size:14px;margin-bottom:10px;display:flex;align-items:center;gap:8px;color:var(--brand-dark)">
                <i data-lucide="plus-circle" style="width:18px;height:18px"></i>
                <span>Yeni Bakım Sayfası Ekle</span>
            </div>

            <form action="{{ route('admin.ayarlar.sayfa-bakim.store') }}" method="POST">
                @csrf
                <div class="form-grid-inline">
                    <div>
                        <label class="form-label" style="font-size:11.5px">Sayfa Adı <span class="required">*</span></label>
                        <input type="text" name="sayfa_adi" required class="form-input" placeholder="Örn: Blog">
                    </div>
                    <div>
                        <label class="form-label" style="font-size:11.5px">URL Pattern <span class="required">*</span></label>
                        <input type="text" name="url_pattern" required class="form-input"
                               placeholder="/blog veya /hizmetler/*"
                               style="font-family:monospace;font-size:12.5px">
                    </div>
                    <div>
                        <label class="form-label" style="font-size:11.5px">Başlangıç</label>
                        <input type="datetime-local" name="baslangic_tarihi" class="form-input">
                    </div>
                    <div>
                        <label class="form-label" style="font-size:11.5px">Bitiş</label>
                        <input type="datetime-local" name="bitis_tarihi" class="form-input">
                    </div>
                    <button type="submit" class="btn btn-primary" style="height:38px">
                        <i data-lucide="plus"></i>
                        <span>Ekle</span>
                    </button>
                </div>

                <details style="margin-top:10px">
                    <summary style="font-size:12px;color:var(--text-muted);cursor:pointer">Gelişmiş ayarlar (başlık ve mesaj)</summary>
                    <div class="form-grid" style="margin-top:10px">
                        <div class="form-group full">
                            <label class="form-label" style="font-size:11.5px">Bakım Başlığı</label>
                            <input type="text" name="baslik" class="form-input" placeholder="Sayfa Bakımda" value="Sayfa Bakımda">
                        </div>
                        <div class="form-group full">
                            <label class="form-label" style="font-size:11.5px">Bakım Mesajı</label>
                            <textarea name="mesaj" rows="2" class="form-textarea"
                                      placeholder="Bu sayfa şu anda bakımdadır.">Bu sayfa şu anda bakımdadır.</textarea>
                        </div>
                    </div>
                </details>
            </form>
        </div>

        {{-- LİSTE --}}
        @if(empty($sayfalar) || count($sayfalar) === 0)
            <div class="section">
                <div class="empty-state">
                    <i data-lucide="file-warning" class="empty-state-icon"></i>
                    <h4>Henüz bakım sayfası yok</h4>
                    <p>Yukarıdaki formdan bakıma almak istediğiniz sayfayı ekleyin.</p>
                </div>
            </div>
        @else
            <div class="table-wrap">
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width:60px">#</th>
                                <th>Sayfa Adı</th>
                                <th>URL Pattern</th>
                                <th style="width:200px">Bakım Süresi</th>
                                <th style="width:110px">Durum</th>
                                <th class="text-right" style="width:140px">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sayfalar as $s)
                                @php $aktif = (int)($s->aktif ?? 0) === 1; @endphp
                                <tr class="bakim-row {{ $aktif ? 'aktif' : '' }}">
                                    <td style="color:var(--text-muted);font-size:12px">#{{ $s->id }}</td>
                                    <td style="font-weight:600">{{ $s->sayfa_adi ?? '—' }}</td>
                                    <td><span class="url-pattern">{{ $s->url_pattern ?? '—' }}</span></td>
                                    <td>
                                        @php
                                            $bas = null; $bit = null;
                                            try {
                                                if (!empty($s->baslangic_tarihi)) $bas = \Carbon\Carbon::parse($s->baslangic_tarihi);
                                                if (!empty($s->bitis_tarihi))     $bit = \Carbon\Carbon::parse($s->bitis_tarihi);
                                            } catch (\Throwable $e) {}
                                        @endphp
                                        @if($bas || $bit)
                                            <div style="font-size:11.5px;line-height:1.5">
                                                @if($bas)<div>📅 {{ $bas->format('d.m.Y H:i') }}</div>@endif
                                                @if($bit)<div style="color:var(--danger)">⏰ {{ $bit->format('d.m.Y H:i') }}</div>@endif
                                            </div>
                                        @else
                                            <span style="color:var(--text-muted);font-size:12px">Süresiz</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($aktif)
                                            <span class="badge badge-danger">
                                                <i data-lucide="alert-triangle" style="width:11px;height:11px"></i>
                                                Bakımda
                                            </span>
                                        @else
                                            <span class="badge badge-success">
                                                <i data-lucide="check" style="width:11px;height:11px"></i>
                                                Yayında
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <div class="table-actions">
                                            <button type="button" class="table-action"
                                                    style="color:{{ $aktif ? 'var(--success)' : 'var(--warning)' }}"
                                                    onclick="toggleSayfa({{ $s->id }})"
                                                    title="{{ $aktif ? 'Yayına al' : 'Bakıma al' }}">
                                                <i data-lucide="{{ $aktif ? 'play' : 'pause' }}"></i>
                                            </button>
                                            <button type="button" class="table-action"
                                                    style="color:var(--danger)"
                                                    onclick="silSayfa({{ $s->id }}, '{{ addslashes($s->sayfa_adi ?? '') }}')"
                                                    title="Sil">
                                                <i data-lucide="trash-2"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- FORMLAR --}}
            @foreach($sayfalar as $s)
                <form id="toggle-{{ $s->id }}"
                      action="{{ route('admin.ayarlar.sayfa-bakim.toggle', $s->id) }}"
                      method="POST" style="display:none">
                    @csrf
                </form>
                <form id="del-{{ $s->id }}"
                      action="{{ route('admin.ayarlar.sayfa-bakim.delete', $s->id) }}"
                      method="POST" style="display:none">
                    @csrf
                    @method('DELETE')
                </form>
            @endforeach
        @endif
    </div>
</div>

<script>
function toggleSayfa(id) {
    document.getElementById('toggle-' + id).submit();
}
function silSayfa(id, ad) {
    if (confirm('Bu bakım kaydını silmek istediğine emin misin?\n\n' + ad)) {
        document.getElementById('del-' + id).submit();
    }
}
</script>

@endsection