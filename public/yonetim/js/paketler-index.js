// Admin paketler sayfasında arama ve filtreler tamamen server-side çalışsın.
// JS sadece doğru formun submit edilmesini sağlıyor.

document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('adminPaketlerSearchForm');
  const aramaInput = document.getElementById('aramaInput');
  const kategoriFiltre = document.getElementById('kategoriFiltre');
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

  if (kategoriFiltre) {
    kategoriFiltre.addEventListener('change', function () {
      form.submit();
    });
  }

  if (durumFiltre) {
    durumFiltre.addEventListener('change', function () {
      form.submit();
    });
  }
});

