<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null; ?>
<?php
if (strip_tags(isset($_GET['id']))) {
    $Sorgu = $db->prepare("SELECT * FROM yazilimlar WHERE seo = ? AND durum = ? AND dil = ?");
    $Sorgu->execute(array($_GET['id'], 1, $_SESSION['k_dil']));
    if ($Sorgu->rowCount()) {
        $Sonuc         = $Sorgu->fetch(PDO::FETCH_ASSOC);
        // Sayaç Başlangıç
        $bugunGiris = $db->query("SELECT * FROM yazilim_hit WHERE ip='{$sayacip}' AND tarih='{$sayactarih}' AND yid='{$Sonuc['id']}'")->rowCount(); // bugün o ip ile girilmişmi
        if ($bugunGiris == 0) { // yani bugün girilmişse
            $db->query("INSERT INTO yazilim_hit SET yid ='{$Sonuc['id']}', tarih='{$sayactarih}', ay='{$sayacay}', yil='{$sayacyil}', simdi='" . time() . "', sayac='1',ip='{$sayacip}'");
        }
        $izlenme     = $db->query("SELECT * FROM yazilim_hit WHERE yid='{$Sonuc['id']}'")->rowCount();
        $Favori        = $db->query("SELECT * FROM favoriler WHERE icerikid = '{$Sonuc['id']}' AND ilanid = '{$Bilgilerim['id']}'")->fetch(PDO::FETCH_ASSOC);
    } else {
        header("Location:" . $url . "/404.html");
    }
} else {
    $Sorgu = $db->prepare("SELECT * FROM yazilimlar WHERE durum = ? AND dil = ? ORDER BY id ASC");
    $Sorgu->execute(array(1, $_SESSION['k_dil']));
    if ($Sorgu->rowCount()) {
        $Sonuc         = $Sorgu->fetch(PDO::FETCH_ASSOC);
        // Sayaç Başlangıç
        $bugunGiris = $db->query("SELECT * FROM yazilim_hit WHERE ip='{$sayacip}' AND tarih='{$sayactarih}' AND yid='{$Sonuc['id']}'")->rowCount(); // bugün o ip ile girilmişmi
        if ($bugunGiris == 0) { // yani bugün girilmişse
            $db->query("INSERT INTO yazilim_hit SET yid ='{$Sonuc['id']}', tarih='{$sayactarih}', ay='{$sayacay}', yil='{$sayacyil}', simdi='" . time() . "', sayac='1',ip='{$sayacip}'");
        }
        $izlenme         = $db->query("SELECT * FROM yazilim_hit WHERE yid='{$Sonuc['id']}'")->rowCount();
        $Favori        = $db->query("SELECT * FROM favoriler WHERE icerikid = '{$Sonuc['id']}' AND ilanid = '{$Bilgilerim['id']}'")->fetch(PDO::FETCH_ASSOC);
    } else {
        header("Location:" . $url . "/404.html");
    }
}
$menubul     = $db->query("SELECT * FROM menu WHERE menu_url = 'paketler.html' OR link = 'paketler.html' AND dil = '{$_SESSION['k_dil']}'")->fetch(PDO::FETCH_ASSOC);
$menubas     = $db->query("SELECT * FROM menu WHERE id = '{$menubul['menu_ust']}' AND dil = '{$_SESSION['k_dil']}'")->fetch(PDO::FETCH_ASSOC);
$oldPrice = $Sonuc['tutar'];
$Sonuc['tutar'] = bayilikindirimi($_SESSION['site_uyeid'], $Sonuc['tutar']);
?>
<!-- ***** SLIDER ***** -->
<div class="top-header overlay" style="background-image: url(<?php echo tema; ?>/uploads/arkaplan/paketler/<?php echo $arkaplan['paketler'] ?>);">
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
<!-- ***** PRICING TABLES ***** -->
<section class="section single-wrap">
    <div class="container">

        <div class="row">
            <div id="singlewrapper" class="col-md-8">

                <div class="content nopad">
                    <div class="item-single-wrapper">
                        <div class="item-box">
                            <div class="item-media text-center">
                                <ul class="pgwSlider">
                                    <li><img src="<?php echo tema; ?>/uploads/webpaketleri/kapak/<?php echo $Sonuc['resim']; ?>" data-large-src="<?php echo tema; ?>/uploads/webpaketleri/kapak/<?php echo $Sonuc['resim']; ?>"></li>
                                    <?php $ISorgu = $db->prepare("SELECT * FROM webpaketresim WHERE rid = ? ORDER BY id ASC");
                                    $ISorgu->execute(array($Sonuc['id']));
                                    $Iislem = $ISorgu->fetchALL(PDO::FETCH_ASSOC); ?>
                                    <?php foreach ($Iislem as $ISonuc) { ?>
                                        <li><img src="<?php echo tema; ?>/uploads/webpaketleri/<?php echo $ISonuc['resim']; ?>" data-large-src="<?php echo tema; ?>/uploads/webpaketleri/<?php echo $ISonuc['resim']; ?>"></li>
                                    <?php } ?>
                                </ul>
                            </div><!-- end item-media -->
                            <div class="clearfix"></div>
                            <div class="clearfix"></div>
                            <div class="boxes boxs filtrecategory">
                                <div class="item-price text-center test">
                                    <?php if ($Sonuc['tutar'] != $oldPrice) { ?>
                                        <p><b style="font-size:18px;margin-right:8px;"><del><?php echo my_number_format($oldPrice) ?> TL</del></b><?php echo my_number_format($Sonuc['tutar']) ?> TL <b style="font-size:18px">+ KDV</b></p>
                                    <?php } else { ?>
                                        <p><b style="font-size:18px;margin-right:8px;"></b><?php echo my_number_format($Sonuc['tutar']) ?> TL <b style="font-size:18px">+ KDV</b></p>
                                    <?php } ?>

                                    <span><?= @$dil['txt228']; ?></span>
                                    <hr>
                                    <small>
                                        <?php if ($Favori) { ?>
                                            <a href="_class/site_islem.php?favoricikar=ok&id=<?php echo $Sonuc['id']; ?>&link=detay/<?php echo $Sonuc['seo']; ?>.html"><i class="fa fa-star-o"></i> <?= @$dil['txt229']; ?></a> &nbsp;&nbsp;
                                        <?php } else { ?>
                                            <a href="_class/site_islem.php?favoriekle=ok&ekleid=<?php echo $Sonuc['id']; ?>v<?php echo $Sonuc['seo']; ?>.html&devam=detay/<?php echo $Sonuc['seo']; ?>"><i class="fa fa-star-o"></i> <?= @$dil['txt230']; ?></a> &nbsp;&nbsp;
                                        <?php } ?>
