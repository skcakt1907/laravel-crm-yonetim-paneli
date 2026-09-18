// Email şablonları - Şablon seçimi
function selectTemplate(id, baslik, icerik) {
  document.getElementById('emailKonu').value = baslik;
  document.getElementById('emailMesaj').value = icerik;
  
  // Scroll to form
  document.getElementById('emailKonu').scrollIntoView({ behavior: 'smooth' });
}

