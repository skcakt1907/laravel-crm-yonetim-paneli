<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null;?>
<?php
if(strip_tags(isset($_GET['id'])))
{
	$Sorgu = $db->prepare("SELECT * FROM sayfalar WHERE seo = ? AND durum = ? AND dil = ?");
	$Sorgu->execute(array($_GET['id'],1,$_SESSION['k_dil']));
	if($Sorgu->rowCount()){
		$Sonuc 		= $Sorgu->fetch(PDO::FETCH_ASSOC);
	}else{
		header("Location:".$url."/404.html");
	}
}
else
{
	$Sorgu = $db->prepare("SELECT * FROM sayfalar WHERE durum = ? AND dil = ? ORDER BY id ASC");
	$Sorgu->execute(array(1,$_SESSION['k_dil']));
	if($Sorgu->rowCount()){
		$Sonuc 		= $Sorgu->fetch(PDO::FETCH_ASSOC);
	}else{
		header("Location:".$url."/404.html");
	}
}
$menubul 	= $db->query("SELECT * FROM menu WHERE menu_url = 'sayfa/".$Sonuc['seo'].".html' OR link = 'sayfa/".$Sonuc['seo'].".html' AND dil = '{$_SESSION['k_dil']}'")->fetch(PDO::FETCH_ASSOC);
$menubas 	= $db->query("SELECT * FROM menu WHERE id = '{$menubul['menu_ust']}' AND dil = '{$_SESSION['k_dil']}'")->fetch(PDO::FETCH_ASSOC);		
?>
<!-- ***** BANNER ***** -->
<div class="top-header overlay" style="background-image: url(<?php echo tema;?>/uploads/arkaplan/sayfalar/<?php echo $arkaplan['sayfalar']?>)">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper">
                    <h1 class="heading"><?php echo $Sonuc['adi'];?></h1>
                    <h3 class="subheading"><?=@$dil['txt339'];?></h3>
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
<!-- ***** TYPOGRAPHY ***** -->
<section class="tipography-ex sec-normal sec-bg1">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
				<?php if($Sonuc['resim'] != ""){?>
				<div class="action-content">
					<img src="<?php echo tema;?>/uploads/sayfalar/<?php echo $Sonuc['resim'];?>" alt="<?php echo $Sonuc['adi'];?>" class="img-responsive">
				</div>
				<?php }?>
                <h2 class="section-heading"><?php echo $Sonuc['adi'];?></h2>
                <div class="sayfa-icerik">
                    <?php echo str_replace("../uploads/", "uploads/", $Sonuc['aciklama']); ?>
                </div>
            </div>
        </div>
    </div>
</section>
<style>
    .sayfa-icerik img{
        max-width: 100%;
        height: auto;
    }
</style>