@extends('admin._layout')

@section('title', 'Müşteriler')

@push('head')
<style>
    /* Bu sayfaya özel ufak eklemeler */
    /* ── Modern filtre kartı ── */
    .crm-filter {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 12px 14px;
        margin-bottom: 18px;
        box-shadow: 0 2px 12px rgba(0,0,0,.05);
    }
    .crm-filter .search-wrap {
        flex: 1 1 260px;
        min-width: 200px;
        position: relative;
        display: flex;
        align-items: center;
    }
    .crm-filter .search-wrap > i,
    .crm-filter .search-wrap > svg {
        position: absolute;
        left: 14px;
        width: 17px;
        height: 17px;
        color: var(--text-muted);
        pointer-events: none;
        z-index: 1;
    }
    .crm-filter .search-wrap input {
        width: 100%;
        height: 44px;
        padding: 0 14px 0 42px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: var(--bg);
        color: var(--text);
        font-size: 14px;
        transition: border-color .15s, box-shadow .15s, background .15s;
    }
    .crm-filter .search-wrap input:focus {
        outline: none;
        border-color: var(--brand);
        box-shadow: 0 0 0 3px var(--brand-soft);
        background: var(--surface);
    }
    .crm-filter .fsel {
        position: relative;
        display: flex;
        align-items: center;
    }
    .crm-filter .fsel > i,
    .crm-filter .fsel > svg {
        position: absolute;
        left: 12px;
        width: 16px;
        height: 16px;
        color: var(--text-muted);
        pointer-events: none;
        z-index: 1;
    }
    .crm-filter select {
        height: 44px;
        min-width: 170px;
        padding: 0 32px 0 36px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: var(--bg);
        color: var(--text);
        font-size: 14px;
        cursor: pointer;
        transition: border-color .15s, box-shadow .15s;
    }
    .crm-filter select:hover { border-color: var(--brand); }
    .crm-filter select:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-soft); }
    .crm-filter .fbtn {
        height: 44px;
        padding: 0 18px;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-weight: 700;
        font-size: 14px;
        text-decoration: none;
        transition: filter .15s, background .15s, color .15s;
        white-space: nowrap;
    }
    .crm-filter .fbtn i { width: 16px; height: 16px; }
    .crm-filter .fbtn-primary { background: var(--brand); color: #1a1a1a; }
    .crm-filter .fbtn-primary:hover { filter: brightness(.93); }
    .crm-filter .fbtn-temizle { background: transparent; color: var(--text-muted); border: 1px solid var(--border); }
    .crm-filter .fbtn-temizle:hover { background: var(--bg); color: var(--text); }
    @media (max-width: 640px) {
        .crm-filter .search-wrap { flex-basis: 100%; }
        .crm-filter .fsel, .crm-filter .fsel select { flex: 1 1 auto; width: 100%; }
        .crm-filter .fbtn { flex: 1 1 auto; justify-content: center; }
    }

    .musteri-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--brand), #8a8a1f);
        color: #000;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 14px;
        flex-shrink: 0;
    }

    .musteri-info { display: flex; align-items: center; gap: 12px; }
    .musteri-info .name { font-weight: 600; color: var(--text); }
    .musteri-info .id { font-size: 11px; color: var(--text-muted); }

    .etiket-pill {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 10.5px;
        font-weight: 600;
        background: var(--brand-soft);
        color: var(--brand);
        margin: 1px 2px;
        border: 1px solid var(--brand-medium);
    }

    .durum-toggle-btn {
        background: transparent;
        border: 1px solid transparent;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all 0.15s ease;
    }
    .durum-toggle-btn.aktif { background: var(--success-soft); color: var(--success); border-color: rgba(16,185,129,0.3); }
    .durum-toggle-btn.pasif { background: var(--danger-soft); color: var(--danger); border-color: rgba(239,68,68,0.3); }

    .toplu-bar {
        position: sticky;
        bottom: 16px;
        background: var(--bg-elevated);
        border: 1px solid var(--brand-medium);
        border-radius: var(--radius-lg);
        padding: 12px 16px;
        box-shadow: var(--shadow-lg);
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 16px;
        z-index: 10;
    }

    /* Mobil ve tablet için tabloyu kart benzeri yap */
    @media (max-width: 768px) {
        .data-table thead { display: none; }
        .data-table, .data-table tbody, .data-table tr, .data-table td {
            display: block;
            width: 100%;
        }
        .data-table tr {
            border-bottom: 1px solid var(--border);
            padding: 12px 8px;
            margin-bottom: 8px;
        }
        .data-table td {
            border-bottom: none;
            padding: 6px 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-height: auto;
        }
        .data-table td::before {
            content: attr(data-label);
            font-weight: 600;
            color: var(--text-muted);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .data-table td.no-label::before { display: none; }
        .musteri-info { flex-direction: row-reverse; }
        .table-actions { justify-content: flex-end; }
    }
</style>
@endpush

@section('content')

{{-- Sayfa başlığı + Aksiyon butonları --}}
<div class="page-header">
    <div>
        <h1 class="page-title">👥 Müşteriler</h1>
        <div class="page-subtitle">
            <strong>{{ number_format($musteriToplam ?? 0) }}</strong> toplam ·
            <strong style="color:#10b981">{{ number_format($musteriSicak ?? 0) }}</strong> aktif ·
            <strong style="color:#f59e0b">{{ number_format($musteriIlimli ?? 0) }}</strong> potansiyel ·
            <strong style="color:#6b7280">{{ number_format($musteriSoguk ?? 0) }}</strong> pasif
        </div>
    </div>
    <div class="page-actions">
        @if(\Illuminate\Support\Facades\Route::has('admin.crm.musteriler.export'))
        <a href="{{ route('admin.crm.musteriler.export') }}" class="btn btn-secondary btn-sm" title="Excel İndir">
            <i data-lucide="download"></i>
            <span>Excel</span>
        </a>
        @endif
        @if(\Illuminate\Support\Facades\Route::has('admin.crm.musteriler.export-template'))
        <a href="{{ route('admin.crm.musteriler.export-template') }}" class="btn btn-secondary btn-sm" title="Şablon">
            <i data-lucide="file-text"></i>
            <span>Şablon</span>
        </a>
        @endif
        <button type="button" onclick="document.getElementById('importModal').classList.add('show')" class="btn btn-secondary btn-sm">
            <i data-lucide="upload"></i>
            <span>İçe Aktar</span>
        </button>
        @if(\Illuminate\Support\Facades\Route::has('admin.crm.musteriler.create'))
        <a href="{{ route('admin.crm.musteriler.create') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Yeni Müşteri</span>
        </a>
        @endif
    </div>
</div>

{{-- İSTATİSTİK KARTLARI (tıklanabilir — listeyi filtreler) --}}
@php
    $aktiflikOrani = ($musteriToplam ?? 0) > 0 ? round(($musteriSicak ?? 0) / $musteriToplam * 100) : 0;
    $secDurum = request('durum');
    $listeUrl = route('admin.crm.musteriler.index');
@endphp
<style>
    /* Kompakt, tıklanabilir istatistik kartları */
    .stat-grid { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; }
    .stat-grid .stat-card { padding: 12px 16px; }
    .stat-grid .stat-ic { display: none; }
    .stat-grid .stat-label { font-size: 11px; }
    .stat-grid .stat-value { font-size: 22px; }
    .stat-grid .stat-meta { font-size: 11px; }
    a.stat-card { text-decoration: none; color: inherit; display: block; transition: transform .12s, box-shadow .12s; }
    a.stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,.08); }
