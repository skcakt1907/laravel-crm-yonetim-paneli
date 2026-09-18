@extends('admin._layout')

@section('title', '📋 ' . ($board->adi ?? 'Kanban'))

@push('head')
<style>
    /* Kanban özel CSS — Sortable.js + drag-drop için */

    /* ── Sürükleme durumları ── */
    /* Bırakılacak yerde kalan boşluk göstergesi */
    .sortable-ghost {
        opacity: 0.4;
        background: var(--bg-subtle, #f1f5f9) !important;
    }
    /* Seçili (basılı tutulan) kart */
    .sortable-chosen {
        cursor: grabbing;
        box-shadow: 0 8px 24px rgba(0,0,0,.22) !important;
    }
    /* Sürükleme sırasında kartın hover transform'u / transition'ı devre dışı —
       yoksa Sortable'ın konumlamasıyla çakışıp kabuk/içerik ayrı kayıyor (iOS glitch). */
    .sortable-chosen,
    .sortable-ghost,
    .sortable-drag {
        transform: none !important;
        transition: none !important;
    }
    .sortable-drag {
        cursor: grabbing;
    }

    .kanban-board {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        padding-bottom: 16px;
        min-width: 100%;
        -webkit-overflow-scrolling: touch;
    }

    .kanban-list {
        min-width: 320px;
        max-width: 320px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 14px;
        flex-shrink: 0;
    }

    .kanban-list-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 12px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--border);
    }

    .yk-list-title {
        cursor: pointer;
        flex: 1;
        padding: 4px 6px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 14px;
        color: var(--text);
        transition: background 0.15s;
    }
    .yk-list-title:hover { background: var(--brand-soft); }

    .kanban-list-body {
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-height: 24px;
    }

    .kanban-card {
        background: var(--bg-elevated);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        cursor: pointer;
        overflow: hidden;
        transition: border-color 0.15s, transform 0.15s;
        touch-action: manipulation;
    }
    .kanban-card:hover {
        border-color: var(--brand);
        transform: translateY(-1px);
    }

    .kanban-card-color {
        height: 6px;
        width: 100%;
    }

    .kanban-card-body {
        padding: 10px 12px;
    }

    .kanban-card-title {
        font-weight: 600;
        font-size: 13px;
        color: var(--text);
        margin-bottom: 4px;
    }

    .kanban-card-desc {
        font-size: 11.5px;
        color: var(--text-muted);
        margin-bottom: 6px;
        line-height: 1.5;
    }

    .add-list-card {
        min-width: 280px;
        flex-shrink: 0;
    }
    .add-list-btn {
        width: 100%;
        background: var(--bg-subtle);
        border: 2px dashed var(--border);
        border-radius: var(--radius-lg);
        color: var(--text-muted);
        font-size: 14px;
        font-weight: 600;
        padding: 18px;
        cursor: pointer;
        transition: all 0.15s;
        text-align: left;
        display: flex;
        align-items: center;
        gap: 10px;
        font-family: inherit;
    }
    .add-list-btn:hover {
        background: var(--brand-soft);
        border-color: var(--brand);
        color: var(--brand);
    }

    .delete-list-btn {
        background: transparent;
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: rgba(239, 68, 68, 0.7);
        width: 28px;
        height: 28px;
        border-radius: 6px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        flex-shrink: 0;
        transition: all 0.15s;
    }
    .delete-list-btn:hover {
        background: rgba(239, 68, 68, 0.12);
        border-color: var(--danger);
        color: var(--danger);
    }

    /* Card modal — Trello-style */
    .card-modal-content {
        max-width: 920px;
        width: 100%;
        max-height: 90vh;
        display: flex;
        flex-direction: row;
        overflow: hidden;
        background: var(--bg-elevated);
        border: 1px solid var(--border);
        border-radius: var(--radius-xl);
    }

    .card-modal-left {
        flex: 1 1 auto;
        min-width: 0;
        padding: 20px;
        overflow-y: auto;
        border-right: 1px solid var(--border);
    }

    .card-modal-right {
        flex: 0 0 340px;
        padding: 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 18px;
        min-width: 0;
    }

    @media (max-width: 860px) {
        /* Mobilde modal dikey: dış kapsayıcının KENDİSİ scroll olur,
           iç panellerin ayrı scroll'u kalkar (yoksa alttaki dosya-ekle
           alanına ulaşılamıyordu — scroll hapsi oluyordu). */
        .card-modal-content {
            flex-direction: column;
            max-height: 92vh;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        .card-modal-left {
            flex: 0 0 auto;
            border-right: none;
            border-bottom: 1px solid var(--border);
            overflow-y: visible;
        }
        .card-modal-right {
            flex: 0 0 auto;
            overflow-y: visible;
        }
    }

    /* Filtre bar */
    .kanban-filter {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 10px 14px;
        margin-bottom: 14px;
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }
    .kanban-filter .form-input,
    .kanban-filter .form-select { font-size: 12px; padding: 6px 10px; height: 34px; }

    /* Add card form */
    .add-card-form input[type="text"] {
        font-size: 13px;
        padding: 9px 12px;
    }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.kanban.index') }}">Kanban</a>
    <span class="sep">/</span>
    <span class="current">{{ $board->adi ?? 'Pano' }}</span>
</div>

{{-- Page Header --}}
<div class="page-header">
    <div>
        <h1 class="page-title">📋 {{ $board->adi ?? 'Pano' }}
            @if(!empty($board->ozel))
                <span style="background:rgba(0,0,0,.08);font-size:12px;font-weight:600;padding:3px 8px;border-radius:6px;vertical-align:middle">🔒 Özel</span>
            @else
                <span style="background:var(--brand-soft);color:var(--brand-hover);font-size:12px;font-weight:600;padding:3px 8px;border-radius:6px;vertical-align:middle">👥 Ortak</span>
            @endif
            @if(!empty($board->kilitli))
                <span style="background:#ef4444;color:#fff;font-size:12px;font-weight:600;padding:3px 8px;border-radius:6px;vertical-align:middle">🔒 Kilitli</span>
            @endif
        </h1>
        <div class="page-subtitle">{{ count($board->lists ?? []) }} liste · sürükle bırak ile düzenle</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.crm.kanban.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Panolara Dön</span>
        </a>
        <a href="{{ url('admin/crm/kanban/'.$board->id.'/takvim') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="calendar"></i>
            <span>Takvim</span>
        </a>
        <a href="{{ url('admin/crm/kanban/'.$board->id.'/arsiv') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="archive"></i>
            <span>Arşiv</span>
        </a>
        @if($board->olusturan_id == session('admin_id'))
        <button type="button" id="kilitBtn" onclick="ykToggleLock()" class="btn btn-sm {{ !empty($board->kilitli) ? 'btn-danger' : 'btn-secondary' }}" title="Kilitliyken sadece siz düzenleyebilirsiniz">
            <i data-lucide="{{ !empty($board->kilitli) ? 'lock' : 'unlock' }}"></i>
            <span>{{ !empty($board->kilitli) ? 'Kilitli' : 'Kilitle' }}</span>
        </button>
        @endif
        @if($canEdit)
        <a href="{{ route('admin.crm.kanban.edit', $board->id) }}" class="btn btn-secondary btn-sm">
            <i data-lucide="edit-2"></i>
            <span>Düzenle</span>
        </a>
        @endif
    </div>
</div>

@if(!empty($board->kilitli))
<div class="alert {{ $canEdit ? 'alert-warning' : 'alert-info' }}" style="margin-bottom:14px">
    🔒 Bu pano <strong>kilitli</strong>. {{ $canEdit ? 'Sahibi olduğunuz için düzenleyebilirsiniz.' : 'Yalnızca panoyu oluşturan düzenleyebilir; siz görüntüleme modundasınız.' }}
</div>
@endif

{{-- Filtre Çubuğu --}}
<div class="kanban-filter">
    <span style="font-size:11px;color:var(--brand);font-weight:700;text-transform:uppercase;letter-spacing:0.06em">🔍 Filtre</span>
    <input type="text" id="filterBaslik" oninput="ykFilter()" placeholder="Kart ara..." class="form-input" style="flex:1;min-width:160px">
    <select id="filterOncelik" onchange="ykFilter()" class="form-select" style="width:auto">
        <option value="">Tüm Öncelikler</option>
        <option value="acil">🔴 Acil</option>
        <option value="yuksek">🟠 Yüksek</option>
        <option value="normal">🟡 Normal</option>
        <option value="dusuk">🟢 Düşük</option>
    </select>
    <select id="filterUye" onchange="ykFilter()" class="form-select" style="width:auto">
        <option value="">Tüm Üyeler</option>
        @foreach($yoneticiler as $yn)
            <option value="{{ $yn->id }}">{{ $yn->adi }}</option>
        @endforeach
    </select>
    <select id="filterDurum" onchange="ykFilter()" class="form-select" style="width:auto">
        <option value="">Tüm Durumlar</option>
        <option value="aktif">Aktif</option>
        <option value="tamamlandi">Tamamlandı</option>
    </select>
    <button onclick="ykFilterTemizle()" class="btn btn-ghost btn-sm" style="height:34px">
        <i data-lucide="x"></i>
        <span>Temizle</span>
    </button>
    <span id="filterSonuc" style="font-size:11px;color:var(--text-muted)"></span>
</div>

{{-- Kanban Board --}}
<div style="display:flex;justify-content:flex-end;align-items:center;gap:8px;margin-bottom:10px">
    <button type="button" onclick="ykUsttenListeEkle()" class="btn btn-primary btn-sm" style="height:34px">
        <i data-lucide="plus"></i>
        <span>Yeni Liste</span>
    </button>
</div>
<div style="overflow-x:auto;padding-bottom:8px">
<div id="kanbanLists" data-board-id="{{ $board->id }}" data-base-url="{{ url('/') }}" class="kanban-board">

@forelse($board->lists ?? [] as $list)
<div data-list-id="{{ $list->id }}" class="kanban-list">

    <div class="kanban-list-header">
        <div class="cursor-move" style="flex:1">
            <span class="yk-list-title" data-list-id="{{ $list->id }}" onclick="ykEditListTitle(this)" title="Düzenlemek için tıkla">
                {{ $list->adi ?? 'Liste' }}
            </span>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">{{ count($list->cards ?? []) }} kart</div>
        </div>
        <button type="button" onclick="deleteList({{ $list->id }}, {{ Illuminate\Support\Js::from($list->adi ?? 'Liste') }}, {{ count($list->cards ?? []) }})" title="Listeyi sil" class="delete-list-btn">
            <i data-lucide="trash-2" style="width:14px;height:14px"></i>
        </button>
    </div>

    <div class="kanban-list-body" data-list-id="{{ $list->id }}">
        @foreach($list->cards ?? [] as $card)
            @php
                $oncelikRenk = ['dusuk'=>'#22c55e','normal'=>'#b8b62e','yuksek'=>'#f97316','acil'=>'#ef4444'][$card->oncelik ?? 'normal'] ?? '#b8b62e';
                $oncelikIkon = ['dusuk'=>'🟢','normal'=>'🟡','yuksek'=>'🟠','acil'=>'🔴'][$card->oncelik ?? 'normal'] ?? '🟡';
                $sonTarihGun = null;
                if (!empty($card->son_tarih)) {
                    try { $sonTarihGun = (int) \Carbon\Carbon::now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($card->son_tarih)->startOfDay(), false); } catch (\Throwable $e) {}
                }
                $baslangicTr = null;
                if (!empty($card->baslangic_tarihi)) {
                    try { $baslangicTr = \Carbon\Carbon::parse($card->baslangic_tarihi); } catch (\Throwable $e) {}
                }
                $etiketler = is_string($card->etiketler ?? null) ? (json_decode($card->etiketler, true) ?: []) : [];
                // Kart linkleri: string/çift-encode/array tüm formatları tolere et
                $kartLinkler = $card->linkler ?? [];
                if (is_string($kartLinkler)) { $kartLinkler = json_decode($kartLinkler, true) ?: []; }
                if (is_string($kartLinkler)) { $kartLinkler = json_decode($kartLinkler, true) ?: []; }
                $kartLinkler = is_array($kartLinkler) ? array_values(array_filter($kartLinkler, fn($l) => is_array($l) && !empty($l['url']))) : [];
            @endphp
            <div data-card-id="{{ $card->id }}" data-list-id="{{ $list->id }}"
                 data-baslik="{{ $card->baslik ?? '' }}"
                 data-aciklama="{{ $card->aciklama ?? '' }}"
                 data-son-tarih="{{ $card->son_tarih ? \Carbon\Carbon::parse($card->son_tarih)->format('Y-m-d') : '' }}"
                 data-baslangic-tarihi="{{ $baslangicTr ? $baslangicTr->format('Y-m-d') : '' }}"
                 data-oncelik="{{ $card->oncelik ?? 'normal' }}"
                 data-etiketler="{{ implode(', ', $etiketler) }}"
                 data-linkler="{{ json_encode($kartLinkler, JSON_UNESCAPED_UNICODE) }}"
                 data-renk="{{ $card->renk ?? '' }}"
                 data-kapak="{{ $card->kapak_url ?? '' }}"
                 data-atanan-id="{{ $card->atanan_id ?? '' }}"
                 data-durum="{{ $card->durum ?? 'aktif' }}"
                 onclick="openCardModal(this)"
                 class="kanban-card"
                 style="border-left:3px solid {{ $oncelikRenk }}">

                @if(!empty($card->kapak_url))
                    <div class="kanban-card-kapak" style="margin:-1px -1px 8px;border-radius:8px 8px 0 0;overflow:hidden;height:120px;background:var(--bg-subtle)">
                        <img src="{{ $card->kapak_url }}" alt="kapak" style="width:100%;height:100%;object-fit:cover;display:block">
                    </div>
                @elseif(!empty($card->renk))
                    <div class="kanban-card-color" style="background:{{ $card->renk }}"></div>
                @endif

                <div class="kanban-card-body">
                    @if(!empty($etiketler))
                    <div style="display:flex;gap:3px;flex-wrap:wrap;margin-bottom:6px">
                        @foreach($etiketler as $tag)
                            <span class="badge badge-brand" style="font-size:9.5px;padding:1px 6px">{{ $tag }}</span>
                        @endforeach
                    </div>
                    @endif

                    <div class="kanban-card-title">{{ $card->baslik ?? '—' }}</div>

                    @if(!empty($card->aciklama))
                        <div class="kanban-card-desc">{{ Str::limit($card->aciklama, 60) }}</div>
                    @endif

                    <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;flex-wrap:wrap;margin-top:6px">
                        <div style="display:flex;align-items:center;gap:6px">
                            <span title="Öncelik" style="color:{{ $oncelikRenk }};font-size:12px">{{ $oncelikIkon }}</span>
                            @if($baslangicTr)
                                <span style="font-size:10px;color:var(--text-muted)" title="Başlangıç: {{ $baslangicTr->format('d.m.Y') }}">▶ {{ $baslangicTr->format('d.m') }}</span>
                            @endif
                            @if($sonTarihGun !== null)
                                @if($sonTarihGun < 0)
                                    <span class="badge badge-danger" style="font-size:9.5px;padding:1px 5px" title="Tarih geçti">⚠️ {{ abs($sonTarihGun) }}g gecikti</span>
                                @elseif($sonTarihGun === 0)
                                    <span class="badge badge-warning" style="font-size:9.5px;padding:1px 5px" title="Son gün bugün">⏰ Bugün</span>
                                @elseif($sonTarihGun <= 3)
                                    <span class="badge badge-warning" style="font-size:9.5px;padding:1px 5px" title="Yaklaşıyor">⏰ {{ $sonTarihGun }}g kaldı</span>
                                @else
                                    <span style="font-size:10px;color:var(--text-muted)">📅 {{ $sonTarihGun }}g</span>
                                @endif
                            @endif
                            @if(!empty($kartLinkler))
                                <span style="font-size:10px;color:var(--text-muted)" title="{{ count($kartLinkler) }} link">🔗 {{ count($kartLinkler) }}</span>
                            @endif
                        </div>

                        @if(!empty($card->atanan_id))
                            @php $atananYn = $yoneticiler->firstWhere('id', $card->atanan_id); @endphp
                            <div style="width:22px;height:22px;border-radius:50%;background:linear-gradient(135deg,var(--brand),#8a8a1f);display:flex;align-items:center;justify-content:center;color:#000;font-weight:700;font-size:10px" title="{{ $atananYn?->adi ?? 'Atanan' }}">
                                {{ strtoupper(substr($atananYn?->adi ?? 'U', 0, 1)) }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Yeni kart formu --}}
    <form class="add-card-form" data-list-id="{{ $list->id }}" onsubmit="return addCard(event, this, {{ $list->id }})" style="margin-top:10px">
        <input type="text" name="baslik" placeholder="✏️ Kart başlığı..." required class="form-input">
        <div style="display:flex;gap:6px;margin-top:6px">
            <button type="submit" class="btn btn-primary btn-sm" style="flex:1">
                <i data-lucide="plus"></i>
                <span>Ekle</span>
            </button>
            <button type="button" onclick="this.closest('form').reset()" class="btn btn-ghost btn-sm">
                <i data-lucide="x"></i>
            </button>
        </div>
    </form>

