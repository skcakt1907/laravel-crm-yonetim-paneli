<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null;?>
<?php 
$page = @intval($_GET['s']);
if(!$page) $page = 1;
$ttsorgu = $db->prepare("SELECT COUNT(*) FROM blog WHERE durum = ? AND dil = ?");
$ttsorgu->execute(array("1",$_SESSION['k_dil']));
$total = $ttsorgu->fetchColumn();
$limit= $limitayar['limit_sayfablog'];
$page_count = ceil($total/$limit);
if($page > $page_count) $page = 1;
$show = $page * $limit - $limit;
$BSorgu = $db->prepare("SELECT * FROM blog WHERE durum = ? AND dil = ? ORDER BY id DESC LIMIT $show,$limit");
$BSorgu->execute(array("1",$_SESSION['k_dil']));
$Bislem = $BSorgu->fetchALL(PDO::FETCH_ASSOC);
$menubul 	= $db->query("SELECT * FROM menu WHERE menu_url = 'blog.html' OR link = 'blog.html' AND dil = '{$_SESSION['k_dil']}'")->fetch(PDO::FETCH_ASSOC);
$menubas 	= $db->query("SELECT * FROM menu WHERE id = '{$menubul['menu_ust']}' AND dil = '{$_SESSION['k_dil']}'")->fetch(PDO::FETCH_ASSOC);
?>
<div class="top-header overlay" style="background-image: url(<?php echo tema;?>/uploads/arkaplan/blog/<?php echo $arkaplan['blog']?>)">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper">
                    <h1 class="heading"><?=@$dil['txt156'];?></h1>
                    <h3 class="subheading"><?=@$dil['txt157'];?></h3>
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
        <a href="<?php echo $sayfalink;?>" class="golink gocheck"><?=@$dil['txt156'];?> </a>
	</div>
</div>
<!-- ***** BLOG DETAILS ***** -->
<section class="shopping blog sec-normal news-item-wrapper sec-bg2 ">
    <div class="container">
        <div class="row">
			<div class="wrap-blog">
				<div class="row">
				<?php if($BSorgu->rowCount() != "0"){?>
					<?php foreach ( $Bislem as $BSonuc ){?>
					<div class="col-md-<?php echo $limitayar['limit_blog'];?> col-xs-12">
						<div class="news-item">
							<div class="news-item-img">
								<?php
								$blogResim = null;
								if (!empty($BSonuc['resim'])) {
									if (strpos($BSonuc['resim'], '/') !== false) {
										$blogResim = asset($BSonuc['resim']);
									} else {
										$blogResim = tema . '/uploads/bloglar/' . $BSonuc['resim'];
									}
								}
								?>
								<?php if($blogResim){?>
								<div class="blogbg" style="background:url(<?php echo $blogResim;?>);"></div>
								<?php }else{?>
								<div class="blogbg" style="background:url(<?php echo tema;?>/assets/img/noimage.png);"></div>
								<?php }?>
								<span><?php echo TvERtXpE3w_tarih($BSonuc['tarih']); ?></span>
							</div>
							<div class="news-text-item">
								<?php
								$lang = isset($_SESSION['k_dil']) ? (int)$_SESSION['k_dil'] : 1; // 1=TR,2=EN,3=AR
								$baslik = $BSonuc['adi'] ?? $BSonuc['baslik'] ?? '';
								$icerik = $BSonuc['aciklama'] ?? $BSonuc['icerik'] ?? '';
								if ($lang === 2) {
									if (!empty($BSonuc['adi_en']) || !empty($BSonuc['baslik_en'])) {
										$baslik = $BSonuc['adi_en'] ?? $BSonuc['baslik_en'];
									}
									if (!empty($BSonuc['aciklama_en']) || !empty($BSonuc['icerik_en'])) {
										$icerik = $BSonuc['aciklama_en'] ?? $BSonuc['icerik_en'];
									}
								} elseif ($lang === 3) {
									if (!empty($BSonuc['adi_ar']) || !empty($BSonuc['baslik_ar'])) {
										$baslik = $BSonuc['adi_ar'] ?? $BSonuc['baslik_ar'];
									}
									if (!empty($BSonuc['aciklama_ar']) || !empty($BSonuc['icerik_ar'])) {
										$icerik = $BSonuc['aciklama_ar'] ?? $BSonuc['icerik_ar'];
									}
								}
								?>
								<h4><?php echo TvERtXpE3w_kisa($baslik,40); ?></h4>
								<p><?php echo TvERtXpE3w_kisa(strip_tags($icerik),200); ?></p>
								<div class="entry-footer">
									<a href="blog/<?php echo $BSonuc['seo']; ?>.html" class="read_more"><?=@$dil['txt159'];?> <i class="fa fa-long-arrow-right" aria-hidden="true"></i></a>
								</div>
							</div>
						</div>
					</div>
					<?php }?>
					<?php }else{?>	
					<div class="balancepage m-0">
						<div class="uyari-info">
							<div class="padding15">
								<i class="fa fa-info-circle m-0 mr-3 mt-1" aria-hidden="true"></i>
								<div class="balanceinfo">
									<h5><strong><?=@$dil['txt160'];?></strong></h5>
									<p style="font-weight:400;margin-bottom:0px;paddin-bottom:0px;">
										<?=@$dil['txt161'];?>
									</p>
								</div>
							</div>
						</div>
					</div>
					<?php }?>
				</div>
				<div class="paginationn-box-row">
					<p><?php echo $total;?> <?=@$dil['txt162'];?>  <?php echo $page;?> - <?php echo $limitayar['limit_sayfablog'];?> <?=@$dil['txt163'];?></p>
					<ul class="paginationn">
					<?php
					if($limitayar['limit_sayfablog'] < $total){
					$showing = 3;
					if($page > 1){?>
					<?php $previous = $page - 1;?>
					<li><a href="blogs/<?php echo $previous;?>.html"><i class="fa fa-angle-double-left"></i></a></li>
					<?php }
					for($i= $page - $showing; $i < $page + $showing + 1; $i++){
					if($i > 0 and $i <= $page_count){
					if($i == $page){?>
					  <li class="active"><a href="javascript:void(0);"><?php echo $i; ?></a></li>
					<?php }else{?>
					<li><a href="blogs/<?php echo $i; ?>.html"><?php echo $i; ?></a></li>
					<?php }
					}
					}
					if($page != $page_count){?>
					<?php  $next = $page +1;?>
					<li><a href="blogs/<?php echo $next; ?>.html"><i class="fa fa-angle-double-right"></i></a></li>
					<?php }} ?>
					</ul>
				</div>
			</div>			
		</div>
	</div>
</section>