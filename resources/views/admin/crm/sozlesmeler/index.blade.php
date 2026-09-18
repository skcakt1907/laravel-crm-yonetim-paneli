@extends('admin._layout')

@section('title', 'Sözleşmeler')

@push('head')
<style>
    .szl-toolbar { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:16px; }
    .szl-toolbar form { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
    .szl-chip { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:20px; font-size:13px;
        border:1px solid var(--border); background:var(--surface); color:var(--text); text-decoration:none; }
    .szl-chip.active { background:var(--brand,#6366f1); color:#fff; border-color:transparent; }
    .szl-dot { width:10px; height:10px; border-radius:50%; display:inline-block; }
    .szl-durum { font-size:11px; padding:3px 10px; border-radius:20px; font-weight:600; }
    .d-taslak    { background:#f3f4f6; color:#6b7280; }
    .d-aktif     { background:#eef2ff; color:#3730a3; }
    .d-imzalandi { background:#ecfdf5; color:#047857; }
    .d-iptal     { background:#fef2f2; color:#b91c1c; }
</style>
@endpush

@section('content')
<div class="page-head" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:18px">
    <div>
        <h1 style="font-size:22px;font-weight:800;margin:0">📄 Sözleşmeler</h1>
        <div style="color:var(--text-muted);font-size:13px;margin-top:2px">{{ number_format($toplam) }} sözleşme</div>
    </div>
    <div style="display:flex;gap:8px">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('katModal').style.display='flex'">
            <i data-lucide="folder"></i> <span>Kategoriler</span>
        </button>
        <a href="{{ route('admin.crm.sozlesmeler.create') }}" class="btn btn-primary">
            <i data-lucide="plus"></i> <span>Yeni Sözleşme</span>
        </a>
    </div>
</div>

{{-- Kategori filtre chip'leri --}}
<div class="szl-toolbar">
    <a href="{{ route('admin.crm.sozlesmeler.index') }}" class="szl-chip {{ !request('kategori') ? 'active' : '' }}">Tümü</a>
    @foreach($kategoriler as $kat)
        <a href="{{ route('admin.crm.sozlesmeler.index', ['kategori' => $kat->id]) }}"
           class="szl-chip {{ request('kategori') == $kat->id ? 'active' : '' }}">
            <span class="szl-dot" style="background:{{ $kat->renk ?: '#6366f1' }}"></span>{{ $kat->ad }}
        </a>
    @endforeach
</div>

{{-- Arama + durum filtresi --}}
<div class="szl-toolbar">
    <form action="{{ route('admin.crm.sozlesmeler.index') }}" method="GET">
        @if(request('kategori'))<input type="hidden" name="kategori" value="{{ request('kategori') }}">@endif
        <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Başlık, no, müşteri ara..." style="min-width:240px">
        <select name="durum" class="form-select" style="max-width:170px">
            <option value="">Tüm durumlar</option>
            <option value="taslak"    {{ request('durum')=='taslak' ? 'selected':'' }}>Taslak</option>
            <option value="aktif"     {{ request('durum')=='aktif' ? 'selected':'' }}>Aktif</option>
            <option value="imzalandi" {{ request('durum')=='imzalandi' ? 'selected':'' }}>İmzalandı</option>
            <option value="iptal"     {{ request('durum')=='iptal' ? 'selected':'' }}>İptal</option>
        </select>
        <button type="submit" class="btn btn-primary"><i data-lucide="filter"></i> <span>Filtrele</span></button>
        @if(request('search') || request('durum'))
        <a href="{{ route('admin.crm.sozlesmeler.index', request('kategori') ? ['kategori'=>request('kategori')] : []) }}" class="btn btn-secondary"><i data-lucide="x"></i></a>
        @endif
    </form>
</div>

<div class="section" style="padding:0">
    <div class="table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Başlık</th>
                    <th>Kategori</th>
                    <th>Müşteri / Taraf</th>
                    <th>Tutar</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                    <th class="text-right">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sozlesmeler as $s)
                <tr>
                    <td style="font-family:monospace;font-size:12px">{{ $s->sozlesme_no }}</td>
                    <td><strong>{{ $s->baslik }}</strong></td>
                    <td>
                        @if($s->kategori_ad)
                            <span class="szl-chip" style="padding:3px 10px;font-size:12px">
                                <span class="szl-dot" style="background:{{ $s->kategori_renk ?: '#6366f1' }}"></span>{{ $s->kategori_ad }}
                            </span>
                        @else <span style="color:var(--text-muted)">—</span> @endif
                    </td>
                    <td>{{ $s->musteri_ad ?: ($s->taraf_adi ?: '—') }}</td>
                    <td>{{ $s->tutar ? '₺'.number_format((float)$s->tutar, 2, ',', '.') : '—' }}</td>
                    <td><span class="szl-durum d-{{ $s->durum }}">{{ ucfirst($s->durum) }}</span></td>
                    <td style="font-size:12px">{{ $s->tarih ? \Carbon\Carbon::parse($s->tarih)->format('d.m.Y') : \Carbon\Carbon::parse($s->created_at)->format('d.m.Y') }}</td>
                    <td class="text-right" style="white-space:nowrap">
                        {{-- Hızlı görüntüleme: sözleşmeyi yeni sekmede açar, yazdırma da oradan.
                             İkon 04.08.2026'da yazıcıdan göze çevrildi — asıl iş görüntülemek. --}}
                        <a href="{{ route('admin.crm.sozlesmeler.yazdir', $s->id) }}" target="_blank" class="table-action" title="Görüntüle / Yazdır"><i data-lucide="eye"></i></a>
                        <a href="{{ route('admin.crm.sozlesmeler.word', $s->id) }}" class="table-action" title="Word indir"><i data-lucide="file-text"></i></a>
                        <a href="{{ route('admin.crm.sozlesmeler.edit', $s->id) }}" class="table-action" title="Düzenle"><i data-lucide="edit"></i></a>
                        <form action="{{ route('admin.crm.sozlesmeler.destroy', $s->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Sözleşme silinsin mi?')">
                            @csrf @method('DELETE')
                            <button class="table-action" style="color:var(--danger)" title="Sil"><i data-lucide="trash-2"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8">
                    <div class="empty-state" style="padding:40px;text-align:center">
                        <i data-lucide="file-text" class="empty-state-icon"></i>
                        <h4>Henüz sözleşme yok</h4>
                        <p>Sağ üstten "Yeni Sözleşme" ile ilk sözleşmeyi oluştur.</p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:16px">
    {{ $sozlesmeler->links() }}
</div>

{{-- Kategori yönetim modalı --}}
<div id="katModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:var(--surface,#fff);border-radius:16px;max-width:480px;width:92%;max-height:85vh;overflow:auto;padding:22px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <h3 style="margin:0;font-size:18px;font-weight:700">Sözleşme Kategorileri</h3>
            <button type="button" onclick="document.getElementById('katModal').style.display='none'" style="background:none;border:none;cursor:pointer"><i data-lucide="x"></i></button>
        </div>

        <form action="{{ route('admin.crm.sozlesmeler.kategori.ekle') }}" method="POST" style="display:flex;gap:8px;margin-bottom:16px">
            @csrf
            <input type="text" name="ad" class="form-input" placeholder="Yeni kategori adı" required style="flex:1">
            <input type="color" name="renk" value="#6366f1" style="width:44px;height:40px;border:1px solid var(--border);border-radius:8px;padding:2px;cursor:pointer">
            <button class="btn btn-primary"><i data-lucide="plus"></i></button>
        </form>

        <div style="display:flex;flex-direction:column;gap:8px">
            @foreach($kategoriler as $kat)
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border:1px solid var(--border);border-radius:10px">
                <span style="display:flex;align-items:center;gap:8px"><span class="szl-dot" style="background:{{ $kat->renk ?: '#6366f1' }}"></span>{{ $kat->ad }}</span>
                <form action="{{ route('admin.crm.sozlesmeler.kategori.sil', $kat->id) }}" method="POST" onsubmit="return confirm('Kategori silinsin mi? (Sözleşmeler silinmez, kategorisi boşalır)')">
                    @csrf @method('DELETE')
                    <button class="table-action" style="color:var(--danger)"><i data-lucide="trash-2"></i></button>
                </form>
            </div>
            @endforeach
            @if($kategoriler->isEmpty())
                <div style="color:var(--text-muted);text-align:center;padding:10px">Henüz kategori yok.</div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    if (window.lucide) lucide.createIcons();
</script>
@endpush
