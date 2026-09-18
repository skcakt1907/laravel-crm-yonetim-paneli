// Referans linki kopyalama
function copyLink() {
  var copyText = document.getElementById("referansLink");
  copyText.select();
  copyText.setSelectionRange(0, 99999);
  document.execCommand("copy");
  alert("Referans linki kopyalandı!");
}