</style>
<div class="stat-grid">

    <a href="{{ $listeUrl }}" class="stat-card" title="Tüm müşterileri göster"
       style="--accent:#b8b62e;--soft:#f6f5e8{{ empty($secDurum) ? ';outline:2px solid #b8b62e' : '' }}">
        <div class="stat-label"><span>👥 Toplam</span></div>
        <div class="stat-value">{{ number_format($musteriToplam ?? 0) }}</div>
        <div class="stat-meta">Tüm müşteriler</div>
    </a>

    <a href="{{ $listeUrl }}?durum=aktif" class="stat-card" title="Sadece aktif müşterileri göster"
       style="--accent:#16a34a;--soft:#f0fdf4{{ $secDurum === 'aktif' ? ';outline:2px solid #10b981' : '' }}">
        <div class="stat-label"><span>✅ Aktif</span></div>
        <div class="stat-value" style="color:#10b981">{{ number_format($musteriSicak ?? 0) }}</div>
        <div class="stat-meta">%{{ $aktiflikOrani }} oran</div>
    </a>

    <a href="{{ $listeUrl }}?durum=potansiyel" class="stat-card" title="Potansiyel müşterileri göster"
       style="--accent:#f59e0b;--soft:#fffbeb{{ $secDurum === 'potansiyel' ? ';outline:2px solid #f59e0b' : '' }}">
        <div class="stat-label"><span>✨ Potansiyel</span></div>
        <div class="stat-value" style="color:#f59e0b">{{ number_format($musteriIlimli ?? 0) }}</div>
        <div class="stat-meta">Potansiyel müşteri</div>
    </a>

    <a href="{{ $listeUrl }}?durum=pasif" class="stat-card" title="Pasif müşterileri göster"
       style="--accent:#6b7280;--soft:#f3f4f6{{ $secDurum === 'pasif' ? ';outline:2px solid #6b7280' : '' }}">
        <div class="stat-label"><span>⏸️ Pasif</span></div>
        <div class="stat-value" style="color:#6b7280">{{ number_format($musteriSoguk ?? 0) }}</div>
        <div class="stat-meta">Pasif müşteri</div>
    </a>

    <a href="{{ $listeUrl }}?durum=aktif" class="stat-card" title="Aktif müşterileri göster"
       style="--accent:#3b82f6;--soft:#eff6ff">
        <div class="stat-label"><span>📊 Aktiflik Oranı</span></div>
        <div class="stat-value">%{{ $aktiflikOrani }}</div>
        <div class="stat-meta">Aktif / toplam</div>
    </a>