</div>
@empty
<div class="section" style="min-width:300px;text-align:center;color:var(--text-muted)">
    📋 Henüz liste yok. Sağdaki "+ Yeni Liste" ile başla.
</div>
@endforelse

{{-- Yeni Liste Ekleme Paneli --}}
<div class="add-list-card" id="ykAddListPanel">
    <button id="ykAddListBtn" onclick="ykToggleAddList(true)" class="add-list-btn">
        <span style="font-size:18px">＋</span>
        <span>Yeni Liste Ekle</span>
    </button>
    <form id="ykAddListForm" onsubmit="return addList(event, this)" style="display:none;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:12px">
        <input type="text" name="adi" id="ykAddListInput" placeholder="Liste adı..." required class="form-input" style="margin-bottom:8px">
        <div style="display:flex;gap:6px">
            <button type="submit" class="btn btn-primary btn-sm" style="flex:1">
                <i data-lucide="plus"></i>
                <span>Ekle</span>
            </button>
            <button type="button" onclick="ykToggleAddList(false)" class="btn btn-ghost btn-sm">
                <i data-lucide="x"></i>
            </button>
        </div>
    </form>
</div>

</div>
</div>

{{-- ═══════════════════════════════════════════════════════
     CARD MODAL — drag/drop'a dokunmadık, sadece görsel
     ═══════════════════════════════════════════════════════ --}}
