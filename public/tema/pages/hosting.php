<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null; ?>
<?php
if (strip_tags(isset($_GET['id']))) {
	$Sorgu = $db->prepare("SELECT * FROM hosting_kategori WHERE seo = ? AND dil = ?");
	$Sorgu->execute(array($_GET['id'], $_SESSION['k_dil']));
	if ($Sorgu->rowCount()) {
		$Sonuc = $Sorgu->fetch(PDO::FETCH_ASSOC);
	} else {
		header("Location:" . $url . "/404.html");
	}
} else {
	$Sorgu = $db->prepare("SELECT * FROM hosting_kategori WHERE dil = ? ORDER BY id ASC");
	$Sorgu->execute(array($_SESSION['k_dil']));
	if ($Sorgu->rowCount()) {
		$Sonuc = $Sorgu->fetch(PDO::FETCH_ASSOC);
	} else {
		header("Location:" . $url . "/404.html");
	}
}
?>
<?php
$page = @intval($_GET['s']);
if (!$page) $page = 1;
$ttsorgu = $db->prepare("SELECT COUNT(*) FROM hostingler WHERE durum = ? AND kategori = ? AND dil = ?");
$ttsorgu->execute(array("1", $Sonuc['id'], $_SESSION['k_dil']));
$total = $ttsorgu->fetchColumn();
$limit = $limitayar['limit_sayfahosting'];
$page_count = ceil($total / $limit);
if ($page > $page_count) $page = 1;
$show = $page * $limit - $limit;
$URUNSorgu = $db->prepare("SELECT * FROM hostingler WHERE durum = ? AND kategori = ? AND dil = ? ORDER BY sira ASC LIMIT $show,$limit");
$URUNSorgu->execute(array("1", $Sonuc['id'], $_SESSION['k_dil']));
$URUNislem = $URUNSorgu->fetchALL(PDO::FETCH_ASSOC);
$menubul 	= $db->query("SELECT * FROM menu WHERE menu_url = 'hosting/{$Sonuc['seo']}.html' OR link = 'hosting/" . $Sonuc['seo'] . ".html' AND dil = '{$_SESSION['k_dil']}'")->fetch(PDO::FETCH_ASSOC);
$menubas 	= $db->query("SELECT * FROM menu WHERE id = '{$menubul['menu_ust']}' AND dil = '{$_SESSION['k_dil']}'")->fetch(PDO::FETCH_ASSOC);
?>
<div class="top-header overlay" style="background-image: url(<?php echo tema; ?>/uploads/arkaplan/hosting/<?php echo $arkaplan['hosting'] ?>)">
	<div class="container">
		<div class="row">
			<div class="col-sm-12 col-md-12">
				<div class="wrapper">
					<h1 class="heading"><?php echo $Sonuc['adi'] ?></h1>
					<h3 class="subheading"><?php echo $Sonuc['kisa'] ?></h3>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="ustbanner">
	<div class="container">
		<a href="index.html" class="golink gocheck"> <?= @$dil['txt158']; ?> </a> <small class="c-white">&#9679;</small>
		<?php if ($menubas['menu_isim'] != "") { ?>
			<a href="<?php echo ($menubas['menu_url'] == "0" ? $menubas['link'] : $menubas['menu_url']); ?>" class="golink gocheck"> <?php echo $menubas['menu_isim']; ?> </a> <small class="c-white">&#9679;</small>
		<?php } else { ?>
			<a href="<?php echo ($menubul['menu_url'] == "0" ? $menubul['link'] : $menubul['menu_url']); ?>" class="golink gocheck"> <?php echo $menubul['menu_isim']; ?> </a> <small class="c-white">&#9679;</small>
		<?php } ?>
		<a href="<?php echo $sayfalink; ?>" class="golink gocheck"> <?php echo $Sonuc['adi'] ?></a>
	</div>
</div>