</div>

{{-- Filtreler --}}
<form action="" method="GET" class="crm-filter">
    <div class="search-wrap">
        <i data-lucide="search"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Ad, e-posta, telefon veya unvan ara...">
    </div>

    <div class="fsel">
        <i data-lucide="activity"></i>
       <select name="durum" class="form-select" style="max-width:200px">
      <option value="">— Tüm Durumlar —</option>
      <option value="aktif" {{ request('durum') == 'aktif' ? 'selected' : '' }}>✅ Aktif</option>
      <option value="potansiyel" {{ request('durum') == 'potansiyel' ? 'selected' : '' }}>✨ Potansiyel</option>
      <option value="pasif" {{ request('durum') == 'pasif' ? 'selected' : '' }}>⏸️ Pasif</option>
  </select>
    </div>

    @if(!empty($yoneticiler) && count($yoneticiler) > 0)
    <div class="fsel">
        <i data-lucide="user-check"></i>
        <select name="sorumlu_id" onchange="this.form.submit()">
            <option value="">Tüm Sorumlular</option>
            @foreach($yoneticiler as $y)
                <option value="{{ $y->id }}" {{ request('sorumlu_id') == $y->id ? 'selected' : '' }}>
                    {{ $y->adi ?? $y->kullaniciadi }}
                </option>
            @endforeach
        </select>
    </div>
    @endif

    <button type="submit" class="fbtn fbtn-primary">
        <i data-lucide="filter"></i>
        <span>Filtrele</span>
    </button>

    @if(request('search') || request('durum') || request('sorumlu_id'))
    <a href="{{ url()->current() }}" class="fbtn fbtn-temizle">
        <i data-lucide="x"></i>
        <span>Temizle</span>
    </a>
    @endif
