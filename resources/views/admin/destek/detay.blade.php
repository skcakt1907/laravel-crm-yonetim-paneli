@extends('admin._layout')

@section('title', 'Destek #' . $destek->id)

@push('head')
<style>
    .ticket-chat {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        overflow: hidden;
    }
    .msg-row {
        padding: 14px 18px;
        border-bottom: 1px solid var(--border);
    }
    .msg-row:last-child { border-bottom: none; }
    .msg-row.from-admin { background: var(--brand-soft); }
    .msg-row.from-customer { background: var(--bg-subtle); }

    /* Cevap sil butonu — hover'da görünür */
    .msg-row .cevap-sil-btn {
        position: absolute;
        top: 8px;
        right: 8px;
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger);
        border: none;
        border-radius: 6px;
        padding: 4px 8px;
        opacity: 0;
        transition: opacity 0.15s;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .msg-row:hover .cevap-sil-btn { opacity: 1; }
    .msg-row .cevap-sil-btn:hover { background: rgba(239, 68, 68, 0.2); }
    .msg-head {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 8px;
    }
    .msg-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 13px;
        flex-shrink: 0;
        color: #fff;
    }
    .msg-avatar.admin { background: linear-gradient(135deg, var(--brand), #8a8a1f); color: #000; }
    .msg-avatar.customer { background: linear-gradient(135deg, #3b82f6, #1e40af); }
    .msg-name { font-weight: 700; font-size: 13px; color: var(--text); }
    .msg-time { font-size: 11px; color: var(--text-muted); margin-left: auto; }
    .msg-body {
        font-size: 13.5px;
        line-height: 1.7;
        color: var(--text);
        white-space: pre-wrap;
        word-break: break-word;
    }
    .msg-file {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 8px;
        padding: 6px 10px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        font-size: 12px;
        color: var(--brand);
        text-decoration: none;
    }
    .msg-file:hover { background: var(--brand-soft); }
</style>
@endpush

@section('content')

@php
    $durum = (int)($destek->durum ?? 0);
    $durumMap = [
        0 => ['label' => 'Bekliyor', 'class' => 'badge-warning', 'icon' => '⏳', 'mini' => 'warning'],
        1 => ['label' => 'Cevaplandı', 'class' => 'badge-success', 'icon' => '✅', 'mini' => 'success'],
        2 => ['label' => 'Kapatıldı', 'class' => 'badge-neutral', 'icon' => '🔒', 'mini' => 'info'],
    ];
    $dur = $durumMap[$durum] ?? $durumMap[0];
    $musteriAdi = trim(($destek->ad ?? '').' '.($destek->soyad ?? '')) ?: ($destek->email ?? '—');

    $tarihFmt = null;
    if (!empty($destek->tarih)) {
        try { $tarihFmt = \Carbon\Carbon::parse($destek->tarih)->format('d.m.Y H:i'); } catch (\Throwable $e) {}
    }
@endphp

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.destek.index') }}">Destek</a>
    <span class="sep">/</span>
    <span class="current">#{{ $destek->id }}</span>
</div>

<div class="profile-header">
    <div class="profile-avatar" style="background:linear-gradient(135deg, #3b82f6, #1e40af); font-size:28px">🆘</div>
    <div class="profile-info">
        <h1 class="profile-name">{{ \Illuminate\Support\Str::limit($destek->baslik ?? 'Talep #'.$destek->id, 60) }}</h1>
        <div class="profile-meta">
            <span class="meta-item"><i data-lucide="hash"></i> Talep #{{ $destek->id }}</span>
            <span class="meta-item"><i data-lucide="user"></i> {{ $musteriAdi }}</span>
            @if(!empty($destek->email))
                <span class="meta-item"><i data-lucide="mail"></i> <a href="mailto:{{ $destek->email }}" style="color:inherit;text-decoration:none">{{ $destek->email }}</a></span>
            @endif
        </div>
        <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
            <span class="badge {{ $dur['class'] }}" style="font-size:12px;padding:5px 10px">
                {{ $dur['icon'] }} {{ $dur['label'] }}
            </span>
            @if(!empty($destek->oncelik))
                <span class="badge badge-neutral" style="font-size:12px;padding:5px 10px">⚡ {{ $destek->oncelik }}</span>
            @endif
            @if(!empty($destek->departman) || !empty($destek->hizmet))
                <span class="badge badge-neutral" style="font-size:12px;padding:5px 10px">🏷️ {{ $destek->departman ?? $destek->hizmet }}</span>
            @endif
        </div>
    </div>
    <div class="profile-actions">
        <a href="{{ route('admin.destek.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Listeye Dön</span>
        </a>
    </div>
</div>

{{-- Mini stats --}}
<div class="mini-stat-grid" style="margin-top:16px">
    <div class="mini-stat {{ $dur['mini'] }}">
        <div class="mini-stat-icon">{{ $dur['icon'] }}</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Durum</div>
            <div class="mini-stat-value" style="font-size:17px">{{ $dur['label'] }}</div>
        </div>
    </div>
    <div class="mini-stat info">
        <div class="mini-stat-icon">💬</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Mesaj Sayısı</div>
            <div class="mini-stat-value">{{ ($cevaplar->count() ?? 0) + 1 }}</div>
        </div>
    </div>
    <div class="mini-stat info">
        <div class="mini-stat-icon">📅</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Açılış</div>
            <div class="mini-stat-value" style="font-size:16px">
                {{ $tarihFmt ?? '—' }}
            </div>
        </div>
    </div>
    <div class="mini-stat {{ !empty($destek->atanan_admin_id) ? 'success' : 'warning' }}">
        <div class="mini-stat-icon">👤</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Atanan Admin</div>
            <div class="mini-stat-value" style="font-size:16px">
                {{ $atananAdmin->adi ?? $atananAdmin->kullaniciadi ?? 'Atanmamış' }}
            </div>
        </div>
    </div>
</div>

<div class="form-grid" style="margin-top:20px">
    {{-- SOL: Mesajlaşma --}}
    <div>
        <div class="section">
            <div class="section-title">
                <i data-lucide="messages-square"></i>
                <span>Mesajlaşma ({{ ($cevaplar->count() ?? 0) + 1 }})</span>
            </div>

            <div class="ticket-chat" id="adminChatBody">
                {{-- Ana mesaj (müşteri) --}}
                <div class="msg-row from-customer">
                    <div class="msg-head">
                        <div class="msg-avatar customer">{{ strtoupper(mb_substr($musteriAdi, 0, 1, 'UTF-8')) }}</div>
                        <div class="msg-name">{{ $musteriAdi }} <span style="font-size:10px;font-weight:600;margin-left:6px;padding:2px 8px;border-radius:10px;background:rgba(34,197,94,.15);color:#15803d">👤 Müşteri</span></div>
                        <div class="msg-time">{{ $tarihFmt ?? '—' }}</div>
                    </div>
                    <div class="msg-body">{{ $destek->mesaj ?? '' }}</div>
                    @if(!empty($destek->dosya))
                        <a href="{{ asset($destek->dosya) }}" target="_blank" class="msg-file">
                            <i data-lucide="paperclip" style="width:13px;height:13px"></i>
                            <span>Ek dosya</span>
                        </a>
                    @endif
                </div>

                {{-- Cevaplar --}}
                @foreach($cevaplar as $c)
                    @php
                        // ========== ÇOKLU FALLBACK ile gönderen tespiti (29 Mayıs 2026 fix) ==========
                        // 1) gonderen_tip kolonu net 'admin' diyorsa → admin
                        // 2) admin_id > 0 varsa → admin
                        // 3) uyeid > 0 ise → müşteri (en güvenilir)
                        // 4) Hiçbiri yoksa → müşteri varsayılan
                        $isAdmin = false;
                        if (isset($c->gonderen_tip) && $c->gonderen_tip === 'admin') {
                            $isAdmin = true;
                        } elseif (!empty($c->admin_id) && (int)$c->admin_id > 0) {
                            $isAdmin = true;
                        } elseif (!empty($c->uyeid) && (int)$c->uyeid > 0) {
                            $isAdmin = false; // uyeid varsa kesin müşteri
                        } elseif (isset($c->gonderen_tip)) {
                            $isAdmin = ($c->gonderen_tip === 'admin');
                        } else {
                            $isAdmin = false; // güvenli default → müşteri
                        }

                        // İsim: admin ise yöneticiden ad çek, yoksa "Admin"
                        $cName = null;
                        if ($isAdmin) {
                            if (!empty($c->admin_id)) {
                                try {
                                    $admin = \Illuminate\Support\Facades\DB::table('yoneticiler')->where('id', $c->admin_id)->first();
                                    if ($admin) {
                                        $cName = $admin->adi ?? $admin->kullaniciadi ?? 'Admin';
                                    }
                                } catch (\Throwable $e) {}
                            }
                            $cName = $cName ?: 'Admin';
                        } else {
                            $cName = trim(($c->ad ?? '').' '.($c->soyad ?? '')) ?: 'Müşteri';
                        }

                        $cTarih = null;
                        if (!empty($c->tarih)) {
                            try { $cTarih = \Carbon\Carbon::parse($c->tarih)->format('d.m.Y H:i'); } catch (\Throwable $e) {}
                        }
                    @endphp
                    <div class="msg-row {{ $isAdmin ? 'from-admin' : 'from-customer' }}" data-msg-id="{{ $c->id }}" style="position:relative">
                        <div class="msg-head">
                            <div class="msg-avatar {{ $isAdmin ? 'admin' : 'customer' }}">
                                {{ strtoupper(mb_substr($cName, 0, 1, 'UTF-8')) }}
                            </div>
                            <div class="msg-name">
                                {{ $cName }}
                                <span style="font-size:10px;font-weight:600;margin-left:6px;padding:2px 8px;border-radius:10px;{{ $isAdmin ? 'background:rgba(99,102,241,.15);color:#4338ca' : 'background:rgba(34,197,94,.15);color:#15803d' }}">
                                    {{ $isAdmin ? '🛡️ Admin' : '👤 Müşteri' }}
                                </span>
                            </div>
                            <div class="msg-time">{{ $cTarih ?? '—' }}</div>
                        </div>
                        <div class="msg-body">{{ $c->mesaj ?? '' }}</div>
                        @if(!empty($c->dosya))
                            <a href="{{ asset($c->dosya) }}" target="_blank" class="msg-file">
                                <i data-lucide="paperclip" style="width:13px;height:13px"></i>
                                <span>Ek dosya</span>
                            </a>
                        @endif

                        {{-- Mesaj sil butonu (hover'da görünür) --}}
                        <button type="button" onclick="cevapSil({{ $c->id }})"
                                class="cevap-sil-btn" title="Bu cevabı sil">
                            <i data-lucide="trash-2" style="width:12px;height:12px"></i>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- CEVAPLA --}}
        @if($durum !== 2)
            <div class="section">
                <div class="section-title">
                    <i data-lucide="reply"></i>
                    <span>Cevap Gönder</span>
                </div>

                <form id="adminCevapForm" action="{{ route('admin.destek.cevapla', $destek->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Mesaj <span class="required">*</span></label>
                        <textarea name="mesaj" rows="6" required class="form-textarea" placeholder="Cevabınızı yazın... (@kullaniciadi ile yöneticileri etiketleyebilirsiniz)"></textarea>
                        <small class="form-help">@kullaniciadi yazarak başka bir yöneticiyi etiketleyebilirsiniz (otomatik mail gider)</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Dosya Ekle (opsiyonel)</label>
                        <input type="file" name="dosya" class="form-input" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.zip,.rar">
                        <small class="form-help">Max 10MB · jpg/png/pdf/doc/zip</small>
                    </div>

                    <div style="display:flex;justify-content:flex-end;gap:8px">
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="send"></i>
                            <span>Cevap Gönder</span>
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>

    {{-- SAĞ: Yan panel --}}
    <div>
        {{-- Müşteri Bilgisi --}}
        <div class="section">
            <div class="section-title">
                <i data-lucide="user"></i>
                <span>Müşteri</span>
            </div>
            <div style="display:flex;align-items:center;gap:12px;padding:12px;background:var(--bg-subtle);border-radius:var(--radius-md);margin-bottom:10px">
                <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#1e40af);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;flex-shrink:0">
                    {{ strtoupper(mb_substr($musteriAdi, 0, 1, 'UTF-8')) }}
                </div>
                <div style="flex:1;min-width:0">
                    <div style="font-weight:700;font-size:13.5px;color:var(--text)">{{ $musteriAdi }}</div>
                    @if(!empty($destek->email))
                        <div style="font-size:11.5px;color:var(--text-muted)">{{ $destek->email }}</div>
                    @endif
                </div>
            </div>
            @if(!empty($destek->email))
                <a href="mailto:{{ $destek->email }}" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center">
                    <i data-lucide="mail"></i>
                    <span>Mail Gönder</span>
                </a>
            @endif
        </div>

        {{-- Admin Atama --}}
        <div class="section">
            <div class="section-title">
                <i data-lucide="user-check"></i>
                <span>Admin Ata</span>
            </div>

            <form action="{{ route('admin.destek.ata', $destek->id) }}" method="POST">
                @csrf
                <div class="form-group" style="margin-bottom:10px">
                    <select name="admin_id" class="form-select">
                        <option value="">— Atanmamış —</option>
                        @foreach($yoneticiler ?? [] as $y)
                            <option value="{{ $y->id }}" {{ ($destek->atanan_admin_id ?? null) == $y->id ? 'selected' : '' }}>
                                {{ $y->adi ?: $y->kullaniciadi }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="width:100%;justify-content:center">
                    <i data-lucide="save"></i>
                    <span>Ata</span>
                </button>
            </form>
        </div>

        {{-- Durum Değiştirme --}}
        <div class="section">
            <div class="section-title">
                <i data-lucide="flag"></i>
                <span>Durum</span>
            </div>

            <div style="display:flex;flex-direction:column;gap:6px">
                @if($durum !== 0)
                    <form action="{{ route('admin.destek.durum', ['id' => $destek->id, 'durum' => 0]) }}" method="POST" style="margin:0">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center">
                            <i data-lucide="clock"></i>
                            <span>Beklemeye Al</span>
                        </button>
                    </form>
                @endif
                @if($durum !== 1)
                    <form action="{{ route('admin.destek.durum', ['id' => $destek->id, 'durum' => 1]) }}" method="POST" style="margin:0" onsubmit="return confirm('Çözüldü olarak işaretle?');">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm" style="width:100%;justify-content:center;background:linear-gradient(135deg,#10b981,#059669)">
                            <i data-lucide="check-circle"></i>
                            <span>Çözüldü</span>
                        </button>
                    </form>
                @endif
                @if($durum !== 2)
                    <form action="{{ route('admin.destek.durum', ['id' => $destek->id, 'durum' => 2]) }}" method="POST" style="margin:0" onsubmit="return confirm('Talebi KAPAT?');">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center;color:var(--text-muted)">
                            <i data-lucide="lock"></i>
                            <span>Kapat</span>
                        </button>
                    </form>
                @endif

                <form action="{{ route('admin.destek.sil', $destek->id) }}" method="POST" onsubmit="return confirm('Bu talebi ve tüm cevaplarını SIL?\nGeri alınamaz!');" style="margin:0;margin-top:8px">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" style="width:100%;justify-content:center">
                        <i data-lucide="trash-2"></i>
                        <span>Talebi Sil</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
    Gizli cevap silme formları (iç içe form YASAK)
═══════════════════════════════════════════════════════════════ --}}
@foreach($cevaplar as $c)
    <form id="cevap-sil-form-{{ $c->id }}"
          action="{{ route('admin.destek.cevap.sil', ['id' => $destek->id, 'cevapId' => $c->id]) }}"
          method="POST" style="display:none">
        @csrf @method('DELETE')
    </form>
@endforeach

<script>
function cevapSil(cevapId) {
    if (!confirm('Bu cevabı silmek istediğine emin misin?\nBu işlem geri alınamaz!')) return;
    var f = document.getElementById('cevap-sil-form-' + cevapId);
    if (f) f.submit();
}
</script>


{{-- ========== AJAX POLLING — Anlık mesajlaşma ========== --}}
<script>
(function(){
    var TALEP_ID = {{ $destek->id }};
    var URL_POLL = "{{ url('admin/destek/'.$destek->id.'/yeni-mesajlar') }}";
    var URL_POST = "{{ route('admin.destek.cevapla.ajax', $destek->id) }}";
    var CSRF = "{{ csrf_token() }}";
    var MUSTERI_AD = @json($musteriAdi);

    var body = document.getElementById('adminChatBody');
    if (!body) return;

    var lastSeenId = 0;
    document.querySelectorAll('.msg-row[data-msg-id]').forEach(function(el){
        var i = parseInt(el.dataset.msgId, 10);
        if (i > lastSeenId) lastSeenId = i;
    });

    var originalTitle = document.title;
    var unreadCount = 0;
    var isWindowFocused = !document.hidden;

    window.addEventListener('focus', function(){
        isWindowFocused = true; unreadCount = 0;
        document.title = originalTitle;
    });
    window.addEventListener('blur', function(){ isWindowFocused = false; });

    function escapeHtml(s) {
        return (s == null ? '' : String(s))
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function appendMessage(m) {
        var row = document.createElement('div');
        row.className = 'msg-row ' + (m.isAdmin ? 'from-admin' : 'from-customer') + ' dch-new';
        row.setAttribute('data-msg-id', m.id);
        row.style.position = 'relative';

        var avatarChar = (m.ad || 'M').charAt(0).toUpperCase();
        var rozeti = m.isAdmin
            ? '<span style="font-size:10px;font-weight:600;margin-left:6px;padding:2px 8px;border-radius:10px;background:rgba(99,102,241,.15);color:#4338ca">🛡️ Admin</span>'
            : '<span style="font-size:10px;font-weight:600;margin-left:6px;padding:2px 8px;border-radius:10px;background:rgba(34,197,94,.15);color:#15803d">👤 Müşteri</span>';

        var html = ''
            + '<div class="msg-head">'
            +   '<div class="msg-avatar ' + (m.isAdmin ? 'admin' : 'customer') + '">' + escapeHtml(avatarChar) + '</div>'
            +   '<div class="msg-name">' + escapeHtml(m.ad) + ' ' + rozeti + '</div>'
            +   '<div class="msg-time">' + escapeHtml(m.tarih) + '</div>'
            + '</div>'
            + '<div class="msg-body">' + escapeHtml(m.mesaj) + '</div>';
        if (m.dosya) {
            html += '<a href="' + escapeHtml(m.dosya) + '" target="_blank" class="msg-file"><span>📎 Ek dosya</span></a>';
        }
        row.innerHTML = html;
        body.appendChild(row);
        body.scrollTop = body.scrollHeight;
    }

    function playSound() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var o = ctx.createOscillator(); var g = ctx.createGain();
            o.connect(g); g.connect(ctx.destination);
            o.type = 'sine';
            o.frequency.setValueAtTime(880, ctx.currentTime);
            o.frequency.exponentialRampToValueAtTime(440, ctx.currentTime + 0.18);
            g.gain.setValueAtTime(0.15, ctx.currentTime);
            g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.2);
            o.start(); o.stop(ctx.currentTime + 0.2);
        } catch(e){}
    }

    var pollFails = 0;
    function poll() {
        fetch(URL_POLL + '?after_id=' + lastSeenId, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r){ return r.ok ? r.json() : Promise.reject(r.status); })
        .then(function(j){
            pollFails = 0;
            if (!j.ok || !j.mesajlar) return;
            var sesCalindi = false;
            j.mesajlar.forEach(function(m){
                if (m.id > lastSeenId) {
                    appendMessage(m);
                    lastSeenId = m.id;
                    // Karşıdan (müşteriden) gelen mesaj için ses + bildirim
                    if (!m.isAdmin && !isWindowFocused) {
                        unreadCount++;
                        if (!sesCalindi) { playSound(); sesCalindi = true; }
                        document.title = '(' + unreadCount + ') ' + originalTitle;
                    }
                }
            });
        })
        .catch(function(){
            pollFails++;
            if (pollFails > 10) clearInterval(window._destekPollTimer);
        });
    }

    body.scrollTop = body.scrollHeight;
    window._destekPollTimer = setInterval(poll, 3000);

    // ========== AJAX FORM GÖNDERIM ==========
    var form = document.getElementById('adminCevapForm');
    if (form) {
        var ta = form.querySelector('textarea');
        var sendBtn = form.querySelector('button[type=submit]');

        function submit() {
            if (!ta || !ta.value.trim()) return;
            if (sendBtn) {
                sendBtn.disabled = true;
                var orig = sendBtn.innerHTML;
                sendBtn.innerHTML = '⏳ Gönderiliyor...';
            }

            var fd = new FormData(form);
            fd.set('_token', CSRF);

            fetch(URL_POST, {
                method: 'POST', body: fd, credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r){ return r.json().catch(function(){ return null; }); })
            .then(function(j){
                if (j && j.ok) {
                    var mesajMetni = fd.get('mesaj');
                    ta.value = '';
                    var fi = form.querySelector('input[type=file]');
                    if (fi) fi.value = '';

                    var simdi = new Date();
                    var trh = String(simdi.getDate()).padStart(2,'0') + '.'
                            + String(simdi.getMonth()+1).padStart(2,'0') + '.'
                            + simdi.getFullYear() + ' '
                            + String(simdi.getHours()).padStart(2,'0') + ':'
                            + String(simdi.getMinutes()).padStart(2,'0');

                    appendMessage({
                        id: j.id, isAdmin: true,
                        ad: 'Admin',
                        mesaj: mesajMetni,
                        tarih: trh,
                        dosya: null
                    });
                    lastSeenId = Math.max(lastSeenId, j.id);
                } else {
                    alert('Cevap gönderilemedi.');
                }
            })
            .catch(function(){ alert('Bağlantı hatası.'); })
            .finally(function(){
                if (sendBtn) { sendBtn.disabled = false; sendBtn.innerHTML = orig || 'Gönder'; }
            });
        }

        form.addEventListener('submit', function(e){
            e.preventDefault();
            submit();
        });
    }
})();
</script>

<style>
@keyframes dchFadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}
.dch-new { animation: dchFadeIn .3s ease-out; }
</style>

@endsection