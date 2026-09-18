// Navbar dropdown'ları birbirine girmesin diye - Geliştirilmiş versiyon
$(document).ready(function() {
  // Tüm dropdown'ları kapat
  function closeAllDropdowns(exceptId) {
    $('.navbar-nav-right .nav-item.dropdown').each(function() {
      var $dropdown = $(this);
      var dropdownId = $dropdown.attr('id');
      if (dropdownId !== exceptId) {
        $dropdown.removeClass('show');
        $dropdown.find('.dropdown-menu').removeClass('show');
        $dropdown.find('.dropdown-toggle').removeClass('show').attr('aria-expanded', 'false');
      }
    });
    // Custom language dropdown'ı da kapat
    if (exceptId !== 'languageDropdownWrapper') {
      $('#languageDropdownMenu').hide();
    }

    // Custom currency dropdown'ı da kapat
    if (exceptId !== 'currencyDropdownWrapper') {
      $('#currencyDropdownMenu').hide();
    }
  }

  // Custom dil dropdown toggle
  window.toggleLanguageMenu = function(e) {
    if (e) {
      e.preventDefault();
      e.stopPropagation();
    }
    var $menu = $('#languageDropdownMenu');
    var isVisible = $menu.is(':visible');
    
    // Tüm dropdown'ları kapat
    closeAllDropdowns(null);
    $('#profileDropdownWrapper').removeClass('show');
    $('#profileDropdownMenu').hide();
    
    // Dil dropdown'ını toggle et
    if (isVisible) {
      $menu.hide();
    } else {
      $menu.show();
    }
  };

  // Custom para birimi dropdown toggle
  window.toggleCurrencyMenu = function(e) {
    if (e) {
      e.preventDefault();
      e.stopPropagation();
    }
    var $menu = $('#currencyDropdownMenu');
    var isVisible = $menu.is(':visible');

    // Tüm dropdown'ları kapat
    closeAllDropdowns(null);
    $('#profileDropdownWrapper').removeClass('show');
    $('#profileDropdownMenu').hide();

    // Currency dropdown'ını toggle et
    if (isVisible) {
      $menu.hide();
    } else {
      $menu.show();
    }
  };

  // Profile dropdown toggle - açıksa kapat, kapalıysa aç
  $('#profileDropdown').on('click', function(e) {
    e.stopPropagation();
    var $dropdown = $('#profileDropdownWrapper');
    var isOpen = $dropdown.hasClass('show');
    
    if (isOpen) {
      // Açıksa kapat
      $dropdown.removeClass('show');
      $('#profileDropdownMenu').removeClass('show').hide();
      $(this).attr('aria-expanded', 'false');
    } else {
      // Kapalıysa aç ve diğerlerini kapat
    closeAllDropdowns('profileDropdownWrapper');
      $('#languageDropdownMenu').hide();
      $('#currencyDropdownMenu').hide();
      $dropdown.addClass('show');
      $('#profileDropdownMenu').addClass('show').show();
      $(this).attr('aria-expanded', 'true');
    }
  });

  // Dışarı tıklandığında tüm dropdown'ları kapat
  $(document).on('click', function(e) {
    var $target = $(e.target);
    var isInsideDropdown = $target.closest('.navbar-nav-right .nav-item.dropdown').length > 0;
    var isInsideCustomLanguageDropdown = $target.closest('#languageDropdownWrapper').length > 0;
    var isInsideCustomCurrencyDropdown = $target.closest('#currencyDropdownWrapper').length > 0;
    var isDropdownToggle = $target.closest('.dropdown-toggle').length > 0;
    var isLanguageToggle = $target.closest('#languageDropdown').length > 0;
    var isCurrencyToggle = $target.closest('#currencyDropdown').length > 0;

    if (!isInsideDropdown && !isInsideCustomLanguageDropdown && !isInsideCustomCurrencyDropdown && !isDropdownToggle && !isLanguageToggle && !isCurrencyToggle) {
      closeAllDropdowns(null);
      $('#profileDropdownWrapper').removeClass('show');
      $('#profileDropdownMenu').removeClass('show').hide();
      $('#languageDropdownMenu').hide();
      $('#currencyDropdownMenu').hide();
    }
  });

  // ESC tuşu ile kapat
  $(document).on('keydown', function(e) {
    if (e.key === 'Escape' || e.keyCode === 27) {
      closeAllDropdowns(null);
      $('#profileDropdownWrapper').removeClass('show');
      $('#profileDropdownMenu').removeClass('show').hide();
      $('#languageDropdownMenu').hide();
      $('#currencyDropdownMenu').hide();
    }
  });

  // Agresif yaklaşım: Her frame'de kontrol et
  setInterval(function() {
    if ($('#languageDropdownWrapper').hasClass('show') || $('#languageDropdownMenu').is(':visible')) {
      var $menu = $('#languageDropdownMenu');
      if ($menu.length && $menu.is(':visible')) {
        $menu.attr('style', 'right: auto !important; left: 0 !important; transform: translateX(-100%) !important; position: absolute !important; top: 100% !important;');
      }
    }

    if ($('#currencyDropdownWrapper').hasClass('show') || $('#currencyDropdownMenu').is(':visible')) {
      var $menu2 = $('#currencyDropdownMenu');
      if ($menu2.length && $menu2.is(':visible')) {
        $menu2.attr('style', 'right: auto !important; left: 0 !important; transform: translateX(-100%) !important; position: absolute !important; top: 100% !important;');
      }
    }
  }, 50);
  
  // Herhangi bir dropdown açıldığında diğerlerini kapat
  $('.navbar-nav-right .nav-item.dropdown').on('shown.bs.dropdown', function() {
    var $current = $(this);
    var currentId = $current.attr('id');
    closeAllDropdowns(currentId);
    $('#languageDropdownMenu').hide();
    $('#currencyDropdownMenu').hide();
  });
  
  // Dışarı tıklandığında dil dropdown'ını kapat
  $(document).on('click', function(e) {
    if (!$(e.target).closest('#languageDropdownWrapper').length && !$(e.target).closest('#languageDropdown').length) {
      $('#languageDropdownMenu').hide();
    }
  });

  // Dışarı tıklandığında currency dropdown'ını kapat
  $(document).on('click', function(e) {
    if (!$(e.target).closest('#currencyDropdownWrapper').length && !$(e.target).closest('#currencyDropdown').length) {
      $('#currencyDropdownMenu').hide();
    }
  });
  
  // Alt menü linklerinin (sub-menu içindeki) normal çalışmasını garanti et
  $(document).on('click', '.sidebar .sub-menu .nav-link', function(e) {
    // Alt menü linkleri her zaman normal davranışına bırakılmalı
    return true;
  });
  
  // Sidebar collapse menülerini toggle yap - Bootstrap'in kendi collapse fonksiyonunu kullan
  // SADECE # ile başlayan (collapse menüleri) linkler için
  $(document).on('click', '.sidebar .nav-link[data-toggle="collapse"][href^="#"]', function(e) {
    // Alt menü içindeki linkleri hiç etkileme
    if ($(this).closest('.sub-menu').length > 0) {
      return true;
    }
    
    // Bootstrap'in kendi collapse fonksiyonunu kullan
    e.preventDefault();
    e.stopPropagation();
    
    var $this = $(this);
    var target = $this.attr('href');
    var $targetCollapse = $(target);

    if (!$targetCollapse.length) {
      return false;
    }
    
    // Bootstrap collapse'i toggle et
    $targetCollapse.collapse('toggle');
    
    return false;
  });
  
  // Bootstrap collapse event'lerini dinle ve aria-expanded'ı güncelle
  $(document).on('show.bs.collapse', '.sidebar .collapse', function() {
    var collapseId = $(this).attr('id');
    if (collapseId) {
      $('.sidebar .nav-link[href="#' + collapseId + '"]').attr('aria-expanded', 'true');
    }
  });
  
  $(document).on('hide.bs.collapse', '.sidebar .collapse', function() {
    var collapseId = $(this).attr('id');
    if (collapseId) {
      $('.sidebar .nav-link[href="#' + collapseId + '"]').attr('aria-expanded', 'false');
    }
  });
  
  $(document).on('shown.bs.collapse', '.sidebar .collapse', function() {
    var collapseId = $(this).attr('id');
    if (collapseId) {
      $('.sidebar .nav-link[href="#' + collapseId + '"]').attr('aria-expanded', 'true');
    }
  });
  
  $(document).on('hidden.bs.collapse', '.sidebar .collapse', function() {
    var collapseId = $(this).attr('id');
    if (collapseId) {
      $('.sidebar .nav-link[href="#' + collapseId + '"]').attr('aria-expanded', 'false');
    }
  });

  // Rehberim tablosu: satıra tıklayınca aşağı kart aç/kapa
  $(document).on('click', '.phonebook-row', function() {
    var id = $(this).data('customer-id');
    var $detailsRow = $('.phonebook-details-row[data-customer-id="' + id + '"]');

    if ($detailsRow.length) {
      $detailsRow.toggle();
    }
  });
});