<div id="cardModal" class="modal-backdrop" onclick="if(event.target===this)closeCardModal()">
    <div class="card-modal-content">

        {{-- SOL: Kart Bilgileri --}}
        <div class="card-modal-left">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
                <h2 style="font-size:16px;font-weight:700;margin:0">✏️ Kart Düzenle</h2>
                <button type="button" onclick="closeCardModal()" class="icon-btn">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div style="display:flex;flex-direction:column;gap:12px">
                <div class="form-group">
                    <label class="form-label">Başlık</label>
                    <input type="text" id="cardBaslik" required class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">Açıklama <span style="font-size:10px;color:var(--text-muted)">— düzenlemek için tıkla ⤢ · linkler tıklanabilir</span></label>
                    <textarea id="cardAciklama" style="display:none"></textarea>
                    <div id="cardAciklamaView" class="form-textarea" onclick="ykAciklamaAc()"
                         style="cursor:pointer;min-height:240px;max-height:340px;overflow-y:auto;white-space:pre-wrap;word-break:break-word;line-height:1.65;font-size:13.5px"></div>
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px">
                    <div class="form-group">
                        <label class="form-label">Öncelik</label>
                        <select id="cardOncelik" class="form-select">
                            <option value="dusuk">🟢 Düşük</option>
                            <option value="normal">🟡 Normal</option>
                            <option value="yuksek">🟠 Yüksek</option>
                            <option value="acil">🔴 Acil</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Başlangıç <span style="font-size:10px;color:var(--text-muted)">(opsiyonel)</span></label>
                        <input type="date" id="cardBaslangic" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Son Tarih <span style="font-size:10px;color:var(--text-muted)">(opsiyonel)</span></label>
                        <input type="date" id="cardSonTarih" class="form-input">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">🔗 Linkler</label>
                    <div id="cardLinklerList" style="display:flex;flex-direction:column;gap:6px;margin-bottom:8px"></div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        <input type="text" id="cardLinkBaslik" placeholder="Başlık (ör: Örnek afiş)" class="form-input" style="flex:1;min-width:120px">
                        <input type="text" id="cardLinkUrl" placeholder="https://..." class="form-input" style="flex:1.4;min-width:160px">
                        <button type="button" onclick="ykAddLink()" class="btn btn-secondary btn-sm" style="white-space:nowrap">+ Ekle</button>
                    </div>
                    <small class="form-help">Görevden taşınan dosyalar da burada listelenir — tıklayınca yeni sekmede açılır. Kaydet'e basmayı unutma.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Kart Rengi</label>
                    <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                        @foreach(['', '#ef4444','#f97316','#f59e0b','#22c55e','#06b6d4','#6366f1','#ec4899','#b8b62e'] as $renk)
                            <button type="button" onclick="ykSetRenk('{{ $renk }}')"
                                id="ykRenkBtn_{{ $loop->index }}"
                                style="width:26px;height:26px;border-radius:6px;border:2px solid transparent;cursor:pointer;background:{{ $renk ?: 'var(--bg-subtle)' }};flex-shrink:0"
                                title="{{ $renk ?: 'Renk yok' }}">{{ $renk ? '' : '✕' }}</button>
                        @endforeach
                        <input type="hidden" id="cardRenk" value="">
                        <span id="cardRenkPreview" style="font-size:11px;color:var(--text-muted)">Seçilmedi</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Kapak Görseli</label>
                    <div id="cardKapakPreviewWrap" style="display:none;position:relative;margin-bottom:8px;border-radius:8px;overflow:hidden;border:1px solid var(--border)">
                        <img id="cardKapakPreview" src="" alt="kapak" style="width:100%;max-height:160px;object-fit:cover;display:block">
                        <button type="button" onclick="ykDeleteKapak()" title="Kapağı kaldır"
                            style="position:absolute;top:6px;right:6px;background:rgba(0,0,0,.6);color:#fff;border:none;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:12px">🗑️</button>
                    </div>
                    <div style="display:flex;gap:6px;align-items:center">
                        <input type="file" id="cardKapakFile" accept="image/*" class="form-input" style="flex:1">
                        <button type="button" onclick="ykUploadKapak()" class="btn btn-primary btn-sm" style="white-space:nowrap">
                            <i data-lucide="image"></i>
                            <span>Yükle</span>
                        </button>
                    </div>
                    <small class="form-help">JPG/PNG/WEBP — maks. 5MB. Kapak kartın üstünde görünür.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Listeye Taşı</label>
                    <div style="display:flex;gap:6px">
                        <select id="cardHedefListe" class="form-select" style="flex:1">
                            <option value="">— Liste seç —</option>
                            @foreach($board->lists ?? [] as $liste)
                                <option value="{{ $liste->id }}">{{ $liste->baslik ?? $liste->adi ?? 'Liste' }}</option>
                            @endforeach
                        </select>
                        <button type="button" onclick="ykMoveTo()" class="btn btn-secondary btn-sm" style="white-space:nowrap">
                            <i data-lucide="arrow-right"></i>
                            <span>Taşı</span>
                        </button>
                    </div>
                </div>

                <div style="display:flex;justify-content:space-between;gap:8px;padding-top:12px;border-top:1px solid var(--border)">
                    <div style="display:flex;gap:4px">
                        <button type="button" onclick="deleteCard()" class="btn btn-ghost btn-sm" style="color:var(--danger)" title="Sil">
                            <i data-lucide="trash-2"></i>
                        </button>
                        <button type="button" onclick="ykCopyCard()" class="btn btn-ghost btn-sm" title="Kopyala">
                            <i data-lucide="copy"></i>
                        </button>
                        <button type="button" onclick="ykArchiveCard()" class="btn btn-ghost btn-sm" title="Arşivle">
                            <i data-lucide="archive"></i>
                        </button>
                    </div>
                    <div style="display:flex;gap:6px">
                        <button type="button" onclick="closeCardModal()" class="btn btn-secondary btn-sm">İptal</button>
                        <button type="button" onclick="saveCard()" class="btn btn-primary btn-sm">
                            <i data-lucide="save"></i>
                            <span>Kaydet</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- SAĞ: Üyeler + Checklist + Yorumlar + Dosyalar + Aktivite --}}
        <div class="card-modal-right">

            {{-- Üyeler --}}
            <div>
                <div class="section-title">
                    <i data-lucide="users"></i>
                    <span>Üyeler</span>
                </div>
                <div id="ykUyelerDiv" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px"></div>
                <div style="display:flex;gap:6px;align-items:center">
                    <select id="ykUyeSelect" class="form-select" style="flex:1">
                        <option value="">— Yönetici seç —</option>
                        @foreach($yoneticiler as $yn)
                            <option value="{{ $yn->id }}">{{ $yn->adi }} ({{ $yn->kullaniciadi }})</option>
                        @endforeach
                    </select>
                    <button type="button" onclick="ykToggleMember(document.getElementById('ykUyeSelect').value)" class="btn btn-primary btn-sm" style="white-space:nowrap">
                        <i data-lucide="plus"></i>
                        <span>Ekle</span>
                    </button>
                </div>
            </div>

            {{-- Checklist --}}
            <div>
                <div class="section-title">
                    <i data-lucide="check-square"></i>
                    <span>Checklist</span>
                </div>
                <div id="ykChecklistDiv" style="margin-bottom:10px"></div>
                <div id="ykNewChecklistWrap" style="display:none;margin-bottom:8px">
                    <div style="display:flex;gap:6px;align-items:center">
                        <input type="text" id="ykNewChecklistInput" placeholder="Checklist başlığı..." class="form-input" style="flex:1">
                        <button type="button" onclick="ykCreateChecklist(document.getElementById('ykNewChecklistInput').value)" class="btn btn-primary btn-sm">Oluştur</button>
                        <button type="button" onclick="document.getElementById('ykNewChecklistWrap').style.display='none';document.getElementById('ykNewChecklistInput').value=''" class="btn btn-ghost btn-sm">
                            <i data-lucide="x"></i>
                        </button>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('ykNewChecklistWrap').style.display='flex';document.getElementById('ykNewChecklistInput').focus()" class="btn btn-secondary btn-sm" style="width:100%">
                    <i data-lucide="plus"></i>
                    <span>Yeni Checklist Ekle</span>
                </button>
            </div>

            {{-- Yorumlar --}}
            <div>
                <div class="section-title">
                    <i data-lucide="message-circle"></i>
                    <span>Yorumlar</span>
                </div>
                <div id="yorumlarDiv" style="margin-bottom:10px"></div>
                <div style="display:flex;gap:6px;align-items:flex-end">
                    <textarea id="yorumText" rows="2" placeholder="Yorum yaz..." class="form-textarea" style="flex:1;min-height:50px"></textarea>
                    <button id="yorumBtn" type="button" onclick="submitComment()" class="btn btn-primary btn-sm" style="white-space:nowrap">
                        <i data-lucide="send"></i>
                        <span>Gönder</span>
                    </button>
                </div>
            </div>

            {{-- Dosya Ekleri --}}
            <div>
                <div class="section-title">
                    <i data-lucide="paperclip"></i>
                    <span>Dosya Ekleri</span>
                </div>
                <div id="eklerDiv" style="margin-bottom:10px"></div>
                <div style="display:flex;gap:6px;align-items:center">
                    <input type="file" id="attachFile" class="form-input" style="flex:1">
                    <button id="attachBtn" type="button" onclick="submitAttachment()" class="btn btn-primary btn-sm" style="white-space:nowrap">
                        <i data-lucide="upload"></i>
                        <span>Yükle</span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Sortable.js + kanban.js (sayfayı yenilemeden bırakıyoruz, JS bozulmaz) --}}
{{-- Açıklama yan paneli — sağdan kayarak açılır --}}
<div id="aciklamaDrawer" style="position:fixed;top:0;right:0;height:100%;width:min(480px,92vw);background:var(--surface,#1c1c1c);box-shadow:-8px 0 30px rgba(0,0,0,.35);z-index:99999;transform:translateX(100%);transition:transform .25s ease;display:flex;flex-direction:column;padding:18px;border-left:1px solid var(--border)">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
        <h3 style="margin:0;font-size:15px;font-weight:700">📝 Açıklama</h3>
        <button type="button" onclick="ykAciklamaKapat()" class="btn btn-primary btn-sm">✓ Tamam</button>
    </div>
    <textarea id="aciklamaDrawerText" class="form-textarea" style="flex:1;resize:none;font-size:14px;line-height:1.65" placeholder="Kart açıklamasını buraya yaz..."></textarea>
    <small class="form-help" style="margin-top:8px">Tamam'a basınca metin karta aktarılır; kalıcı olması için kartı Kaydet.</small>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script src="{{ asset('yonetim/js/kanban.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof initKanban === 'function') initKanban();
});