</form>

{{-- Tablo --}}
<form id="topluForm" method="POST" action="{{ Route::has('admin.crm.musteriler.toplu-islem') ? route('admin.crm.musteriler.toplu-islem') : '#' }}">
@csrf

<div class="table-wrap">
    <div class="table-scroll">
        <table class="data-table" style="min-width:900px">
            <thead>
                <tr>
                    <th style="width:40px" class="no-label">
                        <input type="checkbox" id="secTumu" onclick="tumunuSec(this)" style="cursor:pointer">
                    </th>
                    <th>Durum</th>
                    <th>Müşteri</th>
                    <th>İletişim</th>
                    <th>Firma</th>
                    <th>Etiketler</th>
                    <th>Son Hizmet</th>
                    <th>Son Ödeme</th>
                    <th>Bakiye</th>
                    <th class="text-right">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers ?? [] as $c)
                    @php
                        $cbilgi = \App\Models\CRM\Customer::durumBilgi($c->durum ?? null);
                        $_ad = $c->adi ?? $c->ad ?? '—';
                        $detayUrl = \Illuminate\Support\Facades\Route::has('admin.crm.musteriler.show')
                            ? route('admin.crm.musteriler.show', $c->id) : '#';
                        // Foto: müşterinin kendi fotosu → ID'li üye → e-postalı üye
                        $cFoto = $c->profil_foto ?? null;
                        if (empty($cFoto)) { $cFoto = $uyeFotolar[$c->id] ?? null; }
                        if (empty($cFoto) && !empty($c->email)) { $cFoto = $uyeFotolarEmail[$c->email] ?? null; }
                        $cFotoVar = $cFoto && is_file(public_path($cFoto));
                    @endphp
                    <tr class="clickable musteri-row" data-url="{{ $detayUrl }}">

                        <td class="no-label no-row-click" data-label="" onclick="event.stopPropagation()">
                            <input type="checkbox" name="ids[]" value="{{ $c->id }}" class="musteri-cb" onclick="sayimGuncelle()" style="cursor:pointer">
                        </td>

                        <td data-label="Durum" class="no-row-click" onclick="event.stopPropagation()">
                            <button type="button" class="badge {{ $cbilgi['class'] }} crm-durum-badge" data-id="{{ $c->id }}"
                                    onclick="crmDurumCycle(this)" title="Tıkla: sıradaki duruma geç (Pasif → Potansiyel → Aktif)"
                                    style="cursor:pointer;border:none;font:inherit">
                                {{ $cbilgi['ikon'] }} {{ $cbilgi['label'] }}
                            </button>
                        </td>

                        <td data-label="Müşteri">
                            <div class="musteri-info">
                                <div class="musteri-avatar" style="overflow:hidden">@if($cFotoVar)<img src="{{ asset($cFoto) }}" alt="" style="width:100%;height:100%;object-fit:cover">@else{{ strtoupper(mb_substr($_ad ?: 'M', 0, 1)) }}@endif</div>
                                <div>
                                    <div class="name">{{ $_ad }} {{ $c->soyad ?? '' }}</div>
                                    <div class="id">ID: #{{ $c->id }}</div>
                                </div>
                            </div>
                        </td>

                        <td data-label="İletişim">
                            <div style="font-size:13px">{{ $c->email ?? '—' }}</div>
                            @if(!empty($c->telefon))
                                <div style="font-size:11px;color:var(--text-muted);margin-top:2px">{{ $c->telefon }}</div>
                            @endif
                        </td>

                        <td data-label="Firma" style="color:var(--text-secondary)">
                            {{ $c->firma ?? $c->firmaadi ?? $c->unvan ?? '—' }}
                        </td>

                        <td data-label="Etiketler">
                            @if(!empty($c->etiketler))
                                @foreach((array) $c->etiketler as $et)
                                    <span class="etiket-pill">{{ $et }}</span>
                                @endforeach
                            @else
                                <span style="color:var(--text-muted);font-size:11px">—</span>
                            @endif
                        </td>

                        <td data-label="Son Hizmet" style="font-size:12px;color:var(--text-secondary)">
                            {{ \Illuminate\Support\Str::limit($c->son_hizmet ?? '—', 25) }}
                        </td>

                        <td data-label="Son Ödeme" style="font-size:12px;color:var(--text-secondary)">
                            @php
                                $od = $c->son_odeme_tarih ?? null;
                                $odFmt = '—';
                                if ($od) {
                                    try {
                                        $odFmt = is_numeric($od) ? date('d.m.Y', (int) $od) : \Carbon\Carbon::parse($od)->format('d.m.Y');
                                    } catch (\Throwable $e) { $odFmt = $od; }
                                }
                            @endphp
                            {{ $odFmt }}
                        </td>

                        <td data-label="Bakiye"><strong>₺{{ number_format($c->bakiye ?? 0, 2, ',', '.') }}</strong></td>

                        <td data-label="" class="text-right no-row-click" onclick="event.stopPropagation()">
                            <div class="table-actions">
                                <a href="{{ $detayUrl }}" class="table-action" title="Detay">
                                    <i data-lucide="eye"></i>
                                </a>
                                @if(\Illuminate\Support\Facades\Route::has('admin.crm.musteriler.edit'))
                                <a href="{{ route('admin.crm.musteriler.edit', $c->id) }}" class="table-action" title="Düzenle">
                                    <i data-lucide="edit-2"></i>
                                </a>
                                @endif
                                <button type="button"
                                        onclick="event.stopPropagation();musteriSil({{ $c->id }})"
                                        class="table-action" style="color:var(--danger)" title="Sil">
                                    <i data-lucide="trash-2"></i>
                                </button>
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align:center;padding:48px;color:var(--text-muted)">
                            <i data-lucide="users" style="width:48px;height:48px;display:inline-block;margin-bottom:8px;opacity:0.5"></i>
                            <p style="margin-top:8px">Henüz müşteri yok</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($customers ?? null, 'hasPages') && $customers->hasPages())
        <div class="pagination">
            {{ $customers->withQueryString()->links() }}
        </div>
    @endif
