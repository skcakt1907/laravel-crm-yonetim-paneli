@extends('admin._layout')

@section('title', 'Şifre Kasası')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Şifre Kasası</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">Şifre Kasası</h1>
        <div class="page-subtitle">{{ $toplam }} kayıt · müşteri hesap &amp; şifreleri</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.crm.ozel-kayitlar.export') }}" class="btn btn-secondary btn-sm"><i data-lucide="download"></i> <span>Excel</span></a>
        <a href="{{ route('admin.crm.ozel-kayitlar.export-template') }}" class="btn btn-secondary btn-sm"><i data-lucide="file-spreadsheet"></i> <span>Şablon</span></a>
        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('smImportBox').style.display = document.getElementById('smImportBox').style.display==='none'?'flex':'none'"><i data-lucide="upload"></i> <span>İçe Aktar</span></button>
        <a href="{{ route('admin.crm.ozel-kayitlar.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> <span>Yeni Kayıt</span></a>
    </div>
</div>

<form id="smImportBox" action="{{ route('admin.crm.ozel-kayitlar.import') }}" method="POST" enctype="multipart/form-data"
      class="section" style="display:none;gap:8px;align-items:center;flex-wrap:wrap">
    @csrf
    <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="form-input" style="max-width:340px">
    <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="check"></i> <span>Yükle</span></button>
    <span class="form-help" style="margin-top:0">Önce "Şablon"u indirip doldur. Aynı başlık tek kayıtta toplanır.</span>
</form>

<form method="GET" class="section" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;margin-bottom:16px">
    <div class="form-group" style="flex:1;min-width:230px;margin-bottom:0">
        <label class="form-label" style="font-size:11px">Ara</label>
        <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Müşteri / firma / e-posta / telefon">
    </div>
    <div class="form-group" style="flex:1;min-width:190px;margin-bottom:0">
        <label class="form-label" style="font-size:11px">Bölüm</label>
        <select name="bolum" class="form-select">
            <option value="">Tümü</option>
            @foreach(\App\Models\CRM\SosyalMedyaHesap::BOLUMLER as $kod => $ad)
                <option value="{{ $kod }}" {{ request('bolum') === $kod ? 'selected' : '' }}>{{ $ad }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn btn-primary"><i data-lucide="search"></i> <span>Ara</span></button>
    @if(request('search') || request('bolum'))
        <a href="{{ route('admin.crm.ozel-kayitlar.index') }}" class="btn btn-ghost btn-sm">
            <i data-lucide="x"></i> <span>Temizle</span>
        </a>
    @endif
</form>

<div class="table-wrap">
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Müşteri / Başlık</th>
                    <th>Firma</th>
                    <th>İletişim</th>
                    <th>Hesap</th>
                    <th style="width:110px">Durum</th>
                    <th class="text-right">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kayitlar as $k)
                    @php $aktif = (bool) ($k->durum ?? 1); @endphp
                    <tr @if(!$aktif) style="opacity:.55" @endif>
                        <td>
                            <a href="{{ route('admin.crm.ozel-kayitlar.show', $k->id) }}" style="color:var(--brand);text-decoration:none;font-weight:700">{{ $k->baslik }}</a>
                            @if($k->crm_ad)<div style="font-size:11px;color:var(--text-muted)"><i data-lucide="link" style="width:11px;height:11px"></i> CRM: {{ $k->crm_ad }}</div>@endif
                        </td>
                        <td>{{ $k->firma ?: '—' }}</td>
                        <td style="font-size:12px">
                            @if($k->email){{ $k->email }}<br>@endif
                            @if($k->telefon)<span style="color:var(--text-muted)">{{ $k->telefon }}</span>@endif
                            @if(!$k->email && !$k->telefon)—@endif
                        </td>
                        <td style="white-space:nowrap">
                            @if(($k->sosyal_sayisi ?? 0) > 0)
                                <span class="badge badge-brand" title="Sosyal Medya">
                                    <i data-lucide="share-2" style="width:11px;height:11px"></i> {{ $k->sosyal_sayisi }}
                                </span>
                            @endif
                            @if(($k->web_sayisi ?? 0) > 0)
                                <span class="badge badge-info" title="Web Sitesi Giriş Bilgileri">
                                    <i data-lucide="globe" style="width:11px;height:11px"></i> {{ $k->web_sayisi }}
                                </span>
                            @endif
                            @if(($k->hesap_sayisi ?? 0) == 0)
                                <span class="badge badge-neutral">hesap yok</span>
                            @endif
                        </td>
                        <td>
                            <form action="{{ route('admin.crm.ozel-kayitlar.durum', $k->id) }}" method="POST" style="display:inline">
                                @csrf
                                <button type="submit" class="badge"
                                        style="border:none;cursor:pointer;font-weight:700;
                                               color:{{ $aktif ? '#1E6B2F' : '#9C2B2B' }};
                                               background:{{ $aktif ? '#E2EFDA' : '#F8D7DA' }}"
                                        title="Tıkla, {{ $aktif ? 'pasife al' : 'aktifleştir' }}">
                                    {{ $aktif ? '● Aktif' : '○ Pasif' }}
                                </button>
                            </form>
                        </td>
                        <td class="text-right">
                            <div style="display:inline-flex;gap:4px;align-items:center;justify-content:flex-end">
                                <a href="{{ route('admin.crm.ozel-kayitlar.show', $k->id) }}" class="table-action" title="Aç / Şifreler"><i data-lucide="eye"></i></a>
                                <a href="{{ route('admin.crm.ozel-kayitlar.edit', $k->id) }}" class="table-action" style="color:var(--brand-dark)" title="Düzenle"><i data-lucide="pencil"></i></a>
                                <form action="{{ route('admin.crm.ozel-kayitlar.destroy', $k->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Kayıt ve tüm hesapları silinsin mi?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="table-action" style="color:var(--danger)" title="Sil"><i data-lucide="trash-2"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state" style="padding:36px 0">
                                <i data-lucide="lock" class="empty-state-icon"></i>
                                <h4>Henüz kayıt yok</h4>
                                <p>"Yeni Kayıt" ile başla; bir müşteri seç veya elle yeni bir isim gir.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($kayitlar->hasPages())
<div style="margin-top:16px">{{ $kayitlar->links() }}</div>
@endif

@endsection
