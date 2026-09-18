@extends('admin._layout')

@section('title', 'Data Center')

@push('head')
<style>
    .dc-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; margin-bottom:18px; }
    .dc-stat { background: var(--surface,var(--surface)); border: 1px solid var(--border,var(--border)); border-radius: 12px; padding:14px 16px; }
    .dc-stat .n { font-size:24px; font-weight:800; color: var(--text,var(--text)); }
    .dc-stat .l { font-size:12px; color: var(--text-muted,var(--text-secondary)); margin-top:2px; }
    .dc-stat.warn .n { color: var(--danger); }

    .dc-toolbar { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:14px; }
    .dc-toolbar input, .dc-toolbar select { padding:9px 11px; border: 1px solid var(--border,var(--border-strong)); border-radius: 9px; background: var(--bg,var(--surface)); color: var(--text,var(--text)); font-size:14px; }.dc-toolbar input[type="search"]{min-width:220px; flex:1 1 220px;background: var(--surface);color: var(--text);}
    .dc-count { font-size:13px; color: var(--text-muted); margin-left:auto; }
    .dc-add { background: var(--brand,var(--success)); color: var(--text-inverse); border: none; font-weight:700; padding:9px 16px; border-radius: 9px; cursor:pointer; display:inline-flex; align-items:center; gap:7px; }

    .dc-table-wrap { border: 1px solid var(--border,var(--border)); border-radius: 14px; overflow:auto; max-height:calc(100vh - 330px); background: var(--surface,var(--surface)); }
    table.dc-table { width:100%; border-collapse: collapse; font-size:13.5px; }
    .dc-table th { position:sticky; top:0; background: var(--surface,var(--surface)); text-align:left; padding:10px 12px; border-bottom: 2px solid var(--border,var(--border)); font-weight:700; color: var(--text,var(--text)); white-space:nowrap; z-index:2; }
    .dc-table td { padding:9px 12px; border-bottom: 1px solid var(--border,#f0f0f0); color: var(--text,#222); vertical-align:middle; }
    .dc-table tr:hover td { background: var(--brand-soft,#f8f8ee); }
    .dc-badge { display:inline-block; padding:2px 9px; border-radius: 999px; font-size:11.5px; font-weight:700; }
    .dc-badge.musteri { background: var(--danger-soft); color: var(--danger); }
    .dc-badge.uye { background: var(--success-soft); color: var(--success); }
    .dc-badge.bayi { background: var(--warning-soft); color: var(--warning); }
    .dc-durum { font-size:11.5px; color: var(--text-muted); text-transform:capitalize; }
    /* Durum dropdown (müşteri) + salt-okunur rozet (üye/bayi) */
    .dc-durum-sel { font-size:11.5px; font-weight:700; padding:4px 8px; border-radius: 8px; border: 1px solid var(--border); cursor:pointer; background: var(--surface); color: var(--text-secondary); outline: none; transition:background .15s,color .15s,border-color .15s; }
    .dc-durum-sel:hover { border-color: #cbd5e1; }
    .dc-durum-sel:disabled { opacity:.6; cursor:wait; }
    .dc-durum-sel.akt { background: var(--success-soft); color: var(--success); border-color: #bbf7d0; }
    .dc-durum-sel.pas { background: var(--danger-soft); color: var(--danger); border-color: #fecaca; }
    .dc-durum-sel.pot { background: var(--warning-soft); color: var(--warning); border-color: #fde68a; }
    .dc-durum-rozet { display:inline-block; font-size:11.5px; font-weight:700; padding:3px 10px; border-radius: 999px; background: var(--bg-subtle); color: var(--text-secondary); }
    .dc-durum-rozet.akt { background: var(--success-soft); color: var(--success); }
    .dc-durum-rozet.pas { background: var(--danger-soft); color: var(--danger); }
    .dc-durum-rozet.pot { background: var(--warning-soft); color: var(--warning); }
    /* Bağımsız pop-up (Müşteri Yap onay/bilgi) */
    .dc-pop-overlay { position:fixed; inset:0; background: rgba(15,23,42,.55); z-index:9999; display:flex; align-items:center; justify-content:center; padding:20px; }
    .dc-pop-box { background: var(--surface); color: var(--text); width:100%; max-width:440px; border-radius: 16px; box-shadow:0 20px 60px rgba(0,0,0,.35); overflow:hidden; }
    .dc-pop-head { padding:16px 20px; border-bottom: 1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
    .dc-pop-head h4 { margin:0; font-size:17px; font-weight:800; }
    .dc-pop-x { border: none; background: transparent; font-size:24px; line-height:1; color: var(--text-muted); cursor:pointer; }
    .dc-pop-body { padding:18px 20px; font-size:14px; }
    .dc-pop-foot { padding:14px 20px; border-top: 1px solid var(--border); display:flex; gap:8px; justify-content:flex-end; }
    .dc-pop-btn { font-size:13.5px; font-weight:700; padding:9px 16px; border-radius: 10px; border: 1px solid transparent; cursor:pointer; }
    .dc-pop-ok { background: var(--brand); color: var(--text); }
    .dc-pop-ok:hover { filter:brightness(.95); }
    .dc-pop-cancel { background: var(--surface); color: var(--text-secondary); border-color: var(--border); }
    .dc-pop-cancel:hover { background: var(--bg-subtle); }
    body.theme-dark .dc-pop-box { background: #1f1f1f; color: #eee; }
    /* "Hesabı var" bilgi etiketi (buton değil) */
    .dc-hesap-var { display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:700; color: var(--success); background: var(--success-soft); padding:5px 11px; border-radius: 8px; white-space:nowrap; }
    .dc-hesap-var i { width:14px; height:14px; }
    /* Sayfalama */
    .dc-pager { display:flex; flex-wrap:wrap; gap:6px; justify-content:center; align-items:center; margin-top:14px; }
    .dc-pg-btn { min-width:36px; height:36px; padding:0 10px; border: 1px solid var(--border); background: var(--surface); color: var(--text-secondary); border-radius: 9px; font-size:13.5px; font-weight:700; cursor:pointer; transition:background .12s,border-color .12s,color .12s; }
    .dc-pg-btn:hover:not(:disabled):not(.aktif) { background: var(--bg-subtle); border-color: #cbd5e1; }
    .dc-pg-btn.aktif { background: var(--brand); border-color: var(--brand); color: var(--text); cursor:default; }
    .dc-pg-btn:disabled { opacity:.45; cursor:not-allowed; }
    .dc-pg-dots { color: var(--text-muted); padding:0 2px; }
    body.theme-dark .dc-pg-btn { background: #1f1f1f; color: #ddd; border-color: #333; }
    .dc-missing { color: var(--text-muted); }
    .dc-sure { white-space:nowrap; }
    .dc-sure.sessiz { color: var(--danger); font-weight:700; }
    .dc-sure.uyari { color: #d97706; font-weight:600; }
    .dc-act { background: transparent; border: 1px solid var(--border,var(--border-strong)); color: var(--text,var(--text)); border-radius: 7px; padding:4px 9px; font-size:12px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:4px; }
    .dc-act:hover { border-color: var(--brand); color: var(--brand); }
    .dc-act { white-space:nowrap; }
    .dc-act i { width:14px; height:14px; }
    .dc-acts { display:flex; gap:6px; align-items:center; justify-content:flex-end; flex-wrap:nowrap; }
    .dc-act.ok { border-color: #bbf7d0; color: var(--success); }
    .dc-act.ok:hover { border-color: #16a34a; background: var(--success); color: var(--text-inverse); }

    .dc-modal-bg { position:fixed; inset:0; background: rgba(0,0,0,.55); backdrop-filter:blur(4px); z-index:1000; display:none; align-items:center; justify-content:center; padding:20px; }
    .dc-modal-bg.show { display:flex; }
    .dc-modal { background: var(--surface,var(--surface)); color: var(--text,var(--text)); width:100%; max-width:560px; border-radius: 16px; border: 1px solid var(--border,var(--border)); max-height:92vh; overflow-y:auto; }
    .dc-modal-head { padding:16px 20px; border-bottom: 1px solid var(--border,var(--border)); display:flex; align-items:center; justify-content:space-between; }
    .dc-modal-head h3 { margin:0; font-size:17px; font-weight:700; }
    .dc-modal-body { padding:18px 20px; display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .dc-field { display:flex; flex-direction:column; gap:5px; }
    .dc-field.full { grid-column:1 / -1; }
    .dc-field label { font-size:12px; font-weight:600; color: var(--text-muted,var(--text-secondary)); }
    .dc-field input, .dc-field select, .dc-field textarea { padding:9px 11px; border: 1px solid var(--border,var(--border-strong)); border-radius: 8px; background: var(--bg,var(--surface)); color: var(--text,var(--text)); font-size:14px; }
    .dc-modal-foot { padding:14px 20px; border-top: 1px solid var(--border,var(--border)); display:flex; gap:10px; justify-content:flex-end; }
    .dc-btn { padding:10px 18px; border-radius: 9px; border: none; font-weight:600; cursor:pointer; font-size:14px; }
    .dc-btn-primary { background: var(--brand,var(--success)); color: var(--text-inverse); }
    .dc-btn-ghost { background: transparent; border: 1px solid var(--border,var(--border-strong)); color: var(--text,var(--text)); }
    body.theme-dark .dc-stat, body.theme-dark .dc-table-wrap, body.theme-dark .dc-table th, body.theme-dark .dc-modal { background: #1a1a1a; }
    .dc-bulkbar{display:none;align-items:center;gap:9px;flex-wrap:wrap;background: var(--brand-soft,#f8f8ee);border: 1px solid var(--border,var(--border));border-radius: 10px;padding:9px 12px;margin-bottom:12px}
    .dc-bulkbar.show{display:flex}
    .dc-bulkbar .info{font-weight:700;color: var(--text,var(--text));margin-right:auto}
    .dc-chk{width:16px;height:16px;cursor:pointer;accent-color:var(--brand,#16a34a)}
    .dc-mini{background: var(--surface);border: 1px solid var(--border,var(--border-strong));color: var(--text,var(--text));border-radius: 8px;padding:6px 11px;font-size:12.5px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:5px}
    .dc-mini:hover{border-color: var(--brand,#16a34a);color: var(--brand,var(--success))}
    .dc-mini.primary{background: var(--brand,var(--success));color: var(--text-inverse);border-color: var(--brand,#16a34a)}
    .dc-rcpt{font-size:13px;color: var(--text-muted,var(--text-secondary));background: var(--bg-subtle,#f8f8f3);border: 1px solid var(--border,var(--border));border-radius: 8px;padding:9px 12px}
    /* Liste seçim dropdown'u (checkbox'lı) */
    .dc-ld{position:relative}
    .dc-ld-btn{width:100%;display:flex;align-items:center;justify-content:space-between;gap:8px;padding:9px 11px;border: 1px solid var(--border,var(--border-strong));border-radius: 8px;background: var(--bg,var(--surface));color: var(--text,var(--text));font-size:14px;cursor:pointer;text-align:left}
    .dc-ld-btn i{width:16px;height:16px;flex-shrink:0;transition:transform .15s}
    .dc-ld.open .dc-ld-btn i{transform:rotate(180deg)}
    .dc-ld-panel{display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;z-index:20;background: var(--surface,var(--surface));border: 1px solid var(--border,var(--border-strong));border-radius: 10px;box-shadow:0 12px 32px rgba(0,0,0,.14);max-height:230px;overflow-y:auto;padding:6px}
    .dc-ld.open .dc-ld-panel{display:block}
    .dc-ld-opt{display:flex;align-items:center;gap:8px;padding:8px 9px;border-radius: 7px;font-size:13.5px;cursor:pointer;color: var(--text,var(--text))}
    .dc-ld-opt:hover{background: var(--brand-soft,#f8f8ee)}
    .dc-ld-opt input{width:16px;height:16px;accent-color:var(--brand,#16a34a);cursor:pointer;margin:0}
    .dc-ld-dot{width:9px;height:9px;border-radius: 50%;flex-shrink:0}
    .dc-ld-empty{padding:10px;font-size:13px;color: var(--text-muted,var(--text-secondary))}
    body.theme-dark .dc-ld-panel{background: #1a1a1a}
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title"><i data-lucide="archive"></i> Data Center</h1>
        <div class="page-subtitle">Tüm kayıtların deposu — müşteriler, üyeler ve bayiler burada toplanır.</div>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-secondary btn-sm" onclick="dcExport()"><i data-lucide="download"></i> <span>Dışa Aktar</span></button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="dcImportOpen()"><i data-lucide="upload"></i> <span>İçe Aktar</span></button>
        <a href="{{ route('admin.data-center.listeler') }}" class="btn btn-primary btn-sm"><i data-lucide="list"></i> <span>Listeler</span></a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success" style="margin-bottom:14px">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger" style="margin-bottom:14px">{{ session('error') }}</div>@endif

<div class="dc-stats">
    <div class="dc-stat"><div class="n">{{ number_format($istatistik['toplam']) }}</div><div class="l">Toplam Kayıt</div></div>
    <div class="dc-stat"><div class="n">{{ number_format($istatistik['musteri']) }}</div><div class="l">Müşteri</div></div>
    <div class="dc-stat"><div class="n">{{ number_format($istatistik['uye']) }}</div><div class="l">Üye</div></div>
    <div class="dc-stat"><div class="n">{{ number_format($istatistik['bayi']) }}</div><div class="l">Bayi</div></div>
    <div class="dc-stat warn"><div class="n">{{ number_format($istatistik['sessiz']) }}</div><div class="l">6+ aydır iletişim yok</div></div>
</div>

<div class="dc-toolbar">
    <button class="dc-add" onclick="dcOpen()"><i data-lucide="user-plus"></i> Kişi Ekle</button>
    <input type="search" id="dcSearch" placeholder="🔍 Ad, e-posta, telefon ara...">
    <select id="dcKaynak">
        <option value="">Tüm kaynaklar</option>
        <option value="Müşteri">Müşteri</option>
        <option value="Üye">Üye</option>
        <option value="Bayi">Bayi</option>
    </select>
    <select id="dcDurum">
        <option value="">Tüm durumlar</option>
       @foreach($durumlar as $d)<option value="{{ $d }}">{{ ucfirst($d) }}</option>@endforeach

    </select>
    <select id="dcSektor"><option value="">Tüm sektörler</option></select>
    <select id="dcKonum"><option value="">Tüm konumlar</option></select>
    <div class="dc-ld" id="dcFiltreListe" style="min-width:170px">
        <button type="button" class="dc-ld-btn" onclick="dcFlToggle()">
            <span id="dcFlLabel">Tüm listeler</span>
            <i data-lucide="chevron-down"></i>
        </button>
        <div class="dc-ld-panel">
            @forelse($listeler as $l)
                <label class="dc-ld-opt">
                    <input type="checkbox" class="dc-fl-chk" value="{{ $l->id }}" onchange="dcFlChange()">
                    <span class="dc-ld-dot" style="background: {{ $l->renk ?: '#16a34a' }}"></span>
                    {{ $l->ad }}
                </label>
            @empty
                <div class="dc-ld-empty">Liste yok</div>
            @endforelse
        </div>
    </div>
    <span class="dc-count" id="dcCount">—</span>
</div>

<div class="dc-bulkbar" id="dcBulk">
    <span class="info" id="dcSelInfo">0 seçili</span>
    <button type="button" class="dc-mini primary" onclick="dcBulkOpen('mail')"><i data-lucide="mail"></i> Mail Gönder</button>
    <button type="button" class="dc-mini primary" onclick="dcBulkOpen('sms')"><i data-lucide="message-square"></i> SMS Gönder</button>
    <select id="dcAtaListe" class="dc-mini" style="padding:6px 8px">
        <option value="">Listeye ekle…</option>
        @foreach($listeler as $l)<option value="{{ $l->id }}">{{ $l->ad }}</option>@endforeach
    </select>
    <button type="button" class="dc-mini primary" onclick="dcListeyeEkle()"><i data-lucide="list-plus"></i> Ekle</button>
    <button type="button" class="dc-mini" onclick="dcSelectFiltered()">Filtrelenenlerin tümünü seç</button>
    <button type="button" class="dc-mini" onclick="dcClearSel()">Seçimi temizle</button>
</div>

<div class="dc-table-wrap">
    <table class="dc-table">
        <thead>
            <tr>
                <th style="width:36px"><input type="checkbox" class="dc-chk" id="dcCheckAll" onclick="dcToggleAll(this)"></th>
                <th>Ad / Firma</th>
                <th>Kaynak</th>
                <th>Sektör</th>
                <th>Durum</th>
                <th>E-posta</th>
                <th>Telefon</th>
                <th>Konum</th>
                <th title="Sisteme kayıt olduğundan bu yana">Kayıtlı</th>
                <th title="Son iletişimden bu yana geçen süre">İletişim Yok</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="dcBody"></tbody>
    </table>
</div>
<div id="dcPager" class="dc-pager"></div>

{{-- Kişi ekle/düzenle modalı --}}
<div class="dc-modal-bg" id="dcModalBg">
    <form class="dc-modal" method="POST" action="{{ route('admin.data-center.kisi-kaydet') }}">
        @csrf
        <input type="hidden" name="id" id="f_id">
        <div class="dc-modal-head">
            <h3 id="dcModalTitle">Kişi Ekle</h3>
            <button type="button" class="dc-btn dc-btn-ghost" style="padding:4px 10px" onclick="dcClose()">✕</button>
        </div>
        <div class="dc-modal-body">
            <div class="dc-field full"><label>Ad / Firma *</label><input type="text" name="adi" id="f_adi" required></div>
            <div class="dc-field"><label>E-posta</label><input type="email" name="email" id="f_email"></div>
            <div class="dc-field"><label>Telefon</label><input type="text" name="telefon" id="f_telefon"></div>
            <div class="dc-field full">
                <label>Listeler</label>
                <div class="dc-ld" id="dcListeDrop">
                    <button type="button" class="dc-ld-btn" onclick="dcLdToggle()">
                        <span id="dcLdLabel" style="color: var(--text-muted,var(--text-secondary))">Liste seç…</span>
                        <i data-lucide="chevron-down"></i>
                    </button>
                    <div class="dc-ld-panel" id="dcLdPanel">
                        @forelse($listeler as $l)
                            <label class="dc-ld-opt">
                                <input type="checkbox" name="liste_ids[]" value="{{ $l->id }}" class="dc-ld-chk" onchange="dcLdSync()">
                                <span class="dc-ld-dot" style="background: {{ $l->renk ?: '#16a34a' }}"></span>
                                {{ $l->ad }}
                            </label>
                        @empty
                            <div class="dc-ld-empty">Henüz liste yok. <a href="{{ route('admin.data-center.listeler') }}" target="_blank">Liste oluştur →</a></div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="dc-field"><label>Sektör</label><input type="text" name="sektor" id="f_sektor"></div>
            <div class="dc-field"><label>İl</label><input type="text" name="il" id="f_il"></div>
            <div class="dc-field"><label>İlçe</label><input type="text" name="ilce" id="f_ilce"></div>
            <div class="dc-field full"><label>Ünvan</label><input type="text" name="unvan" id="f_unvan"></div>
            <div class="dc-field full"><label>Adres</label><textarea name="adres" id="f_adres" rows="2"></textarea></div>
            <div class="dc-field"><label>Durum</label>
                <select name="durum" id="f_durum">
                    <option value="pasif">Pasif</option>
                    <option value="potansiyel">Potansiyel</option>
                    <option value="aktif">Aktif (CRM'e taşı)</option>
                </select>
            </div>
        </div>

        {{-- Kayıtlı üye olarak da ekle (giriş yapabilen gerçek müşteri) --}}
        <label class="dc-uye-yap" style="display:flex;gap:10px;align-items:flex-start;margin-top:14px;padding:12px 14px;border: 1px solid #e0ddca;border-radius: 10px;background: #fbfbf7;cursor:pointer;font-size:13px;line-height:1.5">
            <input type="checkbox" name="uye_yap" value="1" id="f_uye_yap" style="margin-top:2px;flex-shrink:0">
            <span>
                <strong>Kayıtlı üye olarak da ekle</strong><br>
                <span style="color: #8a8676">Bu kişi panele giriş yapabilen gerçek müşteri olur (otomatik şifre üretilir). <b>E-posta zorunlu.</b> Giriş bilgilerini sonra müşteri kartından iletebilirsiniz.</span>
            </span>
        </label>

        <div class="dc-modal-foot">
            <button type="button" class="dc-btn dc-btn-ghost" onclick="dcClose()">Vazgeç</button>
            <button type="submit" class="dc-btn dc-btn-primary">Kaydet</button>
        </div>
    </form>
</div>

{{-- Toplu Mail / SMS modalı --}}
<div class="dc-modal-bg" id="mbModalBg">
    <form class="dc-modal" id="mbForm" method="POST" action="" onsubmit="return dcBulkSubmit()">
        @csrf
        <div id="mbHidden"></div>
        <div class="dc-modal-head">
            <h3 id="mbTitle">Toplu Gönder</h3>
            <button type="button" class="dc-btn dc-btn-ghost" style="padding:4px 10px" onclick="dcBulkClose()">✕</button>
        </div>
        <div class="dc-modal-body" style="grid-template-columns:1fr">
            <div class="dc-rcpt" id="mbRcpt"></div>
            <div class="dc-field full" id="mbSablonWrap" style="display:none">
                <label>Şablon (opsiyonel)</label>
                <select id="mbSablon" onchange="dcSablonUygula()"><option value="">— Şablon seç —</option></select>
            </div>
            <div class="dc-field full" id="mbKonuWrap"><label>Konu *</label><input type="text" name="konu" id="mbKonu" maxlength="200" placeholder="E-posta konusu"></div>
            <div class="dc-field full"><label>Mesaj *</label><textarea name="mesaj" id="mbMesaj" rows="6" placeholder="Mesajınız..."></textarea></div>
        </div>
        <div class="dc-modal-foot">
            <button type="button" class="dc-btn dc-btn-ghost" onclick="dcBulkClose()">Vazgeç</button>
            <button type="submit" class="dc-btn dc-btn-primary">Gönder</button>
        </div>
    </form>
</div>

{{-- Toplu müşteri içe aktar modalı --}}
<div class="dc-modal-bg" id="impModalBg">
    <form class="dc-modal" method="POST" action="{{ route('admin.data-center.ice-aktar') }}" enctype="multipart/form-data">
        @csrf
        <div class="dc-modal-head">
            <h3>📥 Toplu Müşteri İçe Aktar</h3>
            <button type="button" class="dc-btn dc-btn-ghost" style="padding:4px 10px" onclick="dcImportClose()">✕</button>
        </div>
        <div class="dc-modal-body" style="grid-template-columns:1fr">
            <div class="dc-rcpt">
                Excel <b>(.xlsx)</b> veya <b>;</b> ayraçlı CSV yükle. <b>İlk satır başlık</b> olmalı.<br>
                Tanınan sütunlar: <b>Ad</b> (zorunlu), Email, Telefon, Sektör, İl, İlçe, Ünvan, Kategori, Adres.
                <a href="#" onclick="dcOrnekIndir(event)" style="color: var(--brand,var(--success));font-weight:600">Örnek şablon indir →</a>
            </div>
            <div class="dc-field full">
                <label>Dosya *</label>
                <input type="file" name="dosya" accept=".xlsx,.xls,.csv,.txt" required>
            </div>
            @if(($listeler ?? collect())->count())
            <div class="dc-field full">
                <label>İçe aktarılanları listeye ekle (opsiyonel)</label>
                <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:4px">
                    @foreach($listeler as $l)
                        <label class="dc-ld-opt" style="border: 1px solid var(--border,var(--border));border-radius: 20px;padding:5px 11px">
                            <input type="checkbox" name="liste_ids[]" value="{{ $l->id }}">
                            <span class="dc-ld-dot" style="background: {{ $l->renk ?: '#16a34a' }}"></span>
                            {{ $l->ad }}
                        </label>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        <div class="dc-modal-foot">
            <button type="button" class="dc-btn dc-btn-ghost" onclick="dcImportClose()">Vazgeç</button>
            <button type="submit" class="dc-btn dc-btn-primary">İçe Aktar</button>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
const DC = {
    rows: @json($kayitlar),
    base: '{{ url('admin/data-center') }}',
    csrf: '{{ csrf_token() }}',
    uyeUrl: '{{ url('admin/uyeler') }}',
    bayiUrl: '{{ url('admin/bayiler') }}',
    sablonlar: @json($mailSablonlari ?? []),
    listeler: @json($listeler ?? []),
};
const dcSel = new Set();
let dcBulkMode = 'mail';
let dcSayfa = 1;
const dcSayfaBoyut = 50;

function dcKey(r){ return r.kaynak + ':' + r.kaynak_id; }
function dcEsc(t){ return String(t ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function dcFiltered(){
    const q = document.getElementById('dcSearch').value.trim().toLocaleLowerCase('tr');
    const kaynak = document.getElementById('dcKaynak').value;
    const durum  = document.getElementById('dcDurum').value;
    const sektor = document.getElementById('dcSektor').value;
    const konum  = document.getElementById('dcKonum').value;
    const listeler = dcFlSelected(); // çok seçimli liste filtresi
    return DC.rows.filter(r => {
        if (kaynak && r.kaynak !== kaynak) return false;
        // durum büyük/küçük harf duyarsız (veride "Aktif", filtrede "aktif" olabilir)
        if (durum && (r.durum || '').toLocaleLowerCase('tr') !== durum.toLocaleLowerCase('tr')) return false;
        // Data Center listesi filtresi — seçili listelerden HERHANGİ birinde olanlar (VEYA)
        if (listeler.length){
            const rl = (r.liste_ids || []).map(String);
            if (!listeler.some(id => rl.includes(id))) return false;
        }
        if (sektor && (r.sektor || '') !== sektor) return false;
        if (konum && (r.konum || '') !== konum) return false;
        if (q){
            const hay = (r.ad + ' ' + r.email + ' ' + r.telefon + ' ' + (r.sektor||'') + ' ' + (r.konum||'')).toLocaleLowerCase('tr');
            if (!hay.includes(q)) return false;
        }
        return true;
    });
}

function dcSureClass(gun){ if (gun === null) return ''; if (gun > 365) return 'sessiz'; if (gun > 180) return 'uyari'; return ''; }

function dcRender(){
    const list = dcFiltered();
    const toplamSayfa = Math.max(1, Math.ceil(list.length / dcSayfaBoyut));
    if (dcSayfa > toplamSayfa) dcSayfa = toplamSayfa;
    if (dcSayfa < 1) dcSayfa = 1;
    const bas = (dcSayfa - 1) * dcSayfaBoyut;
    const shown = list.slice(bas, bas + dcSayfaBoyut);
    const body = document.getElementById('dcBody');
    body.innerHTML = shown.map((r) => {
        const key = dcKey(r);
        const badge = r.kaynak === 'Müşteri' ? 'musteri' : (r.kaynak === 'Üye' ? 'uye' : 'bayi');
        const etiket = r.kaynak; // gerçek kaynak: Müşteri / Üye / Bayi (durum ayrı sütunda)
        let akt = '';
        if (r.kaynak === 'Müşteri'){
            akt += `<button class="dc-act" onclick="dcEdit('${key}')"><i data-lucide="pencil"></i> Düzenle</button>`;
            // Giriş hesabı yoksa "Müşteri Yap", varsa "Hesabı var" etiketi
            if (!r.hesap_var){
                akt += `<button class="dc-act ok" onclick="dcMusteriYap(${r.kaynak_id}, this)"><i data-lucide="user-plus"></i> Müşteri Yap</button>`;
            } else {
                akt += `<span class="dc-hesap-var"><i data-lucide="check-circle"></i> Hesabı var</span>`;
            }
        } else if (r.kaynak === 'Üye'){
            akt = `<a class="dc-act" href="${DC.uyeUrl}/${r.kaynak_id}/detay"><i data-lucide="external-link"></i> Aç</a>`;
        } else if (r.kaynak === 'Bayi'){
            akt = `<a class="dc-act" href="${DC.bayiUrl}/${r.kaynak_id}/duzenle"><i data-lucide="external-link"></i> Aç</a>`;
        }
        const checked = dcSel.has(key) ? 'checked' : '';
        return `<tr>
            <td><input type="checkbox" class="dc-chk dc-row-chk" ${checked} onclick="dcToggleRow('${key}', this.checked)"></td>
            <td style="font-weight:600">${dcEsc(r.ad)}</td>
            <td><span class="dc-badge ${badge}">${etiket}</span></td>
            <td>${r.sektor ? dcEsc(r.sektor) : '<span class="dc-missing">—</span>'}</td>
            <td>${dcDurumHucre(r)}</td>
            <td>${r.email ? dcEsc(r.email) : '<span class="dc-missing">—</span>'}</td>
            <td>${r.telefon ? dcEsc(r.telefon) : '<span class="dc-missing">—</span>'}</td>
            <td>${r.konum ? dcEsc(r.konum) : '<span class="dc-missing">—</span>'}</td>
            <td class="dc-sure">${dcEsc(r.kayit_sure)}</td>
            <td class="dc-sure ${dcSureClass(r.temas_gun)}">${dcEsc(r.temas_sure)}</td>
            <td><div class="dc-acts">${akt}</div></td>
        </tr>`;
    }).join('');
    const basNo = list.length ? (bas + 1) : 0;
    const bitNo = bas + shown.length;
    document.getElementById('dcCount').textContent = list.length
        ? `${basNo}-${bitNo} / ${list.length} kayıt`
        : '0 kayıt';
    dcSayfalamaCiz(list.length, toplamSayfa);
    const all = document.getElementById('dcCheckAll');
    if (all){ const sec = list.filter(r => dcSel.has(dcKey(r))).length; all.checked = list.length > 0 && sec === list.length; }
    dcUpdateBulk();
    if (window.lucide) lucide.createIcons();
}

/* ── Sayfalama çizimi (1 2 3 ... İleri/Geri) ── */
function dcSayfalamaCiz(toplamKayit, toplamSayfa){
    const kap = document.getElementById('dcPager');
    if (!kap) return;
    if (toplamSayfa <= 1){ kap.innerHTML = ''; return; }

    let h = '';
    // Geri
    h += `<button class="dc-pg-btn" ${dcSayfa === 1 ? 'disabled' : ''} onclick="dcSayfaGit(${dcSayfa - 1})">‹ Geri</button>`;

    // Sayfa numaraları: akıllı aralık (ilk, son, aktifin etrafı)
    const aralik = [];
    const goster = (n) => { if (n >= 1 && n <= toplamSayfa && !aralik.includes(n)) aralik.push(n); };
    goster(1); goster(2);
    for (let i = dcSayfa - 1; i <= dcSayfa + 1; i++) goster(i);
    goster(toplamSayfa - 1); goster(toplamSayfa);
    aralik.sort((a, b) => a - b);

    let oncekiN = 0;
    aralik.forEach((n) => {
        if (oncekiN && n - oncekiN > 1){ h += `<span class="dc-pg-dots">…</span>`; }
        h += `<button class="dc-pg-btn ${n === dcSayfa ? 'aktif' : ''}" onclick="dcSayfaGit(${n})">${n}</button>`;
        oncekiN = n;
    });

    // İleri
    h += `<button class="dc-pg-btn" ${dcSayfa === toplamSayfa ? 'disabled' : ''} onclick="dcSayfaGit(${dcSayfa + 1})">İleri ›</button>`;

    kap.innerHTML = h;
}

function dcSayfaGit(n){
    dcSayfa = n;
    dcRender();
    // tabloyu üste kaydır (uzun listede kullanıcı yukarı baksın)
    const wrap = document.querySelector('.dc-table-wrap') || document.getElementById('dcBody');
    if (wrap && wrap.scrollIntoView) wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/* ── durum hücresi: müşteride dropdown, üye/bayide salt-okunur rozet ── */
function dcDurumSinif(durum){
    var d = (durum || '').toString().toLocaleLowerCase('tr');
    if (d === 'aktif') return 'akt';
    if (d === 'pasif') return 'pas';
    if (d === 'potansiyel') return 'pot';
    return '';
}
function dcDurumHucre(r){
    var mevcut = (r.durum || '').toString().toLocaleLowerCase('tr');
    // Üye/Bayi → durumu buradan değiştirilemez, salt-okunur rozet
    if (r.kaynak !== 'Müşteri'){
        var etk = r.durum ? dcEsc(r.durum) : '—';
        return '<span class="dc-durum-rozet ' + dcDurumSinif(mevcut) + '">' + etk + '</span>';
    }
    // Müşteri → seçilebilir dropdown (seçince anında kaydeder)
    var secenekler = [['aktif','Aktif'],['pasif','Pasif'],['potansiyel','Potansiyel']];
    var opts = secenekler.map(function(o){
        var sel = (mevcut === o[0]) ? ' selected' : '';
        return '<option value="' + o[0] + '"' + sel + '>' + o[1] + '</option>';
    }).join('');
    return '<select class="dc-durum-sel ' + dcDurumSinif(mevcut) + '" '
         + 'data-id="' + r.kaynak_id + '" '
         + 'onchange="dcDurumDegistir(this)">' + opts + '</select>';
}
function dcDurumDegistir(sel){
    var id = sel.getAttribute('data-id');
    var yeni = sel.value;
    var eski = sel.getAttribute('data-eski') || '';
    // görsel: renk sınıfını hemen güncelle
    sel.className = 'dc-durum-sel ' + dcDurumSinif(yeni);
    sel.disabled = true;
    fetch(DC.base + '/musteri/' + id + '/durum', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': DC.csrf },
        body: 'durum=' + encodeURIComponent(yeni)
    }).then(function(res){ return res.json().catch(function(){ return {ok:res.ok}; }); })
      .then(function(j){
        sel.disabled = false;
        if (j && j.ok){
            // yerel veriyi de güncelle (filtre/sayaç doğru kalsın)
            var row = DC.rows.find(function(x){ return x.kaynak === 'Müşteri' && String(x.kaynak_id) === String(id); });
            if (row){ row.durum = yeni.charAt(0).toLocaleUpperCase('tr') + yeni.slice(1); if(row.d) row.d.durum = yeni; }
        } else {
            alert('Durum güncellenemedi. Sayfayı yenileyip tekrar deneyin.');
            location.reload();
        }
      }).catch(function(){
        sel.disabled = false;
        alert('Bağlantı hatası — durum güncellenemedi.');
        location.reload();
      });
}

/* ── "Müşteri Yap" — müşteriye giriş hesabı aç (bilgi pop-up'lı) ── */
function dcMusteriYap(id, btn){
    var row = DC.rows.find(function(x){ return x.kaynak === 'Müşteri' && String(x.kaynak_id) === String(id); });
    var ad = row ? row.ad : 'Bu müşteri';
    var email = row ? (row.email || '') : '';

    var onayHtml = '<div style="text-align:left;line-height:1.7">'
        + '<p style="margin:0 0 10px"><strong>' + dcEsc(ad) + '</strong> için giriş yapabileceği bir <strong>müşteri hesabı</strong> oluşturulacak.</p>'
        + '<ul style="margin:0 0 10px 18px;padding:0;color: var(--text-secondary);font-size:13.5px">'
        + '<li>Müşteri, kendi paneline giriş yapabilecek.</li>'
        + '<li>Otomatik bir <strong>geçici şifre</strong> üretilecek.</li>'
        + '<li>Giriş bilgilerini sonra müşteriye iletebilirsiniz.</li>'
        + '</ul>'
        + (email ? '<p style="margin:0;color: var(--text-secondary);font-size:13px">E-posta: <strong>' + dcEsc(email) + '</strong></p>'
                 : '<p style="margin:0;color: #b91c1c;font-size:13px">⚠ Bu müşterinin e-postası yok. Önce "Düzenle" ile e-posta ekleyin.</p>')
        + '</div>';

    dcPopup({
        baslik: 'Müşteri Yap',
        icerik: onayHtml,
        onayMetin: email ? 'Evet, hesap oluştur' : null,
        onay: email ? function(){ dcMusteriYapGonder(id, btn); } : null
    });
}

function dcMusteriYapGonder(id, btn){
    if (btn){ btn.disabled = true; }
    fetch(DC.base + '/musteri/' + id + '/musteri-yap', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': DC.csrf },
        body: ''
    }).then(function(res){ return res.json().catch(function(){ return {ok:false, mesaj:'Beklenmeyen yanıt.'}; }); })
      .then(function(j){
        if (btn){ btn.disabled = false; }
        if (j && j.ok){
            var sonuc = '<div style="text-align:left;line-height:1.8">'
                + '<p style="margin:0 0 10px;color: var(--success);font-weight:600">✓ ' + dcEsc(j.mesaj || 'Hesap oluşturuldu.') + '</p>';
            if (j.yeni && j.sifre){
                sonuc += '<div style="background: var(--bg-subtle);border: 1px solid var(--border);border-radius: 10px;padding:12px;font-size:13.5px">'
                      +  '<div>E-posta: <strong>' + dcEsc(j.email || '') + '</strong></div>'
                      +  '<div>Geçici şifre: <strong style="font-family:monospace;font-size:15px">' + dcEsc(j.sifre) + '</strong></div>'
                      +  '</div>'
                      +  '<p style="margin:10px 0 0;color: var(--text-secondary);font-size:12.5px">Bu şifreyi müşteriye iletin. Müşteri kartından "Geçici Şifre Gönder" ile de iletebilirsiniz.</p>';
            }
            sonuc += '</div>';
            dcPopup({ baslik: 'Müşteri Yap', icerik: sonuc, kapatMetin: 'Tamam', yenile: true });
            // yerel veriyi güncelle: artık hesabı var
            var row = DC.rows.find(function(x){ return x.kaynak === 'Müşteri' && String(x.kaynak_id) === String(id); });
            if (row){ row.hesap_var = true; }
        } else {
            dcPopup({ baslik: 'Müşteri Yap', icerik: '<p style="color: #b91c1c;text-align:left">' + dcEsc((j && j.mesaj) || 'İşlem başarısız.') + '</p>', kapatMetin: 'Kapat' });
        }
      }).catch(function(){
        if (btn){ btn.disabled = false; }
        dcPopup({ baslik: 'Müşteri Yap', icerik: '<p style="color: #b91c1c">Bağlantı hatası — işlem yapılamadı.</p>', kapatMetin: 'Kapat' });
      });
}

/* ── Basit pop-up (onay + bilgi) — bağımsız stil ── */
function dcPopup(o){
    var eski = document.getElementById('dcPopup');
    if (eski) eski.remove();
    var wrap = document.createElement('div');
    wrap.id = 'dcPopup';
    wrap.className = 'dc-pop-overlay';
    var alt = '';
    if (o.onay && o.onayMetin){
        alt += '<button class="dc-pop-btn dc-pop-ok" id="dcPopupOnay">' + dcEsc(o.onayMetin) + '</button>';
    }
    alt += '<button class="dc-pop-btn dc-pop-cancel" id="dcPopupKapat">' + dcEsc(o.kapatMetin || (o.onay ? 'Vazgeç' : 'Kapat')) + '</button>';
    wrap.innerHTML = '<div class="dc-pop-box">'
        + '<div class="dc-pop-head"><h4>' + dcEsc(o.baslik || '') + '</h4>'
        + '<button type="button" class="dc-pop-x" id="dcPopupX">&times;</button></div>'
        + '<div class="dc-pop-body">' + (o.icerik || '') + '</div>'
        + '<div class="dc-pop-foot">' + alt + '</div>'
        + '</div>';
    document.body.appendChild(wrap);
    function kapat(){ wrap.remove(); if (o.yenile) location.reload(); }
    wrap.querySelector('#dcPopupKapat').onclick = kapat;
    wrap.querySelector('#dcPopupX').onclick = kapat;
    wrap.addEventListener('click', function(e){ if (e.target === wrap) kapat(); });
    if (o.onay && o.onayMetin){
        wrap.querySelector('#dcPopupOnay').onclick = function(){ wrap.remove(); o.onay(); };
    }
    if (window.lucide) lucide.createIcons();
}
function dcToggleRow(key, on){ if (on) dcSel.add(key); else dcSel.delete(key); dcUpdateBulk(); const all=document.getElementById('dcCheckAll'); const list=dcFiltered(); if(all){const sec=list.filter(r=>dcSel.has(dcKey(r))).length; all.checked=list.length>0&&sec===list.length;} }
function dcToggleAll(el){ const list = dcFiltered(); if (el.checked) list.forEach(r => dcSel.add(dcKey(r))); else list.forEach(r => dcSel.delete(dcKey(r))); dcRender(); }
function dcSelectFiltered(){ dcFiltered().forEach(r => dcSel.add(dcKey(r))); dcRender(); }
function dcClearSel(){ dcSel.clear(); dcRender(); }
function dcUpdateBulk(){ const n = dcSel.size; document.getElementById('dcBulk').classList.toggle('show', n > 0); document.getElementById('dcSelInfo').textContent = n + ' seçili'; }
function dcSelectedRows(){ return DC.rows.filter(r => dcSel.has(dcKey(r))); }

/* ── seçili müşterileri listeye toplu ekle ── */
function dcListeyeEkle(){
    const sel = document.getElementById('dcAtaListe');
    const listeId = sel.value;
    if (!listeId){ alert('Önce eklenecek listeyi seç.'); return; }
    const ids = dcSelectedRows().filter(r => r.kaynak === 'Müşteri').map(r => r.kaynak_id);
    if (!ids.length){ alert('Seçili müşteri yok. (Sadece "Müşteri" kayıtları listeye eklenebilir.)'); return; }
    const ad = sel.selectedOptions[0].textContent;
    if (!confirm(ids.length + ' müşteri "' + ad + '" listesine eklensin mi?')) return;
    const f = document.createElement('form');
    f.method = 'POST'; f.action = DC.base + '/listeye-ata';
    let html = '<input type="hidden" name="_token" value="' + DC.csrf + '"><input type="hidden" name="liste_id" value="' + listeId + '">';
    ids.forEach(id => { html += '<input type="hidden" name="musteri_ids[]" value="' + id + '">'; });
    f.innerHTML = html;
    document.body.appendChild(f); f.submit();
}

/* ── toplu gönder ── */
function dcBulkOpen(mode){
    dcBulkMode = mode;
    const rows = dcSelectedRows();
    const mailli = rows.filter(r => r.email && r.email.includes('@')).length;
    const smsli  = rows.filter(r => r.telefon && r.telefon.replace(/\D/g,'').length >= 10).length;
    document.getElementById('mbTitle').textContent = mode === 'mail' ? '📧 Toplu Mail Gönder' : '💬 Toplu SMS Gönder';
    document.getElementById('mbKonuWrap').style.display = mode === 'mail' ? 'flex' : 'none';
    document.getElementById('mbRcpt').textContent = mode === 'mail'
        ? (mailli + ' kişiye e-posta gönderilecek (seçilenlerden e-postası olanlar).')
        : (smsli + ' numaraya SMS gönderilecek (seçilenlerden geçerli telefonu olanlar).');
    document.getElementById('mbForm').action = mode === 'mail' ? DC.base + '/toplu-mail' : DC.base + '/toplu-sms';
    const wrap = document.getElementById('mbHidden'); wrap.innerHTML = '';
    rows.forEach(r => {
        if (mode === 'mail'){
            if (r.email && r.email.includes('@')){ const i=document.createElement('input'); i.type='hidden'; i.name='alicilar[]'; i.value=r.email; wrap.appendChild(i); }
        } else {
            const tel=(r.telefon||'').replace(/\D/g,''); if (tel.length>=10){ const i=document.createElement('input'); i.type='hidden'; i.name='numaralar[]'; i.value=tel; wrap.appendChild(i); }
        }
    });
    document.getElementById('mbMesaj').maxLength = mode === 'sms' ? 600 : 5000;
    dcSablonDoldur();
    document.getElementById('mbSablon').value = '';
    document.getElementById('mbModalBg').classList.add('show');
}
function dcBulkClose(){ document.getElementById('mbModalBg').classList.remove('show'); }
function dcBulkSubmit(){
    const n = document.querySelectorAll('#mbHidden input').length;
    if (n === 0){ alert('Seçilenlerde uygun alıcı yok.'); return false; }
    if (dcBulkMode === 'mail' && !document.getElementById('mbKonu').value.trim()){ alert('Konu gerekli.'); return false; }
    if (!document.getElementById('mbMesaj').value.trim()){ alert('Mesaj gerekli.'); return false; }
    return confirm(n + ' alıcıya gönderilsin mi?');
}

/* ── şablon ── */
function dcStrip(html){ const d=document.createElement('div'); d.innerHTML=String(html||''); return (d.textContent||d.innerText||'').replace(/\n{3,}/g,'\n\n').trim(); }
function dcSablonText(t){ return (t.body ?? t.icerik ?? t.content ?? t.mesaj ?? t.govde ?? ''); }
function dcSablonKonu(t){ return (t.subject ?? t.konu ?? t.baslik ?? t.name ?? t.ad ?? ''); }
function dcSablonAdi(t, i){ return (t.name ?? t.ad ?? t.baslik ?? ('Şablon #' + (t.id ?? (i+1)))); }
function dcSablonDoldur(){
    const sel = document.getElementById('mbSablon');
    const list = DC.sablonlar || [];
    document.getElementById('mbSablonWrap').style.display = list.length ? 'flex' : 'none';
    sel.innerHTML = '<option value="">— Şablon seç —</option>';
    list.forEach((t, i) => { const o=document.createElement('option'); o.value=i; o.textContent=dcSablonAdi(t, i); sel.appendChild(o); });
}
function dcSablonUygula(){
    const v = document.getElementById('mbSablon').value;
    if (v === '') return;
    const t = (DC.sablonlar || [])[v]; if (!t) return;
    if (dcBulkMode === 'mail'){
        const k = document.getElementById('mbKonu'); if (!k.value.trim()) k.value = dcSablonKonu(t);
        document.getElementById('mbMesaj').value = dcSablonText(t);
    } else {
        document.getElementById('mbMesaj').value = dcStrip(dcSablonText(t));
    }
}

/* ── kişi ekle / düzenle ── */
function dcOpen(){
    document.getElementById('dcModalTitle').textContent = 'Kişi Ekle';
    ['id','adi','email','telefon','sektor','il','ilce','unvan','adres'].forEach(f => document.getElementById('f_'+f).value = '');
    document.getElementById('f_durum').value = 'pasif';
    dcLdSet([]);
    document.getElementById('dcModalBg').classList.add('show');
}
function dcEdit(key){
    const r = DC.rows.find(x => dcKey(x) === key);
    if (!r || !r.d) return;
    document.getElementById('dcModalTitle').textContent = 'Kişi Düzenle';
    document.getElementById('f_id').value = r.kaynak_id;
    document.getElementById('f_adi').value = r.d.adi || '';
    document.getElementById('f_email').value = r.d.email || '';
    document.getElementById('f_telefon').value = r.d.telefon || '';
    document.getElementById('f_sektor').value = r.d.sektor || '';
    document.getElementById('f_il').value = r.d.il || '';
    document.getElementById('f_ilce').value = r.d.ilce || '';
    document.getElementById('f_unvan').value = r.d.unvan || '';
    document.getElementById('f_adres').value = r.d.adres || '';
    document.getElementById('f_durum').value = r.d.durum || 'pasif';
    dcLdSet(r.liste_ids || []);
    document.getElementById('dcModalBg').classList.add('show');
}
function dcClose(){ document.getElementById('dcModalBg').classList.remove('show'); }

/* ── liste seçim dropdown'u (checkbox'lı) ── */
function dcLdToggle(){ document.getElementById('dcListeDrop').classList.toggle('open'); }
function dcLdSync(){
    const sel = Array.from(document.querySelectorAll('.dc-ld-chk')).filter(c => c.checked);
    const lbl = document.getElementById('dcLdLabel');
    if (!sel.length){ lbl.textContent = 'Liste seç…'; lbl.style.color = 'var(--text-muted,#6b7280)'; }
    else if (sel.length <= 2){ lbl.textContent = sel.map(c => c.closest('.dc-ld-opt').textContent.trim()).join(', '); lbl.style.color = ''; }
    else { lbl.textContent = sel.length + ' liste seçili'; lbl.style.color = ''; }
}
function dcLdSet(ids){
    const set = new Set((ids || []).map(String));
    document.querySelectorAll('.dc-ld-chk').forEach(c => { c.checked = set.has(String(c.value)); });
    dcLdSync();
    const d = document.getElementById('dcListeDrop'); if (d) d.classList.remove('open');
}
/* panel dışına tıklayınca aç/kapa dropdownları kapat */
document.addEventListener('click', e => {
    ['dcListeDrop','dcFiltreListe'].forEach(id => { const d = document.getElementById(id); if (d && !d.contains(e.target)) d.classList.remove('open'); });
});

/* ── çok seçimli liste FİLTRESİ (toolbar) ── */
function dcFlToggle(){ document.getElementById('dcFiltreListe').classList.toggle('open'); }
function dcFlSelected(){ return Array.from(document.querySelectorAll('.dc-fl-chk')).filter(c => c.checked).map(c => String(c.value)); }
function dcFlChange(){
    const sel = Array.from(document.querySelectorAll('.dc-fl-chk')).filter(c => c.checked);
    const lbl = document.getElementById('dcFlLabel');
    if (!sel.length) lbl.textContent = 'Tüm listeler';
    else if (sel.length === 1) lbl.textContent = sel[0].closest('.dc-ld-opt').textContent.trim();
    else lbl.textContent = sel.length + ' liste';
    dcSayfa = 1; dcRender();
}

/* ── DIŞA AKTAR (filtrelenmiş müşteriler → Excel uyumlu CSV) ── */
function dcCsvHucre(v){ v = String(v ?? ''); return /[";\n\r]/.test(v) ? '"' + v.replace(/"/g, '""') + '"' : v; }
function dcListeAdlari(ids){
    const map = {}; (DC.listeler || []).forEach(l => map[String(l.id)] = l.ad);
    return (ids || []).map(id => map[String(id)] || '').filter(Boolean).join(' | ');
}
function dcExport(){
    const rows = dcFiltered();
    if (!rows.length){ alert('Dışa aktarılacak kayıt yok (filtreyi kontrol et).'); return; }
    const head = ['Ad / Firma','Kaynak','Sektör','Durum','E-posta','Telefon','Konum','Listeler','Kayıtlı','İletişim Yok'];
    const satir = [head.map(dcCsvHucre).join(';')];
    rows.forEach(r => satir.push([
        r.ad, r.kaynak, r.sektor || '', r.durum || '', r.email || '', r.telefon || '',
        r.konum || '', dcListeAdlari(r.liste_ids), r.kayit_sure || '', r.temas_sure || ''
    ].map(dcCsvHucre).join(';')));
    dcDosyaIndir('﻿' + satir.join('\r\n'), 'data-center-musteriler.csv');
}

/* ── İÇE AKTAR modalı ── */
function dcImportOpen(){ document.getElementById('impModalBg').classList.add('show'); }
function dcImportClose(){ document.getElementById('impModalBg').classList.remove('show'); }
function dcOrnekIndir(e){
    if (e) e.preventDefault();
    const csv = ['Ad;Email;Telefon;Sektör;İl;İlçe;Ünvan;Kategori;Adres',
                 'Örnek Firma A.Ş.;ornek@firma.com;0212 000 00 00;Optik;İstanbul;Şişli;Satın Alma;VIP;Merkez Mah. No:1'].join('\r\n');
    dcDosyaIndir('﻿' + csv, 'ornek-musteri-sablonu.csv');
}
function dcDosyaIndir(icerik, adi){
    const blob = new Blob([icerik], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob); a.download = adi;
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(() => URL.revokeObjectURL(a.href), 1000);
}
document.getElementById('dcModalBg').addEventListener('click', e => { if (e.target.id === 'dcModalBg') dcClose(); });
document.getElementById('mbModalBg').addEventListener('click', e => { if (e.target.id === 'mbModalBg') dcBulkClose(); });
document.getElementById('impModalBg').addEventListener('click', e => { if (e.target.id === 'impModalBg') dcImportClose(); });

['dcSearch','dcKaynak','dcDurum','dcSektor','dcKonum'].forEach(id => document.getElementById(id).addEventListener('input', () => { dcSayfa = 1; dcRender(); }));

/* sektör + konum filtrelerini doldur */
(function(){
    const uniq = (arr) => [...new Set(arr.filter(Boolean))].sort((a,b)=>a.localeCompare(b,'tr'));
    const sSel = document.getElementById('dcSektor'), kSel = document.getElementById('dcKonum');
    uniq(DC.rows.map(r => r.sektor)).forEach(s => { const o=document.createElement('option'); o.value=s; o.textContent=s; sSel.appendChild(o); });
    uniq(DC.rows.map(r => r.konum)).forEach(k => { const o=document.createElement('option'); o.value=k; o.textContent=k; kSel.appendChild(o); });
})();

dcRender();
</script>
@endpush