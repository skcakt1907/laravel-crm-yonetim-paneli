@extends('admin._layout')

@section('title', '📤 Toplu Mesaj')

@push('head')
<style>
    .tm-tabs { display:flex; gap:4px; border-bottom:2px solid var(--border); margin-bottom:24px; flex-wrap:wrap; }
    .tm-tab { padding:10px 18px; background:none; border:none; cursor:pointer; font-size:14px; font-weight:600;
        color:var(--text-muted); border-bottom:2px solid transparent; margin-bottom:-2px; transition:all .15s;
        display:inline-flex; align-items:center; gap:8px; }
    .tm-tab:hover { color:var(--text); }
    .tm-tab.active { color:var(--brand-dark); border-bottom-color:var(--brand); }

    .tm-stat-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg);
        padding:14px 16px; display:flex; align-items:center; gap:12px; }
    .tm-stat-icon { width:42px; height:42px; border-radius:10px; display:flex; align-items:center;
        justify-content:center; font-size:20px; }
    .tm-stat-num { font-size:22px; font-weight:800; color:var(--text); line-height:1.1; }
    .tm-stat-lab { font-size:11px; color:var(--text-muted); text-transform:uppercase; letter-spacing:.04em; }
    .tm-grid-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:12px; margin-bottom:20px; }

    .tm-pill { display:inline-block; padding:3px 9px; border-radius:6px; font-size:11px; font-weight:600; }
    .tm-pill-mail { background:rgba(59,130,246,.12); color:#3b82f6; }
    .tm-pill-sms { background:rgba(16,185,129,.12); color:#10b981; }
    .tm-pill-reklam { background:rgba(245,158,11,.14); color:#d97706; }

    .tm-placeholders { background:var(--bg-subtle); padding:10px 12px; border-radius:8px;
        font-size:12px; color:var(--text-muted); margin-top:8px; line-height:1.8; }
    .tm-placeholders code { background:rgba(184,182,46,.15); color:var(--brand-dark); padding:2px 6px;
        border-radius:4px; font-size:11px; cursor:pointer; }

    .tm-sablon-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius);
        padding:12px 14px; margin-bottom:8px; display:flex; align-items:center; gap:10px; }
    .tm-sablon-info { flex:1; min-width:0; }
    .tm-sablon-baslik { font-weight:600; font-size:13px; color:var(--text); margin-bottom:2px; }
    .tm-sablon-meta { font-size:11px; color:var(--text-muted); }
    .tm-sablon-preview { font-size:11px; color:var(--text-muted); margin-top:4px; max-width:600px;
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

    /* Şablon modal */
    .tm-modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:2000;
        display:flex; align-items:flex-start; justify-content:center; padding:40px 16px; overflow-y:auto; }
    .tm-modal { background:var(--surface); border-radius:var(--radius); width:100%; max-width:560px;
        box-shadow:0 24px 60px rgba(0,0,0,.25); display:flex; flex-direction:column; }
    .tm-modal-head { display:flex; align-items:center; justify-content:space-between; padding:16px 20px;
        border-bottom:1px solid var(--border); }
    .tm-modal-head h3 { margin:0; font-size:16px; display:flex; align-items:center; gap:8px; color:var(--text); }
    .tm-modal-close { background:none; border:none; cursor:pointer; color:var(--text-muted); padding:4px;
        border-radius:6px; line-height:1; }
    .tm-modal-close:hover { background:var(--bg-subtle); color:var(--text); }
    .tm-modal-body { padding:20px; }
    .tm-modal-body .form-group { margin-bottom:14px; }
    .tm-modal-body label { display:block; font-size:12px; font-weight:600; color:var(--text); margin-bottom:5px; }
    .tm-modal-foot { display:flex; gap:10px; justify-content:flex-end; padding:14px 20px;
        border-top:1px solid var(--border); }

    .tm-log-row td { font-size:12.5px; padding:8px 12px; border-bottom:1px solid var(--border); }
    .tm-log-row:hover { background:var(--bg-subtle); }

    /* Kitle radio */
    .tm-kitle { display:flex; gap:10px; flex-wrap:wrap; }
    .tm-kitle label { flex:1; min-width:150px; display:flex; align-items:center; gap:8px; padding:10px 14px;
        border:1.5px solid var(--border); border-radius:10px; cursor:pointer; transition:all .15s; }
    .tm-kitle label:hover { border-color:var(--brand); }
    .tm-kitle input { accent-color:var(--brand); }
    .tm-kitle input:checked + span { color:var(--brand-dark); font-weight:700; }

    /* CRM filtre grid */
    .tm-crm-filtre { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:10px; }

    /* Manuel picker */
    .tm-picker { border:1px solid var(--border); border-radius:10px; padding:12px; background:var(--bg-subtle); }
    .tm-picker-results { max-height:220px; overflow-y:auto; margin-top:8px; }
    .tm-picker-item { display:flex; align-items:center; gap:8px; padding:7px 8px; border-radius:7px; cursor:pointer; font-size:13px; }
    .tm-picker-item:hover { background:var(--surface); }
    .tm-chips { display:flex; flex-wrap:wrap; gap:6px; margin-top:10px; }
    .tm-chip { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; background:var(--brand);
        color:#000; border-radius:20px; font-size:12px; font-weight:600; }
    .tm-chip button { background:none; border:none; cursor:pointer; color:#000; font-size:14px; line-height:1; padding:0; }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Toplu Mesaj</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">📤 Toplu Mesaj</h1>
        <div class="page-subtitle">Üye ve CRM müşterilerine toplu mail, SMS ve reklam gönderimi</div>
    </div>
</div>

{{-- İstatistik kartları --}}
<div class="tm-grid-stats">
    <div class="tm-stat-card">
        <div class="tm-stat-icon" style="background:rgba(59,130,246,.12);color:#3b82f6">📧</div>
        <div><div class="tm-stat-num">{{ number_format($istatistik['tum_aktif']) }}</div>
        <div class="tm-stat-lab">Mail Alıcı (Üye)</div></div>
    </div>
    <div class="tm-stat-card">
        <div class="tm-stat-icon" style="background:rgba(16,185,129,.12);color:#10b981">📱</div>
        <div><div class="tm-stat-num">{{ number_format($istatistik['tum_telefon']) }}</div>
        <div class="tm-stat-lab">SMS Alıcı (Üye)</div></div>
    </div>
    <div class="tm-stat-card">
        <div class="tm-stat-icon" style="background:rgba(245,158,11,.14);color:#d97706">📣</div>
        <div><div class="tm-stat-num">{{ number_format($istatistik['reklam_izin']) }}</div>
        <div class="tm-stat-lab">Reklam İzinli</div></div>
    </div>
    <div class="tm-stat-card">
        <div class="tm-stat-icon" style="background:rgba(139,92,246,.12);color:#8b5cf6">🏢</div>
        <div><div class="tm-stat-num">{{ number_format($istatistik['crm_toplam']) }}</div>
        <div class="tm-stat-lab">CRM Müşteri</div></div>
    </div>
</div>

{{-- Tab başlıkları --}}
<div class="tm-tabs">
    <button type="button" class="tm-tab {{ $tab === 'mail' ? 'active' : '' }}" data-tab="mail" onclick="tmTab('mail')">
        <i data-lucide="mail"></i><span>Mail</span>
    </button>
    <button type="button" class="tm-tab {{ $tab === 'sms' ? 'active' : '' }}" data-tab="sms" onclick="tmTab('sms')">
        <i data-lucide="message-square"></i><span>SMS</span>
    </button>
    <button type="button" class="tm-tab {{ $tab === 'reklam' ? 'active' : '' }}" data-tab="reklam" onclick="tmTab('reklam')">
        <i data-lucide="megaphone"></i><span>Reklam</span>
    </button>
    <button type="button" class="tm-tab {{ $tab === 'sablonlar' ? 'active' : '' }}" data-tab="sablonlar" onclick="tmTab('sablonlar')">
        <i data-lucide="bookmark"></i><span>Şablonlar</span>
        <span class="badge badge-neutral" style="font-size:10px">{{ count($sablonlar) }}</span>
    </button>
    <button type="button" class="tm-tab {{ $tab === 'gecmis' ? 'active' : '' }}" data-tab="gecmis" onclick="tmTab('gecmis')">
        <i data-lucide="history"></i><span>Geçmiş</span>
    </button>
</div>

{{-- Ortak alıcı-seçim bloğu (mail/sms/reklam form'larına @include ile gömülür) --}}
@php
    $renderHedefBlok = function($pfx, $reklamMi = false) use ($crmSektorler, $trIller, $istatistik) {
        return view('admin.toplu-mesaj._hedef', [
            'pfx' => $pfx, 'reklamMi' => $reklamMi,
            'crmSektorler' => $crmSektorler, 'trIller' => $trIller,
            'istatistik' => $istatistik,
        ])->render();
    };
@endphp

{{-- ═══ TAB: MAIL ═══ --}}
<div id="tab-mail" class="tm-tab-content" style="display:{{ $tab === 'mail' ? 'block' : 'none' }}">
    <form action="{{ route('admin.toplu-mesaj.mail.gonder') }}" method="POST" class="section">
        @csrf
        <div class="section-title"><i data-lucide="send"></i><span>Toplu Mail Gönder</span></div>

        <div class="form-group">
            <label class="form-label">Şablondan Seç (opsiyonel)</label>
            <select class="form-input" onchange="tmSablonYukle(this.value, 'mail')">
                <option value="">— Boş başla —</option>
                @foreach($sablonlar->where('tip', 'mail') as $s)
                    <option value="{{ $s->id }}">{{ $s->baslik }}{{ $s->kategori ? ' ['.$s->kategori.']' : '' }}</option>
                @endforeach
            </select>
        </div>

        {!! $renderHedefBlok('mail') !!}

        <div class="form-group">
            <label class="form-label">Konu <span class="required">*</span></label>
            <input type="text" name="konu" id="mail_konu" required class="form-input" placeholder="Örn: @{{firma_adi}} - Yeni Kampanyamız">
        </div>
        <div class="form-group">
            <label class="form-label">Mesaj İçeriği <span class="required">*</span></label>
            <textarea name="mesaj" id="mail_mesaj" rows="10" required class="form-textarea" placeholder="Sayın @{{musteri_adi}},&#10;&#10;Mesaj içeriği..."></textarea>
            @include('admin.toplu-mesaj._degiskenler', ['hedef' => 'mail_mesaj', 'phs' => ['ad','soyad','tam_ad','musteri_adi','email','telefon','kullanici_adi','sifre_sifirlama_link','firma_adi','tarih']])
            <div style="margin-top:6px">
                <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('mail_gorsel_input').click()">
                    <i data-lucide="image-plus"></i><span>Görsel Ekle</span>
                </button>
                <input type="file" id="mail_gorsel_input" accept="image/*" style="display:none" onchange="tmGorselYukle(this, 'mail_mesaj')">
                <span id="mail_gorsel_durum" style="font-size:11px;color:var(--text-muted);margin-left:6px"></span>
            </div>
        </div>
        @include('admin.toplu-mesaj._sablonkaydet', ['pfx' => 'mail'])
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px">
            <button type="button" class="btn btn-secondary" onclick="tmOnizle('mail', false)">
                <i data-lucide="eye"></i><span>Önizle</span>
            </button>
            <button type="submit" class="btn btn-primary" onclick="return confirm('Toplu mail gönderilsin mi?')">
                <i data-lucide="send"></i><span>Gönder</span>
            </button>
        </div>
    </form>
</div>

{{-- ═══ TAB: SMS ═══ --}}
<div id="tab-sms" class="tm-tab-content" style="display:{{ $tab === 'sms' ? 'block' : 'none' }}">
    <form action="{{ route('admin.toplu-mesaj.sms.gonder') }}" method="POST" class="section">
        @csrf
        <div class="section-title"><i data-lucide="message-square"></i><span>Toplu SMS Gönder</span></div>

        <div class="form-group">
            <label class="form-label">Şablondan Seç (opsiyonel)</label>
            <select class="form-input" onchange="tmSablonYukle(this.value, 'sms')">
                <option value="">— Boş başla —</option>
                @foreach($sablonlar->where('tip', 'sms') as $s)
                    <option value="{{ $s->id }}">{{ $s->baslik }}{{ $s->kategori ? ' ['.$s->kategori.']' : '' }}</option>
                @endforeach
            </select>
        </div>

        {!! $renderHedefBlok('sms') !!}

        <div class="form-group">
            <label class="form-label">SMS Mesajı <span class="required">*</span></label>
            <textarea name="mesaj" id="sms_mesaj" rows="5" required maxlength="1000" class="form-textarea"
                      oninput="document.getElementById('sms_count').innerText=this.value.length" placeholder="Sayın @{{musteri_adi}}, ..."></textarea>
            <div style="font-size:11px;color:var(--text-muted);margin-top:4px"><span id="sms_count">0</span> / 1000 karakter</div>
            @include('admin.toplu-mesaj._degiskenler', ['hedef' => 'sms_mesaj', 'phs' => ['ad','tam_ad','musteri_adi','firma_adi','tarih']])
        </div>
        @include('admin.toplu-mesaj._sablonkaydet', ['pfx' => 'sms'])
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px">
            <a href="{{ route('admin.ayarlar.sms') }}" class="btn btn-ghost btn-sm" style="margin-right:auto"><i data-lucide="settings"></i><span>SMS Ayarları</span></a>
            <button type="submit" class="btn btn-primary" onclick="return confirm('Toplu SMS gönderilsin mi? Maliyet doğurabilir!')">
                <i data-lucide="send"></i><span>SMS Gönder</span>
            </button>
        </div>
    </form>
</div>

{{-- ═══ TAB: REKLAM ═══ --}}
<div id="tab-reklam" class="tm-tab-content" style="display:{{ $tab === 'reklam' ? 'block' : 'none' }}">
    <form action="{{ route('admin.toplu-mesaj.reklam.gonder') }}" method="POST" class="section">
        @csrf
        <div class="section-title"><i data-lucide="megaphone"></i><span>Reklam / Kampanya Gönder</span></div>
        <div class="alert alert-info" style="margin-bottom:16px">
            <i data-lucide="info"></i>
            <div>Reklam gönderimi <strong>yalnızca kampanya iznine sahip üyelere</strong> yapılır (KVKK uyumu). CRM müşteri kitlesinde bu kısıt uygulanmaz.</div>
        </div>

        <div class="form-group">
            <label class="form-label">Şablondan Seç (opsiyonel)</label>
            <select class="form-input" onchange="tmSablonYukle(this.value, 'reklam')">
                <option value="">— Boş başla —</option>
                @foreach($sablonlar->where('tip', 'reklam') as $s)
                    <option value="{{ $s->id }}">{{ $s->baslik }}{{ $s->kategori ? ' ['.$s->kategori.']' : '' }}</option>
                @endforeach
            </select>
        </div>

        {!! $renderHedefBlok('reklam', true) !!}

        <div class="form-group">
            <label class="form-label">Konu <span class="required">*</span></label>
            <input type="text" name="konu" id="reklam_konu" required class="form-input" placeholder="Örn: Size özel kampanya!">
        </div>
        <div class="form-group">
            <label class="form-label">Reklam İçeriği <span class="required">*</span></label>
            <textarea name="mesaj" id="reklam_mesaj" rows="10" required class="form-textarea" placeholder="Sayın @{{musteri_adi}}, ..."></textarea>
            @include('admin.toplu-mesaj._degiskenler', ['hedef' => 'reklam_mesaj', 'phs' => ['ad','tam_ad','musteri_adi','firma_adi','site_url','tarih']])
            <div style="margin-top:6px">
                <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('reklam_gorsel_input').click()">
                    <i data-lucide="image-plus"></i><span>Görsel Ekle</span>
                </button>
                <input type="file" id="reklam_gorsel_input" accept="image/*" style="display:none" onchange="tmGorselYukle(this, 'reklam_mesaj')">
                <span id="reklam_gorsel_durum" style="font-size:11px;color:var(--text-muted);margin-left:6px"></span>
            </div>
        </div>
        @include('admin.toplu-mesaj._sablonkaydet', ['pfx' => 'reklam'])
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px">
            <button type="button" class="btn btn-secondary" onclick="tmOnizle('reklam', true)">
                <i data-lucide="eye"></i><span>Önizle</span>
            </button>
            <button type="submit" class="btn btn-primary" onclick="return confirm('Reklam gönderilsin mi?')">
                <i data-lucide="megaphone"></i><span>Reklam Gönder</span>
            </button>
        </div>
    </form>
</div>

{{-- ═══ TAB: ŞABLONLAR ═══ --}}
<div id="tab-sablonlar" class="tm-tab-content" style="display:{{ $tab === 'sablonlar' ? 'block' : 'none' }}">
    <div class="section">
        <div class="section-title" style="display:flex;align-items:center;justify-content:space-between;gap:10px">
            <span style="display:flex;align-items:center;gap:8px"><i data-lucide="bookmark"></i><span>Kayıtlı Şablonlar</span></span>
            <button type="button" class="btn btn-primary btn-sm" onclick="tmSablonYeni()">
                <i data-lucide="plus"></i><span>Yeni Şablon</span>
            </button>
        </div>
        @if($sablonlar->isEmpty())
            <div class="empty-state"><i data-lucide="bookmark" class="empty-state-icon"></i>
                <h4>Henüz şablon yok</h4>
                <p>Gönderim sırasında "Şablon olarak kaydet" işaretleyebilir ya da yukarıdan <b>Yeni Şablon</b> ile elle ekleyebilirsin.</p>
            </div>
        @else
            @foreach($sablonlar as $s)
                <div class="tm-sablon-card">
                    <span class="tm-pill tm-pill-{{ $s->tip }}">{{ strtoupper($s->tip) }}</span>
                    <div class="tm-sablon-info">
                        <div class="tm-sablon-baslik">{{ $s->baslik }}
                            @if($s->kategori)<span class="badge badge-neutral" style="font-size:10px">{{ $s->kategori }}</span>@endif
                        </div>
                        <div class="tm-sablon-meta">{{ $s->kullanim_sayisi }} kez kullanıldı @if($s->konu) · Konu: {{ $s->konu }} @endif</div>
                        <div class="tm-sablon-preview">{{ mb_substr(strip_tags($s->icerik), 0, 120) }}{{ mb_strlen($s->icerik) > 120 ? '…' : '' }}</div>
                    </div>
                    <button type="button" onclick="tmSablonKopyala({{ $s->id }}, '{{ $s->tip }}')" class="btn btn-ghost btn-sm" title="Tab'a yükle"><i data-lucide="copy"></i></button>
                    <button type="button" onclick='tmSablonDuzenle(@json($s))' class="btn btn-ghost btn-sm" title="Düzenle"><i data-lucide="edit-2"></i></button>
                    <button type="button" onclick="tmSablonSil({{ $s->id }}, '{{ addslashes($s->baslik) }}')" class="btn btn-ghost btn-sm" style="color:var(--danger)" title="Sil"><i data-lucide="trash-2"></i></button>
                </div>
            @endforeach
        @endif
    </div>
</div>

{{-- ═══ MODAL: Yeni / Düzenle Şablon ═══ --}}
<div id="tmSablonModal" class="tm-modal-overlay" style="display:none">
    <div class="tm-modal">
        <div class="tm-modal-head">
            <h3 id="tmModalTitle"><i data-lucide="bookmark"></i> <span>Yeni Şablon</span></h3>
            <button type="button" class="tm-modal-close" onclick="tmSablonModalKapat()"><i data-lucide="x"></i></button>
        </div>
        <div class="tm-modal-body">
            <input type="hidden" id="tmsId" value="">
            <div class="form-group">
                <label>Tip *</label>
                <select id="tmsTip" class="form-input" onchange="tmsKonuGoster()">
                    <option value="mail">📧 Mail</option>
                    <option value="sms">💬 SMS</option>
                    <option value="reklam">📣 Reklam</option>
                </select>
            </div>
            <div class="form-group" style="display:flex;gap:10px;flex-wrap:wrap">
                <div style="flex:2;min-width:180px">
                    <label>Başlık *</label>
                    <input type="text" id="tmsBaslik" class="form-input" placeholder="Şablon adı…">
                </div>
                <div style="flex:1;min-width:140px">
                    <label>Kategori</label>
                    <input type="text" id="tmsKategori" class="form-input" placeholder="ör. sektör/şehir">
                </div>
            </div>
            <div class="form-group" id="tmsKonuGroup">
                <label>Konu <span style="color:var(--text-muted);font-weight:400">(mail için)</span></label>
                <input type="text" id="tmsKonu" class="form-input" placeholder="Mail konusu…">
            </div>
            <div class="form-group">
                <label>İçerik *</label>
                <textarea id="tmsIcerik" class="form-input" rows="6" placeholder="Mesaj içeriği… değişkenler için aşağıdaki ipucuna bakın."></textarea>
                <div style="font-size:11px;color:var(--text-muted);margin-top:5px">
                    Otomatik değişkenler: <code>@{{ad}}</code> <code>@{{tam_ad}}</code> <code>@{{musteri_adi}}</code> <code>@{{firma_adi}}</code> <code>@{{tarih}}</code> — gönderimde her alıcıya göre dolar.
                </div>
            </div>
        </div>
        <div class="tm-modal-foot">
            <button type="button" class="btn btn-ghost" onclick="tmSablonModalKapat()">İptal</button>
            <button type="button" class="btn btn-primary" id="tmsKaydetBtn" onclick="tmSablonModalKaydet()">
                <i data-lucide="save"></i><span>Kaydet</span>
            </button>
        </div>
    </div>
</div>


{{-- ═══ TAB: GEÇMİŞ ═══ --}}
<div id="tab-gecmis" class="tm-tab-content" style="display:{{ $tab === 'gecmis' ? 'block' : 'none' }}">
    <div class="section">
        <div class="section-title"><i data-lucide="history"></i><span>Gönderim Geçmişi (Son 30)</span></div>
        @if($gecmis->isEmpty())
            <div class="empty-state"><i data-lucide="history" class="empty-state-icon"></i><h4>Henüz gönderim yok</h4></div>
        @else
            <div class="table-wrapper" style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse">
                    <thead><tr style="background:var(--bg-subtle)">
                        <th style="text-align:left;padding:10px 12px;font-size:11px;color:var(--text-muted)">Tarih</th>
                        <th style="text-align:left;padding:10px 12px;font-size:11px;color:var(--text-muted)">Tip</th>
                        <th style="text-align:left;padding:10px 12px;font-size:11px;color:var(--text-muted)">Başlık</th>
                        <th style="text-align:left;padding:10px 12px;font-size:11px;color:var(--text-muted)">Hedef</th>
                        <th style="text-align:left;padding:10px 12px;font-size:11px;color:var(--text-muted)">Gönderen</th>
                        <th style="text-align:center;padding:10px 12px;font-size:11px;color:var(--text-muted)">Sonuç</th>
                    </tr></thead>
                    <tbody>
                    @foreach($gecmis as $g)
                        <tr class="tm-log-row">
                            <td style="white-space:nowrap;color:var(--text-muted)">
                                @php try { $tar = \Carbon\Carbon::parse($g->created_at)->format('d.m.Y H:i'); } catch (\Throwable $e) { $tar = null; } @endphp
                                {{ $tar ?? '—' }}
                            </td>
                            <td><span class="tm-pill tm-pill-{{ $g->tip }}">{{ strtoupper($g->tip) }}</span></td>
                            <td>{{ $g->baslik ?? $g->konu ?? '—' }}</td>
                            <td style="font-size:11px;color:var(--text-muted)">{{ $g->hedef_detay ?? $g->hedef_grup ?? '—' }}</td>
                            <td style="color:var(--text-muted)">{{ $g->gonderen_adi ?? '—' }}</td>
                            <td style="text-align:center">
                                <span class="badge badge-success" title="Başarılı">✓ {{ $g->basarili }}</span>
                                @if($g->basarisiz > 0)<span class="badge badge-danger" title="{{ $g->son_hata }}">✗ {{ $g->basarisiz }}</span>@endif
                                @if($g->atlanan > 0)<span class="badge badge-neutral" title="Atlandı">⤴ {{ $g->atlanan }}</span>@endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Gizli şablon sil formları --}}
@foreach($sablonlar as $s)
    <form id="sablon-sil-form-{{ $s->id }}" action="{{ route('admin.toplu-mesaj.sablon.sil', $s->id) }}" method="POST" style="display:none">@csrf @method('DELETE')</form>
@endforeach

<script>
const _TMCSRF = '{{ csrf_token() }}';
const _TM_GORSEL_YUKLE_URL = '{{ route('admin.toplu-mesaj.gorsel.yukle') }}';
const _TM_ALICILAR_URL = '{{ route('admin.toplu-mesaj.alicilar') }}';

function tmTab(tab) {
    ['mail','sms','reklam','sablonlar','gecmis'].forEach(t => {
        const el = document.getElementById('tab-' + t);
        if (el) el.style.display = (t === tab) ? 'block' : 'none';
    });
    document.querySelectorAll('.tm-tab').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
    const url = new URL(window.location); url.searchParams.set('tab', tab); window.history.replaceState({}, '', url);
}

function tmSablonYukle(id, pfx) {
    if (!id) return;
    fetch('{{ url("/admin/toplu-mesaj/sablon") }}/' + id, { headers: { 'X-CSRF-TOKEN': _TMCSRF, 'Accept': 'application/json' } })
        .then(r => r.json()).then(d => {
            if (!d.success) { alert(d.message || 'Şablon yüklenemedi'); return; }
            const konu = document.getElementById(pfx + '_konu');
            if (konu) konu.value = d.sablon.konu || '';
            const mesaj = document.getElementById(pfx + '_mesaj');
            if (mesaj) mesaj.value = d.sablon.icerik || '';
            if (pfx === 'sms') document.getElementById('sms_count').innerText = (d.sablon.icerik || '').length;
        }).catch(err => alert('Hata: ' + err.message));
}

function tmSablonKopyala(id, tip) { tmTab(tip); setTimeout(() => tmSablonYukle(id, tip), 100); }

// ═══ Manuel Şablon: Yeni / Düzenle / Kaydet ═══
function tmsKonuGoster() {
    // Konu alanı sadece mail tipinde anlamlı; sms/reklamda gizle
    const tip = document.getElementById('tmsTip').value;
    document.getElementById('tmsKonuGroup').style.display = (tip === 'mail') ? 'block' : 'none';
}

function tmSablonModalAc() {
    document.getElementById('tmSablonModal').style.display = 'flex';
    if (window.lucide) window.lucide.createIcons();
}
function tmSablonModalKapat() {
    document.getElementById('tmSablonModal').style.display = 'none';
}

function tmSablonYeni() {
    document.getElementById('tmsId').value = '';
    document.getElementById('tmsTip').value = 'mail';
    document.getElementById('tmsBaslik').value = '';
    document.getElementById('tmsKategori').value = '';
    document.getElementById('tmsKonu').value = '';
    document.getElementById('tmsIcerik').value = '';
    document.querySelector('#tmModalTitle span').innerText = 'Yeni Şablon';
    tmsKonuGoster();
    tmSablonModalAc();
}

function tmSablonDuzenle(s) {
    document.getElementById('tmsId').value = s.id;
    document.getElementById('tmsTip').value = s.tip || 'mail';
    document.getElementById('tmsBaslik').value = s.baslik || '';
    document.getElementById('tmsKategori').value = s.kategori || '';
    document.getElementById('tmsKonu').value = s.konu || '';
    document.getElementById('tmsIcerik').value = s.icerik || '';
    document.querySelector('#tmModalTitle span').innerText = 'Şablonu Düzenle';
    tmsKonuGoster();
    tmSablonModalAc();
}

function tmSablonModalKaydet() {
    const id      = document.getElementById('tmsId').value;
    const tip     = document.getElementById('tmsTip').value;
    const baslik  = document.getElementById('tmsBaslik').value.trim();
    const kategori= document.getElementById('tmsKategori').value.trim();
    const konu    = document.getElementById('tmsKonu').value.trim();
    const icerik  = document.getElementById('tmsIcerik').value.trim();

    if (!baslik) { alert('Başlık gerekli.'); return; }
    if (!icerik) { alert('İçerik gerekli.'); return; }

    // Yeni mi güncelleme mi → route belirle
    const url = id
        ? '{{ url("/admin/toplu-mesaj/sablon") }}/' + id + '/guncelle'
        : '{{ route('admin.toplu-mesaj.sablon.kaydet') }}';

    const btn = document.getElementById('tmsKaydetBtn');
    btn.disabled = true;

    const fd = new FormData();
    fd.append('tip', tip);
    fd.append('baslik', baslik);
    fd.append('kategori', kategori);
    fd.append('konu', konu);
    fd.append('icerik', icerik);

    fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': _TMCSRF, 'Accept': 'application/json' },
        body: fd
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) { alert(d.message || 'Kaydedilemedi'); btn.disabled = false; return; }
        // Başarılı → Şablonlar sekmesinde sayfayı tazele (liste + sayaç güncellensin)
        const u = new URL(window.location);
        u.searchParams.set('tab', 'sablonlar');
        window.location = u.toString();
    })
    .catch(err => { alert('Hata: ' + err.message); btn.disabled = false; });
}

// Değişkeni imlecin bulunduğu yere ekle (yoksa sona)
// Görsel yükle, mesaj kutusuna görsel token'ı ekle (backend htmlWrap() bunu <img>'e çevirir).
// NOT: token'ı literal @{{ ile yazmıyoruz — Blade dosyada geçen her @{{...}} bloğunu kendi
// söz dizimi sanıp derlemeye çalışır, bu yüzden süslü parantezleri çalışma anında üretiyoruz.
function tmGorselYukle(inputEl, hedefId) {
    const dosya = inputEl.files && inputEl.files[0];
    if (!dosya) return;
    const durumId = hedefId.replace('_mesaj', '_gorsel_durum');
    const durum = document.getElementById(durumId);
    if (durum) durum.textContent = 'Yükleniyor…';

    const fd = new FormData();
    fd.append('gorsel', dosya);
    fd.append('_token', _TMCSRF);

    fetch(_TM_GORSEL_YUKLE_URL, {
        method: 'POST',
        body: fd,
        headers: { 'X-CSRF-TOKEN': _TMCSRF, 'Accept': 'application/json' },
    })
        .then(r => r.json())
        .then(d => {
            if (d && d.success && d.url) {
                const ac = String.fromCharCode(123, 123), kapa = String.fromCharCode(125, 125);
                tmDegiskenEkle(hedefId, ac + 'gorsel:' + d.url + kapa);
                if (durum) durum.textContent = 'Görsel eklendi ✓';
            } else {
                if (durum) durum.textContent = 'Yüklenemedi.';
            }
        })
        .catch(() => { if (durum) durum.textContent = 'Yüklenemedi.'; })
        .finally(() => { inputEl.value = ''; });
}

function tmDegiskenEkle(hedefId, deg) {
    const ta = document.getElementById(hedefId);
    if (!ta) return;
    const bas = ta.selectionStart ?? ta.value.length;
    const son = ta.selectionEnd ?? ta.value.length;
    ta.value = ta.value.substring(0, bas) + deg + ta.value.substring(son);
    const yeni = bas + deg.length;
    ta.focus();
    ta.setSelectionRange(yeni, yeni);
    if (hedefId === 'sms_mesaj') { const c = document.getElementById('sms_count'); if (c) c.innerText = ta.value.length; }
}

// Mail/Reklam şablonunu yeni sekmede önizle (yazılan mesajla)
function tmOnizle(pfx, reklam) {
    const mesaj = document.getElementById(pfx + '_mesaj') ? document.getElementById(pfx + '_mesaj').value : '';
    const form = document.createElement('form');
    form.method = 'POST'; form.action = '{{ route('admin.toplu-mesaj.onizleme') }}'; form.target = '_blank';
    form.innerHTML = '<input type="hidden" name="_token" value="' + _TMCSRF + '">' +
        '<input type="hidden" name="reklam" value="' + (reklam ? 1 : 0) + '">';
    const ta = document.createElement('input'); ta.type = 'hidden'; ta.name = 'mesaj'; ta.value = mesaj;
    form.appendChild(ta);
    document.body.appendChild(form); form.submit(); document.body.removeChild(form);
}
function tmSablonSil(id, baslik) {
    if (!confirm('"' + baslik + '" şablonunu silmek istediğine emin misin?')) return;
    document.getElementById('sablon-sil-form-' + id).submit();
}

// ── Kademeli il → ilçe (Türkiye geo) ──
const _TM_GEO_ILCE = '{{ route('admin.geo.ilceler') }}';
function trGeoIlceYukle(pfx) {
    const ilSel = document.getElementById(pfx + '_crm_il');
    const ilceSel = document.getElementById(pfx + '_crm_ilce');
    const ilId = ilSel.options[ilSel.selectedIndex]?.getAttribute('data-il-id');
    ilceSel.innerHTML = '<option value="">📍 Tüm İlçeler</option>';
    if (!ilId) { ilceSel.disabled = true; ilceSel.innerHTML = '<option value="">📍 Önce il seçin</option>'; return; }
    ilceSel.disabled = false;
    fetch(_TM_GEO_ILCE + '?il_id=' + ilId, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json()).then(list => {
            list.forEach(i => {
                const o = document.createElement('option');
                o.value = i.ad; o.textContent = i.ad;
                ilceSel.appendChild(o);
            });
        });
}

// ── Kitle / hedef / CRM filtre / manuel picker ──
function tmKitleDegis(pfx) {
    const kitle = document.querySelector(`input[name="kitle_${pfx}"]:checked`).value;
    // controller'a giden gizli kitle alanini guncelle
    const hid = document.getElementById(pfx + '_kitle_val');
    if (hid) hid.value = kitle;
    // hedef seçeneklerini kitleye göre değiştir
    const sel = document.getElementById(pfx + '_hedef');
    sel.querySelectorAll('option').forEach(o => {
        const forK = o.getAttribute('data-kitle');
        o.hidden = forK && forK !== kitle;
    });
    // görünür ilk option'ı seç
    const ilk = Array.from(sel.options).find(o => !o.hidden);
    if (ilk) sel.value = ilk.value;
    // Kitle değişti: manuel liste ve seçimler o kitleye göre yenilensin
    if (_tmListeKitle[pfx] && _tmListeKitle[pfx] !== kitle) {
        _tmListeKitle[pfx] = null;
        _tmSecili[pfx] = {};
        tmChipsCiz(pfx);
    }
    // Sektör alanı sadece CRM'de anlamlı (üyede sektör kolonu yok) — gizle/göster
    const sektorEl = document.getElementById(pfx + '_crm_sektor');
    if (sektorEl) {
        sektorEl.style.display = (kitle === 'crm') ? '' : 'none';
        if (kitle !== 'crm') sektorEl.value = '';
    }
    tmHedefDegis(pfx);
}
function tmHedefDegis(pfx) {
    const kitle = document.querySelector(`input[name="kitle_${pfx}"]:checked`).value;
    const hedef = document.getElementById(pfx + '_hedef').value;
    document.getElementById(pfx + '_picker').style.display = (hedef === 'manuel') ? 'block' : 'none';
    if (hedef === 'manuel') tmListeDoldur(pfx);
    // Konum filtresi: CRM'de filtre/tüm seçeneklerinde, üyede sadece "filtre" hedefinde göster
    const konumGoster = (kitle === 'crm' && hedef !== 'manuel') || (kitle === 'uye' && hedef === 'filtre');
    document.getElementById(pfx + '_crm_filtre').style.display = konumGoster ? 'block' : 'none';
}

// Manuel kişi seçimi — tüm liste sayfada gömülü, filtreli çoklu seçim
const _TM_KISILER = @json($tmKisiler ?? ['uye' => [], 'crm' => []]);
const _tmSecili = {}; // pfx -> {id: text}
const _tmListeKitle = {}; // pfx -> hangi kitle için dolduruldu

function tmEsc(t){ return String(t ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function tmListeDoldur(pfx) {
    const kitle = document.querySelector(`input[name="kitle_${pfx}"]:checked`).value;
    if (_tmListeKitle[pfx] === kitle) return; // zaten bu kitle için dolu
    _tmListeKitle[pfx] = kitle;
    const box = document.getElementById(pfx + '_picker_list');
    if (!box) return;
    const liste = _TM_KISILER[kitle] || [];
    box.innerHTML = liste.map(k =>
        `<label class="tm-picker-item" data-id="${k.id}" data-ara="${tmEsc((k.ad + ' ' + (k.alt || '')).toLowerCase())}"
                style="display:flex;align-items:center;gap:10px;padding:7px 12px;cursor:pointer;border-bottom:1px solid var(--border)">
            <input type="checkbox" onchange="tmKutucuk('${pfx}', ${k.id}, this)">
            <div style="min-width:0"><strong style="font-size:13px">${tmEsc(k.ad)}</strong>
                <div style="font-size:11px;color:var(--text-muted)">${tmEsc(k.alt || '')}</div></div>
        </label>`).join('');
    tmSenkron(pfx);
    tmFiltre(pfx);
}
function tmFiltre(pfx) {
    const q = (document.getElementById(pfx + '_picker_q').value || '').toLowerCase().trim();
    let gorunen = 0;
    document.querySelectorAll('#' + pfx + '_picker_list .tm-picker-item').forEach(el => {
        const uydu = !q || el.dataset.ara.includes(q);
        el.style.display = uydu ? 'flex' : 'none';
        if (uydu) gorunen++;
    });
    const sayi = document.getElementById(pfx + '_picker_sayi');
    if (sayi) sayi.textContent = gorunen + ' kişi listeleniyor · ' + Object.keys(_tmSecili[pfx] || {}).length + ' seçili';
}
function tmKutucuk(pfx, id, cb) {
    const kitle = _tmListeKitle[pfx];
    const kisi = (_TM_KISILER[kitle] || []).find(k => k.id == id);
    if (cb.checked) tmEkle(pfx, id, kisi ? kisi.ad : ('#' + id));
    else tmCikar(pfx, id);
}
function tmGorunenleriSec(pfx) {
    document.querySelectorAll('#' + pfx + '_picker_list .tm-picker-item').forEach(el => {
        if (el.style.display === 'none') return;
        const cb = el.querySelector('input[type=checkbox]');
        if (!cb.checked) { cb.checked = true; tmKutucuk(pfx, el.dataset.id, cb); }
    });
}
function tmSecimTemizle(pfx) {
    _tmSecili[pfx] = {};
    tmChipsCiz(pfx);
}
function tmSenkron(pfx) {
    const sec = _tmSecili[pfx] || {};
    document.querySelectorAll('#' + pfx + '_picker_list .tm-picker-item').forEach(el => {
        el.querySelector('input[type=checkbox]').checked = !!sec[el.dataset.id];
    });
}
function tmEkle(pfx, id, text) {
    if (!_tmSecili[pfx]) _tmSecili[pfx] = {};
    _tmSecili[pfx][id] = text;
    tmChipsCiz(pfx);
}
function tmCikar(pfx, id) { delete _tmSecili[pfx][id]; tmChipsCiz(pfx); }
function tmChipsCiz(pfx) {
    const wrap = document.getElementById(pfx + '_chips');
    const hid = document.getElementById(pfx + '_manuel_hidden');
    wrap.innerHTML = ''; hid.innerHTML = '';
    Object.entries(_tmSecili[pfx] || {}).forEach(([id, text]) => {
        const chip = document.createElement('span');
        chip.className = 'tm-chip';
        chip.innerHTML = text + ' <button type="button">×</button>';
        chip.querySelector('button').onclick = () => tmCikar(pfx, id);
        wrap.appendChild(chip);
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'manuel_ids[]'; inp.value = id;
        hid.appendChild(inp);
    });
    tmSenkron(pfx);
    tmFiltre(pfx);
}

document.addEventListener('DOMContentLoaded', () => {
    ['mail','sms','reklam'].forEach(p => { tmKitleDegis(p); });
});
</script>

@endsection