// Import/Export - Template indirme
function downloadTemplate(type) {
  let csv = '';
  
  if (type === 'uyeler') {
    csv = 'ID,Ad,Soyad,Email,Telefon,TC No,Durum,Tarih\n';
    csv += '1,Ahmet,Yılmaz,ahmet@example.com,05551234567,12345678901,Aktif,2025-01-01\n';
    csv += '2,Mehmet,Demir,mehmet@example.com,05559876543,98765432109,Aktif,2025-01-02\n';
  } else if (type === 'paketler') {
    csv = 'ID,Başlık,Fiyat,Kategori ID,Durum,Anasayfa\n';
    csv += '1,Başlangıç Paketi,999,1,Aktif,Evet\n';
    csv += '2,Profesyonel Paket,2999,1,Aktif,Evet\n';
  }
  
  const blob = new Blob([csv], { type: 'text/csv' });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = type + '_sablon.csv';
  a.click();
}

