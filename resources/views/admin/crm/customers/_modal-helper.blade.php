<style>
.js-modal-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:1040;opacity:0;transition:opacity .2s;}
.js-modal-backdrop.show{opacity:1;}
.js-modal-open{position:fixed!important;top:0;left:0;right:0;bottom:0;z-index:1050;display:flex!important;align-items:flex-start;justify-content:center;overflow-y:auto;padding:40px 16px;}
.js-modal-open .modal-dialog{margin:0;width:100%;transform:translateY(-20px);transition:transform .25s;}
.js-modal-open.show .modal-dialog{transform:translateY(0);}
body.js-modal-lock{overflow:hidden;}
input[type="file"]{padding:8px 12px!important;text-indent:0!important;cursor:pointer;background:rgba(255,255,255,.05)!important;border:1px dashed rgba(184,182,46,.4)!important}input[type="file"]::-webkit-file-upload-button{background:linear-gradient(135deg,#b8b62e,#8a8a1f);color:#000;font-weight:700;border:none;padding:6px 14px;border-radius:8px;cursor:pointer;margin-right:10px;font-family:inherit}body.light input[type="file"]{background:#f1f5f9!important;border-color:rgba(245,158,11,.5)!important;color:#0f172a!important}input[type="checkbox"]{width:auto!important;padding:0!important;text-indent:0!important;background:transparent!important;border:none!important}select{appearance:none;-webkit-appearance:none;-moz-appearance:none;background-image:url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 16 16%27%3e%3cpath fill=%27%23facc15%27 d=%27M8 11L3 6h10z%27/%3e%3c/svg%3e")!important;background-repeat:no-repeat!important;background-position:right 14px center!important;background-size:16px!important;padding-right:42px!important;cursor:pointer}select:hover{border-color:#b8b62e!important}body.light select{background-image:url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 16 16%27%3e%3cpath fill=%27%23475569%27 d=%27M8 11L3 6h10z%27/%3e%3c/svg%3e")!important;background-repeat:no-repeat!important;background-position:right 14px center!important;background-size:16px!important;background-color:#f8fafc!important}select option{background:#1a1a1a!important;color:#fff!important;padding:10px 14px!important;font-weight:500}body.light select option{background:#fff!important;color:#0f172a!important}select::-ms-expand{display:none}select[multiple]{background-image:none!important;padding-right:24px!important}.user-menu-item{display:flex;align-items:center;gap:10px;padding:10px 14px;color:#fff;text-decoration:none;border-radius:8px;font-size:14px;transition:all .2s}.user-menu-name{color:#fff}.user-menu-sub{color:rgba(255,255,255,.5)}body.light .user-menu-name{color:#0f172a!important}body.light .user-menu-sub{color:rgba(15,23,42,.55)!important}.user-menu-item:hover{background:rgba(184,182,46,.15);color:#d4d066}body.light .user-menu-dropdown{background:#fff!important;border-color:rgba(0,0,0,.1)!important;box-shadow:0 12px 32px rgba(0,0,0,.15)!important}body.light .user-menu-item{color:#0f172a}body.light .user-menu-item:hover{background:#fef3c7!important;color:#92400e!important}</style>
<script>
(function(){
    if (window.__cmOpenModal) return; // zaten yüklü
    let backdrop = null;
    function closeAll() {
        document.querySelectorAll('.js-modal-open').forEach(m => {
            m.classList.remove('show','js-modal-open');
            m.setAttribute('style', m.dataset.origStyle || '');
            m.removeAttribute('data-orig-style');
            const dlg = m.querySelector('.modal-dialog');
            if (dlg) {
                dlg.setAttribute('style', dlg.dataset.origStyle || '');
                dlg.removeAttribute('data-orig-style');
            }
        });
        if (backdrop) { backdrop.remove(); backdrop = null; }
        document.body.classList.remove('js-modal-lock');
        document.body.style.overflow = '';
    }
    function openModal(id) {
        if (window.jQuery && jQuery.fn.modal) { try { jQuery('#'+id).modal('show'); return; } catch (e) {} }
        const m = document.getElementById(id);
        if (!m) return;
        if (m.parentNode !== document.body) document.body.appendChild(m);

        backdrop = document.createElement('div');
        backdrop.style.cssText = 'position:fixed !important;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,.6);z-index:100000;opacity:0;transition:opacity .2s;';
        document.body.appendChild(backdrop);
        requestAnimationFrame(() => { backdrop.style.opacity = '1'; });

        // Çok agresif inline stil — dışarıdaki CSS'leri geçersiz kılar
        m.dataset.origStyle = m.getAttribute('style') || '';
        m.style.cssText = 'display:flex !important;position:fixed !important;top:0 !important;left:0 !important;right:0 !important;bottom:0 !important;width:100vw !important;height:100vh !important;z-index:100001 !important;overflow-y:auto !important;padding:40px 16px !important;align-items:flex-start !important;justify-content:center !important;margin:0 !important;';
        m.classList.add('js-modal-open');
        requestAnimationFrame(() => m.classList.add('show'));

        // Dialog animasyonu
        const dlg = m.querySelector('.modal-dialog');
        if (dlg) {
            dlg.dataset.origStyle = dlg.getAttribute('style') || '';
            dlg.style.cssText = 'margin:0 !important;width:auto !important;max-width:1140px !important;transform:translateY(-20px);transition:transform .25s;';
            requestAnimationFrame(() => { dlg.style.transform = 'translateY(0)'; });
        }

        document.body.classList.add('js-modal-lock');
        document.body.style.overflow = 'hidden';

        backdrop.addEventListener('click', closeAll);
        m.querySelectorAll('[data-dismiss="modal"]').forEach(btn => btn.addEventListener('click', closeAll, { once: true }));
        m.addEventListener('click', function(e){ if (e.target === m) closeAll(); });
        document.addEventListener('keydown', function esc(e){ if (e.key==='Escape'){ closeAll(); document.removeEventListener('keydown', esc); }});
    }
    window.__cmOpenModal = openModal;
    window.__cmCloseModal = closeAll;
})();
</script>