</div>

{{-- Toplu işlem barı (seçim olunca açılır) --}}
<div id="topluBar" class="toplu-bar hidden">
    <strong><span id="secimSayisi">0</span> müşteri seçildi</strong>

    <select name="islem" class="form-select" style="max-width:200px" required>
      <option value="">— İşlem seç —</option>
      <option value="aktif">✅ Aktif Yap</option>
      <option value="potansiyel">✨ Potansiyel Yap</option>
      <option value="pasif">⏸️ Pasif Yap</option>
      <option value="sil">🗑️ Sil</option>
  </select>

    <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Seçili müşterilere bu işlem uygulansın mı?')">
        Uygula
    </button>

    <button type="button" class="btn btn-ghost btn-sm" onclick="secimTemizle()">
        İptal
    </button>
</div>

</form>

{{-- ═══ IMPORT MODAL ═══ --}}
<div id="importModal" class="modal-backdrop">
    <div class="modal">
        <div class="modal-header">
            <h3>📥 Müşteri İçe Aktar</h3>
            <button type="button" onclick="document.getElementById('importModal').classList.remove('show')" class="icon-btn">
                <i data-lucide="x"></i>
            </button>
        </div>
        @if(\Illuminate\Support\Facades\Route::has('admin.crm.musteriler.import'))
        <form action="{{ route('admin.crm.musteriler.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">CSV / Excel Dosyası <span class="required">*</span></label>
                    <input type="file" name="file" accept=".csv,.xlsx,.xls" required class="form-input">
                    <p class="form-help">
                        Önce
                        @if(\Illuminate\Support\Facades\Route::has('admin.crm.musteriler.export-template'))
                            <a href="{{ route('admin.crm.musteriler.export-template') }}" style="color:var(--brand)">şablonu indir</a>,
                        @else
                            şablonu indir,
                        @endif
                        doldurup yükle.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="document.getElementById('importModal').classList.remove('show')" class="btn btn-secondary">
                    İptal
                </button>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="upload"></i>
                    <span>İçe Aktar</span>
                </button>
            </div>
        </form>
        @else
        <div class="modal-body">
            <p style="color:var(--text-muted)">İçe aktarma fonksiyonu henüz yapılandırılmamış.</p>
        </div>
        @endif
    </div>
