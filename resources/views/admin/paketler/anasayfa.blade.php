@extends('admin._layout')

@section('title', 'Anasayfa Paketleri')

@push('head')
<style>
    /* Checkbox kart */
    .as-pkt-card {
        position: relative;
        background: var(--surface);
        border: 2px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex; flex-direction: column;
    }
    .as-pkt-card:hover { border-color: var(--brand-medium); }

    .as-pkt-card input[type="checkbox"] { display: none; }
    .as-pkt-card.selected {
        border-color: var(--brand);
        background: var(--brand-soft);
        box-shadow: 0 4px 14px rgba(184,182,46,0.2);
    }
    .as-pkt-card.selected::before {
        content: "";
        position: absolute;
        top: 10px; right: 10px;
        width: 24px; height: 24px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--brand), var(--brand-dark));
        background-image:
            linear-gradient(135deg, var(--brand), var(--brand-dark)),
            url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>');
        background-blend-mode: normal;
        background-position: center, center;
        background-repeat: no-repeat;
        background-size: cover, 14px;
    }

    .as-pkt-card.disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }
    .as-pkt-card.disabled:hover { border-color: var(--border); }

    .as-pkt-cat { font-size: 11px; color: var(--text-muted); margin-bottom: 6px; }
    .as-pkt-name { font-weight: 700; font-size: 14px; margin-bottom: 6px; line-height: 1.4; }
    .as-pkt-desc {
        font-size: 12px; color: var(--text-muted); line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .as-pkt-price {
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px dashed var(--border);
        font-weight: 700; color: var(--brand-dark);
        font-size: 13.5px;
    }

    /* Sayaç chip */
    .secim-sayac {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        background: var(--brand-soft);
        border: 1px solid rgba(184,182,46,0.3);
        border-radius: 99px;
        font-size: 13px;
        font-weight: 600;
    }
    .secim-sayac.full {
        background: rgba(16,185,129,0.12);
        border-color: rgba(16,185,129,0.4);
        color: var(--success);
    }
    .secim-sayac.over {
        background: rgba(239,68,68,0.12);
        border-color: rgba(239,68,68,0.4);
        color: var(--danger);
    }

    .sticky-save {
        position: sticky; bottom: 16px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 12px 16px;
        margin-top: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.08);
        z-index: 10;
    }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.paketler.index') }}">Paketler</a>
    <span class="sep">/</span>
    <span class="current">Anasayfa Paketleri</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="star"></i>
            Anasayfa Paketleri
        </h1>
        <div class="page-subtitle">
            Anasayfada öne çıkacak paketleri seçin · <strong style="color:var(--brand-dark)">Maksimum 4 paket</strong>
        </div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.paketler.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Paketlere Dön</span>
        </a>
    </div>
</div>

{{-- UYARI BANNER --}}
<div class="alert alert-warning" style="margin-bottom:16px;display:flex;align-items:center;gap:12px">
    <i data-lucide="info" style="width:20px;height:20px;flex-shrink:0"></i>
    <div>
        <strong>Bilgi:</strong> Seçtiğiniz her paket, <strong style="color:var(--text)">kendi kategorisi</strong>
        adına ana sayfada bir bölüm açar ve o bölümün başında görünür. Bölümün kalan kartları aynı
        kategorinin en çok incelenen paketleriyle tamamlanır. Bir paket yalnızca kendi kategorisinin
        bölümünde çıkar. Hiç paket seçilmezse ana sayfa varsayılan kategorileri gösterir.
    </div>
</div>

