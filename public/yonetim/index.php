<?php define("GUVENLIK",true);?>
<?php
session_start();
ob_start();

require_once('../_class/baglan.php');
require_once('../_class/fonksiyon.php');
require_once('../_class/class.upload.php');
require_once('../_class/yonetim_seo.php');
require_once('../language/admin_dil.php');
?>
<?php $protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,5))=='https'?'https':'http';
$protocol = isset($_SERVER["HTTPS"]) ? 'https://' : 'http://';
$url=$protocol.$_SERVER["HTTP_HOST"].dirname($_SERVER['PHP_SELF']);
$sayfalink = $protocol.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
?>
<?php
$oturumkontrol = $db->prepare("SELECT * FROM kullanici WHERE id = ?");
$oturumkontrol->execute(array(@$_SESSION['Yonetim_Id']));
if($oturumkontrol->rowCount())
{
    $Bilgilerim = $oturumkontrol->fetch(PDO::FETCH_ASSOC);
}
else
{
    unset($_SESSION['Yonetim_Id']);
    header("Location:".$url."/giris.html");
    exit;
}
?>
<?php
if (isset($_GET['dil']) && is_numeric($_GET['dil']))
{
    $_SESSION['admin_dil'] = @$_GET['dil'];
}
if(!isset($_SESSION['admin_dil']))
{
    $mevcutDil  = $db->query("SELECT * FROM diller WHERE anadil = 1")->fetch(PDO::FETCH_ASSOC);
    $_SESSION['admin_dil'] = @$mevcutDil['id'];
}
else
{
    $mevcutDil = $db->query("SELECT * FROM diller WHERE id = {$_SESSION['admin_dil']}");
    $mevcutDil = $mevcutDil->fetch(PDO::FETCH_ASSOC);
    $_SESSION['admin_dil'] = @$mevcutDil['id'];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <base href="<?php echo $url;?>/yonetim">
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo $title;?></title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/iconfonts/mdi/font/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.addons.css">
    <link href="https://fonts.googleapis.com/css?family=Fira+Sans:100,200,300,400,500,600,700,800,900" rel="stylesheet">
    <!-- endinject -->
    <link rel="stylesheet" href="vendors/iconfonts/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="vendors/iconfonts/simple-line-icon/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/iconfonts/font-awesome/css/font-awesome.min.css" />
    <link rel="stylesheet" href="vendors/iconfonts/flag-icon-css/css/flag-icon.min.css" />

    <link rel="stylesheet" href="vendors/lightgallery/css/lightgallery.css">
    <link rel="stylesheet" href="vendors/summernote/dist/summernote-bs4.css">
    <!-- inject:css -->
    <link rel="stylesheet" href="css/vertical-layout-light/style.css">
    <!-- endinject -->
    <link rel="shortcut icon" href="images/favicon.png" />

    <!-- codemirror css -->
    <link href="vendors/codemirror/lib/codemirror.css" rel="stylesheet" type="text/css" />
    <link href="vendors/codemirror/theme/neat.css" rel="stylesheet" type="text/css" />
    <link href="vendors/codemirror/theme/ambiance.css" rel="stylesheet" type="text/css" />
    <link href="vendors/codemirror/theme/material.css" rel="stylesheet" type="text/css" />
    <link href="vendors/codemirror/theme/neo.css" rel="stylesheet" type="text/css" />
	<link rel="stylesheet" href="https://crm.ornek.com/tema/webajans/css/bootstrap-popover-x.min.css">

    <!--Perfect-Scrollbar css-->
    <link rel="stylesheet" href="vendors/css/perfect-scrollbar.min.css">
    <link rel="stylesheet" href="vendors/css/jquery.mCustomScrollbar.css">

    <!--Multi Select css-->
    <link rel="stylesheet" href="vendors/multiselect/jquery.multiselect.css">


    <!-- plugins:js -->
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <script src="vendors/js/vendor.bundle.addons.js"></script>
    <!-- endinject -->
    <script src="vendors/lightgallery/js/lightgallery-all.min.js"></script>
    <script src="vendors/tinymce/tinymce.min.js"></script>
    <script src="vendors/tinymce/themes/silver/theme.js"></script>
    <!-- codemirror js -->
    <script src="vendors/codemirror/lib/codemirror.js" type="text/javascript"></script>
    <script src="vendors/codemirror/addon/edit/matchbrackets.js" type="text/javascript"></script>
    <script src="vendors/codemirror/mode/htmlmixed/htmlmixed.js" type="text/javascript"></script>
    <script src="vendors/codemirror/mode/xml/xml.js" type="text/javascript"></script>
    <script src="vendors/codemirror/mode/javascript/javascript.js" type="text/javascript"></script>
    <script src="vendors/codemirror/mode/css/css.js" type="text/javascript"></script>
    <script src="vendors/codemirror/mode/clike/clike.js" type="text/javascript"></script>
    <script src="vendors/codemirror/mode/php/php.js" type="text/javascript"></script>
</head>
<body>
<div class="container-scroller">

    <div class="theme-setting-wrapper">
        <div id="settings-trigger"><i class="flag-icon <?=@$mevcutDil['bayrak'];?>"></i></div>
        <div id="theme-settings" class="settings-panel">
            <i class="settings-close mdi mdi-close"></i>
            <p class="settings-heading"><?=@$admindil['txt101'];?></p>
            <?php $DILSorgu = $db->prepare("SELECT * FROM diller ORDER BY sira ASC");
            $DILSorgu->execute();
            $DILislem = $DILSorgu->fetchALL(PDO::FETCH_ASSOC);?>
            <?php foreach ( $DILislem as $DILSonuc ){?>
                <div class="sidebar-bg-options <?php echo($mevcutDil['id'] == $DILSonuc['id'] ? 'selected' : '' );?>">
                    <a href="index.html?dil=<?=$DILSonuc['id'];?>"><div class="flag-icon <?=$DILSonuc['bayrak'];?> mr-3"></div><?=@$DILSonuc['adi'];?></a>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- partial:partials/_navbar.html -->
    <nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
        <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
            <a class="navbar-brand brand-logo" href="index.html"><?=@$admindil['txt100'];?></a>
            <a class="navbar-brand brand-logo-mini" href="index.html"><img src="images/logo-mini.svg" alt="logo"/></a>
        </div>
        <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
            <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
                <span class="mdi mdi-apps"></span>
            </button>
            <ul class="navbar-nav mr-lg-2">
                <li class="nav-item nav-search d-none d-lg-block">
                    <a href="../index.html" target="_blank" class="btn btn-inverse-info btn-sm"><i class="mdi mdi-home-outline font-13"></i> <?=@$admindil['txt102'];?></a>
                </li>
            </ul>
            <ul class="navbar-nav navbar-nav-right">
                <li class="nav-item dropdown">
                    <a class="nav-link count-indicator dropdown-toggle d-flex justify-content-center align-items-center" id="notificationDropdown" href="#" data-toggle="dropdown">
                        <i class="mdi mdi-email-outline mx-0"></i>
                        <span class="count count-email"></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list pt-0" aria-labelledby="messageDropdown">
                        <p class="mb-0 font-weight-normal float-left dropdown-header bg-dark text-white w-100"><?=@$admindil['txt103'];?></p>
                        <?php
                        $bgntarih	= TvERtXpE3w_tr_tarih('Y-m-d');
                        $buguntarih = strtotime($bgntarih);
                        $Sorgu = $db->prepare("SELECT * FROM mesajlar WHERE buguntarih = ? ORDER BY id ASC");
                        $Sorgu->execute(array($buguntarih));
                        $islem = $Sorgu->fetchALL(PDO::FETCH_ASSOC);?>
                        <?php if($Sorgu->rowCount() != "0"){?>
                            <?php foreach ( $islem as $Sonuc ){?>
                                <a class="dropdown-item preview-item">
                                    <div class="preview-item-content flex-grow">
                                        <h6 class="preview-subject ellipsis font-weight-normal"><?php echo $Sonuc['isim']?>
                                        </h6>
                                        <p class="font-weight-light small-text text-muted mb-0">
                                            <?php echo $Sonuc['konu']?>
                                        </p>
                                    </div>
                                </a>
                            <?php }?>
                        <?php }else{?>
                            <p class="text-center pt-5 text-muted"><?=@$admindil['txt104'];?></p>
                        <?php }?>
                    </div>
                </li>
                <li class="nav-item nav-profile dropdown">
                    <a class="nav-link dropdown-toggle" href="yonetici-duzenle/<?php echo $Bilgilerim['id'];?>.html" data-toggle="dropdown" id="profileDropdown">
                        <?php if($Bilgilerim['resim'] != ""){?>
                            <img src="images/users/<?php echo $Bilgilerim['resim'];?>" alt="<?php echo $Bilgilerim['isim'];?>">
                        <?php }else{?>
                            <img src="images/users/avatar.jpg" alt="<?php echo $Bilgilerim['isim'];?>">
                        <?php }?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="profileDropdown">
                        <a href="yonetici-duzenle/<?php echo $Bilgilerim['id'];?>.html" class="dropdown-item">
                            <i class="icon-user text-primary"></i>
                            <?=@$admindil['txt105'];?>
                        </a>
                        <a href="genel-ayarlar.html" class="dropdown-item">
                            <i class="icon-settings text-primary"></i>
                            <?=@$admindil['txt106'];?>
                        </a>
                        <a href="../_class/yonetim_islem.php?cikis=ok" class="dropdown-item">
                            <i class="icon-power text-primary"></i>
                            <?=@$admindil['txt107'];?>
                        </a>
                    </div>
                </li>
            </ul>
            <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
                <span class="mdi mdi-menu"></span>
            </button>
        </div>
    </nav>
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
        <!-- partial:partials/_sidebar.html -->
        <nav class="sidebar sidebar-offcanvas" id="sidebar">
            <ul class="nav">
                <li class="nav-item sidebar-category mt-4">
                    <span style="margin-left: -10px;"><?=@$admindil['txt108'];?></span>
                </li>
                <li class="nav-item <?php echo $anasayfa; ?>">
                    <a class="nav-link" href="index.html">
                        <i class="icon-home  menu-icon"></i>
                        <span class="menu-title"><?=@$admindil['txt1'];?></span>
                    </a>
                </li>

                <li class="nav-item <?php echo $genelayarlar; ?> <?php echo $apiayarlari; ?> <?php echo $iletisimayarlari; ?> <?php echo $sitebakimmodu; ?> <?php echo $resimoptimize; ?> <?php echo $limitayarlari; ?> <?php echo $modulayarlari; ?> <?php echo $sosyalmedyaayarlari; ?> <?php echo $mailayarlari; ?> <?php echo $smsayarlari; ?> <?php echo $arkaplanayarlari; ?>">
                    <a class="nav-link" data-toggle="collapse" href="#site-yonetimi" aria-expanded="false" aria-controls="site-yonetimi">
                        <i class="icon-settings menu-icon"></i>
                        <span class="menu-title"><?=@$admindil['txt2'];?></span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $ayarlarshow;?>" id="site-yonetimi">
                        <ul class="nav flex-column sub-menu">
                            <li class="nav-item <?php echo $genelayarlar; ?>">
                                <a class="nav-link <?php echo $genelayarlar; ?>" href="genel-ayarlar.html"><?=@$admindil['txt3'];?></a>
                            </li>
                            <li class="nav-item <?php echo $apiayarlari; ?>">
                                <a class="nav-link <?php echo $apiayarlari; ?>" href="api-ayarlari.html"><?=@$admindil['txt4'];?></a>
                            </li>
                            <li class="nav-item <?php echo $iletisimayarlari; ?>">
                                <a class="nav-link <?php echo $iletisimayarlari; ?>" href="iletisim-ayarlari.html"><?=@$admindil['txt5'];?></a>
                            </li>
                            <li class="nav-item <?php echo $sosyalmedyaayarlari; ?>">
                                <a class="nav-link <?php echo $sosyalmedyaayarlari; ?>" href="sosyal-medya-ayarlari.html"><?=@$admindil['txt6'];?></a>
                            </li>
                            <li class="nav-item <?php echo $modulayarlari; ?>">
                                <a class="nav-link <?php echo $modulayarlari; ?>" href="modul-ayarlari.html"><?=@$admindil['txt7'];?></a>
                            </li>
                            <li class="nav-item <?php echo $limitayarlari; ?>">
                                <a class="nav-link <?php echo $limitayarlari; ?>" href="limit-ayarlari.html"><?=@$admindil['txt8'];?></a>
                            </li>
                            <li class="nav-item <?php echo $sitebakimmodu; ?>">
                                <a class="nav-link <?php echo $sitebakimmodu; ?>" href="site-bakim-modu.html"><?=@$admindil['txt10'];?></a>
                            </li>
                            <li class="nav-item <?php echo $mailayarlari; ?>">
                                <a class="nav-link <?php echo $mailayarlari; ?>" href="mail-ayarlari.html"><?=@$admindil['txt11'];?></a>
                            </li>
                            <li class="nav-item <?php echo $smsayarlari; ?>">
                                <a class="nav-link <?php echo $smsayarlari; ?>" href="sms-ayarlari.html"><?=@$admindil['txt12'];?></a>
                            </li>
                            <li class="nav-item <?php echo $sanalposlar; ?>">
                                <a class="nav-link <?php echo $sanalposlar; ?>" href="sanal-poslar.html"><?=@$admindil['txt61'];?></a>
                            </li>
                            <li class="nav-item <?php echo $arkaplanayarlari; ?>">
                                <a class="nav-link <?php echo $arkaplanayarlari; ?>" href="arkaplan-ayarlari.html"><?=@$admindil['txt13'];?></a>
                            </li>
                            <li class="nav-item ">
                                <a class="nav-link " href="kupon-listele.html"><?=@$admindil['txt59'];?></a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item <?php echo $dilekle; ?> <?php echo $dillistele; ?>">
                    <a class="nav-link" data-toggle="collapse" href="#dil-yonetimi" aria-expanded="false" aria-controls="dil-yonetimi">
                        <i class="icon-globe menu-icon"></i>
                        <span class="menu-title"><?=@$admindil['txt14'];?></span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $dilshow;?>" id="dil-yonetimi">
                        <ul class="nav flex-column sub-menu">
                            <li class="nav-item <?php echo $dilekle; ?>">
                                <a class="nav-link <?php echo $dilekle; ?>" href="dil-ekle.html"><?=@$admindil['txt17'];?></a>
                            </li>
                            <li class="nav-item <?php echo $dillistele; ?>">
                                <a class="nav-link <?php echo $dillistele; ?>" href="dil-listele.html"><?=@$admindil['txt16'];?></a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item <?php echo $headermenu; ?> <?php echo $footermenu; ?>">
                    <a class="nav-link" data-toggle="collapse" href="#menu-yonetimi" aria-expanded="false" aria-controls="dil-yonetimi">
                        <i class="icon-menu menu-icon"></i>
                        <span class="menu-title"><?=@$admindil['txt18'];?></span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $menushow;?>" id="menu-yonetimi">
                        <ul class="nav flex-column sub-menu">
                            <li class="nav-item <?php echo $headermenu; ?>">
                                <a class="nav-link <?php echo $headermenu; ?>" href="header-menu.html"><?=@$admindil['txt19'];?></a>
                            </li>
                            <li class="nav-item <?php echo $footermenu; ?>">
                                <a class="nav-link <?php echo $footermenu; ?>" href="footer-menu.html"><?=@$admindil['txt21'];?></a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item <?php echo $rehberim; ?> <?php echo $rehberekle; ?> <?php echo $topluemail; ?> <?php echo $toplusms; ?> <?php echo $bildirimsablonlari; ?> <?php echo $tummusteriler; ?> <?php echo $engellenenmusteriler; ?> <?php echo $musteriekle; ?> <?php echo $musteriduzenle; ?>">
                    <a class="nav-link" data-toggle="collapse" href="#rehber-yonetimi" aria-expanded="false" aria-controls="rehber-yonetimi">
                        <i class="icon-people menu-icon"></i>
                        <span class="menu-title"><?=@$admindil['txt66'];?></span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $rehbershow;?>" id="rehber-yonetimi">
                        <ul class="nav flex-column sub-menu">
                            <li class="nav-item <?php echo $tummusteriler; ?> <?php echo $musteriekle; ?> <?php echo $musteriduzenle; ?>">
                                <a class="nav-link <?php echo $tummusteriler; ?> <?php echo $musteriekle; ?> <?php echo $musteriduzenle; ?>" href="tum-musteriler.html"><?=@$admindil['txt30'];?></a>
                            </li>
                            <li class="nav-item <?php echo $engellenenmusteriler; ?>">
                                <a class="nav-link <?php echo $engellenenmusteriler; ?>" href="engellenen-musteriler.html"><?=@$admindil['txt31'];?></a>
                            </li>
                            <li class="nav-item <?php echo $rehberim; ?> <?php echo $rehberekle; ?>">
                                <a class="nav-link <?php echo $rehberim; ?> <?php echo $rehberekle; ?>" href="rehberim.html"><?=@$admindil['txt62'];?></a>
                            </li>
                            <li class="nav-item <?php echo $topluemail; ?>">
                                <a class="nav-link <?php echo $topluemail; ?>" href="toplu-email.html"><?=@$admindil['txt63'];?></a>
                            </li>
                            <li class="nav-item <?php echo $toplusms; ?>">
                                <a class="nav-link <?php echo $toplusms; ?>" href="toplu-sms.html"><?=@$admindil['txt64'];?></a>
                            </li>
                            <li class="nav-item <?php echo $bildirimsablonlari; ?>">
                                <a class="nav-link <?php echo $bildirimsablonlari; ?>" href="bildirim-sablonlari.html"><?=@$admindil['txt65'];?></a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item <?php echo $bekleyen_faturalar; ?> <?php echo $onaylanan_faturalar; ?> <?php echo($_GET['link'] == "bekleyen-faturalar" ? 'active' : '');?> <?php echo($_GET['link'] == "onaylanan-faturalar" ? 'active' : '');?>">
                    <a class="nav-link" data-toggle="collapse" href="#muhasebe" aria-expanded="false" aria-controls="muhasebe">
                        <i class="fa fa-money menu-icon"></i>
                        <span class="menu-title"><?=@$admindil['txt67'];?></span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $muhasebeshow;?> <?php echo($_GET['link'] == "bekleyen-faturalar" ? 'show' : '');?> <?php echo($_GET['link'] == "onaylanan-faturalar" ? 'show' : '');?>" id="muhasebe">
                        <ul class="nav flex-column sub-menu">
                            <li class="nav-item <?php echo $bekleyen_faturalar; ?> <?php echo($_GET['link'] == "bekleyen-faturalar" ? 'active' : '');?>">
                                <a class="nav-link <?php echo $bekleyen_faturalar; ?> <?php echo($_GET['link'] == "bekleyen-faturalar" ? 'active' : '');?>" href="bekleyen-faturalar.html"><?=@$admindil['txt68'];?></a>
                            </li>
                            <li class="nav-item <?php echo $onaylanan_faturalar; ?> <?php echo($_GET['link'] == "onaylanan-faturalar" ? 'active' : '');?>">
                                <a class="nav-link <?php echo $onaylanan_faturalar; ?> <?php echo($_GET['link'] == "onaylanan-faturalar" ? 'active' : '');?>" href="onaylanan-faturalar.html"><?=@$admindil['txt69'];?></a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item <?php echo $destekmerkezi; ?>">
                    <a class="nav-link" href="destek-merkezi.html">
                        <i class="icon-support menu-icon"></i>
                        <span class="menu-title"><?=@$admindil['txt23'];?></span>
                    </a>
                </li>

                <li class="nav-item position-relative">
                    <a class="nav-link" data-toggle="collapse" href="#bayilik-yonetimi" aria-expanded="false" aria-controls="bayilik-yonetimi">
                        <i class="icon-drawer menu-icon"></i>
                        <span class="menu-title"><?=@$admindil['txt70'];?></span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="collapse" id="bayilik-yonetimi">
                        <ul class="nav flex-column sub-menu">
                            <li class="nav-item">
                                <a class="nav-link" href="bayilikler.html">
                                    <i class="icon-envelope menu-icon"></i>
                                    <span class="menu-title"><?=@$admindil['txt71'];?></span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="bayilik-satislar.html">
                                    <i class="icon-envelope menu-icon"></i>
                                    <span class="menu-title"><?=@$admindil['txt72'];?></span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item sidebar-category mt-4">
                    <span style="margin-left: -10px;"><?=@$admindil['txt73'];?></span>
                    <?php $satilanhosting = $db->query("SELECT id FROM satilanlar WHERE durum=0 AND tipi=1")->rowCount();?>
                    <?php $satilanalanadi = $db->query("SELECT id FROM satilanlar WHERE durum=0 AND tipi=0")->rowCount();?>
                    <?php $satilanwebpaketi	= $db->query("SELECT id FROM satilanlar WHERE durum=0 AND tipi=2")->rowCount();?>
                </li>
                <li class="nav-item position-relative <?php echo $hosting_paketler; ?> <?php echo $hosting_kategori; ?> <?php echo $hosting_satislar; ?>">
                    <a class="nav-link" data-toggle="collapse" href="#hosting-yonetimi" aria-expanded="false" aria-controls="hosting-yonetimi">
                        <i class="icon-drawer menu-icon"></i>
                        <span class="menu-title"><?=@$admindil['txt77'];?></span> <span style="padding: 2px 5px;right:30px;" class="badge badge-outline-danger position-absolute"><?php echo $satilanhosting;?></span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $hostingshow;?>" id="hosting-yonetimi">
                        <ul class="nav flex-column sub-menu">
                            <li class="nav-item <?php echo $hosting_satislar; ?>">
                                <a class="nav-link <?php echo $hosting_satislar; ?>" href="hosting-satislar.html"><?=@$admindil['txt74'];?> <span style="padding: 2px 5px;margin-right:4px;" class="badge badge-outline-danger"><?php echo $satilanhosting;?></span></a>
                            </li>
                            <li class="nav-item <?php echo $hosting_paketler; ?>">
                                <a class="nav-link <?php echo $hosting_paketler; ?>" href="hosting-paketler.html"><?=@$admindil['txt75'];?></a>
                            </li>
                            <li class="nav-item <?php echo $hosting_kategori; ?>">
                                <a class="nav-link <?php echo $hosting_kategori; ?>" href="hosting-kategoriler.html"><?=@$admindil['txt76'];?></a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item position-relative <?php echo $alanadi_fiyatlari; ?> <?php echo $alanadi_satislar; ?>">
                    <a class="nav-link" data-toggle="collapse" href="#alanadi-yonetimi" aria-expanded="false" aria-controls="alanadi-yonetimi">
                        <i class="icon-link menu-icon"></i>
                        <span class="menu-title"><?=@$admindil['txt78'];?></span> <span style="padding: 2px 5px;right:30px;" class="badge badge-outline-danger position-absolute"><?php echo $satilanalanadi;?></span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $alanadishow;?>" id="alanadi-yonetimi">
                        <ul class="nav flex-column sub-menu">
                            <li class="nav-item <?php echo $alanadi_satislar; ?>">
                                <a class="nav-link <?php echo $alanadi_satislar; ?>" href="alanadi-satislar.html"><?=@$admindil['txt79'];?> <span style="padding: 2px 5px;margin-right:4px;" class="badge badge-outline-danger"><?php echo $satilanalanadi;?></span></a>
                            </li>
                            <li class="nav-item <?php echo $alanadi_fiyatlari; ?>">
                                <a class="nav-link <?php echo $alanadi_fiyatlari; ?>" href="alanadi-fiyatlari.html"><?=@$admindil['txt80'];?></a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item position-relative deneme <?php echo $web_paketler; ?> <?php echo $web_kategoriler; ?> <?php echo $web_paket_satislar; ?>">
                    <a class="nav-link" data-toggle="collapse" href="#web-yonetimi" aria-expanded="false" aria-controls="web-yonetimi">
                        <i class="icon-bag menu-icon"></i>
                        <span class="menu-title"><?=@$admindil['txt81'];?></span> <span style="padding: 2px 5px;right:30px;" class="badge badge-outline-danger position-absolute"><?php echo $satilanwebpaketi;?></span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $webshow;?>" id="web-yonetimi">
                        <ul class="nav flex-column sub-menu">
                            <li class="nav-item <?php echo $web_paket_satislar; ?>">
                                <a class="nav-link <?php echo $web_paket_satislar; ?>" href="web-paket-satislar.html"><?=@$admindil['txt82'];?> <span style="padding: 2px 5px;margin-right:4px;" class="badge badge-outline-danger"><?php echo $satilanwebpaketi;?></span></a>
                            </li>
                            <li class="nav-item <?php echo $web_paketler; ?>">
                                <a class="nav-link <?php echo $web_paketler; ?>" href="web-paketler.html"><?=@$admindil['txt83'];?></a>
                            </li>
                            <li class="nav-item <?php echo $web_kategoriler; ?>">
                                <a class="nav-link <?php echo $web_kategoriler; ?>" href="web-kategoriler.html"><?=@$admindil['txt84'];?></a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item <?php echo $sayfaekle; ?> <?php echo $sayfalistele; ?>">
                    <a class="nav-link" data-toggle="collapse" href="#sayfa-yonetimi" aria-expanded="false" aria-controls="sayfa-yonetimi">
                        <i class="icon-note menu-icon"></i>
                        <span class="menu-title"><?=@$admindil['txt25'];?></span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $sayfalarshow;?>" id="sayfa-yonetimi">
                        <ul class="nav flex-column sub-menu">
                            <li class="nav-item <?php echo $sayfaekle; ?>">
                                <a class="nav-link <?php echo $sayfaekle; ?>" href="sayfa-ekle.html"><?=@$admindil['txt28'];?></a>
                            </li>
                            <li class="nav-item <?php echo $sayfalistele; ?>">
                                <a class="nav-link <?php echo $sayfalistele; ?>" href="sayfa-listele.html"><?=@$admindil['txt27'];?></a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item <?php echo $hizmetekle; ?> <?php echo $hizmetlistele; ?>">
                    <a class="nav-link" data-toggle="collapse" href="#hizmet-yonetimi" aria-expanded="false" aria-controls="hizmet-yonetimi">
                        <i class="icon-note menu-icon"></i>
                        <span class="menu-title"><?=@$admindil['txt85'];?></span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $hizmetlershow;?>" id="hizmet-yonetimi">
                        <ul class="nav flex-column sub-menu">
                            <li class="nav-item <?php echo $hizmetekle; ?>">
                                <a class="nav-link <?php echo $hizmetekle; ?>" href="hizmet-ekle.html"><?=@$admindil['txt86'];?></a>
                            </li>
                            <li class="nav-item <?php echo $hizmetlistele; ?>">
                                <a class="nav-link <?php echo $hizmetlistele; ?>" href="hizmet-listele.html"><?=@$admindil['txt87'];?></a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item <?php echo $referansekle; ?> <?php echo $referanslistele; ?>">
                    <a class="nav-link" data-toggle="collapse" href="#referans-yonetimi" aria-expanded="false" aria-controls="referans-yonetimi">
                        <i class="icon-magnifier-add menu-icon"></i>
                        <span class="menu-title"><?=@$admindil['txt88'];?></span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $referansshow; ?>" id="referans-yonetimi">
                        <ul class="nav flex-column sub-menu">
                            <li class="nav-item <?php echo $referansekle; ?>">
                                <a class="nav-link <?php echo $referansekle; ?>" href="referans-ekle.html"><?=@$admindil['txt89'];?></a>
                            </li>
                            <li class="nav-item <?php echo $referanslistele; ?>">
                                <a class="nav-link <?php echo $referanslistele; ?>" href="referans-listele.html"><?=@$admindil['txt90'];?></a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item <?php echo $blogekle; ?> <?php echo $bloglistele; ?>">
                    <a class="nav-link" data-toggle="collapse" href="#blog-yonetimi" aria-expanded="false" aria-controls="haber-yonetimi">
                        <i class="icon-book-open menu-icon"></i>
                        <span class="menu-title"><?=@$admindil['txt34'];?></span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $blogshow; ?>" id="blog-yonetimi">
                        <ul class="nav flex-column sub-menu">
                            <li class="nav-item <?php echo $blogekle; ?>">
                                <a class="nav-link <?php echo $blogekle; ?>" href="blog-ekle.html"><?=@$admindil['txt37'];?></a>
                            </li>
                            <li class="nav-item <?php echo $bloglistele; ?>">
                                <a class="nav-link <?php echo $bloglistele; ?>" href="blog-listele.html"><?=@$admindil['txt36'];?></a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item <?php echo $sliderekle; ?> <?php echo $sliderlistele; ?>">
                    <a class="nav-link" data-toggle="collapse" href="#slider-yonetimi" aria-expanded="false" aria-controls="slider-yonetimi">
                        <i class="icon-picture menu-icon"></i>
                        <span class="menu-title"><?=@$admindil['txt38'];?></span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $slidershow; ?>" id="slider-yonetimi">
                        <ul class="nav flex-column sub-menu">
                            <li class="nav-item <?php echo $sliderekle; ?>">
                                <a class="nav-link <?php echo $sliderekle; ?>" href="slider-ekle.html"><?=@$admindil['txt41'];?></a>
                            </li>
                            <li class="nav-item <?php echo $sliderlistele; ?>">
                                <a class="nav-link <?php echo $sliderlistele; ?>" href="slider-listele.html"><?=@$admindil['txt40'];?></a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item <?php echo $bankahesapekle; ?> <?php echo $bankahesaplari; ?>">
                    <a class="nav-link" data-toggle="collapse" href="#banka-hesaplari" aria-expanded="false" aria-controls="slider-yonetimi">
                        <i class="icon-wallet menu-icon"></i>
                        <span class="menu-title"><?=@$admindil['txt91'];?></span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $bankahesapshow; ?>" id="banka-hesaplari">
                        <ul class="nav flex-column sub-menu">
                            <li class="nav-item <?php echo $bankahesapekle; ?>">
                                <a class="nav-link <?php echo $bankahesapekle; ?>" href="banka-hesap-ekle.html"><?=@$admindil['txt92'];?></a>
                            </li>
                            <li class="nav-item <?php echo $bankahesaplari; ?>">
                                <a class="nav-link <?php echo $bankahesaplari; ?>" href="banka-hesaplari.html"><?=@$admindil['txt93'];?></a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item <?php echo $yoneticiekle; ?> <?php echo $yoneticilistele; ?>">
                    <a class="nav-link" data-toggle="collapse" href="#yoneticiler" aria-expanded="false" aria-controls="yoneticiler">
                        <i class="icon-user-follow menu-icon"></i>
                        <span class="menu-title"><?=@$admindil['txt51'];?></span>
                        <i class="menu-arrow"></i>
                    </a>
                    <div class="collapse <?php echo $yoneticishow; ?>" id="yoneticiler">
                        <ul class="nav flex-column sub-menu">
                            <li class="nav-item <?php echo $yoneticiekle; ?>">
                                <a class="nav-link <?php echo $yoneticiekle; ?>" href="yonetici-ekle.html"><?=@$admindil['txt54'];?> </a>
                            </li>
                            <li class="nav-item <?php echo $yoneticilistele; ?>">
                                <a class="nav-link <?php echo $yoneticilistele; ?>" href="yonetici-listele.html"> <?=@$admindil['txt53'];?> </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li class="nav-item sidebar-category mt-4">
                    <span style="margin-left: -10px;"><?=@$admindil['txt94'];?></span>
                </li>
                <li class="nav-item <?php echo $odemebildirim; ?>">
                    <a class="nav-link" href="odemebildirimformu.html">
                        <i class="icon-credit-card menu-icon"></i>
                        <?php $odemebildirimformu = $db->query("SELECT * FROM  odeme_bildirimleri")->rowCount();?>
                        <span class="menu-title"><?=@$admindil['txt95'];?></span> <span style="padding: 2px 5px;" class="badge badge-outline-info"><?php echo $odemebildirimformu;?></span>
                    </a>
                </li>
                <li class="nav-item <?php echo $ebulten; ?>">
                    <a class="nav-link" href="ebulten.html">
                        <i class="icon-envelope-open menu-icon"></i>
                        <?php $ebultensayisi= $db->query("SELECT * FROM  ebulten")->rowCount();?>
                        <span class="menu-title"><?=@$admindil['txt96'];?></span> <span style="padding: 2px 5px;" class="badge badge-outline-info"><?php echo $ebultensayisi;?></span>
                    </a>
                </li>
                <li class="nav-item <?php echo $mesajlar; ?>">
                    <a class="nav-link" href="mesajlar.html">
                        <i class="icon-envelope menu-icon"></i>
                        <?php $okunmamis_mesaj= $db->query("SELECT * FROM  mesajlar WHERE durum = '0'")->rowCount();?>
                        <span class="menu-title"><?=@$admindil['txt55'];?></span> <span style="padding: 2px 5px;" class="badge badge-outline-danger"><?php echo $okunmamis_mesaj; ?></span>
                    </a>
                </li>

                <li class="nav-item <?php echo $yorumlar; ?>">
                    <a class="nav-link" href="yorumlar.html">
                        <i class="icon-bubbles menu-icon"></i>
                        <?php $onaysizyorum = $db->query("SELECT * FROM  yorumlar WHERE durum = '0'")->rowCount();?>
                        <span class="menu-title"><?=@$admindil['txt56'];?></span> <span style="padding: 2px 5px;" class="badge badge-outline-danger"><?php echo $onaysizyorum; ?></span>
                    </a>
                </li>

                <li class="nav-item sidebar-category mt-4">
                    <span style="margin-left: -10px;"><?=@$admindil['txt97'];?></span>
                </li>
                <li class="nav-item <?php echo $notdefteri; ?>">
                    <a class="nav-link" href="not-defteri.html">
                        <i class="icon-calendar menu-icon"></i>
                        <span class="menu-title"><?=@$admindil['txt57'];?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../_class/yonetim_islem.php?cikis=ok">
                        <i class="icon-power menu-icon"></i>
                        <span class="menu-title"><?=@$admindil['txt58'];?></span>
                    </a>
                </li>
            </ul>
        </nav>
        <!-- partial -->
        <!-- Start content -->
        <div class="main-panel">
            <div class="content-wrapper">
                <?php if($mevcutDil['anadil'] != 1): ?>
                    <div class="alert alert-fill-danger" role="alert">
                        <i class="mdi mdi-alert-circle"></i>
                        Şuanda <strong><?=@$mevcutDil['adi'];?></strong> dil versiyonundasınız. Yaptığınız tüm işlemler <strong><?=@$mevcutDil['adi'];?></strong> dili için geçerli olacaktır.
                    </div>
                <?php endif; ?>
                <?php
                if(isset($_GET['sayfa']))
                {
                    $s = $_GET['sayfa'];
                    switch($s)
                    {
                        case 'anasayfa';
                            require_once("sayfalar/anasayfa.php");
                            break;

                        case 'genel-ayarlar';
                            require_once("sayfalar/genel_ayarlar.php");
                            break;

                        case 'api-ayarlari';
                            require_once("sayfalar/api_ayarlari.php");
                            break;

                        case 'iletisim-ayarlari';
                            require_once("sayfalar/iletisim_ayarlari.php");
                            break;

                        case 'sosyal-medya-ayarlari';
                            require_once("sayfalar/sosyal_medya_ayarlari.php");
                            break;

                        case 'modul-ayarlari';
                            require_once("sayfalar/modul_ayarlari.php");
                            break;

                        case 'limit-ayarlari';
                            require_once("sayfalar/limit_ayarlari.php");
                            break;

                        case 'site-bakim-modu';
                            require_once("sayfalar/site_bakim_modu.php");
                            break;

                        case 'mail-ayarlari';
                            require_once("sayfalar/mail_ayarlari.php");
                            break;

                        case 'sms-ayarlari';
                            require_once("sayfalar/sms_ayarlari.php");
                            break;

                        case 'sanal-poslar';
                            require_once("sayfalar/sanal_poslar.php");
                            break;

                        case 'arkaplan-ayarlari';
                            require_once("sayfalar/arkaplan_ayarlari.php");
                            break;

                        case 'sayfa-ekle';
                            require_once("sayfalar/sayfa_ekle.php");
                            break;

                        case 'sayfa-listele';
                            require_once("sayfalar/sayfa_listele.php");
                            break;

                        case 'hizmet-ekle';
                            require_once("sayfalar/hizmet_ekle.php");
                            break;

                        case 'hizmet-listele';
                            require_once("sayfalar/hizmet_listele.php");
                            break;

                        case 'referans-ekle';
                            require_once("sayfalar/referans_ekle.php");
                            break;

                        case 'referans-listele';
                            require_once("sayfalar/referans_listele.php");
                            break;

                        case 'tum-musteriler';
                            require_once("sayfalar/tum_musteriler.php");
                            break;

                        case 'bayilikler';
                            require_once("sayfalar/bayilikler.php");
                            break;

                        case 'bayilik-satislar';
                            require_once("sayfalar/bayilik_satislar.php");
                            break;

                        case 'bayilik-ekle';
                            require_once("sayfalar/bayilik_ekle.php");
                            break;


                        case 'engellenen-musteriler';
                            require_once("sayfalar/engellenen_musteriler.php");
                            break;

                        case 'musteri-ekle';
                            require_once("sayfalar/musteri_ekle.php");
                            break;

                        case 'musteri-duzenle';
                            require_once("sayfalar/musteri_duzenle.php");
                            break;

                        case 'blog-ekle';
                            require_once("sayfalar/blog_ekle.php");
                            break;

                        case 'blog-listele';
                            require_once("sayfalar/blog_listele.php");
                            break;

                        case 'slider-ekle';
                            require_once("sayfalar/slider_ekle.php");
                            break;

                        case 'slider-listele';
                            require_once("sayfalar/slider_listele.php");
                            break;

                        case 'kupon-ekle';
                            require_once("sayfalar/kupon_ekle.php");
                            break;

                        case 'kupon-listele';
                            require_once("sayfalar/kuponlar.php");
                            break;

                        case 'header-menu';
                            require_once("sayfalar/header_menu.php");
                            break;

                        case 'footer-menu';
                            require_once("sayfalar/footer_menu.php");
                            break;

                        case 'destek-merkezi';
                            require_once("sayfalar/destek_merkezi.php");
                            break;

                        case 'destek';
                            require_once("sayfalar/destek.php");
                            break;

                        case 'dil-ekle';
                            require_once("sayfalar/dil_ekle.php");
                            break;

                        case 'dil-listele';
                            require_once("sayfalar/dil_listele.php");
                            break;

                        case 'mesajlar';
                            require_once("sayfalar/mesajlar.php");
                            break;

                        case 'yorumlar';
                            require_once("sayfalar/yorumlar.php");
                            break;

                        case 'bildirim-sablonlari';
                            require_once("sayfalar/bildirim_sablonlari.php");
                            break;

                        case 'sablon-duzenle';
                            require_once("sayfalar/sablon_duzenle.php");
                            break;

                        case 'yonetici-ekle';
                            require_once("sayfalar/yonetici_ekle.php");
                            break;

                        case 'yonetici-listele';
                            require_once("sayfalar/yonetici_listele.php");
                            break;

                        case 'not-defteri';
                            require_once("sayfalar/not_defteri.php");
                            break;

                        case 'rehberim';
                            require_once("sayfalar/rehberim.php");
                            break;

                        case 'rehber-ekle';
                            require_once("sayfalar/rehber_ekle.php");
                            break;

                        case 'toplu-email';
                            require_once("sayfalar/toplu_email.php");
                            break;

                        case 'toplu-sms';
                            require_once("sayfalar/toplu_sms.php");
                            break;


                        case 'hosting-kategoriler';
                            require_once("sayfalar/hosting_kategoriler.php");
                            break;

                        case 'hosting-kategori-ekle';
                            require_once("sayfalar/hosting_kategori_ekle.php");
                            break;

                        case 'hosting-paketler';
                            require_once("sayfalar/hosting_paketler.php");
                            break;

                        case 'hosting-paket-ekle';
                            require_once("sayfalar/hosting_paket_ekle.php");
                            break;

                        case 'web-kategoriler';
                            require_once("sayfalar/web_kategoriler.php");
                            break;

                        case 'web-kategori-ekle';
                            require_once("sayfalar/web_kategori_ekle.php");
                            break;

                        case 'web-paketler';
                            require_once("sayfalar/web_paketler.php");
                            break;

                        case 'web-paket-ekle';
                            require_once("sayfalar/web_paket_ekle.php");
                            break;

                        case 'alanadi-fiyatlari';
                            require_once("sayfalar/alanadi_fiyatlari.php");
                            break;

                        case 'fatura-ekle';
                            require_once("sayfalar/fatura_ekle.php");
                            break;

                        case 'fatura-duzenle';
                            require_once("sayfalar/fatura_duzenle.php");
                            break;

                        case 'bekleyen-faturalar';
                            require_once("sayfalar/bekleyen_faturalar.php");
                            break;

                        case 'onaylanan-faturalar';
                            require_once("sayfalar/onaylanan_faturalar.php");
                            break;

                        case 'ms-hosting-ekle';
                            require_once("sayfalar/ms_hosting_ekle.php");
                            break;

                        case 'ms-hosting-duzenle';
                            require_once("sayfalar/ms_hosting_duzenle.php");
                            break;

                        case 'alanadi-ekle';
                            require_once("sayfalar/alanadi_ekle.php");
                            break;

                        case 'alanadi-duzenle';
                            require_once("sayfalar/alanadi_duzenle.php");
                            break;

                        case 'ms-hizmet-ekle';
                            require_once("sayfalar/ms_hizmet_ekle.php");
                            break;

                        case 'ms-hizmet-duzenle';
                            require_once("sayfalar/ms_hizmet_duzenle.php");
                            break;

                        case 'ms-sozlesme-ekle';
                            require_once("sayfalar/ms_sozlesme_ekle.php");
                            break;

                        case 'ms-sozlesme-duzenle';
                            require_once("sayfalar/ms_sozlesme_duzenle.php");
                            break;

                        case 'ms-rapor-ekle';
                            require_once("sayfalar/ms_rapor_ekle.php");
                            break;

                        case 'ms-rapor-duzenle';
                            require_once("sayfalar/ms_rapor_duzenle.php");
                            break;
                        case 'ms-efatura-ekle';
                            require_once("sayfalar/ms_efatura_ekle.php");
                            break;

                        case 'ms-efatura-duzenle';
                            require_once("sayfalar/ms_efatura_duzenle.php");
                            break;

                        case 'ms-referans-ekle';
                            require_once("sayfalar/ms_referans_ekle.php");
                            break;

                        case 'ms-referans-duzenle';
                            require_once("sayfalar/ms_referans_duzenle.php");
                            break;

                        case 'ms-teklif-ekle';
                            require_once("sayfalar/ms_teklif_ekle.php");
                            break;

                        case 'ms-teklif-duzenle';
                            require_once("sayfalar/ms_teklif_duzenle.php");
                            break;

                        case 'ms-web-paket-ekle';
                            require_once("sayfalar/ms_web_paket_ekle.php");
                            break;

                        case 'ms-web-paket-duzenle';
                            require_once("sayfalar/ms_web_paket_duzenle.php");
                            break;

                        case 'hosting-satislar';
                            require_once("sayfalar/hosting_satislar.php");
                            break;

                        case 'alanadi-satislar';
                            require_once("sayfalar/alanadi_satislar.php");
                            break;

                        case 'web-paket-satislar';
                            require_once("sayfalar/web_paket_satislar.php");
                            break;

                        case 'ebulten';
                            require_once("sayfalar/ebulten.php");
                            break;

                        case 'odemebildirimformu';
                            require_once("sayfalar/odemebildirimformu.php");
                            break;

                        case '404';
                            require_once("sayfalar/404.php");
                            break;

                        case 'web-ofisi-bot-web-paket-ekle';
                            require_once("sayfalar/web_ofisi_bot_web_paket_ekle.php");
                            break;

                        case 'banka-hesap-ekle';
                            require_once("sayfalar/banka_hesap_ekle.php");
                            break;

                        case 'banka-hesaplari';
                            require_once("sayfalar/banka_hesaplari.php");
                            break;

                        default:
                            require_once("sayfalar/anasayfa.php");
                    }
                }
                else
                {
                    require_once("sayfalar/anasayfa.php");
                }
                ?>
            </div>
            <!-- content-wrapper ends -->
            <footer class="footer">
                <div class="d-sm-flex justify-content-center justify-content-sm-between">
                    <span class="text-muted text-center text-sm-left d-block d-sm-inline-block"><?=@$admindil['txt98'];?> <?php echo date("Y");?>  <?=@$admindil['txt99'];?></span>
                </div>
            </footer>
            <!-- partial -->
        </div>
    </div>
    <!-- page-body-wrapper ends -->
</div>

<!-- Plugin js for this page-->
<!-- End plugin js for this page-->
<!-- inject:js -->
<script src="js/off-canvas.js"></script>
<script src="js/hoverable-collapse.js"></script>
<script src="js/template.js"></script>
<script src="js/settings.js"></script>
<script src="js/todolist.js"></script>
<!-- endinject -->
<!-- Custom js for this page-->
<script src="js/dashboard.js"></script>
<!-- End custom js for this page-->
<script src="js/file-upload.js"></script>
<script src="js/typeahead.js"></script>
<script src="js/select2.js"></script>
<script src="https://crm.ornek.com/tema/webajans/js/bootstrap-popover-x.min.js"></script>
<script src="js/formpickers.js"></script>
<script src="js/form-addons.js"></script>
<script src="js/x-editable.js"></script>
<script src="js/dropify.js"></script>
<script src="js/form-repeater.js"></script>
<script src="js/bt-maxLength.js"></script>
<script src="js/tooltips.js"></script>
<script src="js/codeEditor_mirror.js"></script>
<script src="js/editorDemo.js"></script>
<script src="vendors/multiselect/jquery.multiselect.js"></script>
<!--Custom-Scrollbar js-->
<script src="js/jquery.mCustomScrollbar.concat.min.js"></script>
<script type="text/javascript" src="js/jquery.popconfirm.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $(".popconfirm").popConfirm();

        $(".popconfirm1").popConfirm();
        $(".popconfirm2").popConfirm();
    });
