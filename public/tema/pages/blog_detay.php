<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null;?><?php
if(strip_tags(isset($_GET['id'])))
{
	$DETAYSorgu = $db->prepare("SELECT * FROM blog WHERE seo = ? AND dil = ?");
	$DETAYSorgu->execute(array($_GET['id'],$_SESSION['k_dil']));
	if($DETAYSorgu->rowCount())
	{
		$DETAYSonuc = $DETAYSorgu->fetch(PDO::FETCH_ASSOC);
		// Sayaç Başlangıç   
		$bugunGiris =$db->query("SELECT * FROM blog_hit WHERE ip='{$sayacip}' AND tarih='{$sayactarih}' AND blogid='{$DETAYSonuc['id']}'")->rowCount(); // bugün o ip ile girilmişmi   
		if($bugunGiris == 0)
		{ // yani bugün girilmişse  
			$db->query("INSERT INTO blog_hit SET blogid ='{$DETAYSonuc['id']}', tarih='{$sayactarih}', ay='{$sayacay}', yil='{$sayacyil}', simdi='".time()."', sayac='1',ip='{$sayacip}'");   
		}
		$izlenme 		= $db->query("SELECT * FROM blog_hit WHERE blogid='{$DETAYSonuc['id']}'")->rowCount();
		$yorumsayisi	= $db->query("SELECT * FROM  yorumlar WHERE icerik_id = '{$DETAYSonuc['id']}' AND durum = '1'")->rowCount();

	}
	else
	{
		header("Location:".$url."/404.html");
	}
}
else
{
	$DETAYSorgu = $db->prepare("SELECT * FROM blog WHERE dil = ? ORDER BY id ASC");
	$DETAYSorgu->execute(array($_SESSION['k_dil']));
	if($DETAYSorgu->rowCount())
	{
		$DETAYSonuc = $DETAYSorgu->fetch(PDO::FETCH_ASSOC);
		// Sayaç Başlangıç   
		$bugunGiris =$db->query("SELECT * FROM blog_hit WHERE ip='{$sayacip}' AND tarih='{$sayactarih}' AND blogid='{$DETAYSonuc['id']}'")->rowCount(); // bugün o ip ile girilmişmi   
		if($bugunGiris == 0)
		{ // yani bugün girilmişse  
			$db->query("INSERT INTO haber_hit SET blogid ='{$DETAYSonuc['id']}', tarih='{$sayactarih}', ay='{$sayacay}', yil='{$sayacyil}', simdi='".time()."', sayac='1',ip='{$sayacip}'");   
		}
		$izlenme =$db->query("SELECT * FROM blog_hit WHERE blogid='{$DETAYSonuc['id']}'")->rowCount();
		$yorumsayisi = $db->query("SELECT * FROM  yorumlar WHERE icerik_id = '{$DETAYSonuc['id']}' AND durum = '1'")->rowCount();
	}
	else
	{
		header("Location:".$url."/404.html");
	}
}
$menubul 	= $db->query("SELECT * FROM menu WHERE menu_url = 'blog.html' OR link = 'blog.html' AND dil = '{$_SESSION['k_dil']}'")->fetch(PDO::FETCH_ASSOC);
$menubas 	= $db->query("SELECT * FROM menu WHERE id = '{$menubul['menu_ust']}' AND dil = '{$_SESSION['k_dil']}'")->fetch(PDO::FETCH_ASSOC);	
?>
<div class="top-header overlay" style="background-image: url(<?php echo tema;?>/uploads/arkaplan/blog/<?php echo $arkaplan['blog']?>)">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper">
                    <h1 class="heading"><?php echo $DETAYSonuc['adi'];?></h1>
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
		<a href="blog.html" class="golink gocheck"> <?=@$dil['txt156'];?> </a> <small class="c-white">&#9679;</small>
        <a href="<?php echo $sayfalink;?>" class="golink gocheck"><?php echo $DETAYSonuc['adi'];?> </a>
	</div>
