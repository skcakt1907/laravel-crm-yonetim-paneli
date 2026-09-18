@extends('admin._layout')

@section('title', 'Paketler')

@push('head')
<style>
    /* Paket kartı */
    .pkt-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        overflow: hidden;
        transition: all 0.2s ease;
        display: flex;
        flex-direction: column;
    }
    .pkt-card:hover {
        border-color: var(--brand-medium);
        transform: translateY(-3px);
        box-shadow: var(--shadow-md);
    }

    .pkt-cover {
        aspect-ratio: 16/9;
        background: linear-gradient(135deg, var(--brand-soft), rgba(184,182,46,0.04));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 56px;
        position: relative;
        overflow: hidden;
    }
    .pkt-cover img {
        width: 100%; height: 100%;
        object-fit: cover;
    }

    .pkt-badge-overlay {
        position: absolute;
        top: 10px;
        display: flex; gap: 6px;
    }
    .pkt-badge-overlay.left { left: 10px; }
    .pkt-badge-overlay.right { right: 10px; }

    .pkt-body { padding: 16px; flex: 1; display: flex; flex-direction: column; }

    .pkt-title {
        font-weight: 700;
        font-size: 15px;
        line-height: 1.4;
        margin: 0 0 6px;
        color: var(--text);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 42px;
    }

    .pkt-desc {
        font-size: 12.5px;
        color: var(--text-muted);
        line-height: 1.5;
        margin-bottom: 12px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 36px;
    }

    .pkt-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 12px;
    }
    .pkt-price {
        font-weight: 700;
        color: var(--brand-dark);
        font-size: 15px;
    }

    .pkt-actions { display: flex; gap: 6px; margin-top: auto; }
    .pkt-actions .btn { flex: 1; padding: 7px 10px; font-size: 12.5px; }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Paketler</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="package"></i>
            @if(!empty($ozelListe))
                Müşteriye Özel Teklifler
            @else
                Web Paketleri
            @endif
            <span class="badge badge-brand">{{ $paketler->total() }}</span>
        </h1>
        <div class="page-subtitle">
            @if(!empty($ozelListe))
                Müşterilere özel hazırlanmış teklif paketleri
            @else
                Tüm web paketlerini görüntüle, düzenle ve yönet
            @endif
        </div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.paketler.anasayfa') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="star"></i>
            <span>Anasayfa Paketleri</span>
        </a>
        @if(Route::has('admin.export.paketler'))
            <a href="{{ route('admin.export.paketler') }}" class="btn btn-secondary btn-sm">
                <i data-lucide="download"></i>
                <span>Excel İndir</span>
            </a>
        @endif
        @if(!empty($ozelListe) && Route::has('admin.paketler.teklif.create'))
            <a href="{{ route('admin.paketler.teklif.create') }}" class="btn btn-primary">
                <i data-lucide="file-plus"></i>
                <span>Yeni Teklif</span>
            </a>
        @else
            <a href="{{ route('admin.paketler.ekle') }}" class="btn btn-primary">
                <i data-lucide="plus"></i>
                <span>Yeni Paket</span>
            </a>
        @endif
    </div>
</div>

{{-- 4 STAT KARTI --}}
@php
    $totalPkt = $paketler->total();

    $aktifPkt = 0; $pasifPkt = 0; $anasayfaPkt = 0; $kategoriPkt = 0;
    try {
        if (\Schema::hasTable('yazilimlar')) {
            $cols = \Schema::getColumnListing('yazilimlar');
            $dilWhere = in_array('dil', $cols);
            $musteriWhere = in_array('musteri', $cols);

            $baseFn = function() use ($dilWhere, $musteriWhere) {
                $q = \DB::table('yazilimlar');
                if ($dilWhere) $q->where('dil', 1);
                if ($musteriWhere) $q->where('musteri', 0);
                return $q;
            };

            $aktifPkt   = (clone ($baseFn()))->where('durum', 1)->count();
            $pasifPkt   = (clone ($baseFn()))->where('durum', 0)->count();
            $anasayfaPkt = in_array('anasayfa', $cols)
                ? (clone ($baseFn()))->where('anasayfa', 1)->count() : 0;
        }
        if (\Schema::hasTable('web_kategori')) {
            $kategoriPkt = \DB::table('web_kategori')->where('durum', 1)->count();
        }
    } catch (\Throwable $e) {}
@endphp

