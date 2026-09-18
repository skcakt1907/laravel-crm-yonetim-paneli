// Paketler düzenle - Resim silme
function resmiSil(paketId) {
  if (!confirm('Resmi silmek istediğinize emin misiniz?')) {
    return;
  }
  
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  
  fetch(`/admin/paketler/${paketId}/resim-sil`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken
    }
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Resim başarıyla silindi!');
      location.reload();
    } else {
      alert('Hata: ' + (data.message || 'Bilinmeyen hata'));
    }
  })
  .catch(error => {
    alert('Hata oluştu: ' + error.message);
  });
}