</div>
<!-- ***** BLOG DETAILS ***** -->
<section class="shopping blog sec-normal sec-bg2 ">
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <div class="wrap-blog">
                    <div class="row">
                        <div class="col-md-12 col-lg-12">
                            <div class="sec-normal pt-0">
                                <div class="sec-main sec-bg1">
                                    <div class="action-content">
                                        <div class="action">
                                            <div class="metatag">
                                                <div class="kudos">
                                                    <a href="javascript:void(0)"><i class="far fa-comments pl-0"></i> <?php echo $yorumsayisi;?></a>
                                                    <a href="javascript:void(0)"><i class="icon-eye"></i> <?php echo $izlenme;?></a>
                                                </div>
                                            </div>
                                        </div>
										<?php if($DETAYSonuc['resim']){?>
										<img src="<?php echo tema;?>/uploads/bloglar/<?php echo $DETAYSonuc['resim']; ?>" alt="<?php echo $DETAYSonuc['adi'];?>" class="img-responsive">
										<?php }else{?>
										<img src="<?php echo tema;?>/assets/img/noimage.png" alt="<?php echo $DETAYSonuc['adi'];?>" class="img-responsive">
										<?php }?>
                                    </div>
                                    <div class="row text-blog">
                                        <div class="col-sm-12 col-md-12 col-lg-6 p-0">
                                            <div class="timer">
                                                <i class="icon-calendar"></i>
                                                <span class="pl-2 pr-4"> <?php echo TvERtXpE3w_tarih($DETAYSonuc['tarih']);?></span>
                                                <i class="far fa-comments"></i>
                                                <span class="pl-2 pr-4"> <?php echo $yorumsayisi;?> <?=@$dil['txt164'];?></span>
												<i class="icon-eye"></i>
                                                <span class="pl-2"> <?php echo $izlenme;?> <?=@$dil['txt165'];?></span>
                                            </div>
                                        </div>
                                        <div class="col-sm-12 col-md-12 col-lg-6 p-0">
                                            <div class="addthis_inline_share_toolbox_34zm text-right"></div>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="heading blog"><a href="<?php echo $sayfalink;?>"><?php echo $DETAYSonuc['adi'];?></a></div>
                                    <div class="blog-info">
                                        <?php echo str_replace("../uploads/", "uploads/", $DETAYSonuc['aciklama']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 col-lg-12 mb-80">
                            <div class="sec-main sec-bg1">
                                <div class="heading blog"><h3 class="d-inline-block"><?=@$dil['txt166'];?></h3> <h6 class="d-inline-block"><?php echo $yorumsayisi;?> <?=@$dil['txt164'];?></h6></div>
								<?php if($yorumsayisi <= 0){?>
								<div class="alert alert-warning alert-dismissible fade show" role="alert">
								  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
									<span aria-hidden="true">&times;</span>
								  </button>
								  <strong><?=@$dil['txt167'];?></strong> <?=@$dil['txt168'];?>
								</div>
								<?php }?>
								<?php
								function CocuklariGetir($ust_id, $icerik_id, $st = 1, $db)
								{
									global $db;
									$child = $db->prepare("SELECT * FROM yorumlar WHERE durum = ? AND ustid = ? AND icerik_id = ? ORDER BY id ASC");
									$child->execute(array("1", $ust_id, $icerik_id));
									$childislem = $child->fetchALL(PDO::FETCH_ASSOC);
									$html = "";
									foreach ( $childislem as $children )
									{
										$html.= '<hr>
										 <div class="media answer w-100">
												<a href="javascript:void(0)" id="'. $children["id"] .'" data-id="<strong>@'.$children['adi'].'</strong> " class="plans badge badge-pill feat bg-green comment-reply-link">'.@$dil['txt169'].'</a>
												<img class="media-object" src="'.tema.'/img/avatar.jpg" alt="'. $children['adi'] .'">
												<div class="media-body">
													<h4 class="media-heading"><a href="">'. $children['adi'] .'</a></h4>
													<div class="text-blog mt-0">
														<i class="icon-calendar"></i>
														<span class="pl-2 pr-4"> '.TvERtXpE3w_tarih($children['tarih']).'</span>
													</div>
													<div class="text-comments">
														'. $children['yorum'] .'
													</div>
												</div>
											</div>';
											$html.= CocuklariGetir($children['id'], $children['icerik_id'], $st+1, $db);
									}
									return $html;
								}
								?>
								<?php $YSorgu = $db->prepare("SELECT * FROM yorumlar WHERE durum = ? AND icerik_id = ? AND ustid = ? ORDER BY id ASC");
								$YSorgu->execute(array("1",$DETAYSonuc['id'],"0"));
								$islem = $YSorgu->fetchALL(PDO::FETCH_ASSOC);?>
								<?php if($YSorgu->rowCount()){?>
								<?php foreach ( $islem as $YSonuc ){?>
								<hr>								
                                <div class="line"></div>
                                <div class="media w-100">
                                    <a  href="javascript:void(0)" id="<?php echo $YSonuc["id"];?>" data-id="<strong>@<?php echo $YSonuc['adi']; ?></strong> " class="plans badge badge-pill feat bg-green comment-reply-link"><?=@$dil['txt169'];?></a>
                                    <img class="media-object" src="<?php echo tema;?>/img/avatar.jpg" alt="<?php echo $YSonuc['adi']; ?>">
                                    <div class="media-body">
                                        <h4 class="media-heading"><?php echo $YSonuc['adi']; ?></h4>
                                        <div class="text-blog mt-0">
                                            <i class="icon-calendar"></i>
                                            <span class="pl-2 pr-4"> <?php echo TvERtXpE3w_tarih($YSonuc['tarih']); ?></span>
                                        </div>
                                        <div class="text-comments">
                                            <?php echo $YSonuc['yorum']; ?>
                                        </div>
                                    </div>
                                </div>
								
								<?php echo CocuklariGetir($YSonuc['id'], $DETAYSonuc['id'], 1, $db);
								}?> 
								<?php }?> 
                            </div>
                        </div>
                    </div>
                    <div class="sec-main sec-bg1">
                        <div class="randomline">
                            <div class="bigline"></div>
                            <div class="smallline"></div>
                        </div>
         
                </aside>
            </div>
        </div>
    </div>
</section>
<script>
$(document).on('click', '.comment-reply-link', function() {
    var ustid = $(this).attr("id");
    var isim = $(this).attr("data-id");
    $('#ustid').val(ustid);
	$('#yorum').val(isim);
    $('#yorum').focus();
	$("#cevap").show();		
	$("#cevap").html('<a rel="nofollow" id="cancel-comment-reply-link" href="javascript:void(0)">CEVABI İPTAL ETMEK İÇİN TIKLAYINIZ</a>');
});
$(document).on('click', '#cancel-comment-reply-link', function() {
    $('#ustid').val("0");
	$("#cevap").hide();
	$('#yorum').focus();
});
</script>
<?php 
if($_SESSION['yorumbtn'] == 'yes')
{
	echo "
	<script>
	swal({
		type: 'success',
		title: '".@$dil['txt11']."',
		text: '".@$dil['txt181']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";
	unset($_SESSION['yorumbtn']);
}		
if($_SESSION['yorumbtn'] == 'no')
{
	echo "
	<script>
	swal({
		type: 'error',
		title: '".@$dil['txt16']."',
		text: '".@$dil['txt17']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";	
	unset($_SESSION['yorumbtn']);
}
if($_SESSION['yorumbtn'] == 'bos')
{
	echo "
	<script>
	swal({
		type: 'warning',
		title: '".@$dil['txt13']."',
		text: '".@$dil['txt14']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";	
	unset($_SESSION['yorumbtn']);
}
?>