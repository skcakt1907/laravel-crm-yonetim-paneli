{{-- Reusable TinyMCE 7 yükleyici. Sayfada bir kez @include et.
     Kullanım: <textarea class="rich-full"> veya <textarea class="rich-mini">
--}}
<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function(){
  function isLight(){ return document.body.classList.contains('light'); }
  function commonOpts(){
    return {
      license_key: 'gpl',
      menubar: false,
      statusbar: false,
      branding: false,
      promotion: false,
      content_style: 'body{font-family:Inter,system-ui,sans-serif;font-size:13px;color:' + (isLight()?'#0f172a':'#f1f5f9') + ';background:' + (isLight()?'#ffffff':'#0a0a0a') + ';}',
      skin: isLight() ? 'oxide' : 'oxide-dark',
      content_css: isLight() ? 'default' : 'dark',
      language: 'tr',
      language_url: 'https://cdn.jsdelivr.net/npm/tinymce-i18n@latest/langs7/tr.js',
      plugins: 'lists link autolink paste',
      paste_as_text: false,
      browser_spellcheck: true,
      // required textarea gizli olunca HTML5 validation hata verip submit'i engeller
      // Init'te required'i kaldır, submit öncesinde manuel kontrol et
      init_instance_callback: function(editor){
        var ta = editor.getElement();
        if (ta && ta.hasAttribute('required')) {
          ta.removeAttribute('required');
          ta.dataset.wasRequired = '1';
        }
        // Editör değiştikçe textarea'ya yansıt (submit gecikmesi olmasın)
        editor.on('change keyup blur', function(){ editor.save(); });
      }
    };
  }
  function initFull(){
    tinymce.init(Object.assign({}, commonOpts(), {
      selector: 'textarea.rich-full',
      height: 320,
      toolbar: 'undo redo | blocks | bold italic underline forecolor backcolor | alignleft aligncenter alignright | bullist numlist | link unlink | removeformat'
    }));
  }
  function initMini(){
    tinymce.init(Object.assign({}, commonOpts(), {
      selector: 'textarea.rich-mini',
      height: 180,
      toolbar: 'bold italic underline forecolor | bullist numlist | link unlink | removeformat'
    }));
  }
  function bootAll(){
    if (!window.tinymce) return setTimeout(bootAll, 100);
    initFull(); initMini();
  }
  // Submit'ten önce textarea'ya yazılsın + boş required kontrolü
  document.addEventListener('submit', function(e){
    if (!window.tinymce) return;
    try { window.tinymce.triggerSave(); } catch(_) {}
    var form = e.target;
    if (form && form.querySelectorAll) {
      var bos = false;
      form.querySelectorAll('textarea[data-was-required="1"]').forEach(function(ta){
        var val = (ta.value || '').replace(/<[^>]*>/g, '').trim();
        if (!val) {
          bos = true;
          var ed = window.tinymce.get(ta.id) || window.tinymce.activeEditor;
          if (ed) ed.focus();
          alert('Lütfen "' + (ta.getAttribute('placeholder') || ta.name) + '" alanını doldurun.');
        }
      });
      if (bos) { e.preventDefault(); e.stopPropagation(); return false; }
    }
  }, true);
  // Tema değişince yeniden başlat
  window.reinitRich = function(){
    if (!window.tinymce) return;
    tinymce.remove('textarea.rich-full');
    tinymce.remove('textarea.rich-mini');
    initFull(); initMini();
  };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootAll);
  } else {
    bootAll();
  }
})();
</script>
