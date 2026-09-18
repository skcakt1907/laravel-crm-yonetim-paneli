<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null;?>
<?php 
$page = @intval($_GET['s']);
if(!$page) $page = 1;
$ttsorgu = $db->prepare("SELECT COUNT(*) FROM referanslar WHERE durum = ? AND dil = ?");
$ttsorgu->execute(array("1",$_SESSION['k_dil']));
$total = $ttsorgu->fetchColumn();
$limit= $limitayar['limit_sayfareferans'];
$page_count = ceil($total/$limit);
if($page > $page_count) $page = 1;
$show = $page * $limit - $limit;
$Sorgu = $db->prepare("SELECT * FROM referanslar WHERE durum = ? AND dil = ? ORDER BY sira ASC LIMIT $show,$limit");
$Sorgu->execute(array("1",$_SESSION['k_dil']));
$islem = $Sorgu->fetchALL(PDO::FETCH_ASSOC);
$menubul 	= $db->query("SELECT * FROM menu WHERE menu_url = 'referanslar.html' OR link = 'referanslar.html' AND dil = '{$_SESSION['k_dil']}'")->fetch(PDO::FETCH_ASSOC);
$menubas 	= $db->query("SELECT * FROM menu WHERE id = '{$menubul['menu_ust']}' AND dil = '{$_SESSION['k_dil']}'")->fetch(PDO::FETCH_ASSOC);	
?>
<!-- ***** BANNER ***** -->
<div class="top-header overlay" style="background-image: url(<?php echo tema;?>/uploads/arkaplan/referanslar/<?php echo $arkaplan['referanslar']?>)">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper">
                    <h1 class="heading"><?=@$dil['txt337'];?></h1>
					<h3 class="subheading"><?=@$dil['txt338'];?></h3>
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
        <a href="<?php echo $sayfalink;?>" class="golink gocheck"> <?=@$dil['txt337'];?></a>
	</div>
</div>
<!-- ***** TYPOGRAPHY ***** -->
<section class="tipography-ex sec-normal sec-bg1">
    <div class="container">
        <div class="row">            
			<?php foreach ( $islem as $Sonuc ){?>
			<div class="col-sm-12 col-md-<?php echo $limitayar['limit_referanslar'];?> wow animated fadeInUp fast">
				<div class="referanslar">
					<a href="referans/<?php echo $Sonuc['seo']?>.html">
						<div class="thumb"><img  src="<?php echo tema;?>/uploads/referanslar/<?php echo $Sonuc['resim']?>"></div>
						<div class="referansOverlay">
						  <div class="referansOverlayContent">
							<h3><?php echo $Sonuc['kisa']?></h3>
						  </div>
						</div>
						<h1><?php echo $Sonuc['adi']?></h1>
					</a>
				</div>
			</div>
			<?php }?>
        </div>
		<div class="paginationn-box-row">
			<p><?php echo $total;?> <?=@$dil['txt162'];?>  <?php echo $page;?> - <?php echo $limitayar['limit_sayfareferans'];?> <?=@$dil['txt163'];?></p>
			<ul class="paginationn">
			<?php
			if($limitayar['limit_sayfareferans'] < $total){
			$showing = 3;
			if($page > 1){?>
			<?php $previous = $page - 1;?>
			<li><a href="referanslar/<?php echo $previous;?>.html"><i class="fa fa-angle-double-left"></i></a></li>
			<?php }
			for($i= $page - $showing; $i < $page + $showing + 1; $i++){
			if($i > 0 and $i <= $page_count){
			if($i == $page){?>
			  <li class="active"><a href="javascript:void(0);"><?php echo $i; ?></a></li>
			<?php }else{?>
			<li><a href="referanslar/<?php echo $i; ?>.html"><?php echo $i; ?></a></li>
			<?php }
			}
			}
			if($page != $page_count){?>
			<?php  $next = $page +1;?>
			<li><a href="referanslar/<?php echo $next; ?>.html"><i class="fa fa-angle-double-right"></i></a></li>
			<?php }} ?>
			</ul>
		</div>
    </div>
</section>