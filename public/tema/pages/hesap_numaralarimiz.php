<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null;?>
<?php
$page = @intval($_GET['s']);
if(!$page) $page = 1;
$ttsorgu = $db->prepare("SELECT COUNT(*) FROM banka_hesaplari WHERE durum = ?");
$ttsorgu->execute(array("1"));
$total = $ttsorgu->fetchColumn();
$limit= $limitayar['limit_sayfabanka'];
$page_count = ceil($total/$limit);
if($page > $page_count) $page = 1;
$show = $page * $limit - $limit; 
$BankaSorgu = $db->prepare("SELECT * FROM banka_hesaplari WHERE durum = ? ORDER BY id DESC LIMIT $show,$limit");
$BankaSorgu->execute(array("1"));
$Bankaislem = $BankaSorgu->fetchALL(PDO::FETCH_ASSOC);
$menubul 	= $db->query("SELECT * FROM menu WHERE menu_url = 'hesap-numaralarimiz.html' OR link = 'hesap-numaralarimiz.html' AND dil = '{$_SESSION['k_dil']}'")->fetch(PDO::FETCH_ASSOC);
$menubas 	= $db->query("SELECT * FROM menu WHERE id = '{$menubul['menu_ust']}' AND dil = '{$_SESSION['k_dil']}'")->fetch(PDO::FETCH_ASSOC);	
?>
<!-- ***** BANNER ***** -->
<div class="top-header overlay" style="background-image: url(<?php echo tema;?>/uploads/arkaplan/banka_hesaplari/<?php echo $arkaplan['banka_hesaplari']?>)">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper">
                    <h1 class="heading"><?=@$dil['txt300'];?></h1>
					<h3 class="subheading"><?=@$dil['txt301'];?></h3>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="ustbanner">
	<div class="container">
		<a href="index.html" class="golink gocheck"> <?=@$dil['txt158'];?> </a> <small class="c-white">&#9679;</small>
        <?php if($menubas['menu_isim'] != ""){?>
        <a href="<?php echo($menubas['menu_url'] == "0" ? $menubas['link'] : $menubas['menu_url']);?>" class="golink gocheck"> <?php echo $menubas['menu_isim'];?> </a> <small class="c-white">&#9679;</small>
		<?php }else{?>
		<a href="<?php echo($menubul['menu_url'] == "0" ? $menubul['link'] : $menubul['menu_url']);?>" class="golink gocheck"> <?php echo $menubul['menu_isim'];?> </a> <small class="c-white">&#9679;</small>
		<?php }?>
        <a href="<?php echo $sayfalink;?>" class="golink gocheck"> <?=@$dil['txt300'];?></a>
	</div>
</div>
<!-- ***** TYPOGRAPHY ***** -->
<section class=" sec-normal sec-bg2">
    <div class="container">
        <div class="row align-items-center">		
			<?php foreach ( $Bankaislem as $BankaSonuc ){?>
			<div class="col-12 col-md-<?php echo $limitayar['limit_banka'];?>">
				<div class="blog-wrap mb-3" style="overflow:hidden;">
					<div class="row">
						<div class="col-3">
							<div style="display: table-cell;vertical-align: middle;height: 144px;">
								<img class="mw-100 p-2" src="<?php echo tema;?>/uploads/bankalar/<?php echo $BankaSonuc['resim']; ?>" alt="<?php echo $BankaSonuc['banka']; ?>" />
							</div>
						</div>
						<div class="col-9">
							<div class="p-4">
								<p><?php echo $BankaSonuc['banka']; ?>, <br> <?php echo $BankaSonuc['hesap']; ?><br>
								<strong>IBAN: <?php echo $BankaSonuc['iban']; ?></strong><br><br>
								<strong>Ödeme işleminden sonra <a href="https://crm.ornek.com/odeme-bildirim-formu.html">''Buraya Tıklayarak''</a></strong><br>
<strong>ödeme bildirim formunu doldurabilirsiniz.</strong></p>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php }?>
        </div>
		<div class="paginationn-box-row">
			<p><?php echo $total;?> <?=@$dil['txt162'];?>  <?php echo $page;?> - <?php echo $limitayar['limit_sayfabanka'];?> <?=@$dil['txt163'];?></p>
			<ul class="paginationn">
			<?php
			if($limitayar['limit_sayfabanka'] < $total){
			$showing = 3;
			if($page > 1){?>
			<?php $previous = $page - 1;?>
			<li><a href="hesap-numaralarimiz/<?php echo $previous;?>.html"><i class="fa fa-angle-double-left"></i></a></li>
			<?php }
			for($i= $page - $showing; $i < $page + $showing + 1; $i++){
			if($i > 0 and $i <= $page_count){
			if($i == $page){?>
			  <li class="active"><a href="javascript:void(0);"><?php echo $i; ?></a></li>
			<?php }else{?>
			<li><a href="hesap-numaralarimiz/<?php echo $i; ?>.html"><?php echo $i; ?></a></li>
			<?php }
			}
			}
			if($page != $page_count){?>
			<?php  $next = $page +1;?>
			<li><a href="hesap-numaralarimiz/<?php echo $next; ?>.html"><i class="fa fa-angle-double-right"></i></a></li>
			<?php }} ?>
			</ul>
		</div>
    </div>
</section>