</div>

{{-- Sayfa script'i — @push yerine inline koyduk (layout bağımlılığı yok) --}}
<script>
    // Toplu seçim
    function tumunuSec(el) {
        document.querySelectorAll('.musteri-cb').forEach(c => c.checked = el.checked);
        sayimGuncelle();
    }

    // Tekil müşteri silme - iç içe form yasak olduğu için dinamik form oluştururuz
    function musteriSil(id) {
        if (!confirm('Müşteri silinsin mi? Bu işlem geri alınamaz!')) return;
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const f = document.createElement('form');
        f.method = 'POST';
        f.action = '{{ url("admin/crm/musteriler") }}/' + id;
        f.style.display = 'none';

        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = token;
        f.appendChild(csrf);

        const method = document.createElement('input');
        method.type = 'hidden';
        method.name = '_method';
        method.value = 'DELETE';
        f.appendChild(method);

        document.body.appendChild(f);
        f.submit();
    }

    function sayimGuncelle() {
        const n = document.querySelectorAll('.musteri-cb:checked').length;
        const sayiEl = document.getElementById('secimSayisi');
        const barEl = document.getElementById('topluBar');
        if (sayiEl) sayiEl.textContent = n;
        if (barEl) barEl.classList.toggle('hidden', n === 0);
    }

    function secimTemizle() {
        document.querySelectorAll('.musteri-cb, #secTumu').forEach(c => c.checked = false);
        sayimGuncelle();
    }

    // Durum tek-tıkla döngü (Pasif → Potansiyel → Aktif → Pasif) — karta girmeden değiştir
    const CRM_DURUM_CLASSES = ['badge-secondary', 'badge-warning', 'badge-success', 'badge-info'];
    function crmDurumCycle(btn) {
        const id = btn.dataset.id;
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        btn.disabled = true; btn.style.opacity = '.55';
        fetch('{{ url("admin/crm/musteriler") }}/' + id + '/toggle-durum', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(r => r.json()).then(d => {
            if (d.ok) {
                CRM_DURUM_CLASSES.forEach(c => btn.classList.remove(c));
                if (d.class) btn.classList.add(d.class);
                btn.innerHTML = (d.ikon || '') + ' ' + (d.label || '');
            }
        }).catch(err => console.error('durum hata:', err)).finally(() => { btn.disabled = false; btn.style.opacity = ''; });
    }

    // Modal arkaplan tıklayınca kapansın
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('importModal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) modal.classList.remove('show');
            });
        }

        // Müşteri satırına tıklayınca detay sayfasına git
        document.querySelectorAll('.musteri-row').forEach(function(row) {
            row.addEventListener('click', function(e) {
                // no-row-click sınıflı element veya altındaysa atla
                if (e.target.closest('.no-row-click')) return;
                const url = row.dataset.url;
                if (url && url !== '#') {
                    window.location = url;
                }
            });
        });
    });
</script>

@endsection