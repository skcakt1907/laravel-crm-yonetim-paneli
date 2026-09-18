<?php define("GUVENLIK", true); ?>
<!DOCTYPE html>
<html lang="tr">

<head>
	<?php $uri = url;
	if ((substr($_SERVER['HTTP_HOST'], 0, 4) === 'www.') && (substr($uri, 0, 4) !== 'www.')) {
		$uri = ltrim(url, "https://");
		$uri = ltrim($uri, "http://");
		$uri = "http://www." . $uri;
	} ?>
	<?php $protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"], 0, 5)) == 'https' ? 'https' : 'http';
	$protocol = isset($_SERVER["HTTPS"]) ? 'https://' : 'http://';
	$url = $protocol . $_SERVER["HTTP_HOST"] . dirname($_SERVER['PHP_SELF']);
	$sayfalink = $protocol . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
	?>
	<?php $oturum = $db->prepare("SELECT * FROM uyeler WHERE id = ? AND durum = ?");
	$oturum->execute(array($_SESSION['site_uyeid'], "1"));
	if ($oturum->rowCount()) {
		$Bilgilerim = $oturum->fetch(PDO::FETCH_ASSOC);
		$kredi = $db->query("SELECT * FROM krediler WHERE uyeid = '{$Bilgilerim['id']}'")->fetch(PDO::FETCH_ASSOC);
		$varsayilanadres = $db->query("SELECT * FROM adresler WHERE uyeid = '{$Bilgilerim['id']}' AND varsayilan = '1'")->fetch(PDO::FETCH_ASSOC);
	} ?>
	<?php require_once('pages/sayac.php'); ?>
	<base href="<?php echo $url; ?><?php echo (altklasor == "1" ? '/' : ''); ?>">
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo $title; ?></title>
	<meta name="description" content="<?php echo $description; ?>" />
	<meta name="keywords" content="<?php echo $keywords; ?>" />
	<!-- Facebook Metadata Start -->
	<meta property="og:image:height" content="300" />
	<meta property="og:image:width" content="573" />
	<meta property="og:title" content="<?php echo $title; ?>" />
	<meta property="og:description" content="<?php echo $description; ?>" />
	<meta property="og:url" content="<?php echo $sayfalink; ?>" />
	<meta property="og:image" content="<?php echo $url;?><?php echo(altklasor == "1" ? '/' : '');?><?php echo $paylasim;?>" />
	<?php echo dogrulama; ?>
	<link rel="shortcut icon" href="<?php echo tema; ?>/uploads/favicon/<?php echo fav; ?>">
	<!-- Fonts -->
	<link href="<?php echo tema; ?>/fonts/cloudicon/cloudicon.css" rel="stylesheet">
	<link href="<?php echo tema; ?>/fonts/fontawesome/css/all.css" rel="stylesheet">
	<link rel='stylesheet' href="<?php echo tema; ?>/css/font-awesome.min.css" type="text/css" media="all" />
	<link href="<?php echo tema; ?>/fonts/opensans/opensans.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Fira+Sans:100,200,300,400,500,600,700,800,900" rel="stylesheet">
	<!-- CSS styles -->
	<link href="<?php echo tema; ?>/css/bootstrap.min.css" rel="stylesheet">
	<link href="<?php echo tema; ?>/css/owl.carousel.css" rel="stylesheet">
	<link href="<?php echo tema; ?>/css/idangerous.swiper.css" rel="stylesheet">
	<link href="<?php echo tema; ?>/css/animate.min.css" rel="stylesheet">
	<link href="<?php echo tema; ?>/css/gdpr-cookie.css" rel="stylesheet">
	<link rel="stylesheet" href="https://crm.ornek.com/tema/webajans/css/bootstrap-popover-x.min.css">
	<link href="<?php echo tema; ?>/css/slick.css" rel="stylesheet">
	<link href="<?php echo tema; ?>/css/filter.css" rel="stylesheet">
	<link href="<?php echo tema; ?>/css/sweetalert2.min.css" rel="stylesheet">
	<link href="<?php echo tema; ?>/css/mixitup.css" rel="stylesheet">
	<link href="<?php echo tema; ?>/css/style.css" rel="stylesheet">
	<link href="<?php echo tema; ?>/css/update.css" rel="stylesheet" />

	<link rel="stylesheet" href="<?php echo tema; ?>/css/remodal.css">
	<link rel="stylesheet" href="<?php echo tema; ?>/css/remodal-default-theme.css">
	<link href="<?php echo tema; ?>/js/detaygaleri/pgwslider.css" rel="stylesheet">
	<!-- Custom color styles -->
	<link href="<?php echo tema; ?>/css/colors/color.php" rel="stylesheet" title="color" />
	<script src="<?php echo tema; ?>/js/jquery.min.js"></script>
	<link rel="stylesheet" href="<?php echo tema; ?>/js/datatable/dataTables.bootstrap4.min.css">
	<script src="<?php echo tema; ?>/js/datatable/jquery.dataTables.min.js"></script>
	<script src="<?php echo tema; ?>/js/datatable/dataTables.bootstrap4.min.js"></script>
	<script src="<?php echo tema; ?>/js/sweetalert2.all.min.js"></script>
	<script src="<?php echo tema; ?>/js/sweetalert2.min.js"></script>
	<script src="<?php echo tema; ?>/js/lib/i18next.min.js"></script>
	<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-58b57282384b6d76"></script>
	<?php echo analytics; ?>
	<?php echo canli_destek; ?>
	<?php echo whatsapp; ?>
	<script src="<?php echo tema; ?>/js/remodal.js"></script>
	<script src="<?php echo tema; ?>/js/jquery.popconfirm.js" type="text/javascript"></script>
	<script src="<?php echo tema; ?>/js/maskedinput/jquery.maskedinput.js" type="text/javascript"></script>
	<script type="text/javascript">
		$(function() {
			$.mask.definitions['~'] = "[+-]";
			$(".date").mask("99/99/9999");
			$(".telefonmask").mask("0(999) 999 99 99");
			$(".tc").mask("99999999999");
			$("#yas").mask("99");
			$("#phoneExt").mask("(999) 999-9999? x99999");
			$("#iphone").mask("+33 999 999 999");
			$("#tin").mask("99-9999999");
			$("#ssn").mask("999-99-9999");
			$("#product").mask("a*-999-a999", {
				placeholder: " "
			});
			$("#eyescript").mask("~9.99 ~9.99 999");
			$("#iban").mask("TR999999999999999999999999");
			$("#pct").mask("99%");
			$("#phoneAutoclearFalse").mask("(999) 999-9999", {
				autoclear: false,
				completed: function() {
					alert("completed autoclear!");
				}
			});
			$("#phoneExtAutoclearFalse").mask("(999) 999-9999? x99999", {
				autoclear: false
			});
		});
	</script>
	<script src="https://www.google.com/recaptcha/api.js?render=6Ldp3QQfAAAAAKQjeDhHNq-9a5d2BWKhOQ8Xcvi2"></script>
	<script>
		function onClick(e) {
			e.preventDefault();
			grecaptcha.ready(function() {
				grecaptcha.execute('6Ldp3QQfAAAAAKQjeDhHNq-9a5d2BWKhOQ8Xcvi2', {
					action: 'submit'
				}).then(function(token) {
					// Add your logic to submit to your backend server here.
				});
			});
		}
	</script>
