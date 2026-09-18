@extends('admin._layout')

@section('title', 'Ar-Ge Anketi')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Ar-Ge Anketi</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="clipboard-list"></i>
            Ar-Ge Anketi Cevapları
            <span class="badge badge-brand">{{ $anketler->total() }}</span>
        </h1>
        <div class="page-subtitle">Rubito Ar-Ge anketini dolduran müşteriler</div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif

@if($anketler->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="clipboard-list" class="empty-state-icon"></i>
            <h4>Henüz anket cevabı yok</h4>
            <p>Müşteriler <code>/arge-anketi</code> sayfasından doldurdukça burada listelenir.</p>
        </div>
    </div>
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:60px">#</th>
                        <th style="width:210px">Kişi</th>
                        <th>İletişim</th>
                        <th style="width:150px">Bütçe (özet)</th>
                        <th style="width:140px">Tarih</th>
                        <th class="text-right" style="width:130px">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($anketler as $a)
                        @php $yeni = !$a->okundu; @endphp
                        <tr @if($yeni) style="background:linear-gradient(90deg, rgba(184,182,46,.07), transparent)" @endif>
                            <td style="color:var(--text-muted);font-size:12px;vertical-align:top;padding-top:14px">#{{ $a->id }}</td>
                            <td style="vertical-align:top;padding-top:12px">
                                <div style="display:flex;gap:10px;align-items:center">
                                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--brand),var(--brand-dark));color:#000;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0">
                                        {{ mb_strtoupper(mb_substr($a->ad_soyad, 0, 1, 'UTF-8'), 'UTF-8') }}
                                    </div>
                                    <div style="min-width:0">
                                        <div style="font-weight:600;font-size:13px;color:var(--text)">{{ $a->ad_soyad }}</div>
                                        @if($yeni)<span class="badge badge-warning" style="font-size:9px">YENİ</span>@endif
                                    </div>
                                </div>
                            </td>
                            <td style="vertical-align:top;padding-top:14px;font-size:12.5px">
                                <div><i data-lucide="mail" style="width:12px;height:12px"></i> {{ $a->email }}</div>
                                @if($a->telefon)<div style="color:var(--text-muted);margin-top:2px"><i data-lucide="phone" style="width:12px;height:12px"></i> {{ $a->telefon }}</div>@endif
                            </td>
                            <td style="vertical-align:top;padding-top:14px;font-size:12.5px;color:var(--text-secondary)">
                                {{ \Illuminate\Support\Str::limit($a->butce, 40) ?: '—' }}
                            </td>
                            <td style="vertical-align:top;padding-top:14px;font-size:12px;color:var(--text-muted)">
                                {{ $a->created_at ? $a->created_at->format('d.m.Y H:i') : '—' }}
                            </td>
                            <td class="text-right" style="vertical-align:top;padding-top:10px">
                                <div class="table-actions" style="justify-content:flex-end">
                                    <a href="{{ route('admin.arge-anketleri.goster', $a->id) }}" class="table-action" style="color:var(--brand-dark)" title="Gör">
                                        <i data-lucide="eye"></i>
                                    </a>
                                    <button type="button" class="table-action" style="color:var(--danger)" onclick="argeSil({{ $a->id }})" title="Sil">
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

    @if($anketler->hasPages())
        <div style="margin-top:16px">{{ $anketler->links() }}</div>
    @endif

    @foreach($anketler as $a)
        <form id="arge-del-{{ $a->id }}" action="{{ route('admin.arge-anketleri.sil', $a->id) }}" method="POST" style="display:none">
            @csrf @method('DELETE')
        </form>
    @endforeach
@endif

<script>
function argeSil(id) {
    if (confirm('Bu anket cevabını silmek istediğine emin misin?\n\nBu işlem geri alınamaz.')) {
        document.getElementById('arge-del-' + id).submit();
    }
}
</script>

@endsection
