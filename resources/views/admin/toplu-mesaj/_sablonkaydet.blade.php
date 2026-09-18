{{-- "Şablon olarak kaydet" bloğu. Değişken: $pfx --}}
<div class="form-group" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
        <input type="checkbox" name="sablon_kaydet" value="1"
               onchange="document.getElementById('{{ $pfx }}_sablon_detay').style.display=this.checked?'flex':'none'">
        <span>Bunu şablon olarak kaydet</span>
    </label>
    <div id="{{ $pfx }}_sablon_detay" style="display:none;gap:10px;flex:1;min-width:240px">
        <input type="text" name="sablon_adi" class="form-input" placeholder="Şablon adı…" style="flex:2;min-width:140px">
        <input type="text" name="sablon_kategori" class="form-input" placeholder="Kategori (ör. sektör/şehir)" style="flex:1;min-width:120px">
    </div>
</div>
