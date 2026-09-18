<div class="col-md-3 sidebar">
    <div class="panel panel-sidebar">
        <div class="list-group"> 
			<a <?php echo @$hesabim;?> href="hesabim.html" class="list-group-item"> <i class="fas fa-home"></i><?=@$dil['txt340'];?> </a> 
			<a <?php echo @$desteksistemi;?> href="destek-taleplerim.html" class="list-group-item"> <i class="fas fa-life-ring"></i><?=@$dil['txt182'];?> </a> 
			<a href="bilgilerim.html" class="list-group-item"> <i class="fas fa-user"></i><?=@$dil['txt109'];?> </a> 
			<a <?php echo @$faturalarim;?> href="faturalarim.html" class="list-group-item"> <i class="fas fa-file-invoice"></i><?=@$dil['txt247'];?> </a> 
			<a <?php echo @$sepetim;?> href="sepet.html" class="list-group-item"> <i class="fas fa-shopping-basket"></i><?=@$dil['txt393'];?> </a> 
			<a <?php echo @$alan_adlarim;?> href="alan_adlarim.html" class="list-group-item"> <i class="fas fa-globe"></i><?=@$dil['txt26'];?> </a> 
			<a <?php echo @$hostinglerim;?> href="hostinglerim.html" class="list-group-item"> <i class="fas fa-server"></i><?=@$dil['txt307'];?> </a> 
			<a <?php echo @$web_paketlerim;?> href="web_paketlerim.html" class="list-group-item"> <i class="fab fa-chrome"></i><?=@$dil['txt283'];?> </a> 
			<a <?php echo @$hizmetlerim;?> href="hizmetlerim.html" class="list-group-item"> <i class="fas fa-briefcase"></i><?=@$dil['txt305'];?> </a>
			<a <?php echo @$sozlesmelerim;?> href="sozlesmelerim.html" class="list-group-item"> <i class="fas fa-briefcase"></i>Sözleşmelerim </a>
			<a <?php echo @$raporlarim;?> href="raporlarim.html" class="list-group-item"> <i class="fas fa-briefcase"></i>Raporlarım </a>
			<a <?php echo @$efaturalarim;?> href="efaturalarim.html" class="list-group-item"> <i class="fas fa-briefcase"></i>E - Faturalarım</a>
			<a <?php echo @$dosyalarim;?> href="dosyalarim.html" class="list-group-item"> <i class="fas fa-briefcase"></i>Dosyalarım</a>
			<a <?php echo @$referanslarim;?> href="referanslarim.html" class="list-group-item"> <i class="fas fa-briefcase"></i>Referanslarım </a>
			<a <?php echo @$tekliflerim;?> href="tekliflerim.html" class="list-group-item"> <i class="fas fa-briefcase"></i>Tekliflerim </a>
			<a <?php echo @$favorilerim;?> href="favorilerim.html" class="list-group-item"> <i class="far fa-heart"></i><?=@$dil['txt254'];?> </a> 
			<a href="javascript:;" onclick="oturum_kapat()" class="list-group-item"> <i class="fas fa-power-off"></i><?=@$dil['txt341'];?> </a> 
		</div>
    </div>
</div>