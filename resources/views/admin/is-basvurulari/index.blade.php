@extends('admin._layout')

@section('title', 'İş Başvuruları')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">İş Başvuruları</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="briefcase"></i>
            İş Başvuruları
            <span class="badge badge-brand">{{ $basvurular->total() }}</span>
        </h1>
        <div class="page-subtitle">Rubito iş başvurusu formunu dolduran adaylar</div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>
@endif

@if($basvurular->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="briefcase" class="empty-state-icon"></i>
            <h4>Henüz başvuru yok</h4>
            <p>Adaylar <code>/is-basvurusu</code> sayfasından başvurdukça burada listelenir.</p>
        </div>
    </div>
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:60px">#</th>
                        <th style="width:210px">Aday</th>
                        <th>İletişim</th>
                        <th style="width:170px">Pozisyon</th>
                        <th style="width:110px">Lokasyon</th>
                        <th style="width:70px">CV</th>
                        <th style="width:130px">Tarih</th>
                        <th class="text-right" style="width:120px">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($basvurular as $b)
                        @php $yeni = !$b->okundu; @endphp
                        <tr @if($yeni) style="background:linear-gradient(90deg, rgba(184,182,46,.07), transparent)" @endif>
                            <td style="color:var(--text-muted);font-size:12px;vertical-align:top;padding-top:14px">#{{ $b->id }}</td>
                            <td style="vertical-align:top;padding-top:12px">
                                <div style="display:flex;gap:10px;align-items:center">
                                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--brand),var(--brand-dark));color:#000;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0">
                                        {{ mb_strtoupper(mb_substr($b->ad_soyad, 0, 1, 'UTF-8'), 'UTF-8') }}
                                    </div>
                                    <div style="min-width:0">
                                        <div style="font-weight:600;font-size:13px;color:var(--text)">{{ $b->ad_soyad }}</div>
                                        @if($yeni)<span class="badge badge-warning" style="font-size:9px">YENİ</span>@endif
                                    </div>
                                </div>
                            </td>
                            <td style="vertical-align:top;padding-top:14px;font-size:12.5px">
                                <div><i data-lucide="mail" style="width:12px;height:12px"></i> {{ $b->email }}</div>
                                @if($b->telefon)<div style="color:var(--text-muted);margin-top:2px"><i data-lucide="phone" style="width:12px;height:12px"></i> {{ $b->telefon }}</div>@endif
                            </td>
                            <td style="vertical-align:top;padding-top:14px;font-size:12.5px;color:var(--text-secondary)">{{ $b->pozisyon ?: '—' }}</td>
                            <td style="vertical-align:top;padding-top:14px;font-size:12.5px;color:var(--text-muted)">{{ $b->lokasyon ?: '—' }}</td>
                            <td style="vertical-align:top;padding-top:12px">
                                @if($b->cv_dosya)
                                    <a href="{{ asset($b->cv_dosya) }}" target="_blank" class="table-action" style="color:var(--brand-dark)" title="CV indir"><i data-lucide="file-down"></i></a>
                                @else
                                    <span style="color:var(--text-muted);font-size:12px">—</span>
                                @endif
                            </td>
                            <td style="vertical-align:top;padding-top:14px;font-size:12px;color:var(--text-muted)">{{ $b->created_at ? $b->created_at->format('d.m.Y H:i') : '—' }}</td>
                            <td class="text-right" style="vertical-align:top;padding-top:10px">
                                <div class="table-actions" style="justify-content:flex-end">
                                    <a href="{{ route('admin.is-basvurulari.goster', $b->id) }}" class="table-action" style="color:var(--brand-dark)" title="Gör"><i data-lucide="eye"></i></a>
                                    <button type="button" class="table-action" style="color:var(--danger)" onclick="basvuruSil({{ $b->id }})" title="Sil"><i data-lucide="trash-2"></i></button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($basvurular->hasPages())
        <div style="margin-top:16px">{{ $basvurular->links() }}</div>
    @endif

    @foreach($basvurular as $b)
        <form id="bsv-del-{{ $b->id }}" action="{{ route('admin.is-basvurulari.sil', $b->id) }}" method="POST" style="display:none">
            @csrf @method('DELETE')
        </form>
    @endforeach
@endif

<script>
function basvuruSil(id) {
    if (confirm('Bu iş başvurusunu silmek istediğine emin misin?\n\nCV dosyası da silinir, işlem geri alınamaz.')) {
        document.getElementById('bsv-del-' + id).submit();
    }
}
</script>

@endsection