<!-- ***** FILTER PLANS ***** -->
<div class="mixcontainer pb-5 pt-4 sec-bg2 " data-ref="container">
	<div class="container ">
		<div class="pricing special">
			<div class="p-0 m-0">
				<?php if ($URUNSorgu->rowCount() != "0") { ?>
					<?php foreach ($URUNislem as $URUNSonuc) { ?>
						<style>
							@media screen and (min-width: 992px) {

								.mix,
								.gap {
									width: calc(100%/<?php echo $limitayar['limit_hosting']; ?> - (((<?php echo $limitayar['limit_hosting']; ?> - 1) * 1rem) / <?php echo $limitayar['limit_hosting']; ?>));
								}
							}
						</style>
						<div class="mix" data-size="0">
							<div class="wrapper text-center">
								<div class="top-content p-3">
									<img class="svg mb-3" src="<?php echo tema; ?>/fonts/svg/dedicated.svg" alt="linux">
									<div class="title"><?php echo $URUNSonuc['adi']; ?></div>
									<div class="fromer"><?php echo $Sonuc['adi'] ?></div>
									<?php $fiyat = $URUNSonuc['tutar'];
									$fiyat_gorunumu = number_format($fiyat, 0, ',', '.'); ?>
									<div class="price"><?php echo $fiyat_gorunumu ?> TL <span class="period">/<?php echo ($URUNSonuc['zmnt'] == 0 ? 'Aylık' : ''); ?> <?php echo ($URUNSonuc['zmnt'] == 2 ? '3 Aylık' : ''); ?> <?php echo ($URUNSonuc['zmnt'] == 1 ? 'Yıllık' : ''); ?></span></div>
									<?php if ($moduller['alan6'] == "1") { ?>
										<a href="hosting-satinal/<?php echo $URUNSonuc['id']; ?>.html" class="btn btn-2 btn-default-gray-fill"><i class="fas fa-shopping-basket" style="font-size: 15px;"></i> <?= @$dil['txt76']; ?></a>
									<?php } ?>
								</div>
								<ul class="list-info bg-pink p-3 text-left">
									<?php $parcala = preg_split('/,/', $URUNSonuc['ozellikler'], null, PREG_SPLIT_NO_EMPTY);
									foreach ($parcala as $ozellik) { ?>
										<li><span class="fas fa-check-circle mr-2"></span> <?php echo $ozellik; ?></li>
									<?php } ?>
								</ul>
							</div>
						</div>
					<?php } ?>
				<?php } else { ?>
					<div class="alert alert-warning">
						<div class="row">

							<div class="col-md-1"><i class="fa fa-info-circle" style="font-size: 70px;text-align: center;display: block;margin-top: 5px;"></i></div>
							<div class="col-md-11">
								<div class="balanceinfo">
									<h5><strong><?= @$dil['txt160']; ?></strong></h5>
									<p style="font-weight:400;margin-bottom:0px;paddin-bottom:0px;">
										<?= @$dil['txt161']; ?>
									</p>
								</div>
							</div>

						</div>
					</div>
				<?php } ?>
				<div class="gap"></div>
				<div class="gap"></div>
				<div class="gap"></div>
			</div>
		</div>
		<div class="paginationn-box-row">
			<p><?php echo $total; ?> <?= @$dil['txt162']; ?> <?php echo $page; ?> - <?php echo $limitayar['limit_sayfahosting']; ?> <?= @$dil['txt163']; ?></p>
			<ul class="paginationn">
				<?php
				if ($limitayar['limit_sayfahosting'] < $total) {
					$showing = 3;
					if ($page > 1) { ?>
						<?php $previous = $page - 1; ?>
						<li><a href="hostings/<?php echo $Sonuc['seo']; ?>/<?php echo $previous; ?>.html"><i class="fa fa-angle-double-left"></i></a></li>
						<?php }
					for ($i = $page - $showing; $i < $page + $showing + 1; $i++) {
						if ($i > 0 and $i <= $page_count) {
							if ($i == $page) { ?>
								<li class="active"><a href="javascript:void(0);"><?php echo $i; ?></a></li>
							<?php } else { ?>
								<li><a href="hostings/<?php echo $Sonuc['seo']; ?>/<?php echo $i; ?>.html"><?php echo $i; ?></a></li>
						<?php }
						}
					}
					if ($page != $page_count) { ?>
						<?php $next = $page + 1; ?>
						<li><a href="hostings/<?php echo $Sonuc['seo']; ?>/<?php echo $next; ?>.html"><i class="fa fa-angle-double-right"></i></a></li>
				<?php }
				} ?>
			</ul>
		</div>
	</div>
</div>
<!-- ***** FEATURES ***** -->
<section id="features" class="history-section sec-normal">
	<div class="container">
		<div class="randomline">
			<div class="bigline"></div>
			<div class="smallline"></div>
		</div>
		<div class="sec-main sec-bg1">
			<div class="row">
				<div class="col-md-12">
					<div class="info-content">
						<?php echo $Sonuc['aciklama'] ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<?php if (isset($_GET['durum'])) : ?>
	<script>
		swal({
			type: 'success',
			title: 'Başarılı',
			text: 'Hosting Sepetinize Eklendi',
			confirmButtonText: 'Tamam',
			timer: 5000
		})
	</script>
<?php endif; ?>