</script>
<script>
    tinymce.init({
        selector: '#myTextarea,#myTextarea2',
        language: 'tr',
        entity_encoding : "utf-8",
        entities : "",
        branding: false,
        theme: "silver",
        document_base_url : "<?php echo $url;?>/",
        height:500,
        fontsize_formats: "8pt 10pt 12pt 14pt 18pt 24pt 36pt",
        plugins: [
            "image code",
            "advlist autolink link image lists charmap print preview hr anchor pagebreak spellchecker",
            "searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
            "save table contextmenu directionality emoticons template paste textcolor"
        ],
        toolbar: "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | image code | print preview media | forecolor backcolor fontsizeselect emoticons",

        // without images_upload_url set, Upload tab won't show up
        images_upload_url: '../_class/tinymce.php',

        // override default upload handler to simulate successful upload
        images_upload_handler: function (blobInfo, success, failure) {
            var xhr, formData;

            xhr = new XMLHttpRequest();
            xhr.withCredentials = false;
            xhr.open('POST', '../_class/tinymce.php');

            xhr.onload = function() {
                var json;

                if (xhr.status != 200) {
                    failure('HTTP Error: ' + xhr.status);
                    return;
                }

                json = JSON.parse(xhr.responseText);

                if (!json || typeof json.location != 'string') {
                    failure('Invalid JSON: ' + xhr.responseText);
                    return;
                }

                success(json.location);
            };

            formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());

            xhr.send(formData);
        },
    });