{{-- KANBAN JS — orijinal kod, dokunulmadı --}}
@verbatim
</script>
<script>
@endverbatim
const _CSRF=document.querySelector('meta[name="csrf-token"]').content;
const _URLS={addList:@json(route('admin.crm.kanban.lists.store',['boardId'=>$board->id])),addCardTpl:@json(route('admin.crm.kanban.cards.store',['boardId'=>$board->id,'listId'=>'__LID__']))};
const _CARD_URL=@json(url('admin/crm/kanban/'.$board->id.'/lists'));
const _BOARD_BASE=@json(url('admin/crm/kanban/'.$board->id));

// Pano kilitle / aç (sadece sahibi)
const _TOGGLE_LOCK_URL=@json(route('admin.crm.kanban.toggle-lock', ['boardId'=>$board->id]));
function ykToggleLock(){
    const btn=document.getElementById('kilitBtn'); if(btn) btn.disabled=true;
    fetch(_TOGGLE_LOCK_URL,{method:'POST',headers:{'X-CSRF-TOKEN':_CSRF,'Accept':'application/json'}})
      .then(r=>r.json()).then(d=>{ if(d.success){ location.reload(); } else { alert(d.message||'İşlem başarısız'); if(btn) btn.disabled=false; } })
      .catch(e=>{ alert('Hata: '+e.message); if(btn) btn.disabled=false; });
}

