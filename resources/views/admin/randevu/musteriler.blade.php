@extends('admin._layout')

@section('title', 'Müşteriler')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Müşteriler</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">🧑‍🤝‍🧑 Müşteriler
            <span class="badge badge-brand" style="font-size:13px;vertical-align:middle">{{ count($musteriler) }}</span>
        </h1>
        <div class="page-subtitle">Randevu müşterileri (randevu eklenince otomatik de oluşur)</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.randevu.musteri-ekle') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Yeni Müşteri</span>
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:16px">
    <i data-lucide="check-circle"></i> {{ session('success') }}
</div>
@endif

@if(count($musteriler) === 0)
    <div class="section">
        <div class="empty-state">
            <i data-lucide="users" class="empty-state-icon"></i>
            <h4>Henüz müşteri yok</h4>
            <p>Randevu eklediğinde müşteri otomatik oluşur ya da elle ekleyebilirsin.</p>
            <a href="{{ route('admin.randevu.musteri-ekle') }}" class="btn btn-primary btn-sm" style="margin-top:8px">
                <i data-lucide="plus"></i> <span>İlk Müşteriyi Ekle</span>
            </a>
        </div>
    </div>
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Müşteri Adı</th>
                        <th>Telefon</th>
                        <th>Toplam Randevu</th>
                        <th>Son Randevu</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($musteriler as $m)
                        @php $st = $sayilar[$m->id] ?? null; @endphp
                        <tr>
                            <td style="color:var(--text-muted);font-size:12px">#{{ $m->id }}</td>
                            <td style="font-weight:600;color:var(--text)">{{ $m->ad }}</td>
                            <td style="font-size:13px;font-family:monospace">{{ $m->telefon ?? '—' }}</td>
                            <td><span class="badge badge-brand">{{ $st->adet ?? 0 }}</span></td>
                            <td style="font-size:13px;color:var(--text-secondary)">
                                {{ ($st && $st->son) ? \Carbon\Carbon::parse($st->son)->format('d.m.Y H:i') : '—' }}
                            </td>
                            <td class="text-right">
                                <div class="table-actions">
                                    <a href="{{ route('admin.randevu.musteri-duzenle', $m->id) }}" class="table-action" title="Düzenle">
                                        <i data-lucide="edit-2"></i>
                                    </a>
                                    <form action="{{ route('admin.randevu.musteri-sil', $m->id) }}" method="POST" onsubmit="return confirm('{{ addslashes($m->ad) }} silinsin mi?');" style="margin:0;display:inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="table-action" style="color:var(--danger)" title="Sil">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection
