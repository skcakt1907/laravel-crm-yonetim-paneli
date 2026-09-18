// İletişim sayfası - SweetAlert bildirimleri
(function() {
  const successElement = document.getElementById('successMessage');
  const errorElement = document.getElementById('errorMessage');
  
  const successMessage = successElement ? successElement.textContent.trim() : null;
  const errorMessage = errorElement ? errorElement.textContent.trim() : null;

  if (successMessage && typeof Swal !== 'undefined') {
    Swal.fire({
      icon: 'success',
      title: 'Başarılı!',
      text: successMessage,
      confirmButtonText: 'Tamam',
      timer: 5000
    });
  }

  if (errorMessage && typeof Swal !== 'undefined') {
    Swal.fire({
      icon: 'error',
      title: 'Hata!',
      text: errorMessage,
      confirmButtonText: 'Tamam',
      timer: 5000
    });
  }
})();