</script>


<script src="js/light-gallery.js"></script>

<script>
    $(window).on("load",function(){
        $(".scroll").mCustomScrollbar({
            setWidth:false,
            setHeight:false,
            setTop:0,
            setLeft:0,
            axis:"y",
            scrollbarPosition:"inside",
            scrollInertia:950,
            autoDraggerLength:true,
            autoHideScrollbar:false,
            autoExpandScrollbar:false,
            alwaysShowScrollbar:0,
            snapAmount:null,
            snapOffset:0,
            mouseWheel:{
                enable:true,
                scrollAmount:"auto",
                axis:"y",
                preventDefault:false,
                deltaFactor:"auto",
                normalizeDelta:false,
                invert:false,
                disableOver:["select","option","keygen","datalist","textarea"]
            },
            scrollButtons:{
                enable:false,
                scrollType:"stepless",
                scrollAmount:"auto"
            },
            keyboard:{
                enable:true,
                scrollType:"stepless",
                scrollAmount:"auto"
            },
            contentTouchScroll:25,
            advanced:{
                autoExpandHorizontalScroll:false,
                autoScrollOnFocus:"input,textarea,select,button,datalist,keygen,a[tabindex],area,object,[contenteditable='true']",
                updateOnContentResize:true,
                updateOnImageLoad:true,
                updateOnSelectorChange:false,
                releaseDraggableSelectors:false
            },
            theme:"light",
            callbacks:{
                onInit:false,
                onScrollStart:false,
                onScroll:false,
                onTotalScroll:false,
                onTotalScrollBack:false,
                whileScrolling:false,
                onTotalScrollOffset:0,
                onTotalScrollBackOffset:0,
                alwaysTriggerOffsets:true,
                onOverflowY:false,
                onOverflowX:false,
                onOverflowYNone:false,
                onOverflowXNone:false
            },
            live:false,
            liveSelector:null
        });

    });
