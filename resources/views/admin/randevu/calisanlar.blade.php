@extends('admin._layout')

@section('title', 'Çalışanlar')

@push('head')
<style>
    .clsn-ava { width:38px; height:38px; border-radius:50%; object-fit:cover; display:inline-flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:14px; flex:0 0 auto; }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Çalışanlar</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">👤 Çalışanlar
            <span class="badge badge-brand" style="font-size:13px;vertical-align:middle">{{ count($calisanlar) }}</span>
        </h1>
        <div class="page-subtitle">Randevu takviminde kolon olarak görünen çalışanlar</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.randevu.calisan-ekle') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Yeni Çalışan</span>
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:16px">
    <i data-lucide="check-circle"></i> {{ session('success') }}
</div>
@endif

@if(count($calisanlar) === 0)
    <div class="section">
        <div class="empty-state">
            <i data-lucide="users" class="empty-state-icon"></i>
            <h4>Henüz çalışan eklenmemiş</h4>
            <p>Randevu takviminde kolon olması için çalışan ekle.</p>
            <a href="{{ route('admin.randevu.calisan-ekle') }}" class="btn btn-primary btn-sm" style="margin-top:8px">
                <i data-lucide="plus"></i> <span>İlk Çalışanı Ekle</span>
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
                        <th>Çalışan Adı</th>
                        <th>Email</th>
                        <th>Çalışma Aralığı</th>
                        <th>Bu Ayki Toplam Randevu</th>
                        <th>Durum</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($calisanlar as $c)
                        @php $initials = collect(preg_split('/\s+/', trim($c->ad)))->map(fn($p)=>mb_substr($p,0,1,'UTF-8'))->take(2)->implode(''); @endphp
                        <tr>
                            <td style="color:var(--text-muted);font-size:12px">#{{ $c->id }}</td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px">
                                    @if(!empty($c->foto))
                                        <img class="clsn-ava" src="{{ asset($c->foto) }}" alt="">
                                    @else
                                        <span class="clsn-ava" style="background:{{ $c->renk ?? '#f59e0b' }}">{{ mb_strtoupper($initials,'UTF-8') }}</span>
                                    @endif
                                    <span style="font-weight:600;color:var(--text)">{{ $c->ad }}</span>
                                </div>
                            </td>
                            <td style="font-size:13px;color:var(--text-secondary)">{{ $c->email ?? '—' }}</td>
                            <td style="font-size:13px">
                                @if($c->mesai_baslangic && $c->mesai_bitis)
                                    {{ $c->mesai_baslangic }} - {{ $c->mesai_bitis }}
                                @else
                                    <span style="color:var(--text-muted)">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-brand">{{ $sayilar[$c->id] ?? 0 }}</span>
                            </td>
                            <td>
                                @if($c->durum == 1)
                                    <span class="badge badge-success">🟢 Aktif</span>
                                @else
                                    <span class="badge badge-danger">🔴 Pasif</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="table-actions">
                                    <a href="{{ route('admin.randevu.calisan-duzenle', $c->id) }}" class="table-action" title="Düzenle">
                                        <i data-lucide="edit-2"></i>
                                    </a>
                                    <form action="{{ route('admin.randevu.calisan-sil', $c->id) }}" method="POST" onsubmit="return confirm('{{ addslashes($c->ad) }} silinsin mi?');" style="margin:0;display:inline">
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
