{{-- Sağ-alt yüzen DM (yöneticiler arası mesajlaşma) widget'ı --}}
<style>
    #dmw-btn{position:fixed;right:22px;bottom:22px;width:56px;height:56px;border-radius:50%;background:#1f2430;color:#fff;border:none;box-shadow:0 6px 20px rgba(0,0,0,.3);cursor:pointer;z-index:1080;display:flex;align-items:center;justify-content:center}
    #dmw-btn:hover{filter:brightness(1.15)}
    #dmw-btn .dmw-count{position:absolute;top:-4px;right:-4px;background:#ef4444;color:#fff;font-size:11px;font-weight:700;min-width:20px;height:20px;border-radius:10px;display:none;align-items:center;justify-content:center;padding:0 5px;border:2px solid var(--surface)}
    #dmw-panel{position:fixed;right:22px;bottom:88px;width:370px;max-width:calc(100vw - 32px);height:540px;max-height:calc(100vh - 130px);background:var(--surface);border:1px solid var(--border);border-radius:14px;box-shadow:0 12px 40px rgba(0,0,0,.28);z-index:1080;display:none;flex-direction:column;overflow:hidden}
    #dmw-panel.acik{display:flex}
    .dmw-head{padding:12px 14px;background:var(--brand);color:#1a1a1a;display:flex;align-items:center;gap:8px}
    .dmw-head .dmw-title{font-weight:700;font-size:14px;flex:1;display:flex;align-items:center;gap:8px;min-width:0}
    .dmw-head .dmw-title span{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .dmw-head button{background:rgba(0,0,0,.12);border:none;color:#1a1a1a;width:28px;height:28px;border-radius:7px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex:0 0 auto}
    .dmw-head button:hover{background:rgba(0,0,0,.2)}
    .dmw-body{flex:1;overflow-y:auto;background:var(--bg)}

    .dmw-search{padding:10px;border-bottom:1px solid var(--border);background:var(--surface)}
    .dmw-search input{width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:20px;background:var(--bg);color:var(--text);font-size:13px}
    .dmw-person{display:flex;align-items:center;gap:10px;padding:10px 12px;border-bottom:1px solid var(--border);cursor:pointer}
    .dmw-person:hover{background:var(--brand-soft)}
    .dmw-ava{width:42px;height:42px;border-radius:50%;flex:0 0 auto;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px;object-fit:cover;overflow:hidden}
    .dmw-ava img{width:100%;height:100%;object-fit:cover}
    .dmw-avawrap{position:relative;flex:0 0 auto}
    .dmw-dot{position:absolute;right:-1px;bottom:-1px;width:12px;height:12px;border-radius:50%;border:2px solid var(--surface)}
    .dmw-pmain{flex:1;min-width:0}
    .dmw-prow{display:flex;align-items:center;gap:6px}
    .dmw-pname{font-weight:600;font-size:13px;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1}
    .dmw-ptime{font-size:11px;color:var(--text-muted);flex:0 0 auto}
    .dmw-plast{font-size:12px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px}
    .dmw-pbadge{background:#ef4444;color:#fff;font-size:10px;font-weight:700;min-width:18px;height:18px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;padding:0 5px;flex:0 0 auto}
    .dmw-tumu{display:block;text-align:center;padding:10px;border-top:1px solid var(--border);background:var(--surface);color:var(--brand-hover);font-weight:600;font-size:13px;text-decoration:none}
    .dmw-tumu:hover{background:var(--brand-soft)}

    .dmw-msgs{display:flex;flex-direction:column;gap:7px;padding:12px}
    .dmw-row{display:flex}
    .dmw-row.ben{justify-content:flex-end}
    .dmw-bubble{max-width:78%;padding:7px 11px;border-radius:13px;font-size:13px;line-height:1.4;word-wrap:break-word;white-space:pre-wrap}
    .dmw-row.karsi .dmw-bubble{background:var(--surface);border:1px solid var(--border);color:var(--text);border-bottom-left-radius:4px}
    .dmw-row.ben .dmw-bubble{background:var(--msg-ben,#d4e9ff);color:var(--msg-ben-text,#0f2e4d);border-bottom-right-radius:4px}
    .dmw-bubble{position:relative}
    .dmw-acts{display:none;gap:2px;position:absolute;top:-10px}
    .dmw-row.ben .dmw-acts{right:6px}.dmw-row.karsi .dmw-acts{left:6px}
    .dmw-row:hover .dmw-acts{display:flex}
    .dmw-act{background:#fff;border:1px solid var(--border);border-radius:50%;width:22px;height:22px;line-height:1;
             font-size:11px;cursor:pointer;box-shadow:0 1px 4px rgba(0,0,0,.15);padding:0;color:#333}
    .dmw-act:hover{background:var(--brand);color:#1a1a1a}
    .dmw-act.dmw-act-sil{background:#ef4444;color:#fff;border-color:#ef4444}
    .dmw-act.dmw-act-sil:hover{background:#dc2626;border-color:#dc2626;color:#fff}
    .dmw-quote{border-left:3px solid rgba(0,0,0,.35);background:rgba(0,0,0,.06);padding:3px 7px;border-radius:5px;
              font-size:11.5px;margin-bottom:4px;opacity:.85;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .dmw-fwd{font-size:10.5px;opacity:.65;font-style:italic;margin-bottom:2px}
    .dmw-replybar{display:none;align-items:center;gap:8px;padding:6px 10px;background:var(--surface);border-top:1px solid var(--border);font-size:12px}
    .dmw-replybar.acik{display:flex}
    .dmw-replybar .rb-txt{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text)}
    .dmw-replybar .rb-x{cursor:pointer;border:none;background:none;font-size:15px;color:var(--text);padding:0 2px}
    .dmw-fwdmodal{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:100000;display:none;align-items:center;justify-content:center}
    .dmw-fwdmodal.acik{display:flex}
    .dmw-fwdbox{background:#fff;border-radius:14px;width:300px;max-height:70vh;overflow:auto;padding:10px}
    .dmw-fwdbox h4{margin:6px 8px 10px;font-size:14px;color:#222}
    .dmw-fwditem{display:flex;align-items:center;gap:9px;padding:8px;border-radius:9px;cursor:pointer;color:#222}
    .dmw-fwditem:hover{background:#f3f3ee}
    .dmw-mic-rec{background:#ef4444 !important;color:#fff !important;animation:dmwpulse 1s infinite}
    @keyframes dmwpulse{0%,100%{opacity:1}50%{opacity:.45}}
    .dmw-sesbar{display:none;align-items:center;gap:10px;padding:8px 12px;background:var(--surface);border-top:1px solid var(--border);font-size:13px}
    .dmw-sesbar.acik{display:flex}
    .dmw-sesbar .ses-dot{width:10px;height:10px;border-radius:50%;background:#ef4444;animation:dmwpulse 1s infinite;flex:0 0 auto}
    .dmw-sesbar .ses-sure{font-variant-numeric:tabular-nums;color:#ef4444;font-weight:700;flex:1}
    .dmw-sesbar button{border:none;cursor:pointer;font-size:12.5px;padding:5px 12px;border-radius:8px}
    .dmw-sesbar .ses-iptal{background:none;color:var(--text-muted)}
    .dmw-sesbar .ses-gonder{background:var(--brand);color:#1a1a1a;font-weight:700}
    /* WhatsApp tarzı ses oynatıcı */
    .vmsg{display:flex;align-items:center;gap:8px;min-width:190px;max-width:250px}
    .vmsg-btn{flex:0 0 auto;width:30px;height:30px;border-radius:50%;border:none;cursor:pointer;background:rgba(0,0,0,.18);color:inherit;font-size:12px;display:flex;align-items:center;justify-content:center;line-height:1}
    .vmsg-wave{flex:1;display:flex;align-items:center;gap:2px;height:26px;cursor:pointer}
    .vmsg-bar{flex:1 1 0;min-width:2px;background:currentColor;opacity:.3;border-radius:2px}
    .vmsg-bar.on{opacity:.95}
    .vmsg-time{flex:0 0 auto;font-size:10px;opacity:.75;font-variant-numeric:tabular-nums;min-width:30px;text-align:right}
    .dmw-saat{font-size:10px;opacity:.7;margin-top:2px;text-align:right}
    .dmw-foot{display:flex;gap:7px;padding:9px;border-top:1px solid var(--border);background:var(--surface)}
    .dmw-foot textarea{flex:1;resize:none;border:1px solid var(--border);border-radius:18px;padding:8px 12px;font-size:13px;background:var(--bg);color:var(--text);max-height:90px;font-family:inherit}
    .dmw-foot button{background:var(--brand);color:#1a1a1a;border:none;border-radius:50%;width:38px;height:38px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex:0 0 auto;align-self:flex-end}
    .dmw-emoji-btn{background:transparent !important;color:var(--text-muted) !important;font-size:20px !important;width:34px !important;height:38px !important;border-radius:50% !important}
    .dmw-emoji-btn:hover{background:var(--brand-soft) !important}
    .dmw-emoji-panel{display:none;position:absolute;left:8px;right:8px;bottom:56px;background:var(--surface);border:1px solid var(--border);border-radius:12px;box-shadow:0 6px 22px rgba(0,0,0,.2);padding:8px;max-height:190px;overflow-y:auto;z-index:6;grid-template-columns:repeat(8,1fr);gap:2px}
    .dmw-emoji-panel.acik{display:grid}
    .dmw-emoji-panel button{background:transparent;border:none;font-size:20px;cursor:pointer;padding:4px;border-radius:6px;line-height:1}
    .dmw-emoji-panel button:hover{background:var(--brand-soft)}
    .dmw-empty{padding:30px 16px;text-align:center;color:var(--text-muted);font-size:13px}
</style>

<button id="dmw-btn" onclick="dmwToggle()" title="Mesajlaşma" aria-label="Mesajlaşma">
    <i data-lucide="message-circle"></i>
    <span class="dmw-count" id="dmw-count">0</span>
</button>

<div id="dmw-panel">
    <div class="dmw-head">
        <button id="dmw-back" onclick="dmwListeye()" style="display:none" title="Geri"><i data-lucide="arrow-left"></i></button>
        <div class="dmw-title"><i data-lucide="message-circle" style="width:18px;height:18px"></i><span id="dmw-title-text">Mesajlar</span></div>
        <button onclick="dmwSesToggle(this)" id="dmw-ses-btn" title="Bildirim sesi">🔔</button>
        <button onclick="msgRenkAc(this)" title="Balon rengi">🎨</button>
        <button onclick="dmwToggle()" title="Kapat"><i data-lucide="x"></i></button>
    </div>

    {{-- Liste görünümü --}}
    <div id="dmw-list-view" style="flex:1;display:flex;flex-direction:column;min-height:0">
        <div class="dmw-search"><input type="text" id="dmw-search" placeholder="Yeni mesaj için ara..." onkeyup="dmwFilter()"></div>
        <div class="dmw-body" id="dmw-people"><div class="dmw-empty">Yükleniyor...</div></div>
        <a href="{{ route('admin.dm.index') }}" id="dmw-tumu" class="dmw-tumu">Tümünü gör</a>
    </div>

    {{-- Sohbet görünümü --}}
    <div id="dmw-chat-view" style="flex:1;display:none;flex-direction:column;min-height:0;position:relative">
        <div class="dmw-body dmw-msgs" id="dmw-msgs"></div>
        <div id="dmw-emoji-panel" class="dmw-emoji-panel"></div>
        <form class="dmw-foot" onsubmit="return dmwGonder(event)">
            <button type="button" class="dmw-emoji-btn" onclick="dmwEmojiToggle()" title="Emoji">😊</button>
            <button type="button" class="dmw-emoji-btn" onclick="dmwDosyaSec()" title="Dosya ekle">📎</button>
            <button type="button" class="dmw-emoji-btn" id="dmw-mic-btn" onclick="dmwMicToggle()" title="Sesli mesaj">🎤</button>
            <input type="file" id="dmw-dosya" style="display:none" onchange="dmwDosyaGonder(this)">
            <textarea id="dmw-input" rows="1" placeholder="Mesaj yaz..." onkeydown="dmwKey(event)"></textarea>
            <button type="submit" title="Gönder"><i data-lucide="send" style="width:18px;height:18px"></i></button>
        </form>
    </div>
</div>

<script>
/* ===== Mesaj balon rengi — DM + görev ortak, tarayıcıda saklanır (DB yok) ===== */
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
(function(){
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    // İki kanal: admin↔admin ('admin') ve admin↔müşteri ('musteri'). Header butonu ile geçiş.
    const EP_ADMIN = {
        kisiler:@json(route('admin.dm.kisiler')), okunmamis:@json(route('admin.dm.okunmamis')),
        gonder:@json(route('admin.dm.gonder')), ilet:@json(route('admin.dm.ilet')),
        msgBase:@json(url('admin/dm')), mesaj:@json(url('admin/dm/mesaj')),
        tumu:@json(route('admin.dm.index')), bos:'Mesajlaşılacak yönetici yok.'
    };
    const EP_MUSTERI = {
        kisiler:@json(route('admin.musteri-dm.kisiler')), okunmamis:@json(route('admin.musteri-dm.okunmamis')),
        gonder:@json(route('admin.musteri-dm.gonder')), ilet:null,
        msgBase:@json(url('admin/musteri-dm')), mesaj:@json(url('admin/musteri-dm/mesaj')),
        tumu:@json(route('admin.musteri-dm.index')), bos:'Henüz müşteri mesajı yok. (Yeni sohbeti tam sayfadan başlatın)'
    };
    let mod = 'admin';
    function EP(k){ return (mod==='musteri' ? EP_MUSTERI : EP_ADMIN)[k]; }
    const PALET = ['#6366f1','#0ea5e9','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#14b8a6'];

    let acik=false, aktifId=null, aktifAd='', lastId=0, gonderiyor=false, seen=new Set();
    let chatTimer=null, listTimer=null, replyTo=null, iletMid=null;
    let mediaRec=null, sesChunks=[], sesTimer=null, sesSure=0, sesIptal=false;
    const SES_MAX=120; // saniye
    const msgCache={};

    // ===== WhatsApp tarzı ses oynatıcı (widget + tam sayfa ortak) =====
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

    function esc(s){const d=document.createElement('div');d.textContent=s==null?'':s;return d.innerHTML;}
    function bashar(ad){const p=(ad||'').trim().split(/\s+/);let s='';p.slice(0,2).forEach(x=>s+=(x[0]||''));return (s||'?').toUpperCase();}
    function renk(id){return PALET[id % PALET.length];}
    function durumRenk(d){return d==='cevrimici'?'#22c55e':(d==='uzakta'?'#f59e0b':'#9ca3af');}
    function avatarNokta(k){return '<span class="dmw-avawrap">'+avatar(k)+'<span class="dmw-dot" style="background:'+durumRenk(k.durum)+'"></span></span>';}
    function avatar(k){
        if(k.foto){ return '<span class="dmw-ava"><img src="'+esc(k.foto)+'" alt=""></span>'; }
        return '<span class="dmw-ava" style="background:'+renk(k.id)+'">'+esc(bashar(k.ad))+'</span>';
    }

    window.dmwToggle=function(){
        acik=!acik;
        document.getElementById('dmw-panel').classList.toggle('acik', acik);
        if(acik){ if(aktifId){ dmwSohbeteGec(aktifId, aktifAd); } else { dmwListeye(); } }
        else { dmwStopChatPoll(); dmwStopListPoll(); }
        if(window.lucide) lucide.createIcons();
    };

    window.dmwFilter=function(){
        const q=(document.getElementById('dmw-search').value||'').toLowerCase();
        document.querySelectorAll('#dmw-people .dmw-person').forEach(el=>{
            el.style.display=(el.dataset.ad||'').indexOf(q)>-1?'':'none';
        });
    };

    function dmwKisileriYukle(){
        fetch(EP('kisiler'),{headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}})
          .then(r=>r.json()).then(d=>{
            if(!d.success) return;
            const box=document.getElementById('dmw-people');
            if(!d.kisiler.length){ box.innerHTML='<div class="dmw-empty">'+EP('bos')+'</div>'; return; }
            box.innerHTML=d.kisiler.map(k=>{
                const pre = k.son_mesaj ? (k.son_mesaj_ben?'Sen: ':'')+esc(k.son_mesaj) : '<span style="opacity:.6">Henüz mesaj yok</span>';
                const zaman = k.zaman ? '<span class="dmw-ptime">'+esc(k.zaman)+'</span>' : '';
                const badge = k.okunmamis>0 ? '<span class="dmw-pbadge">'+k.okunmamis+'</span>' : '';
                const atanan = k.atanan ? '<span style="font-size:10px;color:'+(k.atanan_ben?'#16a34a':'#b45309')+';font-weight:700">👤 '+esc(k.atanan_ben?'Sen':k.atanan)+'</span>' : '';
                return '<div class="dmw-person" data-ad="'+esc((k.ad||'').toLowerCase())+'" onclick="dmwSohbeteGec('+k.id+',\''+esc(k.ad).replace(/'/g,"\\'")+'\')">'
                    +avatarNokta(k)
                    +'<div class="dmw-pmain"><div class="dmw-prow"><span class="dmw-pname">'+esc(k.ad)+'</span>'+zaman+'</div>'
                    +'<div class="dmw-prow"><span class="dmw-plast">'+pre+'</span>'+badge+'</div>'+(atanan?'<div class="dmw-prow">'+atanan+'</div>':'')+'</div>'
                    +'</div>';
            }).join('');
        }).catch(()=>{});
    }

    window.dmwListeye=function(){
        aktifId=null; aktifAd='';
        dmwStopChatPoll();
        document.getElementById('dmw-chat-view').style.display='none';
        document.getElementById('dmw-list-view').style.display='flex';
        document.getElementById('dmw-back').style.display='none';
        document.getElementById('dmw-title-text').textContent='Mesajlar';
        dmwKisileriYukle();
        dmwStartListPoll();
        if(window.lucide) lucide.createIcons();
    };

    window.dmwSohbeteGec=function(id, ad){
        aktifId=id; aktifAd=ad||''; lastId=0; seen=new Set();
        dmwStopListPoll();
        document.getElementById('dmw-list-view').style.display='none';
        document.getElementById('dmw-chat-view').style.display='flex';
        document.getElementById('dmw-back').style.display='flex';
        document.getElementById('dmw-title-text').textContent=ad||'Sohbet';
        document.getElementById('dmw-msgs').innerHTML='';
        dmwMesajYukle(0);
        dmwStartChatPoll();
        if(window.lucide) lucide.createIcons();
        setTimeout(()=>document.getElementById('dmw-input')?.focus(),100);
    };

    function tik(m){
        if(!m.ben) return '';
        var ok = (m.okundu==1);
        return '<span class="dmw-tik" data-mid="'+m.id+'" style="margin-left:4px;font-size:11px;color:'+(ok?'#1976d2':'rgba(0,0,0,.45)')+'">'+(ok?'✓✓':'✓')+'</span>';
    }
    function dosyaHtml(m){
        if(!m.dosya) return '';
        if(m.ses) return window.vmsgHtml(esc(m.dosya));
        if(m.resim) return '<a href="'+esc(m.dosya)+'" target="_blank"><img src="'+esc(m.dosya)+'" style="max-width:190px;max-height:190px;border-radius:8px;display:block;margin-bottom:4px"></a>';
        return '<a href="'+esc(m.dosya)+'" target="_blank" style="display:flex;align-items:center;gap:6px;color:inherit;margin-bottom:4px;font-weight:600">📎 '+esc(m.dosya_ad||'Dosya')+'</a>';
    }
    function alintiHtml(m){
        if(!m.yanit) return '';
        return '<div class="dmw-quote">'+esc(m.yanit.kim)+': '+esc(m.yanit.mesaj)+'</div>';
    }
    function aksiyonHtml(m){
        var b='<button type="button" class="dmw-act" title="Cevapla" onclick="dmwCevapla('+m.id+')">↩</button>';
        if(EP('ilet')) b+='<button type="button" class="dmw-act" title="İlet" onclick="dmwIletAc('+m.id+')">↪</button>';
        var sahip = (m.benim!==undefined ? m.benim : m.ben); // havuzda sadece kendi mesajın
        if(sahip){
            b+='<button type="button" class="dmw-act" title="Düzenle" onclick="dmwDuzenle('+m.id+')">✏️</button>'
             + '<button type="button" class="dmw-act dmw-act-sil" title="Sil" onclick="dmwSil('+m.id+')">🗑️</button>';
        }
        return '<div class="dmw-acts">'+b+'</div>';
    }
    function bubble(m){
        msgCache[m.id]=m;
        var fwd = m.iletildi ? '<div class="dmw-fwd">↪ İletildi</div>' : '';
        var gad = m.gonderen_ad ? '<div style="font-size:10px;font-weight:700;color:#4f46e5;margin-bottom:2px">'+esc(m.gonderen_ad)+'</div>' : '';
        var icerik = gad + fwd + alintiHtml(m) + dosyaHtml(m) + (m.mesaj ? '<span class="dmw-txt">'+esc(m.mesaj)+'</span>' : '');
        var duz = m.duzenlendi ? ' <span style="opacity:.6;font-size:10px">(düzenlendi)</span>' : '';
        return '<div class="dmw-row '+(m.ben?'ben':'karsi')+'" data-mid="'+m.id+'"><div class="dmw-bubble">'+icerik
             + '<div class="dmw-saat">'+esc(m.saat)+duz+tik(m)+'</div>'+aksiyonHtml(m)+'</div></div>';
    }

    // ===== Cevapla / İlet / Düzenle / Sil =====
    window.dmwCevapla=function(mid){
        var m=msgCache[mid]; if(!m) return;
        replyTo=m;
        var bar=document.getElementById('dmw-reply-bar');
        if(bar){
            bar.querySelector('.rb-txt').textContent=(m.ben?'Sen':aktifAd)+': '+(m.mesaj||(m.dosya_ad||'Dosya'));
            bar.classList.add('acik');
        }
        document.getElementById('dmw-input')?.focus();
    };
    window.dmwCevapIptal=function(){ replyTo=null; document.getElementById('dmw-reply-bar')?.classList.remove('acik'); };

    window.dmwDuzenle=function(mid){
        var m=msgCache[mid]; if(!m) return;
        var yeni=prompt('Mesajı düzenle:', m.mesaj||'');
        if(yeni===null) return;
        yeni=yeni.trim(); if(!yeni){ alert('Boş mesaj olamaz.'); return; }
        fetch(EP('mesaj')+'/'+mid+'/duzenle',{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body:new URLSearchParams({mesaj:yeni})})
          .then(r=>r.json()).then(d=>{
            if(d.success&&d.mesaj){
                msgCache[mid]=d.mesaj;
                var row=document.querySelector('#dmw-msgs .dmw-row[data-mid="'+mid+'"] .dmw-txt');
                if(row) row.textContent=d.mesaj.mesaj;
                var saat=document.querySelector('#dmw-msgs .dmw-row[data-mid="'+mid+'"] .dmw-saat');
                if(saat && !saat.querySelector('.dmw-duz')){ var s=document.createElement('span'); s.className='dmw-duz'; s.style.cssText='opacity:.6;font-size:10px'; s.textContent=' (düzenlendi)'; saat.insertBefore(s, saat.firstChild.nextSibling||null);}
            } else { alert(d.message||'Düzenlenemedi'); }
          }).catch(err=>alert('Hata: '+err.message));
    };

    window.dmwSil=function(mid){
        if(!confirm('Bu mesajı silmek istediğine emin misin?')) return;
        fetch(EP('mesaj')+'/'+mid+'/sil',{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
          .then(r=>r.json()).then(d=>{
            if(d.success){
                var row=document.querySelector('#dmw-msgs .dmw-row[data-mid="'+mid+'"]');
                if(row) row.remove();
            } else { alert(d.message||'Silinemedi'); }
          }).catch(err=>alert('Hata: '+err.message));
    };

    window.dmwIletAc=function(mid){
        if(!EP('ilet')) return; // müşteri modunda iletme yok
        iletMid=mid;
        var modal=document.getElementById('dmw-fwd-modal');
        var box=modal.querySelector('.dmw-fwd-list');
        box.innerHTML='<div class="dmw-empty">Yükleniyor...</div>';
        modal.classList.add('acik');
        fetch(EP('kisiler'),{headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}})
          .then(r=>r.json()).then(d=>{
            if(!d.success||!d.kisiler.length){ box.innerHTML='<div class="dmw-empty">Yönetici yok.</div>'; return; }
            box.innerHTML=d.kisiler.map(k=>'<div class="dmw-fwditem" onclick="dmwIletGonder('+k.id+')">'+avatar(k)+'<span>'+esc(k.ad)+'</span></div>').join('');
          }).catch(()=>{ box.innerHTML='<div class="dmw-empty">Hata.</div>'; });
    };
    window.dmwIletKapat=function(){ iletMid=null; document.getElementById('dmw-fwd-modal')?.classList.remove('acik'); };
    window.dmwIletGonder=function(aliciId){
        if(!iletMid) return;
        var kaynak=iletMid;
        fetch(EP('ilet'),{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body:new URLSearchParams({kaynak_id:kaynak, alici_id:aliciId})})
          .then(r=>r.json()).then(d=>{
            if(d.success){
                dmwIletKapat();
                if(d.mesaj && aliciId==aktifId && !seen.has(d.mesaj.id)){
                    seen.add(d.mesaj.id); if(d.mesaj.id>lastId) lastId=d.mesaj.id;
                    var box=document.getElementById('dmw-msgs'); box.insertAdjacentHTML('beforeend', bubble(d.mesaj)); box.scrollTop=box.scrollHeight;
                } else { alert('İletildi ✓'); }
            } else { alert(d.message||'İletilemedi'); }
          }).catch(err=>alert('Hata: '+err.message));
    };
    function yakinAlt(box){return (box.scrollHeight-box.scrollTop-box.clientHeight)<90;}

    function dmwTikGuncelle(okunanSonId){
        if(!okunanSonId) return;
        document.querySelectorAll('#dmw-msgs .dmw-tik').forEach(function(t){
            if(parseInt(t.dataset.mid||'0') <= okunanSonId){ t.textContent='✓✓'; t.style.color='#1976d2'; }
        });
    }

    function dmwMesajYukle(after){
        if(!aktifId) return;
        fetch(EP('msgBase')+'/'+aktifId+'/mesajlar?after='+after,{headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}})
          .then(r=>r.json()).then(d=>{
            if(!d.success) return;
            if(d.mesajlar && d.mesajlar.length){
                const box=document.getElementById('dmw-msgs');
                const yakin=yakinAlt(box);
                let html=''; let gelenVar=false;
                d.mesajlar.forEach(m=>{ if(m.id>lastId) lastId=m.id; if(seen.has(m.id)) return; seen.add(m.id); if(!m.ben) gelenVar=true; html+=bubble(m); });
                box.insertAdjacentHTML('beforeend', html);
                if(after===0 || yakin) box.scrollTop=box.scrollHeight;
                if(after>0 && gelenVar) dmwBip();
            }
            dmwTikGuncelle(d.okunan_son_id);
        }).catch(()=>{});
    }

    window.dmwGonder=function(e){
        e.preventDefault();
        const inp=document.getElementById('dmw-input');
        const txt=(inp.value||'').trim();
        if(!txt||gonderiyor||!aktifId) return false;
        gonderiyor=true;
        const fd=new FormData(); fd.append('alici_id',aktifId); fd.append('mesaj',txt);
        if(replyTo) fd.append('yanit_id', replyTo.id);
        fetch(EP('gonder'),{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:fd})
          .then(r=>r.json()).then(d=>{
            gonderiyor=false;
            if(d.success&&d.mesaj){
                inp.value=''; inp.style.height='auto'; dmwCevapIptal();
                if(d.mesaj.id>lastId) lastId=d.mesaj.id;
                if(!seen.has(d.mesaj.id)){
                    seen.add(d.mesaj.id);
                    const box=document.getElementById('dmw-msgs');
                    box.insertAdjacentHTML('beforeend', bubble(d.mesaj));
                    box.scrollTop=box.scrollHeight;
                }
            } else { alert(d.message||'Mesaj gönderilemedi'); }
          }).catch(err=>{gonderiyor=false;alert('Hata: '+err.message);});
        return false;
    };

    window.dmwKey=function(e){ if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();dmwGonder(e);} };

    // ===== Emoji paneli =====
    const EMOJILER = ['😀','😁','😂','🤣','😊','😍','😘','😎','🤔','😅','😉','😏','😢','😭','😡','🥳','🤩','😴','🙄','😬','👀','🔥','💯','✨','⭐','❤️','👍','👎','👏','🙏','👌','🤝','💪','🎉','🎊','💡','📌','📎','✅','❌','✔️','⚡','🚀','💰','📞','📧','☕','🤙'];
    let _emojiYuklendi=false;
    window.dmwEmojiToggle=function(){
        const p=document.getElementById('dmw-emoji-panel');
        if(!_emojiYuklendi){
            p.innerHTML=EMOJILER.map(function(e){return '<button type="button" onclick="dmwEmojiSec(\''+e+'\')">'+e+'</button>';}).join('');
            _emojiYuklendi=true;
        }
        p.classList.toggle('acik');
    };
    window.dmwEmojiSec=function(e){
        const inp=document.getElementById('dmw-input');
        inp.value += e;
        inp.dispatchEvent(new Event('input'));
        inp.focus();
        document.getElementById('dmw-emoji-panel').classList.remove('acik');
    };

    // ===== Dosya ekleme =====
    window.dmwDosyaSec=function(){ document.getElementById('dmw-dosya').click(); };
    window.dmwDosyaGonder=function(inp){
        if(!inp.files||!inp.files[0]||!aktifId){ return; }
        const fd=new FormData();
        fd.append('alici_id', aktifId);
        fd.append('dosya', inp.files[0]);
        const txt=(document.getElementById('dmw-input').value||'').trim();
        if(txt) fd.append('mesaj', txt);
        fetch(EP('gonder'),{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:fd})
          .then(r=>r.json()).then(d=>{
            inp.value='';
            if(d.success&&d.mesaj){
                document.getElementById('dmw-input').value='';
                if(d.mesaj.id>lastId) lastId=d.mesaj.id;
                if(!seen.has(d.mesaj.id)){
                    seen.add(d.mesaj.id);
                    const box=document.getElementById('dmw-msgs');
                    box.insertAdjacentHTML('beforeend', bubble(d.mesaj));
                    box.scrollTop=box.scrollHeight;
                }
            } else { alert(d.message||'Dosya gönderilemedi'); }
          }).catch(err=>{ inp.value=''; alert('Hata: '+err.message); });
    };

    // ===== Sesli mesaj (WebM/Opus) =====
    function sesFmt(s){var d=Math.floor(s/60),k=s%60;return d+':'+(k<10?'0':'')+k;}
    function dmwSesBarGuncelle(){var el=document.querySelector('#dmw-ses-bar .ses-sure');if(el)el.textContent=sesFmt(sesSure);}
    function dmwSesBar(ac){var b=document.getElementById('dmw-ses-bar');if(b)b.classList.toggle('acik',!!ac);
        var mic=document.getElementById('dmw-mic-btn');if(mic)mic.classList.toggle('dmw-mic-rec',!!ac);}
    window.dmwMicToggle=function(){ if(mediaRec && mediaRec.state==='recording'){ dmwSesDur(); } else { dmwSesBasla(); } };
    function dmwSesBasla(){
        if(!aktifId) return;
        if(!navigator.mediaDevices || !window.MediaRecorder){ alert('Tarayıcınız ses kaydını desteklemiyor.'); return; }
        navigator.mediaDevices.getUserMedia({audio:true}).then(function(stream){
            sesChunks=[]; sesIptal=false;
            var mime = MediaRecorder.isTypeSupported('audio/webm;codecs=opus') ? 'audio/webm;codecs=opus'
                     : (MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : '');
            try { mediaRec = mime ? new MediaRecorder(stream,{mimeType:mime}) : new MediaRecorder(stream); }
            catch(e){ mediaRec = new MediaRecorder(stream); }
            mediaRec.ondataavailable=function(e){ if(e.data && e.data.size) sesChunks.push(e.data); };
            mediaRec.onstop=function(){
                stream.getTracks().forEach(function(t){t.stop();});
                if(sesTimer){clearInterval(sesTimer);sesTimer=null;}
                dmwSesBar(false);
                if(!sesIptal && sesChunks.length){
                    var blob=new Blob(sesChunks,{type:'audio/webm'});
                    if(blob.size>0) dmwSesYolla(blob);
                }
            };
            mediaRec.start();
            sesSure=0; dmwSesBarGuncelle(); dmwSesBar(true);
            sesTimer=setInterval(function(){ sesSure++; dmwSesBarGuncelle(); if(sesSure>=SES_MAX) dmwSesDur(); },1000);
        }).catch(function(err){ alert(dmwMikHata(err)); });
    }
    function dmwMikHata(err){
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
    window.dmwSesDur=function(){ if(mediaRec && mediaRec.state!=='inactive') mediaRec.stop(); };
    window.dmwSesIptal=function(){ sesIptal=true; dmwSesDur(); };
    function dmwSesYolla(blob){
        var fd=new FormData(); fd.append('alici_id',aktifId); fd.append('dosya', blob, 'ses-mesaj.webm');
        fetch(EP('gonder'),{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:fd})
          .then(r=>r.json()).then(d=>{
            if(d.success&&d.mesaj){
                if(d.mesaj.id>lastId) lastId=d.mesaj.id;
                if(!seen.has(d.mesaj.id)){ seen.add(d.mesaj.id); var box=document.getElementById('dmw-msgs'); box.insertAdjacentHTML('beforeend', bubble(d.mesaj)); box.scrollTop=box.scrollHeight; }
            } else { alert(d.message||'Ses gönderilemedi'); }
          }).catch(err=>alert('Hata: '+err.message));
    }

    document.getElementById('dmw-input')?.addEventListener('input',function(){this.style.height='auto';this.style.height=Math.min(this.scrollHeight,90)+'px';});

    function dmwStartChatPoll(){ dmwStopChatPoll(); chatTimer=setInterval(()=>dmwMesajYukle(lastId),4000); }
    function dmwStopChatPoll(){ if(chatTimer){clearInterval(chatTimer);chatTimer=null;} }
    function dmwStartListPoll(){ dmwStopListPoll(); listTimer=setInterval(dmwKisileriYukle,10000); }
    function dmwStopListPoll(){ if(listTimer){clearInterval(listTimer);listTimer=null;} }

    // ===== Bildirim sesi (mp3 dosyası; çalamazsa Web Audio bip yedek) =====
    var _dmwSonOkunmamis = -1, _dmwAudioCtx = null, _dmwSes = null;
    const U_BILDIRIM_SES = @json(asset('sounds/bildirim.mp3'));
    function dmwSesAcik(){ return localStorage.getItem('dmSesKapali') !== '1'; }
    function dmwSesObj(){
        if(!_dmwSes){ _dmwSes = new Audio(U_BILDIRIM_SES); _dmwSes.preload='auto'; _dmwSes.volume=0.7; }
        return _dmwSes;
    }
    function dmwBip(){
        if(!dmwSesAcik()) return;
        try{
            var a = dmwSesObj(); a.currentTime = 0;
            var p = a.play();
            if(p && p.catch) p.catch(function(){ dmwBeep(); }); // mp3 çalınamazsa bip
        }catch(e){ dmwBeep(); }
    }
    // Yedek: dosya yoksa/çalamazsa Web Audio bip
    function dmwBeep(){
        try{
            if(!_dmwAudioCtx) _dmwAudioCtx = new (window.AudioContext||window.webkitAudioContext)();
            var ctx=_dmwAudioCtx; if(ctx.state==='suspended') ctx.resume();
            var t=ctx.currentTime;
            [880,1175].forEach(function(f,i){
                var o=ctx.createOscillator(), g=ctx.createGain();
                o.type='sine'; o.frequency.value=f; o.connect(g); g.connect(ctx.destination);
                var st=t+i*0.13;
                g.gain.setValueAtTime(0.0001, st);
                g.gain.exponentialRampToValueAtTime(0.2, st+0.02);
                g.gain.exponentialRampToValueAtTime(0.0001, st+0.18);
                o.start(st); o.stop(st+0.21);
            });
        }catch(e){}
    }
    // Tarayıcı autoplay kuralı: ilk tıklamada sesi "kilit aç" (sessiz çalıp durdur)
    document.addEventListener('click', function(){
        try{ var a=dmwSesObj(); a.muted=true; var p=a.play(); if(p&&p.then){ p.then(function(){ a.pause(); a.currentTime=0; a.muted=false; }).catch(function(){ a.muted=false; }); } else { a.pause(); a.muted=false; } }catch(e){}
        try{ if(!_dmwAudioCtx) _dmwAudioCtx=new (window.AudioContext||window.webkitAudioContext)(); if(_dmwAudioCtx.state==='suspended') _dmwAudioCtx.resume(); }catch(e){}
    }, {once:true});
    window.dmwSesToggle=function(btn){
        var kapali = localStorage.getItem('dmSesKapali')==='1';
        localStorage.setItem('dmSesKapali', kapali ? '0' : '1');
        if(btn){ btn.textContent = kapali ? '🔔' : '🔕'; btn.title = kapali ? 'Bildirim sesi açık' : 'Bildirim sesi kapalı'; }
        if(kapali) dmwBip(); // açınca örnek ses
    };

    function dmwRozet(){
        fetch(EP_ADMIN.okunmamis,{headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}})
          .then(r=>r.json()).then(d=>{
            const el=document.getElementById('dmw-count');
            if(el){
                if(d.count>0){ el.textContent=d.count>99?'99+':d.count; el.style.display='flex'; }
                else { el.style.display='none'; }
            }
            if(_dmwSonOkunmamis>=0 && d.count>_dmwSonOkunmamis) dmwBip();
            _dmwSonOkunmamis=d.count;
          }).catch(()=>{});
    }
    // Yanıt çubuğu (mesaj kutusunun üstüne) + İlet modalı (body'ye)
    (function injectUI(){
        var msgs=document.getElementById('dmw-msgs');
        if(msgs && !document.getElementById('dmw-reply-bar')){
            var bar=document.createElement('div');
            bar.id='dmw-reply-bar'; bar.className='dmw-replybar';
            bar.innerHTML='<span style="opacity:.7">↩</span><span class="rb-txt"></span><button type="button" class="rb-x" onclick="dmwCevapIptal()">×</button>';
            msgs.parentNode.insertBefore(bar, msgs.nextSibling);
        }
        if(msgs && !document.getElementById('dmw-ses-bar')){
            var sb=document.createElement('div');
            sb.id='dmw-ses-bar'; sb.className='dmw-sesbar';
            sb.innerHTML='<span class="ses-dot"></span><span class="ses-sure">0:00</span>'
                +'<button type="button" class="ses-iptal" onclick="dmwSesIptal()">İptal</button>'
                +'<button type="button" class="ses-gonder" onclick="dmwSesDur()">Gönder</button>';
            msgs.parentNode.insertBefore(sb, msgs.nextSibling);
        }
        if(!document.getElementById('dmw-fwd-modal')){
            var modal=document.createElement('div');
            modal.id='dmw-fwd-modal'; modal.className='dmw-fwdmodal';
            modal.innerHTML='<div class="dmw-fwdbox"><h4>İlet → Kime?</h4><div class="dmw-fwd-list"></div>'
                +'<div style="text-align:right;margin-top:6px"><button type="button" class="dmw-act" style="width:auto;border-radius:8px;padding:5px 12px" onclick="dmwIletKapat()">Kapat</button></div></div>';
            modal.addEventListener('click',function(e){ if(e.target===modal) dmwIletKapat(); });
            document.body.appendChild(modal);
        }
    })();

    dmwRozet();
    setInterval(dmwRozet, 15000);
    // Ses butonu ilk durumu (kapalıysa 🔕)
    (function(){ var b=document.getElementById('dmw-ses-btn'); if(b && localStorage.getItem('dmSesKapali')==='1'){ b.textContent='🔕'; b.title='Bildirim sesi kapalı'; } })();
})();
</script>
