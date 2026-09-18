<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null;?>
<?php
if(strip_tags(isset($_GET['id'])))
{
	$Sorgu = $db->prepare("SELECT * FROM referanslar WHERE seo = ? AND durum = ? AND dil = ?");
	$Sorgu->execute(array($_GET['id'],1,$_SESSION['k_dil']));
	if($Sorgu->rowCount()){
		$Sonuc 		= $Sorgu->fetch(PDO::FETCH_ASSOC);
	}else{
		header("Location:".$url."/404.html");
	}
}
else
{
	$Sorgu = $db->prepare("SELECT * FROM referanslar WHERE durum = ? AND dil = ? ORDER BY id ASC");
	$Sorgu->execute(array(1,$_SESSION['k_dil']));
	if($Sorgu->rowCount()){
		$Sonuc 		= $Sorgu->fetch(PDO::FETCH_ASSOC);
	}else{
		header("Location:".$url."/404.html");
	}
}
$menubul 	= $db->query("SELECT * FROM menu WHERE menu_url = 'referanslar.html' OR link = 'referanslar.html' AND dil = '{$_SESSION['k_dil']}'")->fetch(PDO::FETCH_ASSOC);
$menubas 	= $db->query("SELECT * FROM menu WHERE id = '{$menubul['menu_ust']}' AND dil = '{$_SESSION['k_dil']}'")->fetch(PDO::FETCH_ASSOC);	
?>
<!-- ***** BANNER ***** -->
<div class="top-header overlay" style="background-image: url(<?php echo tema;?>/uploads/arkaplan/referanslar/<?php echo $arkaplan['referanslar']?>)">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper">
                    <h1 class="heading"><?php echo $Sonuc['adi'];?></h1>
                    <h3 class="subheading"><?php echo $Sonuc['kisa']?></h3>
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
        <a href="<?php echo $sayfalink;?>" class="golink gocheck"> <?php echo $Sonuc['adi']?></a>
	</div>
</div>

<!-- ***** FEATURES ***** -->
<section id="features" class="history-section sec-normal ">
    <div class="container">
        <div class="sec-main sec-bg1">
            <div class="row">
                <div class="col-md-12">
                    <div class="info-content referans-icerik">

                        <?php echo $Sonuc['aciklama']?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<style>
    .referans-icerik img{
        max-width: 100%;
        height: auto;
    }
</style>