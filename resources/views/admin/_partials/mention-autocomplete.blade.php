{{-- @mention autocomplete — global. Sayfada bir kez @include et.
     Kullanım: <textarea class="mention-enabled"></textarea>
--}}
@php
    $__adminler_mention = \Illuminate\Support\Facades\DB::table('yoneticiler')
        ->where('durum', 1)
        ->select('id', 'kullaniciadi', 'adi')
        ->orderBy('kullaniciadi')
        ->get();
@endphp

<div id="globalMentionPopup" style="display:none;position:absolute;background:#0a0a0a;border:1px solid rgba(184,182,46,.4);border-radius:10px;padding:6px;max-height:240px;overflow-y:auto;z-index:1000;min-width:240px;box-shadow:0 8px 24px rgba(0,0,0,.5)"></div>

<script>
(function(){
  const ADMINS = @json($__adminler_mention->map(fn($a) => ['k' => $a->kullaniciadi, 'a' => $a->adi])->toArray());
  const popup = document.getElementById('globalMentionPopup');
  let activeTa = null;

  function hide(){ popup.style.display = 'none'; activeTa = null; }

  function show(query, ta){
    const filtered = ADMINS.filter(a => (a.k || '').toLowerCase().includes((query || '').toLowerCase())).slice(0, 8);
    if (!filtered.length){ hide(); return; }
    popup.innerHTML = filtered.map(a =>
      `<div style="cursor:pointer;padding:8px 10px;border-radius:6px;display:flex;align-items:center;gap:8px;color:#fff;transition:background .15s"
        onmouseover="this.style.background='rgba(184,182,46,.15)'" onmouseout="this.style.background=''"
        onmousedown="event.preventDefault();window.__pickMention('${a.k.replace(/'/g, "\\'")}')">
        <span>👤</span><span><strong>${a.k}</strong> <span style="color:rgba(255,255,255,.5);font-size:12px">(${(a.a||'').replace(/'/g, "\\'")})</span></span>
      </div>`
    ).join('');
    activeTa = ta;
    const rect = ta.getBoundingClientRect();
    popup.style.display = 'block';
    popup.style.top  = (window.scrollY + rect.bottom + 4) + 'px';
    popup.style.left = (window.scrollX + rect.left) + 'px';
  }

  window.__pickMention = function(username){
    if (!activeTa) return;
    const v = activeTa.value;
    const pos = activeTa.selectionStart;
    const before = v.substring(0, pos);
    const after = v.substring(pos);
    const atIdx = before.lastIndexOf('@');
    if (atIdx !== -1){
      activeTa.value = before.substring(0, atIdx) + '@' + username + ' ' + after;
      activeTa.focus();
      const newPos = atIdx + username.length + 2;
      activeTa.setSelectionRange(newPos, newPos);
      activeTa.dispatchEvent(new Event('input', { bubbles: true }));
    }
    hide();
  };

  document.addEventListener('input', function(e){
    const ta = e.target;
    if (!(ta && ta.tagName === 'TEXTAREA' && ta.classList.contains('mention-enabled'))) return;
    const v = ta.value;
    const pos = ta.selectionStart;
    const before = v.substring(0, pos);
    const m = before.match(/@(\w*)$/);
    if (m){ show(m[1], ta); } else { hide(); }
  });

  document.addEventListener('focusin', function(e){
    if (!(e.target && e.target.tagName === 'TEXTAREA' && e.target.classList.contains('mention-enabled'))) hide();
  });

  document.addEventListener('click', function(e){
    if (!popup.contains(e.target) && e.target !== activeTa) hide();
  });

  // Esc ile kapat
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') hide();
  });
})();
</script>