function addCard(e,f,listId){e.preventDefault();const fd=new FormData(f);fetch(_URLS.addCardTpl.replace('__LID__',listId),{method:'POST',headers:{'X-CSRF-TOKEN':_CSRF,'Accept':'application/json'},body:fd}).then(r=>r.json()).then(d=>{if(d.success||d.id||d.card){location.reload();}else{alert('Hata: '+(d.message||'Kart eklenemedi'));}}).catch(err=>{alert('Hata: '+err.message);});return false;}
function addList(e,f){e.preventDefault();const fd=new FormData(f);fetch(_URLS.addList,{method:'POST',headers:{'X-CSRF-TOKEN':_CSRF,'Accept':'application/json'},body:fd}).then(r=>r.json()).then(d=>{if(d.success||d.id||d.list){location.reload();}else{alert('Hata: '+(d.message||'Liste eklenemedi'));}}).catch(err=>{alert('Hata: '+err.message);});return false;}

function ykToggleAddList(open){const btn=document.getElementById('ykAddListBtn');const form=document.getElementById('ykAddListForm');if(open){btn.style.display='none';form.style.display='block';document.getElementById('ykAddListInput').focus();}else{btn.style.display='flex';form.style.display='none';form.reset();}}

// Üstteki "Yeni Liste" butonu → board'u en sağa kaydır + paneli aç
function ykUsttenListeEkle(){
    var board=document.getElementById('kanbanLists');
    if(board){ board.scrollTo({left:board.scrollWidth,behavior:'smooth'}); }
    setTimeout(function(){ ykToggleAddList(true); }, 300);
}

// Fare tekerleği ile yatay kaydırma (liste içi dikey scroll'a dokunmadan)
(function(){
    var board=document.getElementById('kanbanLists');
    if(!board) return;
    board.addEventListener('wheel', function(e){
        if(e.deltaY===0) return;
        board.scrollLeft += e.deltaY;
        e.preventDefault();
    }, {passive:false});
})();

function deleteList(id, adi, kartSayisi){
    let msg = 'Liste silinsin mi?\n\n"' + adi + '"';
    if(kartSayisi > 0) msg += '\n\n⚠️ Bu liste ' + kartSayisi + ' kart içeriyor. Silersen kartlar da silinir!';
    if(!confirm(msg)) return;
    fetch(_BOARD_BASE+'/lists/'+id,{method:'DELETE',headers:{'X-CSRF-TOKEN':_CSRF,'Accept':'application/json'}}).then(r=>r.json()).then(d=>{if(d.success){location.reload();}else{alert('Hata: '+(d.message||'Liste silinemedi'));}}).catch(err=>{alert('Hata: '+err.message);});
}

let _currentCard = null;

function openCardModal(el){
    const cardId = el.dataset.cardId;
    const listId = el.dataset.listId;
    _currentCard = {cardId, listId, el};

    document.getElementById('cardBaslik').value = el.dataset.baslik || '';
    document.getElementById('cardAciklama').value = el.dataset.aciklama || '';
    ykAciklamaRender();
    document.getElementById('cardSonTarih').value = el.dataset.sonTarih || '';
    document.getElementById('cardBaslangic').value = el.dataset.baslangicTarihi || '';
    document.getElementById('cardOncelik').value = el.dataset.oncelik || 'normal';
    document.getElementById('cardHedefListe').value = '';
    ykSetRenk(el.dataset.renk || '');
    ykSetKapakPreview(el.dataset.kapak || '');
    document.getElementById('cardKapakFile').value = '';
    try{_cardLinkler = JSON.parse(el.dataset.linkler || '[]') || [];}catch(e){_cardLinkler = [];}
    if(!Array.isArray(_cardLinkler)) _cardLinkler = [];
    ykRenderLinkler();

    document.getElementById('cardModal').classList.add('show');

    fetch(_BOARD_BASE+'/cards/'+cardId,{headers:{'Accept':'application/json','X-CSRF-TOKEN':_CSRF}})
        .then(r=>r.json()).then(d=>{
            if(d.success && d.card){
                ykRenderComments(d.card.comments||[]);
                ykRenderAttachments(d.card.attachments||[]);
                ykRenderMembers(d.card.members||[]);
                ykRenderChecklists(d.card.checklists||[]);
            }
        }).catch(err=>console.error(err));
}

function closeCardModal(){
    document.getElementById('cardModal').classList.remove('show');
    document.getElementById('aciklamaDrawer').style.transform = 'translateX(100%)';
    _currentCard = null;
}

function ykSetRenk(renk){
    document.getElementById('cardRenk').value = renk;
    document.querySelectorAll('[id^="ykRenkBtn_"]').forEach(b=>b.style.borderColor='transparent');
    const idx = ['', '#ef4444','#f97316','#f59e0b','#22c55e','#06b6d4','#6366f1','#ec4899','#b8b62e'].indexOf(renk);
    if(idx>=0){const btn=document.getElementById('ykRenkBtn_'+idx);if(btn)btn.style.borderColor='var(--brand)';}
    document.getElementById('cardRenkPreview').textContent = renk ? 'Seçildi: '+renk : 'Seçilmedi';
}

