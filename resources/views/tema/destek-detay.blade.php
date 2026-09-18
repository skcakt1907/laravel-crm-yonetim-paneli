@extends('layouts.panel')

@section('page_title', 'Destek Talebi #' . $destek->id)

@section('panel_content')
@php
    $kullaniciAd = trim((Auth::guard('uye')->user()->ad ?? '').' '.(Auth::guard('uye')->user()->soyad ?? ''));
    $firmaAdi = config('app.name', 'Destek Ekibi');
    $kapali = ($destek->durum ?? 0) == 2;

    // Mesaj akışı: ana talep + cevaplar, eskiden yeniye
    $cevaplarSirali = $cevaplar ? $cevaplar->sortBy('id') : collect();
    $oncelikText = $destek->oncelik;
    if (is_numeric($destek->oncelik)) {
        $oncelikMap = ['0' => 'Normal', '1' => 'Yüksek', '2' => 'Acil'];
        $oncelikText = $oncelikMap[(string) $destek->oncelik] ?? 'Normal';
    }
@endphp

<div class="main-content destek-chat-wrap" style="padding:0;overflow:hidden">

    {{-- ÜST BİLGİ ŞERİDİ --}}
    <div class="destek-chat-header">
        <div class="dch-left">
            <div class="dch-avatar">🎧</div>
            <div>
                <div class="dch-title">{{ $destek->baslik }}</div>
                <div class="dch-meta">
                    <span><i class="fa fa-hashtag"></i> #{{ $destek->id }}</span>
                    <span><i class="fa fa-tag"></i> {{ $destek->hizmet ?? $destek->departman ?? 'Genel' }}</span>
                    <span><i class="fa fa-flag"></i> {{ $oncelikText }}</span>
                </div>
            </div>
        </div>
        <div class="dch-right">
            @if($destek->durum == 0)
                <span class="dch-status pending">🟡 Beklemede</span>
            @elseif($destek->durum == 1)
                <span class="dch-status answered">{{ __('messages.answered_badge') }}</span>
            @elseif($destek->durum == 2)
                <span class="dch-status closed">{{ __('messages.closed_badge') }}</span>
            @endif
            @if(!$kapali)
                <form action="{{ route('destek.kapat', $destek->id) }}" method="POST" style="display:inline;margin:0">
                    @csrf
                    <button type="submit" class="dch-close-btn"
                            onclick="return confirm('Bu talebi kapatmak istediğinizden emin misiniz? Kapatıldıktan sonra mesaj gönderemezsiniz.')">
                        <i class="fa fa-check"></i> {{ __('messages.close') }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- MESAJ BALONLARI --}}
    <div class="destek-chat-body" id="destekChatBody">
        {{-- İlk talep (her zaman müşteriden) --}}
        <div class="dch-msg me">
            <div class="dch-bubble">
                <div class="dch-bubble-head">
                    <strong>{{ $kullaniciAd ?: 'Siz' }}</strong>
                    <span class="dch-time">{{ date('d.m.Y H:i', strtotime($destek->tarih)) }}</span>
                </div>
                <div class="dch-bubble-body">{!! nl2br(e($destek->mesaj)) !!}</div>
                @if(!empty($destek->dosya))
                    <a class="dch-file" target="_blank" href="{{ asset($destek->dosya) }}">
                        <i class="fas fa-paperclip"></i> Eklenti
                    </a>
                @endif
            </div>
        </div>

        @foreach($cevaplarSirali as $cevap)
            @php
                // ========== ÇOKLU FALLBACK ile gönderen tespiti (29 Mayıs 2026 — KRİTİK BUG FIX) ==========
                // ÖNCEKİ BUG: $isAdmin = in_array((int)($cevap->durum ?? 0), [0, 1]);
                // → durum kolonuna bakıyordu, müşteri mesajlarını "admin" sayıyordu (durum=0 default)
                //
                // YENİ MANTIK (sıralı kontrol):
                // 1) gonderen_tip kolonu net 'admin' diyorsa → admin
                // 2) admin_id > 0 varsa → admin
                // 3) uyeid > 0 ise → müşteri (en güvenilir gösterge)
                // 4) Default → müşteri
                $isAdmin = false;
                if (isset($cevap->gonderen_tip) && $cevap->gonderen_tip === 'admin') {
                    $isAdmin = true;
                } elseif (!empty($cevap->admin_id) && (int)$cevap->admin_id > 0) {
                    $isAdmin = true;
                } elseif (!empty($cevap->uyeid) && (int)$cevap->uyeid > 0) {
                    $isAdmin = false;
                } elseif (isset($cevap->gonderen_tip)) {
                    $isAdmin = ($cevap->gonderen_tip === 'admin');
                }
            @endphp
            <div class="dch-msg {{ $isAdmin ? 'admin' : 'me' }}" data-msg-id="{{ $cevap->id }}">
                <div class="dch-bubble">
                    <div class="dch-bubble-head">
                        <strong>{{ $isAdmin ? $firmaAdi . ' Destek' : ($kullaniciAd ?: 'Siz') }}</strong>
                        <span class="dch-time">{{ date('d.m.Y H:i', strtotime($cevap->tarih)) }}</span>
                    </div>
                    <div class="dch-bubble-body">{!! nl2br(e($cevap->mesaj)) !!}</div>
                    @if(!empty($cevap->dosya))
                        <a class="dch-file" target="_blank" href="{{ asset($cevap->dosya) }}">
                            <i class="fas fa-paperclip"></i> Eklenti
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- MESAJ GÖNDERME ALANI --}}
    @if(!$kapali)
        <form class="destek-chat-input" action="{{ route('destek.cevapla', $destek->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label class="dch-file-btn" title="{{ __('messages.attach_file') }}">
                <i class="fas fa-paperclip"></i>
                <input type="file" name="dosya" accept="image/*,.pdf,.doc,.docx,.zip,.rar" onchange="this.nextElementSibling.textContent=this.files[0]?this.files[0].name:''">
                <span class="dch-file-name"></span>
            </label>
            <textarea name="mesaj" rows="1" placeholder="{{ __('messages.write_message_placeholder') }}" required
                      oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,140)+'px'"></textarea>
            <button type="submit" class="dch-send-btn">
                <i class="fas fa-paper-plane"></i> {{ __('messages.submit') }}
            </button>
            @error('mesaj')<div class="dch-error">{{ $message }}</div>@enderror
            @error('dosya')<div class="dch-error">{{ $message }}</div>@enderror
        </form>
    @else
        <div class="destek-chat-closed">
            <i class="fa fa-lock"></i> {{ __('messages.ticket_closed_note') }}
            <a href="{{ route('destek.talebi.olustur') }}">yeni bir talep</a> {{ __('messages.create_verb') }}
        </div>
    @endif

