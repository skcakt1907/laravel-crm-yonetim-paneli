<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null; ?>
<?php
$str = '';
$order = 'id DESC';
$ordertext = "Sıralama";
if ($_GET['kelime'] != "") {
    $kelime = $_GET['kelime'];
    $str .= " AND adi LIKE '%$kelime%'";
}
if ($_GET['kategoriler'] != "") {
    $kategoriler = $_GET['kategoriler'];
    $str .= " AND find_in_set($kategoriler,kategori)";
}
if ($_GET['siralama'] == "yeni") {
    $order = 'id DESC';
    $ordertext = "Tarihe göre (Önce en yeni)";
}
if ($_GET['siralama'] == "eski") {
    $order = 'id ASC';
    $ordertext = "Tarihe göre (Önce en eski)";
}

if (strip_tags(isset($_GET['kategori']))) {
    $Sorgu = $db->prepare("SELECT * FROM web_kategori WHERE seo = ? AND dil = ? ");
    $Sorgu->execute(array($_GET['kategori'], $_SESSION['k_dil']));
    if ($Sorgu->rowCount()) {
        $Sonuc = $Sorgu->fetch(PDO::FETCH_ASSOC);
        $page = @intval($_GET['s']);
        if (!$page) $page = 1;
        $ttsorgu = $db->prepare("SELECT COUNT(*) FROM yazilimlar WHERE durum = ? AND find_in_set(?,kategori) AND dil = ?");
        $ttsorgu->execute(array("1", $Sonuc['id'], $_SESSION['k_dil']));
        $total = $ttsorgu->fetchColumn();
        $limit = $limitayar['limit_sayfapaketler'];
        $page_count = ceil($total / $limit);
        if ($page > $page_count) $page = 1;
        $show = $page * $limit - $limit;
        $URUNSorgu = $db->prepare("SELECT * FROM yazilimlar WHERE durum = ? AND find_in_set(?,kategori) AND dil = ? ORDER BY sira DESC LIMIT $show,$limit");
        $URUNSorgu->execute(array("1", $Sonuc['id'], $_SESSION['k_dil']));
        $URUNislem = $URUNSorgu->fetchALL(PDO::FETCH_ASSOC);
    } else {
        header("Location:" . $url . "/404.html");
    }
} else {
    $page = @intval($_GET['s']);
    if (!$page) $page = 1;
    $ttsorgu = $db->prepare("SELECT COUNT(*) FROM yazilimlar WHERE durum = ? AND dil = ? $str");
    $ttsorgu->execute(array("1", $_SESSION['k_dil']));
    $total = $ttsorgu->fetchColumn();
    $limit = $limitayar['limit_sayfapaketler'];
    $page_count = ceil($total / $limit);
    if ($page > $page_count) $page = 1;
    $show = $page * $limit - $limit;
    $URUNSorgu = $db->prepare("SELECT * FROM yazilimlar WHERE durum = ? AND dil = ? $str ORDER BY $order LIMIT $show,$limit");
    $URUNSorgu->execute(array("1", $_SESSION['k_dil']));
    $URUNislem = $URUNSorgu->fetchALL(PDO::FETCH_ASSOC);
}

