// Üyeler listesinde arama ve durum filtresi tamamen server-side çalışsın.
// JS sadece form submit ve temizleme işini yapar.

document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('adminUyelerSearchForm');
  const aramaInput = document.getElementById('aramaInput');
  const durumFiltre = document.getElementById('durumFiltre');

  if (!form) return;

  if (aramaInput) {
    aramaInput.addEventListener('keyup', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        form.submit();
      }
    });
  }

  if (durumFiltre) {
    durumFiltre.addEventListener('change', function () {
      form.submit();
    });
  }
});

function aramaTemizle() {
  const form = document.getElementById('adminUyelerSearchForm');
  if (!form) return;

  const aramaInput = document.getElementById('aramaInput');
  const durumFiltre = document.getElementById('durumFiltre');

  if (aramaInput) aramaInput.value = '';
  if (durumFiltre) durumFiltre.value = '';

  form.submit();
}