<form action="{{ route('admin.paketler.anasayfa.kaydet') }}" method="POST" id="anasayfaForm">
    @csrf

    {{-- ARAMA + SAYAÇ --}}
    <div class="section" style="padding:14px;margin-bottom:16px">
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <form method="GET" style="flex:1;min-width:240px;display:flex;gap:8px;margin:0" onsubmit="event.stopPropagation();">
                <input type="text" name="q" value="{{ $arama ?? '' }}"
                       placeholder="Paket adı veya açıklama ile ara..."
                       class="form-input" style="flex:1">
                <button type="submit" formaction="{{ route('admin.paketler.anasayfa') }}" formmethod="GET"
                        class="btn btn-secondary btn-sm">
                    <i data-lucide="search"></i>
                    <span>Ara</span>
                </button>
            </form>
            <div class="secim-sayac" id="sayacChip">
                <i data-lucide="check-square" style="width:16px;height:16px"></i>
                <span><span id="secilenSayisi">{{ $secilenSayisi ?? 0 }}</span> / 4 seçili</span>
            </div>
        </div>
    </div>

    {{-- PAKETLER --}}
    @if(empty($paketler) || count($paketler) === 0)
        <div class="section">
            <div class="empty-state">
                <i data-lucide="package-x" class="empty-state-icon"></i>
                <h4>Paket yok</h4>
                <p>Aramaya uygun paket bulunamadı.</p>
            </div>
        </div>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px">
            @foreach($paketler as $p)
                @php
                    $secili = (int)($p->anasayfa ?? 0) === 1;
                @endphp
                <label class="as-pkt-card {{ $secili ? 'selected' : '' }}" data-as-card>
                    <input type="checkbox" name="paket_ids[]" value="{{ $p->id }}"
                           {{ $secili ? 'checked' : '' }} class="as-pkt-checkbox">

                    @if(!empty($p->kategori_adi) || !empty($p->kategori))
                        <div class="as-pkt-cat">
                            <i data-lucide="folder" style="width:11px;height:11px;display:inline;vertical-align:middle"></i>
                            {{ $p->kategori_adi ?? $p->kategori }}
                        </div>
                    @endif

                    <div class="as-pkt-name">{{ $p->adi ?? '—' }}</div>

                    @if(!empty($p->kisa))
                        <div class="as-pkt-desc">
                            {{ \Illuminate\Support\Str::limit(strip_tags($p->kisa), 100) }}
                        </div>
                    @endif

                    @if(!empty($p->tutar))
                        <div class="as-pkt-price">
                            ₺{{ number_format($p->tutar, 0, ',', '.') }}
                        </div>
                    @endif
                </label>
            @endforeach
        </div>
    @endif

    <div class="sticky-save">
        <div style="font-size:13px;color:var(--text-muted)">
            <span id="sayacInfo">Seçili paketler anasayfada gösterilir</span>
        </div>
        <div style="display:flex;gap:10px">
            <a href="{{ route('admin.paketler.index') }}" class="btn btn-secondary">
                <i data-lucide="x"></i>
                <span>İptal</span>
            </a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i>
                <span>Seçimi Kaydet</span>
            </button>
        </div>
    </div>
</form>

<script>
(function() {
    const cards = document.querySelectorAll('[data-as-card]');
    const sayac = document.getElementById('secilenSayisi');
    const chip = document.getElementById('sayacChip');
    const MAX = 4;

    function updateSayac() {
        const secili = document.querySelectorAll('.as-pkt-checkbox:checked').length;
        sayac.textContent = secili;

        chip.classList.remove('full', 'over');
        if (secili === MAX) chip.classList.add('full');
        else if (secili > MAX) chip.classList.add('over');

        // Maks. aşıldıysa seçilmemişleri "disabled" gibi göster (ama tıklamaya izin ver, kullanıcı kararı)
        cards.forEach(c => {
            const cb = c.querySelector('.as-pkt-checkbox');
            if (!cb.checked && secili >= MAX) {
                c.classList.add('disabled');
            } else {
                c.classList.remove('disabled');
            }
        });
    }

    cards.forEach(card => {
        const cb = card.querySelector('.as-pkt-checkbox');
        cb.addEventListener('change', () => {
            if (cb.checked) card.classList.add('selected');
            else card.classList.remove('selected');
            updateSayac();
        });
    });

    updateSayac();
})();
</script>

@endsection