$menubul     = $db->query("SELECT * FROM menu WHERE menu_url = 'paketler.html' OR link = 'paketler.html' AND dil = '{$_SESSION['k_dil']}'")->fetch(PDO::FETCH_ASSOC);
$menubas     = $db->query("SELECT * FROM menu WHERE id = '{$menubul['menu_ust']}' AND dil = '{$_SESSION['k_dil']}'")->fetch(PDO::FETCH_ASSOC);
?>
<div class="top-header overlay" style="background-image: url(<?php echo tema; ?>/uploads/arkaplan/paketler/<?php echo $arkaplan['paketler'] ?>)">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper">
                    <h1 class="heading"><?= @$dil['txt282']; ?></h1>
                    <h3 class="subheading"><?= @$dil['txt335']; ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="ustbanner">
    <div class="container">
        <a href="index.html" class="golink gocheck"> <?= @$dil['txt158']; ?> </a> <small class="c-white">&#9679;</small>
        <?php if ($menubas['menu_isim'] != "") { ?>
            <a href="<?php echo ($menubas['menu_url'] == "0" ? $menubas['link'] : $menubas['menu_url']); ?>" class="golink gocheck"> <?php echo $menubas['menu_isim']; ?> </a>
        <?php } else { ?>
            <a href="<?php echo ($menubul['menu_url'] == "0" ? $menubul['link'] : $menubul['menu_url']); ?>" class="golink gocheck"> <?php echo $menubul['menu_isim']; ?> </a>
        <?php } ?>
    </div>
</div>

