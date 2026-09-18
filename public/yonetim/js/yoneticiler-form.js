// Yöneticiler form - Üye seçimi ve otomatik doldurma
(function () {
  const modeExisting = document.getElementById('account_mode_existing');
  const modeNew = document.getElementById('account_mode_new');
  const existingSection = document.getElementById('existing_uye_section');
  const newSection = document.getElementById('new_uye_section');

  const uyeSelect = document.getElementById('uye_id_select');
  const uyeSearch = document.getElementById('uye_search');
  const kullaniciInput = document.getElementById('kullaniciadi');
  const emailInput = document.getElementById('email');
  const adInput = document.getElementById('adi');
  const telefonInput = document.getElementById('telefon');

  const uyeAd = document.getElementById('uye_ad');
  const uyeSoyad = document.getElementById('uye_soyad');
  const uyeEmail = document.getElementById('uye_email');
  const uyeTelefon = document.getElementById('uye_telefon');

  function applySelection(option) {
    if (!option || !option.value) {
      emailInput.value = '';
      adInput.value = '';
      telefonInput.value = '';
      if (!kullaniciInput.dataset.manual) {
        kullaniciInput.value = '';
      }
      return;
    }

    const email = option.getAttribute('data-email') || '';
    const ad = option.getAttribute('data-ad') || '';
    const telefon = option.getAttribute('data-telefon') || '';

    emailInput.value = email;
    adInput.value = ad;
    telefonInput.value = telefon;

    if (!kullaniciInput.dataset.manual) {
      const suggestion = email ? email.split('@')[0] : ('uye' + option.value);
      kullaniciInput.value = suggestion;
    }
  }

  function setRequired(el, required) {
    if (!el) return;
    if (required) el.setAttribute('required', 'required');
    else el.removeAttribute('required');
  }

  function setReadonly(el, readonly) {
    if (!el) return;
    el.readOnly = !!readonly;
  }

  function updateModeUI() {
    const isNew = !!(modeNew && modeNew.checked);

    if (existingSection) existingSection.style.display = isNew ? 'none' : '';
    if (newSection) newSection.style.display = isNew ? '' : 'none';

    // Existing selection requirement
    setRequired(uyeSelect, !isNew);

    // New member requirement
    setRequired(uyeAd, isNew);
    setRequired(uyeSoyad, isNew);
    setRequired(uyeEmail, isNew);

    // Auto-fill panel fields
    setReadonly(emailInput, true);
    setReadonly(adInput, true);
    setReadonly(telefonInput, true);

    if (isNew) {
      // Clear selection-based fields; they'll be filled from new inputs
      if (uyeSelect) uyeSelect.value = '';
      if (uyeSearch) uyeSearch.value = '';
      applySelection(null);
    } else {
      // Clear new member fields
      if (uyeAd) uyeAd.value = '';
      if (uyeSoyad) uyeSoyad.value = '';
      if (uyeEmail) uyeEmail.value = '';
      if (uyeTelefon) uyeTelefon.value = '';
    }
  }

  function applyNewInputsToPreview() {
    const email = (uyeEmail && uyeEmail.value ? uyeEmail.value.trim() : '');
    const ad = [
      uyeAd && uyeAd.value ? uyeAd.value.trim() : '',
      uyeSoyad && uyeSoyad.value ? uyeSoyad.value.trim() : '',
    ].filter(Boolean).join(' ');
    const telefon = (uyeTelefon && uyeTelefon.value ? uyeTelefon.value.trim() : '');

    emailInput.value = email;
    adInput.value = ad;
    telefonInput.value = telefon;

    if (!kullaniciInput.dataset.manual) {
      const suggestion = email ? email.split('@')[0] : '';
      if (suggestion) kullaniciInput.value = suggestion;
    }
  }

  if (uyeSelect) {
    uyeSelect.addEventListener('change', function () {
      applySelection(this.selectedOptions[0]);
    });

    if (uyeSelect.value) {
      applySelection(uyeSelect.selectedOptions[0]);
    }
  }

  if (uyeSearch && uyeSelect) {
    // Cache original option order
    const allOptions = Array.from(uyeSelect.options);

    function filterOptions(termRaw) {
      const term = (termRaw || '').trim().toLowerCase();
      allOptions.forEach((opt, idx) => {
        if (idx === 0) {
          opt.hidden = false;
          return;
        }
        if (!term) {
          opt.hidden = false;
          return;
        }
        const text = (opt.textContent || '').toLowerCase();
        opt.hidden = !text.includes(term);
      });

      // If current selection becomes hidden, reset it
      const selected = uyeSelect.selectedOptions && uyeSelect.selectedOptions[0];
      if (selected && selected.hidden) {
        uyeSelect.value = '';
        applySelection(null);
      }
    }

    uyeSearch.addEventListener('input', function () {
      filterOptions(this.value);
    });

    // Initialize filter for old input
    filterOptions(uyeSearch.value);
  }

  if (modeExisting) modeExisting.addEventListener('change', updateModeUI);
  if (modeNew) modeNew.addEventListener('change', updateModeUI);

  if (uyeAd) uyeAd.addEventListener('input', applyNewInputsToPreview);
  if (uyeSoyad) uyeSoyad.addEventListener('input', applyNewInputsToPreview);
  if (uyeEmail) uyeEmail.addEventListener('input', applyNewInputsToPreview);
  if (uyeTelefon) uyeTelefon.addEventListener('input', applyNewInputsToPreview);

  if (kullaniciInput) {
    kullaniciInput.addEventListener('input', function () {
      if (this.value.trim().length) {
        this.dataset.manual = '1';
      } else {
        delete this.dataset.manual;
      }
    });
  }

  // Initial UI state
  updateModeUI();
  if (modeNew && modeNew.checked) {
    applyNewInputsToPreview();
  }
})();

