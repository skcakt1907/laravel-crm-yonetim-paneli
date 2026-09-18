@extends('admin._layout')

@section('title', 'Temizlik Kontrol Listesi')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.temizlik.index') }}">Temizlik Kontrol</a>
    <span class="sep">/</span>
    <span class="current">Kontrol Listesi</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title"><i data-lucide="list-checks"></i> Kontrol Listesi</h1>
        <div class="page-subtitle">Her temizlik kontrolünde sorulacak maddeler</div>
    </div>
    <a href="{{ route('admin.temizlik.index') }}" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i> <span>Kontrollere Dön</span>
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif
@if(session('error'))
    <div class="alert alert-danger"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>
@endif
@if($errors->any())
    <div class="alert alert-danger"><i data-lucide="alert-circle"></i><div>{{ $errors->first() }}</div></div>
@endif

<div class="alert alert-info" style="margin-bottom:16px">
    <i data-lucide="info"></i>
    <div>
        Buraya eklediğiniz maddeler <strong>her yeni kontrolde</strong> otomatik olarak listelenir.
        Bir maddeyi silseniz bile <strong>geçmiş kayıtlar bozulmaz</strong>.
    </div>
</div>

{{-- Yeni madde --}}
<div class="section" style="margin-bottom:16px">
    <div class="section-title"><i data-lucide="plus-circle"></i><span>Yeni Madde Ekle</span></div>
    <form method="POST" action="{{ route('admin.temizlik.madde.ekle') }}">
        @csrf
        <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:12px;align-items:end">
            <div class="form-group" style="margin:0">
                <label class="form-label">Madde <span class="required">*</span></label>
                <input type="text" name="baslik" required class="form-input"
                       placeholder="Örn: Tuvaletler temizlendi" maxlength="255">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Açıklama (opsiyonel)</label>
                <input type="text" name="aciklama" class="form-input"
                       placeholder="Örn: Zemin, ayna, sabunluk dahil" maxlength="500">
            </div>
            <button type="submit" class="btn btn-primary"><i data-lucide="plus"></i> <span>Ekle</span></button>
        </div>
    </form>
</div>

{{-- Mevcut maddeler --}}
<div class="table-wrap">
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:52px" class="text-center">Sıra</th>
                    <th>Madde</th>
                    <th style="width:96px" class="text-center">Aktif</th>
                    <th class="text-right" style="width:130px">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($maddeler as $m)
                <tr>
                    <form method="POST" action="{{ route('admin.temizlik.madde.guncelle', $m->id) }}" id="mf-{{ $m->id }}">
                        @csrf
                        <td class="text-center" style="color:var(--text-muted);font-size:12px">{{ $m->sira }}</td>
                        <td>
                            <input type="text" name="baslik" value="{{ $m->baslik }}" class="form-input"
                                   required maxlength="255" style="margin-bottom:6px">
                            <input type="text" name="aciklama" value="{{ $m->aciklama }}" class="form-input"
                                   placeholder="Açıklama (opsiyonel)" maxlength="500"
                                   style="font-size:12px;padding:6px 10px">
                        </td>
                        <td class="text-center">
                            <input type="checkbox" name="aktif" value="1" {{ $m->aktif ? 'checked' : '' }}
                                   style="width:19px;height:19px;cursor:pointer;accent-color:var(--brand)">
                        </td>
                        <td class="text-right">
                            <div style="display:flex;gap:6px;justify-content:flex-end">
                                <button type="submit" class="table-action" title="Kaydet" style="color:var(--success)">
                                    <i data-lucide="save"></i>
                                </button>
                            </div>
                        </td>
                    </form>
                </tr>
                <tr style="border-bottom:1px solid var(--border)">
                    <td colspan="4" style="padding:0 12px 10px;text-align:right">
                        <form method="POST" action="{{ route('admin.temizlik.madde.sil', $m->id) }}"
                              onsubmit="return confirm('“{{ addslashes($m->baslik) }}” maddesi silinsin mi? Geçmiş kayıtlar korunur.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm" style="color:var(--danger);border:1px solid var(--danger)">
                                <i data-lucide="trash-2"></i> <span>Sil</span>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4">
                    <div class="empty-state" style="padding:34px 0">
                        <i data-lucide="list-checks" class="empty-state-icon"></i>
                        <h4>Liste boş</h4>
                        <p>Yukarıdaki formdan ilk maddeyi ekleyin.</p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