<!-- ***** FILTER PLANS ***** -->
<div class="mixcontainer pb-5 pt-4 sec-bg2" data-ref="container">
    <div class="container">

        <div class="row">
            <div class="col-md-12">
                <div class="promo-item-wrap">

                    <div class="row">
                        <div class="col-md-3 d-none d-lg-block">
                            <!-- SIDEBAR AREA START HERE -->
                            <aside class="sidebar">
                                <div class="sidebar-widget">
                                    <h3 class="widget-title"><?= @$dil['txt334']; ?></h3>
                                    <ul class="hidden-xs">
                                        <?php $toplam_paket_sayisi = $db->query("SELECT id FROM yazilimlar WHERE durum = '1' AND dil = '{$_SESSION['k_dil']}'")->rowCount(); ?>
                                        <li><a href="paketler.html"><?= @$dil['txt336']; ?> (<?php echo $toplam_paket_sayisi; ?>)</a></li>
                                        <?php $KATSorgu = $db->prepare("SELECT * FROM web_kategori WHERE durum = ? ORDER BY sira ASC");
                                        $KATSorgu->execute(array("1"));
                                        $KATislem = $KATSorgu->fetchALL(PDO::FETCH_ASSOC); ?>
                                        <?php foreach ($KATislem as $KATSonuc) { ?>
                                            <?php $paket_sayisi = $db->query("SELECT id FROM yazilimlar WHERE durum = '1' AND find_in_set({$KATSonuc['id']},kategori) AND dil = '{$_SESSION['k_dil']}'")->rowCount(); ?>
                                            <li><a href="paketler.html?kategori=<?php echo $KATSonuc['seo']; ?>"><?php echo $KATSonuc['adi']; ?> (<?php echo $paket_sayisi; ?>)</a></li>
                                        <?php } ?>
                                    </ul>
                                    <select class="filtrecategory form-control">
                                        <?php $toplam_paket_sayisi = $db->query("SELECT id FROM yazilimlar WHERE durum = '1' AND dil = '{$_SESSION['k_dil']}'")->rowCount(); ?>
                                        <option value="paketler.html"><?= @$dil['txt336']; ?> (<?php echo $toplam_paket_sayisi; ?>)</option>
                                        <?php $KATSorgu = $db->prepare("SELECT * FROM web_kategori WHERE durum = ? ORDER BY sira ASC");
                                        $KATSorgu->execute(array("1"));
                                        $KATislem = $KATSorgu->fetchALL(PDO::FETCH_ASSOC); ?>
                                        <?php foreach ($KATislem as $KATSonuc) { ?>
                                            <?php $paket_sayisi = $db->query("SELECT id FROM yazilimlar WHERE durum = '1' AND find_in_set({$KATSonuc['id']},kategori) AND dil = '{$_SESSION['k_dil']}'")->rowCount(); ?>
                                            <option value="paketler.html?kategori=<?php echo $KATSonuc['seo']; ?>"><?php echo $KATSonuc['adi']; ?> (<?php echo $paket_sayisi; ?>)</option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <!-- Categories Widget End -->
                            </aside>
                            <!-- SIDEBAR AREA END HERE -->
                        </div>

                        <div class="col-md-9 col-sm-12">
                            <div class="content-before">
                                <div class="row">
                                    <div class="col-md-7 col-sx-12">
                                        <form class="dropForm" action="paketler.html">
                                            <div class="input-prepend">
                                                <div class="btn-group">
                                                    <select name="kategoriler" class="form-contol p-2">
                                                        <option value="">Tüm Paketler</option>
                                                        <?php
                                                        $KATSorgu = $db->prepare("SELECT * FROM web_kategori WHERE durum = ? AND dil = ? ORDER BY id ASC");
                                                        $KATSorgu->execute(array("1", $_SESSION['k_dil']));
                                                        $KATislem = $KATSorgu->fetchALL(PDO::FETCH_ASSOC); ?>
                                                        <?php foreach ($KATislem as $KATSonuc) { ?>
                                                            <option value="<?php echo $KATSonuc['id']; ?>"><?php echo $KATSonuc['adi']; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <input type="text" class="form-control" name="kelime" placeholder="Ürün Aramak İçin Yazın...">
                                                <button class="btn btn-primary" tabindex="-1"><i class="fa fa-search"></i></button>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-5 col-sx-12 text-right hidden-xs">
                                        <div class="catalog-order">
                                            <select name="orderby" class="form-contol p-2" id="siralama" onchange="location = this.value;">
                                                <option <?php echo ($_GET['siralama'] == "yeni" ? 'selected' : ''); ?> value="paketler.html?siralama=yeni">Tarihe göre (Önce en yeni)</option>
                                                <option <?php echo ($_GET['siralama'] == "eski" ? 'selected' : ''); ?> value="paketler.html?siralama=eski">Tarihe göre (Önce en eski)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div><!-- end row -->
                            </div>
                            <!-- TAB PANES CONTENT START -->
                            <?php if ($URUNSorgu->rowCount() != "0") { ?>
                                <div class="tab-content">
                                    <div class="row">
                                        <?php foreach ($URUNislem as $URUNSonuc) {
                                            $oldPrice = $URUNSonuc['tutar'];
                                            $URUNSonuc['tutar'] = bayilikindirimi($_SESSION['site_uyeid'], $URUNSonuc['tutar']);
                                        ?>
                                            <?php $izlenme     = $db->query("SELECT * FROM yazilim_hit WHERE yid='{$URUNSonuc['id']}'")->rowCount(); ?>
                                            <!-- Single Product Start -->
                                            <div class="col-md-<?php echo $limitayar['limit_paket']; ?> col-sm-<?php echo $limitayar['limit_paket']; ?>">
                                                <div class="item-demo">
                                                    <figure class="paketkapak" style="background-image:url(<?php echo tema; ?>/uploads/webpaketleri/kapak/<?php echo $URUNSonuc['resim'] ?>)">
                                                        <div class="product-caption">
                                                            <div class="caption-cel">
                                                                <div class="product-link">
                                                                    <div>
                                                                        <div>
                                                                            <a href="detay/<?php echo $URUNSonuc['seo']; ?>.html"><?= @$dil['txt75']; ?> <span><i class="fa fa-eye"></i></span></a>
                                                                        </div>
                                                                        <?php if ($moduller['alan6'] == "1") { ?>
                                                                            <div>
                                                                                <a href="web-paket-satinal/<?php echo $URUNSonuc['id']; ?>.html"><?= @$dil['txt76']; ?><span><i class="fa fa-shopping-cart"></i></span></a>
                                                                            </div>
                                                                            <div style="margin-top:10px;">
                                                                                <form action="_class/site_islem.php" method="post">
                                                                                    <input hidden name="urun_id" value="<?php echo $URUNSonuc['id'] ?>" required readonly>
                                                                                    <button class="sepet_clas" type="submit" name="webpaket_sepet" href="javascript:;"><?= @$dil['txt394']; ?><span><i class="fa fa-shopping-cart"></i></span></button>
                                                                                </form>
                                                                            </div>
                                                                        <?php } ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </figure>
                                                    <div class="product-info">
                                                        <div class="product-header">
                                                            <h3 class="product-name"><a href="detay/<?php echo $URUNSonuc['seo']; ?>.html"><?php echo $URUNSonuc['adi'] ?></a></h3>
                                                            <span class="p-author">
                                                                <?php $kategoriler = explode(",", $URUNSonuc['kategori']); ?>
                                                                <?php foreach ($kategoriler as $key) { ?>
                                                                    <?php
                                                                    $ran = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f');
                                                                    $color = "#" . $ran[rand(0, 15)] . $ran[rand(0, 15)] . $ran[rand(0, 15)] . $ran[rand(0, 15)] . $ran[rand(0, 15)] . $ran[rand(0, 15)]; ?>
                                                                    <?php $kategoriler    = $db->query("SELECT * FROM web_kategori WHERE id = '{$key}'")->fetch(PDO::FETCH_ASSOC); ?>
                                                                    <a style="color:<?php echo $color; ?>" href="paketler.html?kategori=<?php echo $kategoriler['seo']; ?>"><?php echo $kategoriler['adi']; ?></a>
                                                                <?php } ?>
                                                            </span>
                                                        </div>
                                                        <div class="product-meta">
                                                            <span class="meta-download">
                                                                <i class="fa fa-eye"></i><?php echo $izlenme; ?>
                                                            </span>
                                                            <?php if ($URUNSonuc['tutar'] != $oldPrice) { ?>
                                                                <span class="paketfiyat"><?php echo my_number_format($URUNSonuc['tutar']); ?> TL</span><span class="paketfiyat_bayi"><del><?php echo my_number_format($oldPrice) ?> TL</del></span>
                                                            <?php } else { ?>
                                                                <span class="paketfiyat"><?php echo my_number_format($URUNSonuc['tutar']); ?> TL</span>
                                                            <?php } ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Single Product End -->
                                        <?php } ?>
                                    </div>
                                </div>
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
                        </div>
                        <!-- PAGER  START HERE -->

                        <div class="d-block d-lg-none col-12">
                            <!-- SIDEBAR AREA START HERE -->
                            <aside class="sidebar">
                                <div class="sidebar-widget">
                                    <h3 class="widget-title"><?= @$dil['txt334']; ?></h3>
                                    <ul class="hidden-xs">
                                        <?php $toplam_paket_sayisi = $db->query("SELECT id FROM yazilimlar WHERE durum = '1' AND dil = '{$_SESSION['k_dil']}'")->rowCount(); ?>
                                        <li><a href="paketler.html"><?= @$dil['txt336']; ?> (<?php echo $toplam_paket_sayisi; ?>)</a></li>
                                        <?php $KATSorgu = $db->prepare("SELECT * FROM web_kategori WHERE durum = ? ORDER BY sira ASC");
                                        $KATSorgu->execute(array("1"));
                                        $KATislem = $KATSorgu->fetchALL(PDO::FETCH_ASSOC); ?>
                                        <?php foreach ($KATislem as $KATSonuc) { ?>
                                            <?php $paket_sayisi = $db->query("SELECT id FROM yazilimlar WHERE durum = '1' AND find_in_set({$KATSonuc['id']},kategori) AND dil = '{$_SESSION['k_dil']}'")->rowCount(); ?>
                                            <li><a href="paketler.html?kategori=<?php echo $KATSonuc['seo']; ?>"><?php echo $KATSonuc['adi']; ?> (<?php echo $paket_sayisi; ?>)</a></li>
                                        <?php } ?>
                                    </ul>
                                    <select class="filtrecategory form-control">
                                        <?php $toplam_paket_sayisi = $db->query("SELECT id FROM yazilimlar WHERE durum = '1' AND dil = '{$_SESSION['k_dil']}'")->rowCount(); ?>
                                        <option value="paketler.html"><?= @$dil['txt336']; ?> (<?php echo $toplam_paket_sayisi; ?>)</option>
                                        <?php $KATSorgu = $db->prepare("SELECT * FROM web_kategori WHERE durum = ? ORDER BY sira ASC");
                                        $KATSorgu->execute(array("1"));
                                        $KATislem = $KATSorgu->fetchALL(PDO::FETCH_ASSOC); ?>
                                        <?php foreach ($KATislem as $KATSonuc) { ?>
                                            <?php $paket_sayisi = $db->query("SELECT id FROM yazilimlar WHERE durum = '1' AND find_in_set({$KATSonuc['id']},kategori) AND dil = '{$_SESSION['k_dil']}'")->rowCount(); ?>
                                            <option value="paketler.html?kategori=<?php echo $KATSonuc['seo']; ?>"><?php echo $KATSonuc['adi']; ?> (<?php echo $paket_sayisi; ?>)</option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <!-- Categories Widget End -->
                            </aside>
                            <!-- SIDEBAR AREA END HERE -->
                        </div>
                    </div>


                    <div class="paginationn-box-row">
                        <p><?php echo $total; ?> <?= @$dil['txt162']; ?> <?php echo $page; ?> - <?php echo $limitayar['limit_sayfapaketler']; ?> <?= @$dil['txt163']; ?></p>
                        <ul class="paginationn">
                            <?php
                            if ($limitayar['limit_sayfapaketler'] < $total) {
                                $showing = 3;
                                if ($page > 1) { ?>
                                    <?php $previous = $page - 1; ?>
                                    <?php if (strip_tags(isset($_GET['kategori']))) { ?>
                                        <li><a href="paketler.html?kategori=<?php echo $_GET['kategori']; ?>&s=<?php echo $previous; ?>"><i class="fa fa-angle-double-left"></i></a></li>
                                    <?php } else { ?>
                                        <li><a href="paketler/<?php echo $previous; ?>.html"><i class="fa fa-angle-double-left"></i></a></li>
                                    <?php } ?>
                                    <?php }
                                for ($i = $page - $showing; $i < $page + $showing + 1; $i++) {
                                    if ($i > 0 and $i <= $page_count) {
                                        if ($i == $page) { ?>
                                            <li class="active"><a href="javascript:void(0);"><?php echo $i; ?></a></li>
                                        <?php } else { ?>
                                            <?php if (strip_tags(isset($_GET['kategori']))) { ?>
                                                <li><a href="paketler.html?kategori=<?php echo $_GET['kategori']; ?>&s=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                                            <?php } else { ?>
                                                <li><a href="paketler/<?php echo $i; ?>.html"><?php echo $i; ?></a></li>
                                            <?php } ?>
                                    <?php }
                                    }
                                }
                                if ($page != $page_count) { ?>
                                    <?php $next = $page + 1; ?>
                                    <?php if (strip_tags(isset($_GET['kategori']))) { ?>
                                        <li><a href="paketler.html?kategori=<?php echo $_GET['kategori']; ?>&s=<?php echo $next; ?>"><i class="fa fa-angle-double-right"></i></a></li>
                                    <?php } else { ?>
                                        <li><a href="paketler/<?php echo $next; ?>.html"><i class="fa fa-angle-double-right"></i></a></li>
                                    <?php } ?>
                            <?php }
                            } ?>
                        </ul>
                    </div>
                    <!-- PAGER  END HERE -->
                </div>
            </div>
        </div>

    </div>
</div>
<?php if (isset($_GET['durum'])) : ?>
    <script>
        swal({
            type: 'success',
            title: 'Başarılı',
            text: 'Web Paketi Sepetinize Eklendi',
            confirmButtonText: 'Tamam',
            timer: 5000
        })
    </script>
<?php endif; ?>