</script>
<script>
    $(function () {
        $('select#uyeler').multiselect({
            columns: 3,
            placeholder: '-Seçiniz-',
            search: true,
            searchOptions: {
                'default': 'Arama'
            },
            selectAll: true
        });

    });
</script>
<?php
if(@$_SESSION['satissil'] == 'yes')
{
    echo "
		<script>
			$.toast({
			  heading: 'Başarılı!',
			  text: 'Başarı ile silinmiştir.',
			  showHideTransition: 'slide',
			  icon: 'success',
			  loaderBg: '#fff',
			  position: 'top-right'
			})
		</script>";
    unset($_SESSION['satissil']);
}
if(@$_SESSION['satissil'] == 'no')
{
    echo "
		<script>
			$.toast({
			  heading: 'Hata!',
			  text: 'Hata oluştu tekrar deneyiniz.!',
			  showHideTransition: 'slide',
			  icon: 'error',
			  loaderBg: '#fff',
			  position: 'top-right'
			})
		</script>";
    unset($_SESSION['satissil']);
}
if(@$_SESSION['satis_tumu'] == 'yes')
{
    echo "
		<script>
			$.toast({
			  heading: 'Başarılı!',
			  text: 'Seçilen kayıtlar başarıyla silinmiştir.',
			  showHideTransition: 'slide',
			  icon: 'success',
			  loaderBg: '#fff',
			  position: 'top-right'
			})
		</script>";
    unset($_SESSION['satis_tumu']);
}
if(@$_SESSION['satis_tumu'] == 'no')
{
    echo "
		<script>
			$.toast({
			  heading: 'Hata!',
			  text: 'Hata oluştu tekrar deneyiniz.!',
			  showHideTransition: 'slide',
			  icon: 'error',
			  loaderBg: '#fff',
			  position: 'top-right'
			})
		</script>";
    unset($_SESSION['satis_tumu']);
}
if($_SESSION['faturasil'] == 'yes')
{
    echo "
		<script>
			$.toast({
			  heading: 'Başarılı!',
			  text: 'Başarıyla silinmiştir.',
			  showHideTransition: 'slide',
			  icon: 'success',
			  loaderBg: '#fff',
			  position: 'top-right'
			})
		</script>";
    unset($_SESSION['faturasil']);
}
if($_SESSION['faturasil'] == 'no')
{
    echo "
		<script>
			$.toast({
			  heading: 'Hata!',
			  text: 'Hata oluştu tekrar deneyiniz.!',
			  showHideTransition: 'slide',
			  icon: 'error',
			  loaderBg: '#fff',
			  position: 'top-right'
			})
		</script>";
    unset($_SESSION['faturasil']);
}
if($_SESSION['satilanlar'] == 'yes')
{
    echo "
		<script>
			$.toast({
			  heading: 'Başarılı!',
			  text: 'Başarıyla silinmiştir.',
			  showHideTransition: 'slide',
			  icon: 'success',
			  loaderBg: '#fff',
			  position: 'top-right'
			})
		</script>";
    unset($_SESSION['satilanlar']);
}
if($_SESSION['satilanlar'] == 'no')
{
    echo "
		<script>
			$.toast({
			  heading: 'Hata!',
			  text: 'Hata oluştu tekrar deneyiniz.!',
			  showHideTransition: 'slide',
			  icon: 'error',
			  loaderBg: '#fff',
			  position: 'top-right'
			})
		</script>";
    unset($_SESSION['satilanlar']);
}
if($_SESSION['satilanlar_tumu'] == 'yes')
{
    echo "
		<script>
			$.toast({
			  heading: 'Başarılı!',
			  text: 'Seçilen kayıtlar başarıyla silinmiştir.',
			  showHideTransition: 'slide',
			  icon: 'success',
			  loaderBg: '#fff',
			  position: 'top-right'
			})
		</script>";
    unset($_SESSION['satilanlar_tumu']);
}
if($_SESSION['satilanlar_tumu'] == 'no')
{
    echo "
		<script>
			$.toast({
			  heading: 'Hata!',
			  text: 'Hata oluştu tekrar deneyiniz.!',
			  showHideTransition: 'slide',
			  icon: 'error',
			  loaderBg: '#fff',
			  position: 'top-right'
			})
		</script>";
    unset($_SESSION['satilanlar_tumu']);
}
if($_SESSION['fatura_tumu'] == 'yes')
{
    echo "
		<script>
			$.toast({
			  heading: 'Başarılı!',
			  text: 'Seçilen kayıtlar başarıyla silinmiştir.',
			  showHideTransition: 'slide',
			  icon: 'success',
			  loaderBg: '#fff',
			  position: 'top-right'
			})
		</script>";
    unset($_SESSION['fatura_tumu']);
}
if($_SESSION['fatura_tumu'] == 'no')
{
    echo "
		<script>
			$.toast({
			  heading: 'Hata!',
			  text: 'Hata oluştu tekrar deneyiniz.!',
			  showHideTransition: 'slide',
			  icon: 'error',
			  loaderBg: '#fff',
			  position: 'top-right'
			})
		</script>";
    unset($_SESSION['fatura_tumu']);
}
if($_SESSION['secim'] == 'secimyok')
{
    echo "
		<script>
			$.toast({
			  heading: 'Uyarı!',
			  text: 'Hiç bir şey seçmediniz. Lütfen işlem yapmak istediğiniz eylemi ve ID leri seçin.',
			  showHideTransition: 'slide',
			  icon: 'warning',
			  loaderBg: '#fff',
			  position: 'top-right'
			})
		</script>";
    unset($_SESSION['secim']);
}
if(isset($_SESSION['kullanici_giris']) == 'yes')
{
    echo "
		<script>
			$.toast({
		      heading: 'Başarılı',
		      text: 'Sisteme başarıyla giriş yapılmıştır.',
		      showHideTransition: 'slide',
		      icon: 'success',
		      loaderBg: '#fff',
		      position: 'top-right'
		    })
		</script>";
    unset($_SESSION['kullanici_giris']);
}
if($_SESSION['demohesap'] == 'no')
{
    echo "
		<script>
			$.toast({
		      heading: 'Uyarı',
		      text: 'Demo hesapta işlem yapamassınız.!',
		      showHideTransition: 'slide',
		      icon: 'warning',
		      loaderBg: '#fff',
		      position: 'top-right'
		    })
		</script>";
    unset($_SESSION['demohesap']);
}
?>
</body>
</html>
<?php ob_end_flush(); ?>