</head>

<body>
	<!-- ***** LOADING PAGE ****** -->
	<div id="spinner-area">
		<div class="spinner">
			<div class="double-bounce1"></div>
			<div class="double-bounce2"></div>
			<div class="spinner-txt"><?= @$dil['txt1']; ?></div>
		</div>
	</div>
	<!-- ***** UPLOADED MENU FROM HEADER.HTML ***** -->
	<header id="header" <?php echo ($gizle == "evet" ? 'style="display:none;"' : '') ?>>
		<!-- ***** NAV MENU ****** -->
		<div class="menu-wrap">
			<?php if ($moduller['alan7'] == "1") { ?>
				<section id="menu1">
					<div class="container">
						<div class="row">
							<div class="col-lg-12">
								<ul class="top-nav">
									<?php if (isset($_SESSION["site_email"])) { ?>
										<li class="primary-action">
											<a onclick="oturum_kapat()" href="javascript:;"><i class="fas fa-power-off"></i>
												<?= @$dil['txt2']; ?> </a>
										</li>
										<li>
											<a class="btn btn-default-yellow-fill giris" href="sepet.html"><i class="fas fa-shopping-basket pr-1"></i> <?= @$dil['txt393']; ?>
												(<?php echo sepet_count ?>)</a>
										</li>
									<?php } else { ?>
										<li class="primary-action">
											<a href="giris.html"><i class="fas fa-user-edit"></i> <?= @$dil['txt3']; ?></a>
										</li>
									<?php } ?>
									<li>
										<a class="btn btn-default-yellow-fill giris" href="hesabim.html"><i class="fas fa-lock pr-1"></i> <?= @$dil['txt4']; ?></a>
									</li>
								</ul>
							</div>
						</div>
					</div>
				</section>
			<?php } else { ?>
				<section id="menu1">
					<div class="container">
						<div class="row">
							<div class="col-lg-12">
								<ul class="top-nav">
									<?php if (facebook) { ?>
										<li class="primary-action ml-1 sosyalbg">
											<a target="_blank" style="color:#3B5998;" href="<?php echo facebook; ?>"><i class="fab fa-facebook-f"></i></a>
										</li>
									<?php } ?>
									<?php if (twitter) { ?>
										<li class="primary-action ml-2 sosyalbg">
											<a target="_blank" style="color:#26CCFF;" href="<?php echo twitter; ?>"><i class="fab fa-twitter"></i></a>
										</li>
									<?php } ?>
									<?php if (instagram) { ?>
										<li class="primary-action ml-2 sosyalbg">
											<a target="_blank" style="color:#DD2A7B;" href="<?php echo instagram; ?>"><i class="fab fa-instagram"></i></a>
										</li>
									<?php } ?>
									<?php if (linkedin) { ?>
										<li class="primary-action ml-2 sosyalbg">
											<a target="_blank" style="color:#0E76A8;" href="<?php echo linkedin; ?>"><i class="fab fa-linkedin-in"></i></a>
										</li>
									<?php } ?>
									<?php if (youtube) { ?>
										<li class="primary-action ml-2 sosyalbg">
											<a target="_blank" style="color:#FF4032;" href="<?php echo youtube; ?>"><i class="fab fa-youtube"></i></a>
										</li>
									<?php } ?>
								</ul>
							</div>
						</div>
					</div>
				</section>
			<?php } ?>
			<div class="nav-menu">
				<div class="container">
					<div class="row">
						<div class="col-3 col-md-3">
							<a href="index.html">
								<img class="svg logo-menu" src="<?php echo tema; ?>/uploads/logo/<?php echo logo; ?>" alt="<?php echo firma_adi; ?>">
							</a>
						</div>
						<nav id="menu" class="col-9 col-md-9">
							<div class="navigation float-right">
								<button class="menu-toggle">
									<span class="icon"></span>
									<span class="icon"></span>
									<span class="icon"></span>
								</button>
								<ul class="main-menu nav navbar-nav navbar-right">
									<?php $MENUSorgu = $db->prepare("SELECT * FROM menu WHERE menu_durum = ? AND menu_ust = ? AND dil = ? ORDER BY menu_sira ASC");
									$MENUSorgu->execute(array("1", "0", $_SESSION['k_dil']));
									$MENUislem = $MENUSorgu->fetchALL(PDO::FETCH_ASSOC); ?>
									<?php foreach ($MENUislem as $MENUSonuc) { ?>
										<?php $altvarmi	= $db->query("SELECT * FROM menu WHERE menu_durum = '1' AND menu_ust = '{$MENUSonuc['id']}' ORDER BY id DESC LIMIT 1")->rowCount(); ?>
										<li class="menu-item menu-item-has-children"><a class="v-stroke" <?php echo ($MENUSonuc['sekme'] == 1 ? 'target="_blank"' : ''); ?> href="<?php echo ($MENUSonuc['menu_url'] == "0" ? $MENUSonuc['link'] : $MENUSonuc['menu_url']); ?>"><?php echo $MENUSonuc['menu_isim']; ?></a>
											<?php $ALTMENUSorgu = $db->prepare("SELECT * FROM menu WHERE menu_durum = ? AND menu_ust = ? AND dil = ? ORDER BY menu_sira ASC");
											$ALTMENUSorgu->execute(array("1", $MENUSonuc['id'], $_SESSION['k_dil']));
											$ALTMENUislem = $ALTMENUSorgu->fetchALL(PDO::FETCH_ASSOC); ?>
											<?php if ($ALTMENUSorgu->rowCount()) { ?>
												<div class="sub-menu menu-large">
													<div class="service-list">
														<?php foreach ($ALTMENUislem as $ALTMENUSonuc) { ?>
															<div class="service">
																<div class="media-body">
																	<a <?php echo ($ALTMENUSonuc['sekme'] == 1 ? 'target="_blank"' : ''); ?> class="menu-item" href="<?php echo ($ALTMENUSonuc['menu_url'] == "0" ? $ALTMENUSonuc['link'] : $ALTMENUSonuc['menu_url']); ?>"><i class="fas fa-angle-right"></i>
																		<?php echo $ALTMENUSonuc['menu_isim']; ?></a>
																</div>
															</div>
														<?php } ?>
													</div>
												</div>
											<?php } ?>
										</li>
									<?php } ?>
								</ul>
							</div>
						</nav>
					</div>
				</div>
			</div>
		</div>
		<!-- ***** NAV MENU MOBILE ****** -->
		<div class="menu-wrap mobile">
			<div class="container">
				<div class="row">
					<div class="col-6">
						<a href="index.html"><img class="svg logo-menu" src="<?php echo tema; ?>/uploads/logo/<?php echo logo; ?>" alt="<?php echo firma_adi; ?>"></a>
					</div>
					<div class="col-6">
						<nav class="nav-menu">
							<button id="nav-toggle" class="menu-toggle">
								<span class="icon"></span>
								<span class="icon"></span>
								<span class="icon"></span>
							</button>

							<ul class="main-menu">
								<?php $MENUSorgu = $db->prepare("SELECT * FROM menu WHERE menu_durum = ? AND menu_ust = ? AND dil = ? ORDER BY menu_sira ASC");
								$MENUSorgu->execute(array("1", "0", $_SESSION['k_dil']));
								$MENUislem = $MENUSorgu->fetchALL(PDO::FETCH_ASSOC); ?>
								<?php foreach ($MENUislem as $MENUSonuc) { ?>
									<?php $altvarmi	= $db->query("SELECT * FROM menu WHERE menu_durum = '1' AND menu_ust = '{$MENUSonuc['id']}' ORDER BY id DESC LIMIT 1")->rowCount(); ?>
									<li class="menu-item <?php echo ($altvarmi > 0 ? 'menu-item-has-children' : ''); ?>"><a <?php echo ($MENUSonuc['sekme'] == 1 ? 'target="_blank"' : ''); ?> href="<?php echo ($MENUSonuc['menu_url'] == "0" ? $MENUSonuc['link'] : $MENUSonuc['menu_url']); ?>"><?php echo $MENUSonuc['menu_isim']; ?></a>
										<?php $ALTMENUSorgu = $db->prepare("SELECT * FROM menu WHERE menu_durum = ? AND menu_ust = ? AND dil = ? ORDER BY menu_sira ASC");
										$ALTMENUSorgu->execute(array("1", $MENUSonuc['id'], $_SESSION['k_dil']));
										$ALTMENUislem = $ALTMENUSorgu->fetchALL(PDO::FETCH_ASSOC); ?>
										<?php if ($ALTMENUSorgu->rowCount()) { ?>
											<ul class="sub-menu">
												<?php foreach ($ALTMENUislem as $ALTMENUSonuc) { ?>
													<li class="menu-item">
														<a <?php echo ($ALTMENUSonuc['sekme'] == 1 ? 'target="_blank"' : ''); ?> href="<?php echo ($ALTMENUSonuc['menu_url'] == "0" ? $ALTMENUSonuc['link'] : $ALTMENUSonuc['menu_url']); ?>"><?php echo $ALTMENUSonuc['menu_isim']; ?></a>
													</li>
												<?php } ?>
											</ul>
										<?php } ?>
									</li>
								<?php } ?>
								<li class="mt-4">
									<a href="tel:<?php echo telefon; ?>">
										<p class="c-grey"><?= @$dil['txt21']; ?> <?php echo telefon; ?></p>
									</a>
									<a href="mailto:<?php echo email; ?>">
										<p class="c-grey"><?= @$dil['txt22']; ?> <?php echo email; ?></p>
									</a>
								</li>
								<?php if ($moduller['alan7'] == "1") { ?>
									<li>
										<a href="hesabim.html">
											<div class="btn btn-default-yellow-fill mt-3"><?= @$dil['txt4']; ?></div>
										</a>
									</li>
								<?php } ?>
							</ul>
						</nav>
					</div>
				</div>
			</div>
		</div>
		<!-- ***** TRANSLATION ****** -->
		<?php $DILSorgu = $db->prepare("SELECT * FROM diller ORDER BY sira ASC");
		$DILSorgu->execute();
		$DILislem 	= $DILSorgu->fetchALL(PDO::FETCH_ASSOC);
		$dilyaz  	= $db->query("SELECT * FROM diller WHERE id = '{$_SESSION['k_dil']}'")->fetch(PDO::FETCH_ASSOC);
		$dilsay		= $db->query("SELECT * FROM  diller")->rowCount(); ?>
		<?php if ($dilsay > 1) { ?>
			<section id="drop-lng" class="btn-group btn-group-toggle toplang" data-toggle="buttons">
				<?php foreach ($DILislem as $DILSonuc) { ?>
					<label data-id="<?= @$DILSonuc['id']; ?>" class="btn btn-secondary mb-2 dildegis">
						<input type="radio" name="options" id="option1" <?php echo ($dilyaz['id'] == $DILSonuc['id'] ? 'checked' : '') ?>> <?php echo $DILSonuc['adi']; ?>
					</label>
				<?php } ?>
			</section>
		<?php } ?>
		<!-- Javascript -->
	</header>
	<!-- Float İcon -->
	<a href="sepet.html" <?php if(sepet_count == 0) : ?> hidden <?php endif; ?> class="float">
		<div class="center"><i class="fa fa-shopping-basket my-float"></i> (<?php echo sepet_count ?>)</div>
	</a>
	<?php
	if (isset($_GET['sayfa'])) {
		$s = $_GET['sayfa'];
		switch ($s) {

			case 'anasayfa';
				require_once("pages/anasayfa.php");
				break;

			case 'bayilik';
				require_once("pages/bayilik.php");
				break;

			case 'sayfalar';
				require_once("pages/sayfalar.php");
				break;

			case 'hizmet';
				require_once("pages/hizmet.php");
				break;

			case 'blog';
				require_once("pages/blog.php");
				break;

			case 'blog-detay';
				require_once("pages/blog_detay.php");
				break;

			case 'paketler';
				require_once("pages/paketler.php");
				break;

			case 'domain-tescil';
				require_once("pages/domain_tescil.php");
				break;

			case 'hosting';
				require_once("pages/hosting.php");
				break;

			case 'paytr-iframe';
				require_once("pages/paytr-iframe.php");
				break;

			case 'referans-detay';
				require_once("pages/referans_detay.php");
				break;

			case 'sepet';
				require_once("pages/sepet.php");
				break;

			case 'sepet-satinal';
				require_once("pages/sepet-satinal.php");
				break;

			case 'referanslar';
				require_once("pages/referanslar.php");
				break;

			case 'hesap-numaralarimiz';
				require_once("pages/hesap_numaralarimiz.php");
				break;

			case 'detay';
				require_once("pages/detay.php");
				break;

			case 'giris';
				require_once("pages/giris.php");
				break;

			case 'hesabim';
				require_once("pages/hesabim.php");
				break;

			case 'dosyalarim';
				require_once("pages/dosyalarim.php");
				break;

			case 'destek-taleplerim';
				require_once("pages/destek_taleplerim.php");
				break;

			case 'destek-talebi-olustur';
				require_once("pages/destek_talebi_olustur.php");
				break;

			case 'destek-detay';
				require_once("pages/destek_detay.php");
				break;

			case 'bilgilerim';
				require_once("pages/bilgilerim.php");
				break;

			case 'hosting-satinal';
				require_once("pages/hosting_satinal.php");
				break;

			case 'web-paket-satinal';
				require_once("pages/web_paket_satinal.php");
				break;

			case 'alanadi-satinal';
				require_once("pages/alanadi_satinal.php");
				break;

			case 'hostinglerim';
				require_once("pages/hostinglerim.php");
				break;

			case 'faturalarim';
				require_once("pages/faturalarim.php");
				break;

			case 'web_paketlerim';
				require_once("pages/web_paketlerim.php");
				break;

			case 'alan_adlarim';
				require_once("pages/alan_adlarim.php");
				break;

			case 'hizmetlerim';
				require_once("pages/hizmetlerim.php");
				break;


			case 'sozlesmelerim';
				require_once("pages/sozlesmelerim.php");
				break;


			case 'raporlarim';
				require_once("pages/raporlarim.php");
				break;


			case 'efaturalarim';
				require_once("pages/efaturalarim.php");
				break;


			case 'referanslarim';
				require_once("pages/referanslarim.php");
				break;


			case 'tekliflerim';
				require_once("pages/tekliflerim.php");
				break;

			case 'siparis-sonuc';
				require_once("pages/siparis_sonuc.php");
				break;

			case 'bakiyem';
				require_once("pages/bakiyem.php");
				break;

			case 'fatura-detay';
				require_once("pages/fatura_detay.php");
				break;

			case 'odeme-bildirim-formu';
				require_once("pages/odeme_bildirim_formu.php");
				break;

			case 'iletisim';
				require_once("pages/iletisim.php");
				break;

			case 'favorilerim';
				require_once("pages/favorilerim.php");
				break;

			case '404';
				require_once("pages/404.php");
				break;

			default:
				require_once("pages/anasayfa.php");
		}
	} else {
		require_once("pages/anasayfa.php");
	}
	?>
	<!-- ***** UPLOADED FOOTER FROM FOOTER.HTML ***** -->
	<footer class="footer" <?php echo ($gizle == "evet" ? 'style="display:none;"' : '') ?>>

		<div class="container">
			<div class="footer-top">
				<div class="row">
					<?php $FMENUSorgu = $db->prepare("SELECT * FROM footermenu WHERE menu_durum = ? AND menu_ust = ? AND dil = ? ORDER BY menu_sira ASC");
					$FMENUSorgu->execute(array("1", "0", $_SESSION['k_dil']));
					$FMENUislem = $FMENUSorgu->fetchALL(PDO::FETCH_ASSOC); ?>
					<?php foreach ($FMENUislem as $FMENUSonuc) { ?>
						<div class="col-sm-6 col-md-3">
							<div class="heading"><?php echo $FMENUSonuc['menu_isim']; ?></div>
							<?php $FALTMENUSorgu = $db->prepare("SELECT * FROM footermenu WHERE menu_durum = ? AND menu_ust = ? AND dil = ? ORDER BY menu_sira ASC");
							$FALTMENUSorgu->execute(array("1", $FMENUSonuc['id'], $_SESSION['k_dil']));
							$FALTMENUislem = $FALTMENUSorgu->fetchALL(PDO::FETCH_ASSOC); ?>
							<?php if ($FALTMENUSorgu->rowCount()) { ?>
								<ul class="footer-menu">
									<?php foreach ($FALTMENUislem as $FALTMENUSonuc) { ?>
										<li class="menu-item">
											<a <?php echo ($FALTMENUSonuc['sekme'] == 1 ? 'target="_blank"' : ''); ?> href="<?php echo ($FALTMENUSonuc['menu_url'] == "0" ? $FALTMENUSonuc['link'] : $FALTMENUSonuc['menu_url']); ?>"><?php echo $FALTMENUSonuc['menu_isim']; ?></a>
										</li>
									<?php } ?>
								</ul>
							<?php } ?>
						</div>
					<?php } ?>
					<div class="col-sm-6 col-md-3">

						<div class="copyrigh">
							<?php if (adres) { ?><p><i class="fa fa-map-marker"></i> <?php echo adres; ?> </p><?php } ?>
							<?php if (telefon) { ?><p><a href="tel:<?php echo telefon; ?>"><i class="fa fa-phone"></i>
										<?php echo telefon; ?></a></p><?php } ?>
							<?php if (fax) { ?><p><a href="tel:<?php echo fax; ?>" <i class="fa fa-phone"></i>
										<?php echo fax; ?></a></p>
								<?php if (email) { ?><p><a href="mailto:<?php echo email; ?>"><i class="fa fa-envelope-o"></i> <?php echo email; ?></a></p><?php } ?>
							<?php } ?>
						</div>
						<div class="soc-icons">
							<?php if (facebook) { ?>
								<a target="_blank" href="<?php echo facebook; ?>"><i style="color:#3B5998;" class="fab fa-facebook-f"></i></a>
							<?php } ?>
							<?php if (twitter) { ?>
								<a target="_blank" href="<?php echo twitter; ?>"><i style="color:#26CCFF;" class="fab fa-twitter"></i></a>
							<?php } ?>
							<?php if (instagram) { ?>
								<a target="_blank" href="<?php echo instagram; ?>"><i style="color:#DD2A7B;" class="fab fa-instagram"></i></a>
							<?php } ?>
							<?php if (linkedin) { ?>
								<a target="_blank" href="<?php echo linkedin; ?>"><i style="color:#0E76A8;" class="fab fa-linkedin-in"></i></a>
							<?php } ?>
							<?php if (youtube) { ?>
								<a target="_blank" href="<?php echo youtube; ?>"><i style="color:#FF4032;" class="fab fa-youtube"></i></a>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="col-md-6 offset-md-3 text-center pt-4">
			<a href="https://play.google.com/store/apps/details?id=com.rzmobile.isortagim&hl=tr&gl=US">
				<img border="0" src="v8/playstore.png" width="166" height="60"></a>&nbsp;&nbsp;<a href="https://apps.apple.com/us/app/dn-i-%C5%9F-orta%C4%9F%C4%B1m/id1603113206"><img border="0" src="v8/appstore.png" width="166" height="60"></a></p>

			<p><?php echo copyright; ?></p>
		</div>
		</form>
		</div>
		</div>
		</div>
		<div class="footer-bottom">
			<div class="container">
				<div class="row">
					<div class="col-lg-6">
						<?php $DILSorgu = $db->prepare("SELECT * FROM diller ORDER BY sira ASC");
						$DILSorgu->execute();
						$DILislem 	= $DILSorgu->fetchALL(PDO::FETCH_ASSOC);
						$dilyaz  	= $db->query("SELECT * FROM diller WHERE id = '{$_SESSION['k_dil']}'")->fetch(PDO::FETCH_ASSOC);
						$dilsay		= $db->query("SELECT * FROM  diller")->rowCount(); ?>
						<?php if ($dilsay > 1) { ?>
							<ul class="footer-menu">
								<li id="drop-lng" class="btn-group btn-group-toggle" data-toggle="buttons">
									<?php foreach ($DILislem as $DILSonuc) { ?>
										<label data-id="<?= @$DILSonuc['id']; ?>" class="btn btn-secondary mb-2 dildegis">
											<input type="radio" name="options" id="option1" <?php echo ($dilyaz['id'] == $DILSonuc['id'] ? 'checked' : '') ?>>
											<?php echo $DILSonuc['adi']; ?>
										</label>
									<?php } ?>
								</li>
							</ul>
						<?php } ?>
					</div>
					<div class="col-lg-6">
						<ul class="payment-list">
							<li>
								<p><?= @$dil['txt367']; ?></p>
							</li>
							<li><i class="fab fa-cc-paraf"></i></li>
							<li><i class="fab fa-cc-visa"></i></li>
							<li><i class="fab fa-cc-mastercard"></i></li>
							<li><i class="fab fa-cc-apple-pay"></i></li>
							<li><i class="fab fa-cc-discover"></i></li>
							<li><i class="fab fa-cc-amazon-pay"></i></li>

						</ul>
					</div>
				</div>
			</div>
		</div>
	</footer>

	<!-- ***** BUTTON GO TOP ***** -->
	<a href="#0" class="cd-top"> <i class="fas fa-angle-up"></i> </a>
	<!-- Javascript -->
	<script src="<?php echo tema; ?>/js/typed.js"></script>
	<script defer src="<?php echo tema; ?>/js/popper.min.js"></script>
	<script defer src="<?php echo tema; ?>/js/bootstrap.min.js"></script>
	<script defer src="<?php echo tema; ?>/js/idangerous.swiper.min.js"></script>
	<script defer src="<?php echo tema; ?>/js/jquery.countdown.js"></script>
	<script defer src="<?php echo tema; ?>/js/jquery.magnific-popup.min.js"></script>
	<script defer src="<?php echo tema; ?>/js/slick.min.js"></script>
	<script src="https://crm.ornek.com/tema/webajans/js/bootstrap-popover-x.min.js"></script>
	<script defer src="<?php echo tema; ?>/js/owl.carousel.min.js"></script>
	<script defer src="<?php echo tema; ?>/js/isotope.min.js"></script>
	<script src="<?php echo tema; ?>/js/wow.min.js"></script>
	<script defer src="<?php echo tema; ?>/js/filter.js"></script>
	<script defer src="<?php echo tema; ?>/js/sidebar.js"></script>
	<script defer src="<?php echo tema; ?>/js/detaygaleri/pgwslider.js"></script>
	<script>
		new WOW().init();
	</script>
	<script defer src="<?php echo tema; ?>/js/scripts.js"></script>
	<script>
		function oturum_kapat() {
			swal({
				title: "<?= @$dil['txt7']; ?>",
				text: "<?= @$dil['txt8']; ?>",
				type: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				cancelButtonText: "<?= @$dil['txt9']; ?>",
				confirmButtonText: "<?= @$dil['txt10']; ?>"
			}).then((result) => {
				if (result.value) {
					swal({
						title: "<?= @$dil['txt11']; ?>",
						text: "<?= @$dil['txt12']; ?>",
						type: "success",
						icon: 'success',
						timer: 5000
					}).then(function() {
						window.location.href = '_class/site_islem.php?cikis=ok';
					});
				}
			});
		}
	</script>
	<script>
		$(".dildegis").click(function() {
			var dilID = $(this).data("id");
			$.ajax({
					url: 'dildegis.php',
					dataType: 'JSON',
					data: {
						id: dilID
					},
				})
				.done(function(msg) {
					if (msg.hata) {
						alert("Bir hata oluştu");
					} else {
						window.location = "index.html";
					}
				})
				.fail(function(err) {
					console.log(err);
				});
		});
	</script>

	<?php
	if ($_SESSION['satinal'] == 'bos') {
		echo "
		<script>
		swal({
			type: 'warning',
			title: '" . @$dil['txt13'] . "',
			text: '" . @$dil['txt14'] . "',
			confirmButtonText: '" . @$dil['txt15'] . "',
			timer: 5000
		})
		</script>";
		unset($_SESSION['satinal']);
	}
	if ($_SESSION['odeme_havale'] == 'bos') {
		echo "
		<script>
		swal({
			type: 'warning',
			title: '" . @$dil['txt13'] . "',
			text: '" . @$dil['txt14'] . "',
			confirmButtonText: '" . @$dil['txt15'] . "',
			timer: 5000
		})
		</script>";
		unset($_SESSION['odeme_havale']);
	}
	if ($_SESSION['odeme_havale'] == 'no') {
		echo "
		<script>
		swal({
			type: 'error',
			title: '" . @$dil['txt16'] . "',
			text: '" . @$dil['txt17'] . "',
			confirmButtonText: '" . @$dil['txt15'] . "',
			timer: 5000
		})
		</script>";
		unset($_SESSION['odeme_havale']);
	}
	if ($_SESSION['odeme_kredi'] == 'yetersiz-kredi') {
		echo "
		<script>
		swal({
			type: 'warning',
			title: '" . @$dil['txt13'] . "',
			text: '" . @$dil['txt18'] . "',
			confirmButtonText: '" . @$dil['txt15'] . "',
			timer: 5000
		})
		</script>";
		unset($_SESSION['odeme_kredi']);
	}
	if ($_SESSION['odeme_kredi'] == 'no') {
		echo "
		<script>
		swal({
			type: 'error',
			title: '" . @$dil['txt16'] . "',
			text: '" . @$dil['txt17'] . "',
			confirmButtonText: '" . @$dil['txt15'] . "',
			timer: 5000
		})
		</script>";
		unset($_SESSION['odeme_kredi']);
	}
	if ($_SESSION['ebultenbtn'] == 'yes') {
		echo "
		<script>
		swal({
			type: 'success',
			title: '" . @$dil['txt11'] . "',
			text: '" . @$dil['txt19'] . "',
			confirmButtonText: '" . @$dil['txt15'] . "',
			timer: 5000
		})
		</script>";
		unset($_SESSION['ebultenbtn']);
	}
	if ($_SESSION['ebultenbtn'] == 'no') {
		echo "
		<script>
		swal({
			type: 'error',
			title: '" . @$dil['txt16'] . "',
			text: '" . @$dil['txt17'] . "',
			confirmButtonText: '" . @$dil['txt15'] . "',
			timer: 5000
		})
		</script>";
		unset($_SESSION['ebultenbtn']);
	}
	if ($_SESSION['ebultenbtn'] == 'bos') {
		echo "
		<script>
		swal({
			type: 'warning',
			title: '" . @$dil['txt13'] . "',
			text: '" . @$dil['txt14'] . "',
			confirmButtonText: '" . @$dil['txt15'] . "',
			timer: 5000
		})
		</script>";
		unset($_SESSION['ebultenbtn']);
	}
	if ($_SESSION['sitedemo'] == 'no') {
		echo "
		<script>
		swal({
			type: 'warning',
			title: '" . @$dil['txt13'] . "',
			text: '" . @$dil['txt20'] . "',
			confirmButtonText: '" . @$dil['txt15'] . "',
			timer: 5000
		})
		</script>";
		unset($_SESSION['sitedemo']);
	}
	?>

</body>

</html>