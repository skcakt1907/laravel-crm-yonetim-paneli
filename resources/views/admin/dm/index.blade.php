@extends('admin._layout')

@section('title', 'Mesajlaşma (DM)')

@push('head')
<style>
    .dm-wrap{display:flex;gap:0;border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;background:var(--surface);height:calc(100vh - 170px);min-height:460px}
    .dm-list{width:300px;flex:0 0 300px;border-right:1px solid var(--border);display:flex;flex-direction:column;background:var(--surface)}
    .dm-list-head{padding:12px;border-bottom:1px solid var(--border)}
    .dm-list-head h3{margin:0 0 8px;font-size:15px;font-weight:700;color:var(--text)}
    .dm-search{width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:8px;background:var(--bg);color:var(--text);font-size:13px}
    .dm-people{overflow-y:auto;flex:1}
    .dm-person{display:flex;align-items:center;gap:10px;padding:10px 12px;text-decoration:none;border-bottom:1px solid var(--border);color:var(--text)}
    .dm-person:hover{background:var(--brand-soft)}
    .dm-person.active{background:var(--brand-soft);border-left:3px solid var(--brand)}
    .dm-ava{width:40px;height:40px;border-radius:50%;flex:0 0 auto;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px}
    .dm-person-main{flex:1;min-width:0}
    .dm-person-name{font-weight:600;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .dm-person-last{font-size:12px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .dm-person-meta{display:flex;flex-direction:column;align-items:flex-end;gap:4px}
    .dm-badge{background:#ef4444;color:#fff;font-size:10px;font-weight:700;min-width:18px;height:18px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;padding:0 5px}

    .dm-chat{flex:1;display:flex;flex-direction:column;min-width:0;background:var(--bg)}
    .dm-chat-head{padding:12px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;background:var(--surface)}
    .dm-chat-head .name{font-weight:700;font-size:14px;color:var(--text)}
    .dm-chat-head .rol{font-size:12px;color:var(--text-muted)}
    .dm-msgs{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:8px}
    .dm-row{display:flex}
    .dm-row.ben{justify-content:flex-end}
    .dm-bubble{max-width:72%;padding:8px 12px;border-radius:14px;font-size:13px;line-height:1.45;word-wrap:break-word;white-space:pre-wrap}
    .dm-row.karsi .dm-bubble{background:var(--surface);border:1px solid var(--border);color:var(--text);border-bottom-left-radius:4px}
    .dm-row.ben .dm-bubble{background:var(--msg-ben,#d4e9ff);color:var(--msg-ben-text,#0f2e4d);border-bottom-right-radius:4px}
    .dm-saat{font-size:10px;opacity:.7;margin-top:3px;text-align:right}
    .dm-bubble{position:relative}
    .dm-acts{display:none;gap:3px;position:absolute;top:-12px}
    .dm-row.ben .dm-acts{right:8px}.dm-row.karsi .dm-acts{left:8px}
    .dm-row:hover .dm-acts{display:flex}
    .dm-act{background:var(--surface);border:1px solid var(--border);border-radius:50%;width:24px;height:24px;line-height:1;font-size:12px;cursor:pointer;box-shadow:0 1px 5px rgba(0,0,0,.18);padding:0;color:var(--text)}
    .dm-act:hover{background:var(--brand);color:#1a1a1a}
    .dm-act.dm-act-sil{background:#ef4444;color:#fff;border-color:#ef4444}
    .dm-act.dm-act-sil:hover{background:#dc2626;border-color:#dc2626;color:#fff}
    .dm-quote{border-left:3px solid rgba(0,0,0,.3);background:rgba(0,0,0,.06);padding:4px 8px;border-radius:6px;font-size:12px;margin-bottom:5px;opacity:.85;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .dm-fwd{font-size:11px;opacity:.65;font-style:italic;margin-bottom:3px}
    .dm-replybar{display:none;align-items:center;gap:8px;padding:8px 12px;background:var(--brand-soft);border-top:1px solid var(--border);font-size:12.5px}
    .dm-replybar.acik{display:flex}
    .dm-replybar .rb-txt{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text)}
    .dm-replybar .rb-x{cursor:pointer;border:none;background:none;font-size:17px;color:var(--text)}
    .dm-fwdmodal{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;display:none;align-items:center;justify-content:center}
    .dm-fwdmodal.acik{display:flex}
    .dm-fwdbox{background:var(--surface);border-radius:14px;width:320px;max-height:72vh;overflow:auto;padding:12px}
    .dm-fwdbox h4{margin:4px 6px 12px;font-size:15px;color:var(--text)}
    .dm-fwditem{display:flex;align-items:center;gap:10px;padding:9px;border-radius:10px;cursor:pointer;color:var(--text)}
    .dm-fwditem:hover{background:var(--brand-soft)}
    .dm-mic-rec{background:#ef4444 !important;color:#fff !important;animation:dmpulse 1s infinite}
    @keyframes dmpulse{0%,100%{opacity:1}50%{opacity:.45}}
    .dm-sesbar{display:none;align-items:center;gap:10px;padding:10px 14px;background:var(--brand-soft);border-top:1px solid var(--border);font-size:13px}
    .dm-sesbar.acik{display:flex}
    .dm-sesbar .ses-dot{width:11px;height:11px;border-radius:50%;background:#ef4444;animation:dmpulse 1s infinite;flex:0 0 auto}
    .dm-sesbar .ses-sure{font-variant-numeric:tabular-nums;color:#ef4444;font-weight:700;flex:1}
    .dm-sesbar button{border:none;cursor:pointer;font-size:13px;padding:6px 14px;border-radius:8px}
    .dm-sesbar .ses-iptal{background:none;color:var(--text-muted)}
    .dm-sesbar .ses-gonder{background:var(--brand);color:#1a1a1a;font-weight:700}
    /* WhatsApp tarzı ses oynatıcı */
    .vmsg{display:flex;align-items:center;gap:8px;min-width:200px;max-width:280px}
    .vmsg-btn{flex:0 0 auto;width:34px;height:34px;border-radius:50%;border:none;cursor:pointer;background:rgba(0,0,0,.18);color:inherit;font-size:13px;display:flex;align-items:center;justify-content:center;line-height:1}
    .vmsg-wave{flex:1;display:flex;align-items:center;gap:2px;height:28px;cursor:pointer}
    .vmsg-bar{flex:1 1 0;min-width:2px;background:currentColor;opacity:.3;border-radius:2px}
    .vmsg-bar.on{opacity:.95}
    .vmsg-time{flex:0 0 auto;font-size:10px;opacity:.75;font-variant-numeric:tabular-nums;min-width:30px;text-align:right}
    .dm-foot{padding:10px 12px;border-top:1px solid var(--border);display:flex;gap:8px;background:var(--surface)}
    .dm-foot textarea{flex:1;resize:none;border:1px solid var(--border);border-radius:10px;padding:9px 12px;font-size:13px;background:var(--bg);color:var(--text);max-height:120px;font-family:inherit}
    .dm-chat{position:relative}
    .dm-aux-btn{background:transparent;border:none;color:var(--text-muted);font-size:20px;cursor:pointer;align-self:flex-end;width:38px;height:38px;border-radius:50%}
    .dm-aux-btn:hover{background:var(--brand-soft)}
    .dm-emoji-panel{display:none;position:absolute;left:12px;bottom:62px;width:330px;max-width:calc(100% - 24px);background:var(--surface);border:1px solid var(--border);border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.2);padding:8px;max-height:210px;overflow-y:auto;z-index:6;grid-template-columns:repeat(8,1fr);gap:2px}
    .dm-emoji-panel.acik{display:grid}
    .dm-emoji-panel button{background:transparent;border:none;font-size:21px;cursor:pointer;padding:4px;border-radius:6px;line-height:1}
    .dm-emoji-panel button:hover{background:var(--brand-soft)}
    .dm-empty{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--text-muted);gap:10px;text-align:center;padding:20px}
    @media (max-width:760px){
        .dm-wrap{height:calc(100vh - 140px)}
        .dm-list{width:120px;flex:0 0 120px}
        .dm-person-last,.dm-person .dm-person-meta{display:none}
    }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Mesajlaşma</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">💬 Mesajlaşma (DM)</h1>
        <div class="page-subtitle">Yöneticiler arası birebir mesajlaşma</div>
    </div>
</div>

@php
    function dm_bashar($ad){
        $p = preg_split('/\s+/', trim((string)$ad));
        $s = '';
        foreach (array_slice($p, 0, 2) as $x) { $s .= mb_substr($x, 0, 1, 'UTF-8'); }
        return mb_strtoupper($s ?: '?', 'UTF-8');
    }
    $dm_renkler = ['#6366f1','#0ea5e9','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#14b8a6'];
@endphp

<div class="dm-wrap">
    {{-- SOL: kişi listesi --}}
    <aside class="dm-list">
        <div class="dm-list-head">
            <h3>Kişiler</h3>
            <input type="text" id="dmSearch" class="dm-search" placeholder="Yeni mesaj için ara..." onkeyup="dmFilter()">
        </div>
        <div class="dm-people" id="dmPeople">
            @forelse($kisiler as $k)
                @php $renk = $dm_renkler[$k->id % count($dm_renkler)]; @endphp
                <a href="{{ route('admin.dm.konusma', $k->id) }}"
                   class="dm-person {{ ($aktif && $aktif->id == $k->id) ? 'active' : '' }}"
                   data-ad="{{ mb_strtolower($k->adgosterim,'UTF-8') }}">
                    @php $durRenk = ['cevrimici'=>'#22c55e','uzakta'=>'#f59e0b','cevrimdisi'=>'#9ca3af'][$k->durum ?? 'cevrimdisi'] ?? '#9ca3af'; @endphp
                    <span style="position:relative;flex:0 0 auto">
                        @if(!empty($k->foto))
                            <span class="dm-ava" style="overflow:hidden"><img src="{{ $k->foto }}" alt="" style="width:100%;height:100%;object-fit:cover"></span>
                        @else
                            <span class="dm-ava" style="background:{{ $renk }}">{{ dm_bashar($k->adgosterim) }}</span>
                        @endif
                        <span style="position:absolute;right:-1px;bottom:-1px;width:12px;height:12px;border-radius:50%;border:2px solid var(--surface);background:{{ $durRenk }}"></span>
                    </span>
                    <div class="dm-person-main">
                        <div class="dm-person-name">{{ $k->adgosterim }}</div>
                        <div class="dm-person-last">
                            @if($k->son_mesaj){{ $k->son_mesaj_ben ? 'Sen: ' : '' }}{{ \Illuminate\Support\Str::limit($k->son_mesaj, 26) }}@else<span style="opacity:.6">Henüz mesaj yok</span>@endif
                        </div>
                    </div>
                    <div class="dm-person-meta">
                        @if($k->son_mesaj_zaman)<span style="font-size:11px;color:var(--text-muted)">{{ $k->son_mesaj_zaman }}</span>@endif
                        @if($k->okunmamis > 0)<span class="dm-badge">{{ $k->okunmamis }}</span>@endif
                    </div>
                </a>
            @empty
                <div style="padding:16px;color:var(--text-muted);font-size:13px">Mesajlaşılacak yönetici yok.</div>
            @endforelse
        </div>
    </aside>

    {{-- SAĞ: sohbet --}}
    <main class="dm-chat">
        @if($aktif)
            @php $arenk = $dm_renkler[$aktif->id % count($dm_renkler)]; @endphp
            <div class="dm-chat-head">
                @if(!empty($aktif->foto))
                    <span class="dm-ava" style="width:36px;height:36px;overflow:hidden"><img src="{{ $aktif->foto }}" alt="" style="width:100%;height:100%;object-fit:cover"></span>
                @else
                    <span class="dm-ava" style="background:{{ $arenk }};width:36px;height:36px;font-size:13px">{{ dm_bashar($aktif->adgosterim) }}</span>
                @endif
                @php
                    $aDur = $aktif->durum ?? 'cevrimdisi';
                    $aDurRenk = ['cevrimici'=>'#22c55e','uzakta'=>'#f59e0b','cevrimdisi'=>'#9ca3af'][$aDur] ?? '#9ca3af';
                    $aDurEtiket = ['cevrimici'=>'Çevrimiçi','uzakta'=>'Uzakta','cevrimdisi'=>'Çevrimdışı'][$aDur] ?? 'Çevrimdışı';
                @endphp
                <div>
                    <div class="name">{{ $aktif->adgosterim }}</div>
                    <div class="rol" style="display:flex;align-items:center;gap:5px">
                        <span style="width:8px;height:8px;border-radius:50%;background:{{ $aDurRenk }};display:inline-block"></span>
                        {{ $aDurEtiket }}
                    </div>
                </div>
                <button type="button" onclick="msgRenkAc(this)" title="Mesaj balon rengi"
                        style="margin-left:auto;background:var(--brand-soft);border:1px solid var(--border);width:36px;height:36px;border-radius:10px;cursor:pointer;font-size:16px">🎨</button>
            </div>
            <div class="dm-msgs" id="dmMsgs"></div>
            <div id="dmEmojiPanel" class="dm-emoji-panel"></div>
            <form class="dm-foot" id="dmForm" onsubmit="return dmGonder(event)">
                <button type="button" class="dm-aux-btn" onclick="dmEmojiToggle()" title="Emoji">😊</button>
                <button type="button" class="dm-aux-btn" onclick="dmDosyaSec()" title="Dosya ekle">📎</button>
                <button type="button" class="dm-aux-btn" id="dmMicBtn" onclick="dmMicToggle()" title="Sesli mesaj">🎤</button>
                <input type="file" id="dmDosya" style="display:none" onchange="dmDosyaGonder(this)">
                <textarea id="dmInput" rows="1" placeholder="Mesaj yaz... (Enter ile gönder)" onkeydown="dmKey(event)"></textarea>
                <button type="submit" class="btn btn-primary" style="align-self:flex-end">
                    <i data-lucide="send"></i>
                </button>
            </form>
        @else
            <div class="dm-empty">
                <i data-lucide="message-circle" style="width:52px;height:52px;opacity:.35"></i>
                <div style="font-weight:700;color:var(--text);font-size:16px">Bir sohbet seç</div>
                <div>Soldan bir sohbete tıkla veya yukarıdan kişi ara, yeni bir sohbet başlat.</div>
            </div>
        @endif
    </main>
</div>

@endsection

@push('scripts')
<script>
/* ===== Mesaj balon rengi (bağımsız kopya — guard'lı) ===== */
(function(){
    if (window.__msgRenk) return; window.__msgRenk = true;
    var KEY='msgBenRenk', DEF='#d4e9ff';
    var PRESET=[['#d4e9ff','Soft Mavi'],['#d8f3d0','Soft Yeşil'],['#e7ddff','Soft Mor'],['#ffdce8','Soft Pembe'],['#e9edf2','Gri'],['#eef0c9','Sarı'],['#d0f0ee','Turkuaz'],['#ffe6cc','Şeftali']];
    function okunur(hex){ try{var r=parseInt(hex.substr(1,2),16),g=parseInt(hex.substr(3,2),16),b=parseInt(hex.substr(5,2),16); return (0.299*r+0.587*g+0.114*b)>140?'#0f2e4d':'#ffffff';}catch(e){return '#0f2e4d';} }
    function uygula(c){ var d=document.documentElement.style; d.setProperty('--msg-ben',c); d.setProperty('--msg-ben-text', okunur(c)); }
    window.msgRenkUygula=uygula;
    uygula(localStorage.getItem(KEY) || DEF);
    window.msgRenkSec=function(c){ localStorage.setItem(KEY,c); uygula(c); var i=document.querySelector('#msg-renk-pop input[type=color]'); if(i)i.value=c; document.querySelectorAll('#msg-renk-pop [data-sw]').forEach(function(b){ b.style.borderColor=(b.dataset.sw.toLowerCase()===c.toLowerCase()?'#333':'#fff'); }); };
    window.msgRenkAc=function(btn){
        var old=document.getElementById('msg-renk-pop'); if(old){ old.remove(); return; }
        var cur=localStorage.getItem(KEY)||DEF;
        var pop=document.createElement('div'); pop.id='msg-renk-pop';
        pop.style.cssText='position:fixed;z-index:100050;background:#fff;border:1px solid #ddd;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.22);padding:12px;width:210px';
        var sw=PRESET.map(function(p){return '<button type="button" data-sw="'+p[0]+'" onclick="msgRenkSec(\''+p[0]+'\')" title="'+p[1]+'" style="width:34px;height:34px;border-radius:50%;border:2px solid '+(p[0].toLowerCase()===cur.toLowerCase()?'#333':'#fff')+';background:'+p[0]+';cursor:pointer;box-shadow:0 0 0 1px #e2e2e2"></button>';}).join('');
        pop.innerHTML='<div style="font-size:12px;font-weight:700;color:#333;margin-bottom:9px">🎨 Mesaj Balon Rengi</div>'
            +'<div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px">'+sw+'</div>'
            +'<label style="display:flex;align-items:center;gap:8px;font-size:12px;color:#444">Özel renk: <input type="color" value="'+cur+'" oninput="msgRenkSec(this.value)" style="width:40px;height:28px;border:1px solid #ddd;border-radius:6px;background:none;cursor:pointer"></label>';
        document.body.appendChild(pop);
        var r=btn.getBoundingClientRect();
        pop.style.top=Math.min(r.bottom+6, window.innerHeight-180)+'px';
        pop.style.left=Math.max(8, Math.min(r.left, window.innerWidth-224))+'px';
        setTimeout(function(){ document.addEventListener('click', function kapat(e){ if(!pop.contains(e.target) && e.target!==btn){ pop.remove(); document.removeEventListener('click',kapat); } }); },60);
    };
})();

function dmFilter(){
    const q=(document.getElementById('dmSearch').value||'').toLowerCase();
    document.querySelectorAll('#dmPeople .dm-person').forEach(function(el){
        el.style.display = (el.dataset.ad||'').indexOf(q)>-1 ? '' : 'none';
    });
}

@if($aktif)
const _DM_CSRF = document.querySelector('meta[name="csrf-token"]').content;
const _DM_AKTIF = {{ $aktif->id }};
const _DM_MSG_URL = @json(url('admin/dm/'.$aktif->id.'/mesajlar'));
const _DM_GONDER = @json(route('admin.dm.gonder'));
const _DM_ILET = @json(route('admin.dm.ilet'));
const _DM_MESAJ = @json(url('admin/dm/mesaj'));
const _DM_KISILER = @json(route('admin.dm.kisiler'));
let _dmLastId = 0;
let _dmGonderiyor = false;
let _dmSeen = new Set();
let _dmReplyTo = null, _dmIletMid = null;
let _dmRec=null, _dmSesChunks=[], _dmSesTimer=null, _dmSesSure=0, _dmSesIptal=false;
const _DM_SES_MAX=120;
const _dmCache = {};

// ===== WhatsApp tarzı ses oynatıcı (widget ile ortak; guard'lı) =====
window.vmsgFmt = window.vmsgFmt || function(s){ s=Math.max(0,Math.floor(s||0)); var d=Math.floor(s/60),k=s%60; return d+':'+(k<10?'0':'')+k; };
window.vmsgHtml = window.vmsgHtml || function(src){
    var H=[35,55,80,45,95,60,30,70,50,85,40,65,90,55,35,75,50,95,45,60,80,40,70,55,85,45,65,50];
    var bars=H.map(function(h){return '<span class="vmsg-bar" style="height:'+h+'%"></span>';}).join('');
    return '<div class="vmsg"><button type="button" class="vmsg-btn">▶</button>'
         + '<div class="vmsg-wave">'+bars+'</div><span class="vmsg-time">0:00</span>'
         + '<audio class="vmsg-audio" preload="metadata" src="'+src+'"></audio></div>';
};
window.vmsgWire = window.vmsgWire || function(){
    if(window.__vmsgWired) return; window.__vmsgWired=true;
    document.addEventListener('click', function(e){
        var btn=e.target.closest('.vmsg-btn'); var wave=e.target.closest('.vmsg-wave');
        if(btn){
            var a=btn.closest('.vmsg').querySelector('.vmsg-audio');
            document.querySelectorAll('.vmsg-audio').forEach(function(o){ if(o!==a) o.pause(); });
            if(a.paused){
                if(!isFinite(a.duration) || a.duration===0){
                    a.currentTime=1e7;
                    var fix=function(){ a.removeEventListener('durationchange',fix); a.currentTime=0; a.play(); };
                    a.addEventListener('durationchange', fix);
                } else { a.play(); }
            } else { a.pause(); }
        } else if(wave){
            var a2=wave.closest('.vmsg').querySelector('.vmsg-audio');
            var r=wave.getBoundingClientRect(); var frac=(e.clientX-r.left)/r.width;
            if(isFinite(a2.duration)) a2.currentTime=Math.max(0,Math.min(1,frac))*a2.duration;
        }
    });
    function box(e){ return e.target && e.target.closest ? e.target.closest('.vmsg') : null; }
    document.addEventListener('play', function(e){ var b=box(e); if(b) b.querySelector('.vmsg-btn').textContent='⏸'; }, true);
    document.addEventListener('pause', function(e){ var b=box(e); if(b) b.querySelector('.vmsg-btn').textContent='▶'; }, true);
    document.addEventListener('ended', function(e){ var b=box(e); if(b){ b.querySelector('.vmsg-btn').textContent='▶'; b.querySelectorAll('.vmsg-bar').forEach(function(x){x.classList.remove('on');}); } }, true);
    document.addEventListener('loadedmetadata', function(e){ var b=box(e); if(b && isFinite(e.target.duration)){ b.querySelector('.vmsg-time').textContent=vmsgFmt(e.target.duration); } }, true);
    document.addEventListener('timeupdate', function(e){ var b=box(e); if(!b) return; var a=e.target; var dur=isFinite(a.duration)?a.duration:0;
        b.querySelector('.vmsg-time').textContent=vmsgFmt(a.currentTime||0);
        var bars=b.querySelectorAll('.vmsg-bar'); var on=dur?Math.round((a.currentTime/dur)*bars.length):0;
        bars.forEach(function(x,i){ x.classList.toggle('on', i<on); });
    }, true);
};
vmsgWire();

function dmEsc(s){const d=document.createElement('div');d.textContent=s==null?'':s;return d.innerHTML;}
function dmTik(m){
    if(!m.ben) return '';
    var ok=(m.okundu==1);
    return '<span class="dm-tik" data-mid="'+m.id+'" style="margin-left:5px;font-size:11px;color:'+(ok?'#1976d2':'rgba(0,0,0,.4)')+'">'+(ok?'✓✓':'✓')+'</span>';
}

function dmDosya(m){
    if(!m.dosya) return '';
    if(m.ses) return window.vmsgHtml(dmEsc(m.dosya));
    if(m.resim) return '<a href="'+dmEsc(m.dosya)+'" target="_blank"><img src="'+dmEsc(m.dosya)+'" style="max-width:240px;max-height:240px;border-radius:10px;display:block;margin-bottom:5px"></a>';
    return '<a href="'+dmEsc(m.dosya)+'" target="_blank" style="display:flex;align-items:center;gap:6px;color:inherit;margin-bottom:5px;font-weight:600">📎 '+dmEsc(m.dosya_ad||'Dosya')+'</a>';
}
function dmAlinti(m){
    if(!m.yanit) return '';
    return '<div class="dm-quote">'+dmEsc(m.yanit.kim)+': '+dmEsc(m.yanit.mesaj)+'</div>';
}
function dmAksiyon(m){
    var b='<button type="button" class="dm-act" title="Cevapla" onclick="dmCevapla('+m.id+')">↩</button>'
        + '<button type="button" class="dm-act" title="İlet" onclick="dmIletAc('+m.id+')">↪</button>';
    if(m.ben){
        b+='<button type="button" class="dm-act" title="Düzenle" onclick="dmDuzenle('+m.id+')">✏️</button>'
         + '<button type="button" class="dm-act dm-act-sil" title="Sil" onclick="dmSil('+m.id+')">🗑️</button>';
    }
    return '<div class="dm-acts">'+b+'</div>';
}
function dmBubble(m){
    _dmCache[m.id]=m;
    const row=document.createElement('div');
    row.className='dm-row '+(m.ben?'ben':'karsi');
    row.dataset.id=m.id;
    var fwd = m.iletildi ? '<div class="dm-fwd">↪ İletildi</div>' : '';
    var icerik = fwd + dmAlinti(m) + dmDosya(m) + (m.mesaj ? '<span class="dm-txt">'+dmEsc(m.mesaj)+'</span>' : '');
    var duz = m.duzenlendi ? ' <span class="dm-duz" style="opacity:.6;font-size:10px">(düzenlendi)</span>' : '';
    row.innerHTML='<div class="dm-bubble">'+icerik+'<div class="dm-saat">'+dmEsc(m.saat)+duz+dmTik(m)+'</div>'+dmAksiyon(m)+'</div>';
    return row;
}

window.dmCevapla=function(mid){
    var m=_dmCache[mid]; if(!m) return;
    _dmReplyTo=m;
    var bar=document.getElementById('dmReplyBar');
    if(bar){ bar.querySelector('.rb-txt').textContent=(m.ben?'Sen':'{{ $aktif->adgosterim ?? '' }}')+': '+(m.mesaj||(m.dosya_ad||'Dosya')); bar.classList.add('acik'); }
    document.getElementById('dmInput')?.focus();
};
window.dmCevapIptal=function(){ _dmReplyTo=null; document.getElementById('dmReplyBar')?.classList.remove('acik'); };

window.dmDuzenle=function(mid){
    var m=_dmCache[mid]; if(!m) return;
    var yeni=prompt('Mesajı düzenle:', m.mesaj||'');
    if(yeni===null) return; yeni=yeni.trim();
    if(!yeni){ alert('Boş mesaj olamaz.'); return; }
    fetch(_DM_MESAJ+'/'+mid+'/duzenle',{method:'POST',headers:{'X-CSRF-TOKEN':_DM_CSRF,'Accept':'application/json'},body:new URLSearchParams({mesaj:yeni})})
      .then(r=>r.json()).then(d=>{
        if(d.success&&d.mesaj){
            _dmCache[mid]=d.mesaj;
            var t=document.querySelector('#dmMsgs .dm-row[data-id="'+mid+'"] .dm-txt');
            if(t) t.textContent=d.mesaj.mesaj;
            var saat=document.querySelector('#dmMsgs .dm-row[data-id="'+mid+'"] .dm-saat');
            if(saat && !saat.querySelector('.dm-duz')){ var s=document.createElement('span'); s.className='dm-duz'; s.style.cssText='opacity:.6;font-size:10px'; s.textContent=' (düzenlendi)'; saat.insertBefore(s, saat.firstChild.nextSibling||null); }
        } else { alert(d.message||'Düzenlenemedi'); }
      }).catch(err=>alert('Hata: '+err.message));
};

window.dmSil=function(mid){
    if(!confirm('Bu mesajı silmek istediğine emin misin?')) return;
    fetch(_DM_MESAJ+'/'+mid+'/sil',{method:'POST',headers:{'X-CSRF-TOKEN':_DM_CSRF,'Accept':'application/json'}})
      .then(r=>r.json()).then(d=>{
        if(d.success){ var row=document.querySelector('#dmMsgs .dm-row[data-id="'+mid+'"]'); if(row) row.remove(); }
        else { alert(d.message||'Silinemedi'); }
      }).catch(err=>alert('Hata: '+err.message));
};

window.dmIletAc=function(mid){
    _dmIletMid=mid;
    var modal=document.getElementById('dmFwdModal');
    var box=modal.querySelector('.dm-fwd-list');
    box.innerHTML='<div style="padding:12px;color:var(--text-muted)">Yükleniyor...</div>';
    modal.classList.add('acik');
    fetch(_DM_KISILER,{headers:{'Accept':'application/json','X-CSRF-TOKEN':_DM_CSRF}})
      .then(r=>r.json()).then(d=>{
        if(!d.success||!d.kisiler.length){ box.innerHTML='<div style="padding:12px;color:var(--text-muted)">Yönetici yok.</div>'; return; }
        box.innerHTML=d.kisiler.map(function(k){
            var av = k.foto ? '<span class="dm-ava" style="width:34px;height:34px;overflow:hidden"><img src="'+dmEsc(k.foto)+'" style="width:100%;height:100%;object-fit:cover"></span>'
                            : '<span class="dm-ava" style="width:34px;height:34px;font-size:12px;background:#6366f1">'+dmEsc((k.ad||'?').substr(0,1).toUpperCase())+'</span>';
            return '<div class="dm-fwditem" onclick="dmIletGonder('+k.id+')">'+av+'<span>'+dmEsc(k.ad)+'</span></div>';
        }).join('');
      }).catch(()=>{ box.innerHTML='<div style="padding:12px;color:var(--text-muted)">Hata.</div>'; });
};
window.dmIletKapat=function(){ _dmIletMid=null; document.getElementById('dmFwdModal')?.classList.remove('acik'); };
window.dmIletGonder=function(aliciId){
    if(!_dmIletMid) return;
    fetch(_DM_ILET,{method:'POST',headers:{'X-CSRF-TOKEN':_DM_CSRF,'Accept':'application/json'},body:new URLSearchParams({kaynak_id:_dmIletMid, alici_id:aliciId})})
      .then(r=>r.json()).then(d=>{
        if(d.success){
            dmIletKapat();
            if(d.mesaj && aliciId==_DM_AKTIF){ dmRender([d.mesaj], true); }
            else { alert('İletildi ✓'); }
        } else { alert(d.message||'İletilemedi'); }
      }).catch(err=>alert('Hata: '+err.message));
};

function dmYakinAlt(box){ return (box.scrollHeight - box.scrollTop - box.clientHeight) < 80; }

function dmTikGuncelle(okunanSonId){
    if(!okunanSonId) return;
    document.querySelectorAll('#dmMsgs .dm-tik').forEach(function(t){
        if(parseInt(t.dataset.mid||'0') <= okunanSonId){ t.textContent='✓✓'; t.style.color='#1976d2'; }
    });
}

function dmRender(list, scroll){
    const box=document.getElementById('dmMsgs');
    const yakin=dmYakinAlt(box);
    list.forEach(function(m){
        if(m.id>_dmLastId) _dmLastId=m.id;
        if(_dmSeen.has(m.id)) return;
        _dmSeen.add(m.id);
        box.appendChild(dmBubble(m));
    });
    if(scroll || yakin) box.scrollTop=box.scrollHeight;
}

function dmYukle(after){
    fetch(_DM_MSG_URL+'?after='+after,{headers:{'Accept':'application/json','X-CSRF-TOKEN':_DM_CSRF}})
      .then(r=>r.json()).then(d=>{
        if(!d.success) return;
        if(d.mesajlar && d.mesajlar.length){ dmRender(d.mesajlar, after===0); }
        dmTikGuncelle(d.okunan_son_id);
      }).catch(()=>{});
}

function dmGonder(e){
    e.preventDefault();
    const inp=document.getElementById('dmInput');
    const txt=(inp.value||'').trim();
    if(!txt || _dmGonderiyor) return false;
    _dmGonderiyor=true;
    const fd=new FormData();
    fd.append('alici_id', _DM_AKTIF);
    fd.append('mesaj', txt);
    if(_dmReplyTo) fd.append('yanit_id', _dmReplyTo.id);
    fetch(_DM_GONDER,{method:'POST',headers:{'X-CSRF-TOKEN':_DM_CSRF,'Accept':'application/json'},body:fd})
      .then(r=>r.json()).then(d=>{
        _dmGonderiyor=false;
        if(d.success && d.mesaj){
            inp.value=''; inp.style.height='auto'; dmCevapIptal();
            dmRender([d.mesaj], true);
        } else { alert(d.message||'Mesaj gönderilemedi'); }
      }).catch(err=>{ _dmGonderiyor=false; alert('Hata: '+err.message); });
    return false;
}

function dmKey(e){
    if(e.key==='Enter' && !e.shiftKey){ e.preventDefault(); dmGonder(e); }
}

// ===== Emoji =====
const DM_EMOJILER = ['😀','😁','😂','🤣','😊','😍','😘','😎','🤔','😅','😉','😏','😢','😭','😡','🥳','🤩','😴','🙄','😬','👀','🔥','💯','✨','⭐','❤️','👍','👎','👏','🙏','👌','🤝','💪','🎉','🎊','💡','📌','📎','✅','❌','✔️','⚡','🚀','💰','📞','📧','☕','🤙'];
let _dmEmojiYuklendi=false;
function dmEmojiToggle(){
    const p=document.getElementById('dmEmojiPanel');
    if(!_dmEmojiYuklendi){
        p.innerHTML=DM_EMOJILER.map(function(e){return '<button type="button" onclick="dmEmojiSec(\''+e+'\')">'+e+'</button>';}).join('');
        _dmEmojiYuklendi=true;
    }
    p.classList.toggle('acik');
}
function dmEmojiSec(e){
    const inp=document.getElementById('dmInput');
    inp.value+=e; inp.dispatchEvent(new Event('input')); inp.focus();
    document.getElementById('dmEmojiPanel').classList.remove('acik');
}

// ===== Dosya =====
function dmDosyaSec(){ document.getElementById('dmDosya').click(); }
function dmDosyaGonder(inp){
    if(!inp.files||!inp.files[0]) return;
    const fd=new FormData();
    fd.append('alici_id', _DM_AKTIF);
    fd.append('dosya', inp.files[0]);
    const txt=(document.getElementById('dmInput').value||'').trim();
    if(txt) fd.append('mesaj', txt);
    fetch(_DM_GONDER,{method:'POST',headers:{'X-CSRF-TOKEN':_DM_CSRF,'Accept':'application/json'},body:fd})
      .then(r=>r.json()).then(d=>{
        inp.value='';
        if(d.success&&d.mesaj){ document.getElementById('dmInput').value=''; dmRender([d.mesaj], true); }
        else { alert(d.message||'Dosya gönderilemedi'); }
      }).catch(err=>{ inp.value=''; alert('Hata: '+err.message); });
}

// ===== Sesli mesaj (WebM/Opus) =====
function _dmSesFmt(s){var d=Math.floor(s/60),k=s%60;return d+':'+(k<10?'0':'')+k;}
function _dmSesBarGuncelle(){var el=document.querySelector('#dmSesBar .ses-sure');if(el)el.textContent=_dmSesFmt(_dmSesSure);}
function _dmSesBar(ac){var b=document.getElementById('dmSesBar');if(b)b.classList.toggle('acik',!!ac);
    var mic=document.getElementById('dmMicBtn');if(mic)mic.classList.toggle('dm-mic-rec',!!ac);}
window.dmMicToggle=function(){ if(_dmRec && _dmRec.state==='recording'){ dmSesDur(); } else { dmSesBasla(); } };
function dmSesBasla(){
    if(!navigator.mediaDevices || !window.MediaRecorder){ alert('Tarayıcınız ses kaydını desteklemiyor.'); return; }
    navigator.mediaDevices.getUserMedia({audio:true}).then(function(stream){
        _dmSesChunks=[]; _dmSesIptal=false;
        var mime = MediaRecorder.isTypeSupported('audio/webm;codecs=opus') ? 'audio/webm;codecs=opus'
                 : (MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : '');
        try { _dmRec = mime ? new MediaRecorder(stream,{mimeType:mime}) : new MediaRecorder(stream); }
        catch(e){ _dmRec = new MediaRecorder(stream); }
        _dmRec.ondataavailable=function(e){ if(e.data && e.data.size) _dmSesChunks.push(e.data); };
        _dmRec.onstop=function(){
            stream.getTracks().forEach(function(t){t.stop();});
            if(_dmSesTimer){clearInterval(_dmSesTimer);_dmSesTimer=null;}
            _dmSesBar(false);
            if(!_dmSesIptal && _dmSesChunks.length){
                var blob=new Blob(_dmSesChunks,{type:'audio/webm'});
                if(blob.size>0) _dmSesYolla(blob);
            }
        };
        _dmRec.start();
        _dmSesSure=0; _dmSesBarGuncelle(); _dmSesBar(true);
        _dmSesTimer=setInterval(function(){ _dmSesSure++; _dmSesBarGuncelle(); if(_dmSesSure>=_DM_SES_MAX) dmSesDur(); },1000);
    }).catch(function(err){ alert(_dmMikHata(err)); });
}
function _dmMikHata(err){
    var ad = err && err.name ? err.name : '?';
    var ms = err && err.message ? err.message : '';
    var ctx = '\n\n[teknik] hata: ' + ad + ' | ' + ms
            + '\nsecure: ' + window.isSecureContext + ' | ' + location.protocol + '//' + location.host;
    var base;
    if(ad==='NotAllowedError' || ad==='SecurityError') base='Mikrofon izni reddedilmiş (tarayıcı/Windows/antivirüs engeli).';
    else if(ad==='NotFoundError' || ad==='DevicesNotFoundError') base='Mikrofon bulunamadı. Cihaz bağlı/aktif mi?';
    else if(ad==='NotReadableError' || ad==='TrackStartError') base='Mikrofona erişilemedi — başka uygulama (Zoom/Teams vb.) kullanıyor olabilir.';
    else base='Mikrofon kullanılamadı.';
    return base + ctx;
}
window.dmSesDur=function(){ if(_dmRec && _dmRec.state!=='inactive') _dmRec.stop(); };
window.dmSesIptal=function(){ _dmSesIptal=true; dmSesDur(); };
function _dmSesYolla(blob){
    const fd=new FormData(); fd.append('alici_id', _DM_AKTIF); fd.append('dosya', blob, 'ses-mesaj.webm');
    fetch(_DM_GONDER,{method:'POST',headers:{'X-CSRF-TOKEN':_DM_CSRF,'Accept':'application/json'},body:fd})
      .then(r=>r.json()).then(d=>{
        if(d.success&&d.mesaj){ dmRender([d.mesaj], true); }
        else { alert(d.message||'Ses gönderilemedi'); }
      }).catch(err=>alert('Hata: '+err.message));
}
// Ses kayıt çubuğunu enjekte et (mesaj kutusunun üstüne)
(function(){
    var form=document.getElementById('dmForm');
    if(form && !document.getElementById('dmSesBar')){
        var sb=document.createElement('div');
        sb.id='dmSesBar'; sb.className='dm-sesbar';
        sb.innerHTML='<span class="ses-dot"></span><span class="ses-sure">0:00</span>'
            +'<button type="button" class="ses-iptal" onclick="dmSesIptal()">İptal</button>'
            +'<button type="button" class="ses-gonder" onclick="dmSesDur()">Gönder</button>';
        form.parentNode.insertBefore(sb, form);
    }
})();

// otomatik yükseklik
document.getElementById('dmInput').addEventListener('input', function(){
    this.style.height='auto'; this.style.height=Math.min(this.scrollHeight,120)+'px';
});

// Yanıt çubuğu + İlet modalı enjeksiyonu
(function(){
    var form=document.getElementById('dmForm');
    if(form && !document.getElementById('dmReplyBar')){
        var bar=document.createElement('div');
        bar.id='dmReplyBar'; bar.className='dm-replybar';
        bar.innerHTML='<span style="opacity:.7">↩</span><span class="rb-txt"></span><button type="button" class="rb-x" onclick="dmCevapIptal()">×</button>';
        form.parentNode.insertBefore(bar, form);
    }
    if(!document.getElementById('dmFwdModal')){
        var modal=document.createElement('div');
        modal.id='dmFwdModal'; modal.className='dm-fwdmodal';
        modal.innerHTML='<div class="dm-fwdbox"><h4>İlet → Kime?</h4><div class="dm-fwd-list"></div>'
            +'<div style="text-align:right;margin-top:8px"><button type="button" class="btn btn-secondary btn-sm" onclick="dmIletKapat()">Kapat</button></div></div>';
        modal.addEventListener('click',function(e){ if(e.target===modal) dmIletKapat(); });
        document.body.appendChild(modal);
    }
})();

// ilk yükleme + polling
dmYukle(0);
setInterval(function(){ dmYukle(_dmLastId); }, 4000);
@endif
</script>
@endpush
