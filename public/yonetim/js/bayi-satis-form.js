// Bayi satış formu - Paket seçimi ve komisyon hesaplama
document.getElementById('paketSelect').addEventListener('change', function() {
  const fiyat = this.options[this.selectedIndex].getAttribute('data-fiyat');
  if (fiyat) {
    document.getElementById('satisTutari').value = fiyat;
    hesaplaKomisyon();
  }
});

document.getElementById('satisTutari').addEventListener('input', hesaplaKomisyon);

function hesaplaKomisyon() {
  const tutar = parseFloat(document.getElementById('satisTutari').value) || 0;
  const komisyon = tutar * 0.10;
  document.getElementById('komisyonTutar').textContent = '₺' + komisyon.toFixed(2);
}