<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6">
            <i data-lucide="package"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Paket</div>
            <div class="stat-card-value">{{ $totalPkt }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#10b981">
            <i data-lucide="check-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Aktif</div>
            <div class="stat-card-value" style="color:var(--success)">{{ $aktifPkt }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(184,182,46,0.18);color:#b8b62e">
            <i data-lucide="star"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Anasayfada</div>
            <div class="stat-card-value" style="color:var(--brand-dark)">{{ $anasayfaPkt }}</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Max 4 paket</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(139,92,246,0.18);color:#8b5cf6">
            <i data-lucide="layers"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Kategori</div>
            <div class="stat-card-value">{{ $kategoriPkt }}</div>
        </div>
    </div>
</div>

{{-- FİLTRE BARI --}}
<form method="GET" class="section" style="padding:14px;margin-bottom:16px">
    <div style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:10px;align-items:end">
        <div>
            <label class="form-label">Ara</label>
            <input type="text" name="q" value="{{ $arama ?? '' }}"
                   class="form-input" placeholder="Paket adı, açıklama veya kategori...">
        </div>
        <div>
            <label class="form-label">Kategori</label>
            <select name="kategori" class="form-select">
                <option value="">Tüm Kategoriler</option>
                @foreach($kategoriler ?? [] as $k)
                    <option value="{{ $k->id }}" @if(($kategori ?? '') == $k->id) selected @endif>
                        {{ $k->adi }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">Durum</label>
            <select name="durum" class="form-select">
                <option value="">Tümü</option>
                <option value="1" @if(($durum ?? '') === '1') selected @endif>Aktif</option>
                <option value="0" @if(($durum ?? '') === '0') selected @endif>Pasif</option>
            </select>
        </div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary btn-sm">
                <i data-lucide="search"></i>
                <span>Filtrele</span>
            </button>
            @if(!empty($arama) || !empty($kategori) || ($durum ?? '') !== '')
                <a href="{{ route('admin.paketler.index') }}" class="btn btn-ghost btn-sm" title="Temizle">
                    <i data-lucide="x"></i>
                </a>
            @endif
        </div>
    </div>
</form>

{{-- OLUŞTURULAN MÜŞTERİ TEKLİFLERİ (paket_teklifleri) --}}
@if(!empty($ozelListe) && isset($paketTeklifleri) && $paketTeklifleri->count() > 0)
    <div class="section" style="margin-bottom:20px;padding:0;overflow:hidden">
        <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px">
            <i data-lucide="file-check" style="width:18px;height:18px;color:var(--brand)"></i>
            <strong style="font-size:14px">Oluşturulan Teklifler</strong>
            <span class="badge badge-brand" style="margin-left:6px">{{ $paketTeklifleri->count() }}</span>
        </div>
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Teklif</th>
                        <th>Müşteri</th>
                        <th>Tutar</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($paketTeklifleri as $pt)
                        @php
                            $ptMusteri = trim(($pt->uye_ad ?? '') . ' ' . ($pt->uye_soyad ?? '')) ?: ($pt->uye_email ?? '—');
                            $ptDurum = $pt->durum ?? 'beklemede';
                            $ptDurumBadge = match($ptDurum) {
                                'onaylandi','odendi','paid' => ['badge-success', '✓ Onay'],
                                'reddedildi','iptal'        => ['badge-danger', '✗ Red'],
                                default                     => ['badge-warning', '⏳ Bekliyor'],
                            };
                            $ptLink = null;
                            if (!empty($pt->seo) && \Illuminate\Support\Facades\Route::has('teklif.detay.slug')) {
                                $ptLink = route('teklif.detay.slug', $pt->seo);
                            } elseif (!empty($pt->token) && \Illuminate\Support\Facades\Route::has('teklif.detay.public')) {
                                $ptLink = route('teklif.detay.public', $pt->token);
                            }
                        @endphp
                        <tr>
                            <td>
                                @if($ptLink)
                                    <a href="{{ $ptLink }}" target="_blank" style="color:var(--brand);text-decoration:none;font-weight:700">{{ $pt->baslik ?? 'Paket Teklifi' }}</a>
                                @else
                                    <strong>{{ $pt->baslik ?? 'Paket Teklifi' }}</strong>
                                @endif
                            </td>
                            <td style="font-size:13px">{{ $ptMusteri }}</td>
                            <td><strong style="color:var(--brand)">₺{{ number_format((float)($pt->toplam_tl ?? 0), 2, ',', '.') }}</strong></td>
                            <td><span class="badge {{ $ptDurumBadge[0] }}">{{ $ptDurumBadge[1] }}</span></td>
                            <td style="font-size:12px;color:var(--text-muted)">
                                {{ !empty($pt->created_at) ? \Carbon\Carbon::parse($pt->created_at)->format('d.m.Y H:i') : '—' }}
                            </td>
                            <td class="text-right">
                                <div style="display:inline-flex;gap:4px;align-items:center;justify-content:flex-end">
                                    @if($ptLink)
                                        <a href="{{ $ptLink }}" target="_blank" class="table-action" title="Teklifi Görüntüle">
                                            <i data-lucide="eye"></i>
                                        </a>
                                    @endif
                                    @if(\Illuminate\Support\Facades\Route::has('admin.paketler.teklif.duzenle'))
                                        <a href="{{ route('admin.paketler.teklif.duzenle', $pt->id) }}" class="table-action" style="color:var(--brand-dark)" title="Teklifi Düzenle">
                                            <i data-lucide="pencil"></i>
                                        </a>
                                    @endif
                                    @if(\Illuminate\Support\Facades\Route::has('admin.paketler.teklif.sil'))
                                        <button type="button" class="table-action" style="color:var(--danger)"
                                                title="Teklifi Sil" onclick="silTeklif({{ $pt->id }}, '{{ addslashes($pt->baslik ?? 'Paket Teklifi') }}')">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin:8px 0 14px;font-size:13px;color:var(--text-muted);display:flex;align-items:center;gap:6px">
        <i data-lucide="package" style="width:14px;height:14px"></i>
        <span>Aşağıda kataloğa tanımlı özel teklif paketleri listelenir.</span>
    </div>

    {{-- Teklif sil formları (HTML iç içe form yasak — dışarıda) --}}
    @if(\Illuminate\Support\Facades\Route::has('admin.paketler.teklif.sil'))
        @foreach($paketTeklifleri as $pt)
            <form id="del-teklif-{{ $pt->id }}" action="{{ route('admin.paketler.teklif.sil', $pt->id) }}" method="POST" style="display:none">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
        <script>
        function silTeklif(id, baslik) {
            if (confirm('Bu teklifi silmek istediğinize emin misiniz?\n\n' + baslik + '\n\nBu işlem geri alınamaz.')) {
                document.getElementById('del-teklif-' + id).submit();
            }
        }
        </script>
    @endif
@endif

{{-- KART GRID --}}
@php
    $ozelTeklifVar = !empty($ozelListe) && isset($paketTeklifleri) && $paketTeklifleri->count() > 0;
@endphp
@if($paketler->isEmpty() && !$ozelTeklifVar)
    <div class="section">
        <div class="empty-state">
            <i data-lucide="package-x" class="empty-state-icon"></i>
            <h4>Paket bulunamadı</h4>
            <p>
                @if(!empty($arama) || !empty($kategori) || ($durum ?? '') !== '')
                    Arama kriterlerine uygun paket yok. Filtreyi temizleyip tekrar deneyin.
                @else
                    Henüz hiç paket eklenmemiş. Yeni paket ekleyerek başlayın.
                @endif
            </p>
            <div style="margin-top:16px">
                @if(!empty($ozelListe) && Route::has('admin.paketler.teklif.create'))
                    <a href="{{ route('admin.paketler.teklif.create') }}" class="btn btn-primary">
                        <i data-lucide="file-plus"></i>
                        <span>Yeni Teklif Oluştur</span>
                    </a>
                @else
                    <a href="{{ route('admin.paketler.ekle') }}" class="btn btn-primary">
                        <i data-lucide="plus"></i>
                        <span>Yeni Paket Ekle</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
@else
    @if(!$paketler->isEmpty())
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px">
        @foreach($paketler as $p)
            @php
                // Kapak görseli fallback (3 klasör)
                $imgUrl = null;
                if (!empty($p->resim)) {
                    foreach (['tema/uploads/webpaketleri/kapak/', 'tema/uploads/webpaketleri/', 'tema/uploads/webpaketleri/kucuk/'] as $dir) {
                        if (file_exists(public_path($dir . $p->resim))) {
                            $imgUrl = asset($dir . $p->resim);
                            break;
                        }
                    }
                }
                $aktif = (int)($p->durum ?? 0) === 1;
                $anasayfada = (int)($p->anasayfa ?? 0) === 1;
                $sepetOnerli = (int)($p->sepet_oner ?? 0) === 1;
            @endphp

            <div class="pkt-card">
                {{-- Kapak --}}
                <div class="pkt-cover">
                    @if($imgUrl)
                        <img src="{{ $imgUrl }}" alt="{{ $p->adi }}" loading="lazy">
                    @else
                        <i data-lucide="package" style="width:56px;height:56px;color:var(--brand);opacity:0.4"></i>
                    @endif

                    {{-- Sağ üst: durum badge --}}
                    <div class="pkt-badge-overlay right">
                        @if($aktif)
                            <span class="badge badge-success" style="backdrop-filter:blur(4px);background:rgba(16,185,129,0.85);color:#fff">
                                <i data-lucide="check" style="width:11px;height:11px"></i>
                            </span>
                        @else
                            <span class="badge badge-neutral" style="backdrop-filter:blur(4px);background:rgba(0,0,0,0.55);color:#fff">
                                <i data-lucide="pause" style="width:11px;height:11px"></i>
                            </span>
                        @endif
                        @if($anasayfada)
                            <span class="badge badge-brand" style="backdrop-filter:blur(4px);background:rgba(184,182,46,0.95);color:#000">
                                <i data-lucide="star" style="width:11px;height:11px"></i>
                            </span>
                        @endif
                        @if($sepetOnerli)
                            <span class="badge" style="backdrop-filter:blur(4px);background:rgba(139,92,246,0.9);color:#fff" title="Sepette önerilen">
                                <i data-lucide="sparkles" style="width:11px;height:11px"></i>
                            </span>
                        @endif
                    </div>

                    {{-- Sol üst: kategori --}}
                    @if(!empty($p->kategori_adi))
                        <div class="pkt-badge-overlay left">
                            <span class="badge" style="backdrop-filter:blur(4px);background:rgba(0,0,0,0.6);color:#fff;font-size:10.5px">
                                {{ $p->kategori_adi }}
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Body --}}
                <div class="pkt-body">
                    <h3 class="pkt-title">{{ $p->adi ?? '—' }}</h3>
                    <div class="pkt-desc">{{ \Illuminate\Support\Str::limit(strip_tags($p->kisa ?? ''), 90) ?: '—' }}</div>

                    <div class="pkt-meta">
                        <div class="pkt-price">
                            @php $pktTutar = is_numeric($p->tutar ?? null) ? (float)$p->tutar : 0; @endphp
                            @if($pktTutar > 0)
                                ₺{{ number_format($pktTutar, 0, ',', '.') }}
                            @else
                                <span style="color:var(--text-muted);font-weight:500">—</span>
                            @endif
                        </div>
                        <div style="font-size:11px;color:var(--text-muted)">
                            #{{ $p->id }}
                        </div>
                    </div>

                    <div class="pkt-actions">
                        <a href="{{ route('admin.paketler.duzenle', $p->id) }}" class="btn btn-primary btn-sm">
                            <i data-lucide="edit-3"></i>
                            <span>Düzenle</span>
                        </a>
                        @if(\Illuminate\Support\Facades\Route::has('admin.paketler.sepet-oner-toggle'))
                        <button type="button" class="btn btn-ghost btn-sm"
                                title="{{ $sepetOnerli ? 'Sepet önerisinden çıkar' : 'Sepette öne çıkar' }}"
                                style="flex:0 0 auto;padding:7px 10px;color:{{ $sepetOnerli ? '#8b5cf6' : 'var(--text-muted)' }}"
                                onclick="sepetOnerToggle({{ $p->id }})">
                            <i data-lucide="sparkles"></i>
                        </button>
                        @endif
                        <button type="button" class="btn btn-ghost btn-sm" title="Sil"
                                style="color:var(--danger);flex:0 0 auto;padding:7px 10px"
                                onclick="silPaket({{ $p->id }})">
                            <i data-lucide="trash-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    {{-- Pagination --}}
    @if($paketler->hasPages())
        <div style="margin-top:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;padding:14px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md)">
            <div style="font-size:13px;color:var(--text-muted)">
                {{ $paketler->firstItem() ?? 0 }} – {{ $paketler->lastItem() ?? 0 }} / Toplam
                <strong style="color:var(--text)">{{ $paketler->total() }}</strong>
            </div>
            {{ $paketler->withQueryString()->links() }}
        </div>
    @endif

    {{-- SİL FORMLARI (HTML iç içe form yasak) --}}
    @foreach($paketler as $p)
        <form id="del-pkt-{{ $p->id }}"
              action="{{ route('admin.paketler.sil', $p->id) }}"
              method="POST" style="display:none">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
@endif

@if(!$paketler->isEmpty() && \Illuminate\Support\Facades\Route::has('admin.paketler.sepet-oner-toggle'))
    @foreach($paketler as $p)
        <form id="oner-pkt-{{ $p->id }}" action="{{ route('admin.paketler.sepet-oner-toggle', $p->id) }}" method="POST" style="display:none">
            @csrf
        </form>
    @endforeach
@endif

<script>
function silPaket(id) {
    if (confirm('Bu paketi silmek istediğinize emin misiniz? Bu işlem geri alınamaz.')) {
        document.getElementById('del-pkt-' + id).submit();
    }
}
function sepetOnerToggle(id) {
    var f = document.getElementById('oner-pkt-' + id);
    if (f) f.submit();
}
</script>

@endsection