</div>

@push('styles')
<style>
/* ═══ DESTEK CHAT TASARIMI ═══ */
.destek-chat-wrap {
    display:flex;flex-direction:column;
    background:#f9fafb;border-radius:12px;
    box-shadow:0 2px 12px rgba(0,0,0,.06);
    min-height:65vh;
    overflow:hidden;
    border:1px solid #ececec;
}
/* Header */
.destek-chat-header {
    background:linear-gradient(135deg, #1a2332, #28364d);
    color:#fff;
    padding:14px 18px;
    display:flex;justify-content:space-between;align-items:center;gap:12px;
    flex-wrap:wrap;
}
.destek-chat-header .dch-left { display:flex;align-items:center;gap:12px;min-width:0;flex:1 }
.destek-chat-header .dch-avatar {
    width:42px;height:42px;border-radius:50%;
    background:rgba(255,255,255,.2);
    display:flex;align-items:center;justify-content:center;
    font-size:22px;flex-shrink:0;
}
.destek-chat-header .dch-title { font-weight:700;font-size:15px;color:#fff;line-height:1.3 }
.destek-chat-header .dch-meta {
    font-size:11.5px;opacity:.9;margin-top:3px;
    display:flex;gap:12px;flex-wrap:wrap;
}
.destek-chat-header .dch-meta span i { margin-right:3px;opacity:.7 }
.destek-chat-header .dch-right { display:flex;gap:8px;align-items:center;flex-wrap:wrap }
.dch-status { font-size:12px;padding:5px 10px;border-radius:8px;background:rgba(255,255,255,.18);font-weight:600 }
.dch-close-btn {
    background:#fff;color:#1a2332;border:none;
    padding:6px 14px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;
    transition:all .15s;
}
.dch-close-btn:hover { background:#fef3c7;color:#92400e }

/* Body — mesajlar */
.destek-chat-body {
    flex:1;padding:18px;overflow-y:auto;max-height:60vh;min-height:280px;
    display:flex;flex-direction:column;gap:14px;
    background:linear-gradient(180deg, #f9fafb 0%, #fff 100%);
}
.dch-msg { display:flex;max-width:78%; }
.dch-msg.me { align-self:flex-end;justify-content:flex-end }
.dch-msg.admin { align-self:flex-start;justify-content:flex-start }
.dch-bubble {
    padding:11px 14px;border-radius:14px;line-height:1.45;
    box-shadow:0 1px 3px rgba(0,0,0,.06);
    word-wrap:break-word;
}
.dch-msg.me .dch-bubble {
    background:linear-gradient(135deg, #1a2332, #28364d);
    color:#fff;border-bottom-right-radius:4px;
}
.dch-msg.admin .dch-bubble {
    background:#fff;color:#1f2937;
    border:1px solid #e5e7eb;border-bottom-left-radius:4px;
}
.dch-bubble-head {
    display:flex;justify-content:space-between;gap:10px;
    margin-bottom:5px;font-size:11.5px;opacity:.9;font-weight:600;
}
.dch-msg.me .dch-bubble-head strong { color:rgba(255,255,255,.95) }
.dch-msg.admin .dch-bubble-head strong { color:#1a2332 }
.dch-time { opacity:.7;font-weight:400;font-size:11px;white-space:nowrap }
.dch-bubble-body { font-size:14px;white-space:pre-wrap }
.dch-file {
    display:inline-flex;align-items:center;gap:5px;
    margin-top:8px;padding:5px 10px;border-radius:8px;
    background:rgba(255,255,255,.2);color:inherit;
    text-decoration:none;font-size:12px;
}
.dch-msg.admin .dch-file { background:#f3f4f6;color:#1a2332 }
.dch-file:hover { text-decoration:none;opacity:.85 }

/* Input alanı */
.destek-chat-input {
    background:#fff;border-top:1px solid #ececec;
    padding:10px 14px;
    display:flex;gap:8px;align-items:flex-end;
    position:relative;
}
.destek-chat-input textarea {
    flex:1;border:1px solid #e5e7eb;border-radius:20px;
    padding:10px 16px;font-size:14px;font-family:inherit;
    resize:none;max-height:140px;line-height:1.5;
    outline:none;transition:border-color .15s, box-shadow .15s;
}
.destek-chat-input textarea:focus {
    border-color:#1a2332;box-shadow:0 0 0 3px rgba(26,35,50,.12);
}
.dch-file-btn {
    background:#f3f4f6;border-radius:50%;width:42px;height:42px;
    display:flex;align-items:center;justify-content:center;cursor:pointer;
    flex-shrink:0;color:#6b7280;transition:all .15s;position:relative;
    margin:0;
}
.dch-file-btn:hover { background:#eef2ff;color:#1a2332 }
.dch-file-btn input[type=file] { display:none }
.dch-file-name {
    position:absolute;bottom:-18px;left:50%;transform:translateX(-50%);
    font-size:10px;color:#6b7280;white-space:nowrap;max-width:120px;
    overflow:hidden;text-overflow:ellipsis;
}
.dch-send-btn {
    background:linear-gradient(135deg, #1a2332, #28364d);
    color:#fff;border:none;border-radius:22px;
    padding:0 18px;height:42px;font-weight:600;font-size:13px;
    cursor:pointer;flex-shrink:0;display:flex;align-items:center;gap:6px;
    transition:filter .15s;box-shadow:0 3px 8px rgba(26,35,50,.25);
}
.dch-send-btn:hover { filter:brightness(.95) }
.dch-error {
    position:absolute;top:-22px;left:14px;
    background:#fef2f2;color:#dc2626;padding:3px 10px;
    border-radius:6px;font-size:12px;
}

/* Kapalı talep mesajı */
.destek-chat-closed {
    background:#f3f4f6;color:#6b7280;
    padding:18px;text-align:center;font-size:14px;
    border-top:1px solid #ececec;
}
.destek-chat-closed a { color:#1a2332;font-weight:600 }

/* Scrollbar */
.destek-chat-body::-webkit-scrollbar { width:6px }
.destek-chat-body::-webkit-scrollbar-thumb { background:#d1d5db;border-radius:3px }
.destek-chat-body::-webkit-scrollbar-thumb:hover { background:#9ca3af }

/* Mobile */
@media (max-width: 575.98px) {
    .destek-chat-header { padding:12px 14px }
    .destek-chat-header .dch-title { font-size:14px }
    .destek-chat-header .dch-meta { font-size:11px;gap:8px }
    .destek-chat-body { padding:12px;max-height:55vh }
    .dch-msg { max-width:90% }
    .dch-bubble-body { font-size:13.5px }
    .destek-chat-input { padding:8px 10px;gap:6px }
    .dch-file-btn { width:38px;height:38px }
    .dch-send-btn { padding:0 14px;height:38px;font-size:12px }
    .destek-chat-input textarea { padding:8px 14px;font-size:13.5px }
}
</style>
@endpush

@push('scripts')
<script>
(function(){
    var body = document.getElementById('destekChatBody');
    if (body) body.scrollTop = body.scrollHeight;

    // ========== AJAX POLLING SİSTEMİ ==========
    var TALEP_ID = {{ $destek->id }};
    var URL_POLL = "{{ url('destek/'.$destek->id.'/yeni-mesajlar') }}";
    var URL_POST = "{{ route('destek.cevapla.ajax', $destek->id) }}";
    var CSRF     = "{{ csrf_token() }}";
    var KULLANICI_AD = @json($kullaniciAd ?: 'Siz');
    var FIRMA_AD = @json($firmaAdi);
    var KAPALI = {{ $kapali ? 'true' : 'false' }};

    // Son görülen mesaj ID
    var lastSeenId = 0;
    document.querySelectorAll('.dch-msg[data-msg-id]').forEach(function(el){
        var i = parseInt(el.dataset.msgId, 10);
        if (i > lastSeenId) lastSeenId = i;
    });

    var originalTitle = document.title;
    var unreadCount = 0;
    var isWindowFocused = !document.hidden;

    window.addEventListener('focus', function(){
        isWindowFocused = true;
        unreadCount = 0;
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
        var wrap = document.createElement('div');
        wrap.className = 'dch-msg ' + (m.isAdmin ? 'admin' : 'me') + ' dch-new';
        wrap.setAttribute('data-msg-id', m.id);

        var html = ''
            + '<div class="dch-bubble">'
            +   '<div class="dch-bubble-head">'
            +     '<strong>' + escapeHtml(m.ad) + '</strong>'
            +     '<span class="dch-time">' + escapeHtml(m.tarih) + '</span>'
            +   '</div>'
            +   '<div class="dch-bubble-body">' + escapeHtml(m.mesaj).replace(/\n/g, '<br>') + '</div>';
        if (m.dosya) {
            html += '<a class="dch-file" target="_blank" href="' + escapeHtml(m.dosya) + '"><i class="fas fa-paperclip"></i> Eklenti</a>';
        }
        html += '</div>';
        wrap.innerHTML = html;

        body.appendChild(wrap);
        body.scrollTop = body.scrollHeight;
    }

    function playSound() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var o = ctx.createOscillator();
            var g = ctx.createGain();
            o.connect(g); g.connect(ctx.destination);
            o.type = 'sine';
            o.frequency.setValueAtTime(880, ctx.currentTime);
            o.frequency.exponentialRampToValueAtTime(440, ctx.currentTime + 0.18);
            g.gain.setValueAtTime(0.15, ctx.currentTime);
            g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.2);
            o.start();
            o.stop(ctx.currentTime + 0.2);
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
            if (!j.ok) return;

            var oldKapali = KAPALI;
            KAPALI = (j.durum === 2);

            if (j.mesajlar && j.mesajlar.length > 0) {
                var sesCalindi = false;
                j.mesajlar.forEach(function(m){
                    if (m.id > lastSeenId) {
                        appendMessage(m);
                        lastSeenId = m.id;
                        // Sadece KARŞIDAN gelen mesaj için ses + bildirim (admin → müşteri)
                        if (m.isAdmin && !isWindowFocused) {
                            unreadCount++;
                            if (!sesCalindi) { playSound(); sesCalindi = true; }
                            document.title = '(' + unreadCount + ') ' + originalTitle;
                        }
                    }
                });
            }

            // Talep kapatıldıysa input alanını gizle
            if (KAPALI && !oldKapali) {
                var inp = document.querySelector('.destek-chat-input');
                if (inp) inp.style.display = 'none';
                var closedBox = document.querySelector('.destek-chat-closed');
                if (!closedBox) {
                    var box = document.createElement('div');
                    box.className = 'destek-chat-closed';
                    box.innerHTML = '<i class="fa fa-lock"></i> Bu talep kapatıldı.';
                    body.parentNode.appendChild(box);
                }
            }
        })
        .catch(function(){
            pollFails++;
            if (pollFails > 10) return; // Çok hata varsa polling'i durdur
        });
    }

    // Her 3 saniyede bir
    setInterval(poll, 3000);

    // ========== AJAX FORM GÖNDERİM ==========
    var form = document.querySelector('.destek-chat-input');
    if (form) {
        var ta = form.querySelector('textarea');
        var sendBtn = form.querySelector('.dch-send-btn');

        function submitForm() {
            if (!ta.value.trim()) return;
            sendBtn.disabled = true;
            var orijIc = sendBtn.innerHTML;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gönderiliyor...';

            var fd = new FormData(form);
            fd.set('_token', CSRF);

            fetch(URL_POST, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r){ return r.json().catch(function(){ return null; }); })
            .then(function(j){
                if (j && j.ok) {
                    ta.value = '';
                    ta.style.height = 'auto';
                    var fileInput = form.querySelector('input[type=file]');
                    if (fileInput) fileInput.value = '';
                    var fnameSpan = form.querySelector('.dch-file-name');
                    if (fnameSpan) fnameSpan.textContent = '';

                    // Hemen göster (polling beklenmesin)
                    appendMessage({
                        id: j.id,
                        isAdmin: false,
                        ad: KULLANICI_AD,
                        mesaj: fd.get('mesaj'),
                        tarih: new Date().toLocaleString('tr-TR').slice(0,16).replace('T',' '),
                        dosya: null
                    });
                    lastSeenId = Math.max(lastSeenId, j.id);
                } else {
                    alert('Mesaj gönderilemedi. Lütfen tekrar deneyin.');
                }
            })
            .catch(function(){
                alert('Bağlantı hatası. Lütfen tekrar deneyin.');
            })
            .finally(function(){
                sendBtn.disabled = false;
                sendBtn.innerHTML = orijIc;
            });
        }

        form.addEventListener('submit', function(e){
            e.preventDefault();
            submitForm();
        });

        if (ta) {
            ta.addEventListener('keydown', function(e){
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    submitForm();
                }
            });
        }
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
@endpush
@endsection