<span class="meta-download">
							<i class="fa fa-eye"></i><?php echo $izlenme;?>
						</span>                                    </small>
                                    <hr>
                                    <?php if ($Sonuc['demo_link']) { ?><a target="_blank" href="<?php echo $Sonuc['demo_link'] ?>" class="btn btn-2 btn-default d-block mb-3"><?= @$dil['txt232']; ?></a><?php } ?>
                                    <?php if ($Sonuc['demo_admin_link']) { ?><a target="_blank" href="<?php echo $Sonuc['demo_admin_link'] ?>" class="btn btn-2 btn-default d-block mb-3"><?= @$dil['txt233']; ?></a><?php } ?>
                                    <?php if ($moduller['alan6'] == "1") { ?>
                                        <a href="web-paket-satinal/<?php echo $Sonuc['id']; ?>.html" class="btn btn-2 btn-primary d-block"><?= @$dil['txt76']; ?></a>
                                        <form style="width: 100%;margin-top:10px" action="_class/site_islem.php" method="post">
                                            <input hidden name="urun_id" value="<?php echo $Sonuc['id'] ?>" required readonly>
                                            <button style="width: 100%;" class="btn btn-2 btn-primary d-block" type="submit" name="webpaket_sepet" href="javascript:;"><?= @$dil['txt394']; ?><span><i class="fa fa-shopping-cart"></i></span></button>
                                        </form>
                                    <?php } ?>
                                    <div class="addthis_inline_share_toolbox_34zm list-inline social"></div>
                                </div><!-- end price -->
                            </div><!-- end boxes -->
                            <div class="item-desc">
                                <?php echo $Sonuc['aciklama'] ?>

                                <?php if ($Sonuc['talimat']) { ?>
                                    <div class="panel panel-info">
                                        <div class="panel-leftheading">
                                            <h3 class="panel-lefttitle"><?= @$dil['txt227']; ?></h3>
                                        </div>
                                        <div class="panel-rightbody">
                                            <?php echo $Sonuc['talimat'] ?>
                                        </div>
                                        <div class="clearfix"></div>
                                    </div>
                                <?php } ?>
                            </div><!-- end item-desc -->
                        </div><!-- end item-box -->
                    </div><!-- end item-single-wrapper -->
                </div><!-- end content -->

            </div><!-- end singlewrapper -->
            <div class="col-md-4">
                <div class="boxes boxs filtrecategoryhidden">
                    <div class="item-price text-center">
                        <?php if ($Sonuc['tutar'] != $oldPrice) { ?>
                            <p><b style="font-size:18px;margin-right:8px;"><del><?php echo my_number_format($oldPrice) ?> TL</del></b><?php echo my_number_format($Sonuc['tutar']) ?> TL <b style="font-size:18px">+ KDV</b></p>
                        <?php } else { ?>
                            <p><b style="font-size:18px;margin-right:8px;"></b><?php echo my_number_format($Sonuc['tutar']) ?> TL <b style="font-size:18px">+ KDV</b></p>
                        <?php } ?>

                        <span><?= @$dil['txt228']; ?></span>
                        <hr>
                        <small>
                            <?php if ($Favori) { ?>
                                <a href="_class/site_islem.php?favoricikar=ok&id=<?php echo $Sonuc['id']; ?>&link=detay/<?php echo $Sonuc['seo']; ?>.html"><i class="fa fa-star-o"></i> <?= @$dil['txt229']; ?></a> &nbsp;&nbsp;
                            <?php } else { ?>
                                <a href="_class/site_islem.php?favoriekle=ok&ekleid=<?php echo $Sonuc['id']; ?>&link=detay/<?php echo $Sonuc['seo']; ?>.html&devam=detay/<?php echo $Sonuc['seo']; ?>"><i class="fa fa-star-o"></i> <?= @$dil['txt230']; ?></a> &nbsp;&nbsp;
                            <?php } ?>
                            <span class="meta-download">
							<i class="fa fa-eye"></i><?php echo $izlenme;?>
						</span>
                        </small>
                        <hr>
                        <?php if ($Sonuc['demo_link']) { ?><a target="_blank" href="<?php echo $Sonuc['demo_link'] ?>" class="btn btn-2 btn-default d-block mb-3"><?= @$dil['txt232']; ?></a><?php } ?>
                        <?php if ($Sonuc['demo_admin_link']) { ?><a target="_blank" href="<?php echo $Sonuc['demo_admin_link'] ?>" class="btn btn-2 btn-default d-block mb-3"><?= @$dil['txt233']; ?></a><?php } ?>
                        <?php if ($moduller['alan6'] == "1") { ?>
                            <a href="web-paket-satinal/<?php echo $Sonuc['id']; ?>.html" class="btn btn-2 btn-primary d-block"><?= @$dil['txt76']; ?></a>
                            <form style="width: 100%;margin-top:10px" action="_class/site_islem.php" method="post">
                                <input hidden name="urun_id" value="<?php echo $Sonuc['id'] ?>" required readonly>
                                <button style="width: 100%;" class="btn btn-2 btn-primary d-block" type="submit" name="webpaket_sepet" href="javascript:;"><?= @$dil['txt394']; ?><span><i class="fa fa-shopping-cart"></i></span></button>
                            </form>
                        <?php } ?>
                        <div class="addthis_inline_share_toolbox_34zm list-inline social"></div>
                    </div><!-- end price -->
                </div><!-- end boxes -->
                <?php if ($Sonuc['kisa']) { ?>
                    <div class="boxes boxs">
                        <div class="item-details">
                            <?php echo nl2br($Sonuc['kisa']); ?>
                        </div><!-- end item-details -->
                    </div><!-- end boxes -->
                <?php } ?>
                <?php if ($Sonuc['ozellik']) { ?>
                    <div class="boxes boxs">
                        <div class="item-details">
                            <?php echo nl2br($Sonuc['ozellik']); ?>
                        </div><!-- end item-details -->
                    </div><!-- end boxes -->
                <?php } ?>
                <div class="boxes boxs">
                    <div class="item-details">
                        <table>
                            <tr>
                                <td><?= @$dil['txt234']; ?></td>
                                <td><?php echo TvERtXpE3w_tarih($Sonuc['tarih']); ?></td>
                            </tr>
                            <tr>
                                <td><?= @$dil['txt235']; ?></td>
                                <td>
                                    <div class="product-tags">
                                        <?php $kategoriler = explode(",", $Sonuc['kategori']); ?>
                                        <?php foreach ($kategoriler as $key) { ?>
                                            <?php
                                            $ran = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f');
                                            $color = "#" . $ran[rand(0, 15)] . $ran[rand(0, 15)] . $ran[rand(0, 15)] . $ran[rand(0, 15)] . $ran[rand(0, 15)] . $ran[rand(0, 15)]; ?>
                                            <?php $kategoriler    = $db->query("SELECT * FROM web_kategori WHERE id = '{$key}'")->fetch(PDO::FETCH_ASSOC); ?>
                                            <a style="color:<?php echo $color; ?>" href="paketler.html?kategori=<?php echo $kategoriler['seo']; ?>"><?php echo $kategoriler['adi']; ?></a>
                                        <?php } ?>
                                    </div><!-- en tags -->
                                </td>
                            </tr>
                            <tr>
                                <td><?= @$dil['txt179']; ?>:</td>
                                <td>
                                    <div class="product-tags">
                                        <?php $etiketler = explode(",", $Sonuc['etiketler']); ?>
                                        <?php foreach ($etiketler as $key) { ?>
                                            <?php
                                            $ran = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f');
                                            $color = "#" . $ran[rand(0, 15)] . $ran[rand(0, 15)] . $ran[rand(0, 15)] . $ran[rand(0, 15)] . $ran[rand(0, 15)] . $ran[rand(0, 15)]; ?>
                                            <a style="color:<?php echo $color; ?>" href="detay/<?php echo $Sonuc['seo']; ?>.html"><?php echo $key; ?></a>
                                        <?php } ?>
                                    </div><!-- en tags -->
                                </td>
                            </tr>

                        </table>
                    </div><!-- end item-details -->
                </div><!-- end boxes -->
            </div><!-- end sidebar -->
        </div><!-- end row -->
    </div><!-- end container -->
</section>
<script>
    $(document).ready(function() {
        $('.pgwSlider').pgwSlider();
    });
</script>
<?php
if ($_SESSION['favoriekle'] == 'yes') {
    echo "
	<script>
	swal({
		type: 'success',
		title: '" . @$dil['txt11'] . "',
		text: '" . @$dil['txt236'] . "',
		confirmButtonText: '" . @$dil['txt15'] . "',
		timer: 5000
	})
	</script>";
    unset($_SESSION['favoriekle']);
}
if ($_SESSION['favoriekle'] == 'no') {
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
    unset($_SESSION['favoriekle']);
}
if ($_SESSION['favoricikar'] == 'yes') {
    echo "
	<script>
	swal({
		type: 'success',
		title: '" . @$dil['txt11'] . "',
		text: '" . @$dil['txt237'] . "',
		confirmButtonText: '" . @$dil['txt15'] . "',
		timer: 5000
	})
	</script>";
    unset($_SESSION['favoricikar']);
}
if ($_SESSION['favoricikar'] == 'no') {
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
    unset($_SESSION['favoricikar']);
}
?>