/* ── Kart linkleri ── */
let _cardLinkler = [];
function ykEsc(t){return String(t??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function ykRenderLinkler(){
    const wrap = document.getElementById('cardLinklerList');
    if(!wrap) return;
    if(!_cardLinkler.length){ wrap.innerHTML = '<div style="font-size:12px;color:var(--text-muted)">Henüz link yok.</div>'; return; }
    wrap.innerHTML = _cardLinkler.map((l,i)=>`
        <div style="display:flex;align-items:center;gap:8px;padding:7px 10px;background:var(--bg-subtle);border:1px solid var(--border);border-radius:8px">
            <a href="${ykEsc(l.url)}" target="_blank" rel="noopener" onclick="event.stopPropagation()"
               style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--brand);font-weight:600;font-size:13px;text-decoration:none"
               title="${ykEsc(l.url)}">🔗 ${ykEsc(l.baslik || l.url)}</a>
            <button type="button" onclick="ykRemoveLink(${i})" style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:14px;flex-shrink:0" title="Listeden kaldır">✕</button>
        </div>`).join('');
}
function ykAddLink(){
    const b = document.getElementById('cardLinkBaslik'), u = document.getElementById('cardLinkUrl');
    let url = (u.value || '').trim();
    if(!url){ u.focus(); return; }
    if(!/^https?:\/\//i.test(url)) url = 'https://' + url;
    _cardLinkler.push({ baslik: (b.value || '').trim() || url, url: url });
    b.value = ''; u.value = '';
    ykRenderLinkler();
}
function ykRemoveLink(i){ _cardLinkler.splice(i, 1); ykRenderLinkler(); }

/* ── Açıklama yan paneli ── */
function ykAciklamaAc(){
    document.getElementById('aciklamaDrawerText').value = document.getElementById('cardAciklama').value;
    document.getElementById('aciklamaDrawer').style.transform = 'translateX(0)';
    setTimeout(()=>document.getElementById('aciklamaDrawerText').focus(), 260);
}
function ykAciklamaKapat(){
    document.getElementById('cardAciklama').value = document.getElementById('aciklamaDrawerText').value;
    document.getElementById('aciklamaDrawer').style.transform = 'translateX(100%)';
    ykAciklamaRender();
}

/* Açıklamadaki URL'leri tıklanabilir yap */
function ykLinkify(t){
    return ykEsc(t).replace(/(https?:\/\/[^\s<]+|www\.[^\s<]+)/gi, function(u){
        var href = /^www\./i.test(u) ? 'https://' + u : u;
        return '<a href="' + href + '" target="_blank" rel="noopener" onclick="event.stopPropagation()" style="color:var(--brand);text-decoration:underline;word-break:break-all">' + u + '</a>';
    });
}
function ykAciklamaRender(){
    var v = document.getElementById('cardAciklama').value || '';
    var div = document.getElementById('cardAciklamaView');
    if(!div) return;
    div.innerHTML = v.trim() ? ykLinkify(v) : '<span style="color:var(--text-muted)">Açıklama yazmak için tıkla...</span>';
}

function saveCard(){
    if(!_currentCard) return;
    const bt = document.getElementById('cardBaslangic').value;
    const st = document.getElementById('cardSonTarih').value;
    if(bt && st && bt > st){ alert('Başlangıç tarihi, son tarihten sonra olamaz!'); return; }
    const data = {
        baslik: document.getElementById('cardBaslik').value,
        aciklama: document.getElementById('cardAciklama').value,
        baslangic_tarihi: bt || null,
        son_tarih: st || null,
        oncelik: document.getElementById('cardOncelik').value,
        renk: document.getElementById('cardRenk').value,
        linkler: _cardLinkler
    };
    fetch(_BOARD_BASE+'/lists/'+_currentCard.listId+'/cards/'+_currentCard.cardId,{
        method:'PUT',
        headers:{'X-CSRF-TOKEN':_CSRF,'Accept':'application/json','Content-Type':'application/json'},
        body:JSON.stringify(data)
    }).then(r=>r.json()).then(d=>{
        if(d.success){location.reload();}else{alert('Hata: '+(d.message||'Kaydedilemedi'));}
    }).catch(err=>alert('Hata: '+err.message));
}

function deleteCard(){
    if(!_currentCard || !confirm('Kart silinsin mi?')) return;
    fetch(_BOARD_BASE+'/lists/'+_currentCard.listId+'/cards/'+_currentCard.cardId,{
        method:'DELETE',
        headers:{'X-CSRF-TOKEN':_CSRF,'Accept':'application/json'}
    }).then(r=>r.json()).then(d=>{
        if(d.success){location.reload();}else{alert('Hata: '+(d.message||'Silinemedi'));}
    }).catch(err=>alert('Hata: '+err.message));
}

function ykMoveTo(){
    if(!_currentCard) return;
    const hedefListId = document.getElementById('cardHedefListe').value;
    if(!hedefListId){alert('Hedef liste seçin');return;}
    if(hedefListId == _currentCard.listId){alert('Aynı listede zaten');return;}
    fetch(_BOARD_BASE+'/move-card',{
        method:'POST',
        headers:{'X-CSRF-TOKEN':_CSRF,'Accept':'application/json','Content-Type':'application/json'},
        body:JSON.stringify({card_id:_currentCard.cardId, hedef_list_id:hedefListId})
    }).then(r=>r.json()).then(d=>{
        if(d.success){location.reload();}else{alert('Hata: '+(d.message||'Taşınamadı'));}
    }).catch(err=>alert('Hata: '+err.message));
}

function ykCopyCard(){
    if(!_currentCard || !confirm('Kart kopyalansın mı?')) return;
    fetch(_BOARD_BASE+'/lists/'+_currentCard.listId+'/cards/'+_currentCard.cardId+'/copy',{
        method:'POST',
        headers:{'X-CSRF-TOKEN':_CSRF,'Accept':'application/json'}
    }).then(r=>r.json()).then(d=>{
        if(d.success){location.reload();}else{alert('Hata: '+(d.message||'Kopyalanamadı'));}
    }).catch(err=>alert('Hata: '+err.message));
}

function ykArchiveCard(){
    if(!_currentCard || !confirm('Kart arşivlensin mi?')) return;
    fetch(_BOARD_BASE+'/lists/'+_currentCard.listId+'/cards/'+_currentCard.cardId+'/archive',{
        method:'POST',
        headers:{'X-CSRF-TOKEN':_CSRF,'Accept':'application/json'}
    }).then(r=>r.json()).then(d=>{
        if(d.success){location.reload();}else{alert('Hata: '+(d.message||'Arşivlenemedi'));}
    }).catch(err=>alert('Hata: '+err.message));
}

function ykEditListTitle(span){
    const oldText = span.textContent.trim();
    const input = document.createElement('input');
    input.type='text';input.value=oldText;
    input.style.cssText='padding:4px 8px;font-size:14px;width:100%;font-weight:600;border:1px solid var(--brand);border-radius:4px';
    span.replaceWith(input);input.focus();input.select();
    const save = () => {
        const newText = input.value.trim();
        if(newText && newText !== oldText){
            fetch(_BOARD_BASE+'/lists/'+span.dataset.listId,{
                method:'PUT',
                headers:{'X-CSRF-TOKEN':_CSRF,'Accept':'application/json','Content-Type':'application/json'},
                body:JSON.stringify({adi:newText})
            }).then(r=>r.json()).then(d=>{
                if(d.success){location.reload();}else{alert('Hata: '+(d.message||'Güncellenemedi'));input.replaceWith(span);}
            }).catch(()=>input.replaceWith(span));
        } else { input.replaceWith(span); }
    };
    input.addEventListener('blur', save);
    input.addEventListener('keydown', e => { if(e.key==='Enter'){save();} else if(e.key==='Escape'){input.replaceWith(span);} });
}

function ykRenderComments(comments){
    const div = document.getElementById('yorumlarDiv');
    if(!comments.length){div.innerHTML='<div style="color:var(--text-muted);font-size:12px;padding:4px 0">Henüz yorum yok.</div>';return;}
    div.innerHTML = comments.map(c=>`
        <div style="background:var(--bg-subtle);border-radius:8px;padding:8px 10px;margin-bottom:6px">
            <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text-muted);margin-bottom:3px">
                <strong style="color:var(--text)">${c.yazar_adi || 'Admin'}</strong>
                <span>${c.created_at || ''} <button onclick="ykDeleteComment(${c.id})" style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:11px">✕</button></span>
            </div>
            <div style="font-size:12.5px;color:var(--text);white-space:pre-wrap">${c.mesaj || ''}</div>
        </div>
    `).join('');
}

function ykRenderAttachments(attachments){
    const div = document.getElementById('eklerDiv');
    if(!attachments.length){div.innerHTML='<div style="color:var(--text-muted);font-size:12px;padding:4px 0">Henüz dosya eki yok.</div>';return;}
    div.innerHTML = attachments.map(a=>{
        return `
            <div style="display:flex;align-items:center;gap:8px;background:var(--bg-subtle);border-radius:6px;padding:6px 10px;margin-bottom:4px">
                <span>📎</span>
                <a href="${a.dosya_url || '#'}" target="_blank" style="flex:1;color:var(--brand);text-decoration:none;font-size:12.5px">${a.dosya_adi || 'Dosya'}</a>
                <button onclick="ykDeleteAttachment(${a.id})" style="background:none;border:none;color:var(--danger);cursor:pointer">✕</button>
            </div>
        `;
    }).join('');
}

function submitComment(){
    if(!_currentCard) return;
    const text = document.getElementById('yorumText').value.trim();
    if(!text) return;
    fetch(_BOARD_BASE+'/cards/'+_currentCard.cardId+'/comments',{
        method:'POST',
        headers:{'X-CSRF-TOKEN':_CSRF,'Accept':'application/json','Content-Type':'application/json'},
        body:JSON.stringify({mesaj:text})
    }).then(r=>r.json()).then(d=>{
        if(d.success){document.getElementById('yorumText').value='';ykRenderComments(d.comments || []);}
        else{alert('Hata: '+(d.message||'Eklenemedi'));}
    }).catch(err=>alert('Hata: '+err.message));
}

function ykDeleteComment(commentId){
    if(!_currentCard || !confirm('Yorum silinsin mi?')) return;
    fetch(_BOARD_BASE+'/cards/'+_currentCard.cardId+'/comments/'+commentId,{
        method:'DELETE',headers:{'X-CSRF-TOKEN':_CSRF,'Accept':'application/json'}
    }).then(r=>r.json()).then(d=>{if(d.success){ykRenderComments(d.comments||[]);}});
}

function ykSetKapakPreview(url){
    const wrap = document.getElementById('cardKapakPreviewWrap');
    const img = document.getElementById('cardKapakPreview');
    if(url){ img.src = url; wrap.style.display = 'block'; }
    else { img.src = ''; wrap.style.display = 'none'; }
}

function ykUploadKapak(){
    if(!_currentCard) return;
    const file = document.getElementById('cardKapakFile').files[0];
    if(!file){alert('Görsel seçin');return;}
    const fd = new FormData(); fd.append('kapak', file);
    fetch(_BOARD_BASE+'/cards/'+_currentCard.cardId+'/cover',{
        method:'POST',headers:{'X-CSRF-TOKEN':_CSRF,'Accept':'application/json'},body:fd
    }).then(r=>r.json()).then(d=>{
        if(d.success){
            ykSetKapakPreview(d.kapak_url || '');
            if(_currentCard.el) _currentCard.el.dataset.kapak = d.kapak_url || '';
            document.getElementById('cardKapakFile').value='';
        } else { alert('Hata: '+(d.message||'Kapak yüklenemedi')); }
    }).catch(err=>alert('Hata: '+err.message));
}

function ykDeleteKapak(){
    if(!_currentCard || !confirm('Kapak görseli kaldırılsın mı?')) return;
    fetch(_BOARD_BASE+'/cards/'+_currentCard.cardId+'/cover',{
        method:'DELETE',headers:{'X-CSRF-TOKEN':_CSRF,'Accept':'application/json'}
    }).then(r=>r.json()).then(d=>{
        if(d.success){
            ykSetKapakPreview('');
            if(_currentCard.el) _currentCard.el.dataset.kapak = '';
        } else { alert('Hata: '+(d.message||'Kaldırılamadı')); }
    }).catch(err=>alert('Hata: '+err.message));
}

function submitAttachment(){
    if(!_currentCard) return;
    const file = document.getElementById('attachFile').files[0];
    if(!file){alert('Dosya seçin');return;}
    const fd = new FormData(); fd.append('dosya', file);
    fetch(_BOARD_BASE+'/cards/'+_currentCard.cardId+'/attachments',{
        method:'POST',headers:{'X-CSRF-TOKEN':_CSRF,'Accept':'application/json'},body:fd
    }).then(r=>r.json()).then(d=>{
        if(d.success){document.getElementById('attachFile').value='';ykRenderAttachments(d.attachments||[]);}
        else{alert('Hata: '+(d.message||'Yüklenemedi'));}
    });
}

function ykDeleteAttachment(attachmentId){
    if(!_currentCard || !confirm('Dosya silinsin mi?')) return;
    fetch(_BOARD_BASE+'/cards/'+_currentCard.cardId+'/attachments/'+attachmentId,{
        method:'DELETE',headers:{'X-CSRF-TOKEN':_CSRF,'Accept':'application/json'}
    }).then(r=>r.json()).then(d=>{if(d.success){ykRenderAttachments(d.attachments||[]);}});
}

function ykRenderMembers(members){
    const div = document.getElementById('ykUyelerDiv');
    if(!members || !members.length){
        div.innerHTML = '<div style="color:var(--text-muted);font-size:12px">Henüz üye yok.</div>';
        return;
    }
    const ykEsc = s => String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    div.innerHTML = members.map(m=>{
        // Sunucu kanban_card_members kaydini yonetici iliskisiyle doner:
        // ad -> m.yonetici.adi, kaldirma -> yonetici_id (m.id ARA TABLO id'sidir, KULLANMA!)
        const ad  = (m.yonetici && (m.yonetici.adi || m.yonetici.kullaniciadi)) || m.adi || 'Üye';
        const yid = (m.yonetici_id != null) ? m.yonetici_id : (m.yonetici ? m.yonetici.id : 0);
        return `
            <div style="display:inline-flex;align-items:center;gap:6px;background:var(--brand-soft);color:var(--brand);padding:4px 10px;border-radius:14px;font-size:11.5px;font-weight:600">
                ${ykEsc(ad)}
                <button onclick="ykToggleMember(${parseInt(yid,10)||0})" title="Üyeyi karttan çıkar" style="background:none;border:none;color:inherit;cursor:pointer;font-size:13px;padding:0">×</button>
            </div>
        `;
    }).join('');
}

function ykToggleMember(uyeId){
    if(!_currentCard || !uyeId) return;
    fetch(_BOARD_BASE+'/cards/'+_currentCard.cardId+'/members/toggle',{
        method:'POST',
        headers:{'X-CSRF-TOKEN':_CSRF,'Accept':'application/json','Content-Type':'application/json'},
        body:JSON.stringify({uye_id:uyeId})
    }).then(r=>r.json()).then(d=>{if(d.success){ykRenderMembers(d.members||[]);document.getElementById('ykUyeSelect').value='';}});
}

function ykRenderChecklists(checklists){
    const div = document.getElementById('ykChecklistDiv');
    if(!checklists || !checklists.length){
        div.innerHTML = '<div style="color:var(--text-muted);font-size:12px">Henüz checklist yok.</div>';
        return;
    }
    div.innerHTML = checklists.map(cl=>{
        const items = (cl.items || []).map(it=>`
            <div style="display:flex;align-items:center;gap:6px;padding:4px 0;font-size:12.5px">
                <input type="checkbox" ${it.tamamlandi ? 'checked' : ''} onchange="ykToggleChecklistItem(${cl.id},${it.id})">
                <span style="${it.tamamlandi ? 'text-decoration:line-through;color:var(--text-muted)' : ''}">${it.metin || ''}</span>
            </div>
        `).join('');
        return `
            <div style="background:var(--bg-subtle);border-radius:8px;padding:10px;margin-bottom:8px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                    <strong style="font-size:12.5px">${cl.baslik || 'Checklist'}</strong>
                    <button onclick="ykDeleteChecklist(${cl.id})" style="background:none;border:none;color:var(--danger);cursor:pointer">✕</button>
                </div>
                ${items}
                <div style="display:flex;gap:4px;margin-top:6px">
                    <input type="text" id="cli_${cl.id}" placeholder="Yeni madde..." class="form-input" style="font-size:12px;padding:5px 8px;flex:1">
                    <button onclick="ykAddChecklistItem(${cl.id})" class="btn btn-primary btn-sm" style="padding:5px 10px;font-size:11px">+</button>
                </div>
            </div>
        `;
    }).join('');
}

function ykCreateChecklist(baslik){
    if(!_currentCard || !baslik.trim()) return;
    fetch(_BOARD_BASE+'/cards/'+_currentCard.cardId+'/checklists',{
        method:'POST',
        headers:{'X-CSRF-TOKEN':_CSRF,'Accept':'application/json','Content-Type':'application/json'},
        body:JSON.stringify({baslik:baslik.trim()})
    }).then(r=>r.json()).then(d=>{
        if(d.success){
            document.getElementById('ykNewChecklistInput').value='';
            document.getElementById('ykNewChecklistWrap').style.display='none';
            ykRenderChecklists(d.checklists||[]);
        }
    });
}

function ykDeleteChecklist(checklistId){
    if(!_currentCard || !confirm('Checklist silinsin mi?')) return;
    fetch(_BOARD_BASE+'/cards/'+_currentCard.cardId+'/checklists/'+checklistId,{
        method:'DELETE',headers:{'X-CSRF-TOKEN':_CSRF,'Accept':'application/json'}
    }).then(r=>r.json()).then(d=>{if(d.success){ykRenderChecklists(d.checklists||[]);}});
}

function ykAddChecklistItem(checklistId){
    const input = document.getElementById('cli_'+checklistId);
    const metin = input.value.trim();
    if(!metin) return;
    fetch(_BOARD_BASE+'/cards/'+_currentCard.cardId+'/checklists/'+checklistId+'/items',{
        method:'POST',
        headers:{'X-CSRF-TOKEN':_CSRF,'Accept':'application/json','Content-Type':'application/json'},
        body:JSON.stringify({metin})
    }).then(r=>r.json()).then(d=>{if(d.success){input.value='';ykRenderChecklists(d.checklists||[]);}});
}

function ykToggleChecklistItem(checklistId, itemId){
    fetch(_BOARD_BASE+'/cards/'+_currentCard.cardId+'/checklists/'+checklistId+'/items/'+itemId+'/toggle',{
        method:'POST',headers:{'X-CSRF-TOKEN':_CSRF,'Accept':'application/json'}
    }).then(r=>r.json()).then(d=>{if(d.success){ykRenderChecklists(d.checklists||[]);}});
}

function ykRenderActivity(activity){
    const div = document.getElementById('ykAktiviteDiv');
    if(!div) return; // Aktivite bölümü kaldırıldı
    if(!activity || !activity.length){div.innerHTML='<div style="color:var(--text-muted);font-size:12px">Henüz aktivite yok.</div>';return;}
    div.innerHTML = activity.map(a=>`
        <div style="font-size:11.5px;padding:5px 0;border-bottom:1px solid var(--border);color:var(--text-secondary)">
            <strong>${a.kullanici || 'Admin'}</strong> · ${a.aciklama || ''} <span style="color:var(--text-muted)">· ${a.created_at || ''}</span>
        </div>
    `).join('');
}

{{-- Filtre fonksiyonları --}}
function ykFilter(){
    const baslik = (document.getElementById('filterBaslik').value || '').toLowerCase();
    const oncelik = document.getElementById('filterOncelik').value;
    const uye = document.getElementById('filterUye').value;
    const durum = document.getElementById('filterDurum').value;
    let gosterilen = 0, toplam = 0;
    document.querySelectorAll('[data-card-id]').forEach(card=>{
        toplam++;
        const cBaslik = (card.dataset.baslik || '').toLowerCase();
        const cOncelik = card.dataset.oncelik || '';
        const cUye = card.dataset.atananId || '';
        const cDurum = card.dataset.durum || '';
        const match = (!baslik || cBaslik.includes(baslik))
            && (!oncelik || cOncelik === oncelik)
            && (!uye || cUye === uye)
            && (!durum || cDurum === durum);
        card.style.display = match ? '' : 'none';
        if(match) gosterilen++;
    });
    const sonuc = document.getElementById('filterSonuc');
    if(baslik || oncelik || uye || durum){
        sonuc.textContent = gosterilen + ' / ' + toplam + ' kart';
    } else {
        sonuc.textContent = '';
    }
}

function ykFilterTemizle(){
    document.getElementById('filterBaslik').value='';
    document.getElementById('filterOncelik').value='';
    document.getElementById('filterUye').value='';
    document.getElementById('filterDurum').value='';
    ykFilter();
}
</script>

{{-- Sayfa yukarı/aşağı kaydırma — sabit, sağ kenarda DİKEY ORTADA
     (sol=sidebar, sağ alt=mesaj baloncuğu ile çakışmasın diye ortada) --}}
<style>
    #ykScrollBtns{
        position:fixed; right:16px; top:50%; transform:translateY(-50%);
        z-index:900; display:flex; flex-direction:column; gap:8px;
    }
    #ykScrollBtns button{
        width:40px; height:40px; border-radius:50%;
        background:var(--brand,#b8b62e); color:#1a1d24; border:none; cursor:pointer;
        box-shadow:0 3px 12px rgba(0,0,0,.20);
        display:flex; align-items:center; justify-content:center;
        transition:transform .12s, filter .12s; opacity:.85;
    }
    #ykScrollBtns button:hover{ opacity:1; filter:brightness(.96); }
    #ykScrollBtns button:active{ transform:scale(.94); }
    #ykScrollBtns i{ width:19px; height:19px; }
    @media (max-width:640px){ #ykScrollBtns{ right:10px; } }
</style>
<div id="ykScrollBtns">
    <button type="button" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="En üste git" aria-label="En üste git">
        <i data-lucide="chevron-up"></i>
    </button>
    <button type="button" onclick="window.scrollTo({top:document.body.scrollHeight,behavior:'smooth'})" title="En alta git" aria-label="En alta git">
        <i data-lucide="chevron-down"></i>
    </button>
</div>
<script>
    if (window.lucide) { try { lucide.createIcons(); } catch(e){} }
</script>

@endsection