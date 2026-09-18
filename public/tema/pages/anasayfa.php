<!-- ***** SLIDER ***** -->
<section id="owl-demo" class="owl-carousel owl-theme owl-loaded owl-drag">
<?php $Sorgu = $db->prepare("SELECT * FROM slider WHERE durum = ? AND dil = ?");
$Sorgu->execute(array("1",$_SESSION['k_dil']));
$islem = $Sorgu->fetchALL(PDO::FETCH_ASSOC);?>
	<?php foreach ( $islem as $Sonuc ){?>
	<div class="full h-100 overlay" style="background-image:url(<?php echo tema;?>/uploads/slider/<?php echo $Sonuc['resim']?>)">
		<div class="total-grad-grey"></div>
		<div class="vc-parent">
			<div class="vc-child">
				<div class="top-banner">
					<div class="container text-center">
						<h1 class="heading"><?php echo $Sonuc['adi']?></h1>
						<h3 class="subheading"><?php echo $Sonuc['aciklama']?></h3>
						<?php if($Sonuc['url']){?>
						<a <?php echo($Sonuc['sekme'] == 1 ? 'target="_blank"' : '');?> href="<?php echo $Sonuc['url'];?>" class="btn btn-2 btn-default-yellow-fill mr-3"><i class="fas fa-lock pr-1"></i><?=@$dil['txt67'];?></a>
						<?php }?>
					</div>
					<div class="mouseroll">
						<div class="mouse"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php }?>
</section>

<!-- ***** DOMAİN ***** -->
<?php if($moduller['alan1'] == "1"){?>
<?php 
$alanadi 	= $db->query("SELECT * FROM alanadi WHERE id='1'")->fetch(PDO::FETCH_ASSOC);
$uzanti 		= json_decode($alanadi['uzanti']);
$kayit 		= json_decode($alanadi['kayit']);
$yenileme 	= json_decode($alanadi['yenileme']);
?>
<script type="text/javascript" language="javascript">
$(document).ready(
	function(){
		$(".secimYap").click(function(){
			var kontrol = $(this).is(":checked");
			if(kontrol == true){
				$(this).parent("label").removeClass("noselect").addClass("select");
			}else{
				$(this).parent("label").removeClass("select").addClass("noselect");
			}
		});	
		
		$("#domainSorgula").click(
			function(){
				var veri = $("#domainForm").serialize();
				if($("#alanadi").val() == ''){alert("<?=@$dil['txt68'];?>"); return;}
				$("#domainBilgileri").html('<div class="ortala"><img src="<?php echo tema;?>/img/loading.gif" alt="<?=@$dil["txt1"];?>" /></div>');
				$.ajax({type: 'POST', url: '<?php echo tema;?>/data/kontrol.php', data: veri, success: function(gelen) {
					$("#domainBilgileri").html(gelen);
					$(".link").unbind('click');
					$(".link").bind('click',function(){$(this).parent("td").find(".popDiv").fadeIn("normal");});
					$(".close").unbind('click');
					$(".close").bind('click',function(){$(".popDiv").fadeOut("normal");});
				}});
				
			}
		);
		$(".close").click(function(){$(".popDiv").fadeOut("normal");});
	}
);
</script>
<section class="search-domain section-padding p-5">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-lg-9 col-md-12 col-sm-12 col-xs-12 centered wow fadeInUp" data-wow-delay="0.3s">
				<h2 class="mb-4 cl-black text-center"><?=@$dil['txt69'];?></h2>
				<div class="search-domain-content">
					<form action="" id="domainForm">
						<input type="text" name="alanadi" placeholder="<?=@$dil['txt70'];?>" required >
						<select name="uzanti[]">
							<?php if (is_array($uzanti)) :
							foreach ($uzanti as $k => $v) :?>
							<?php $degisken = explode(".", $uzanti[$k]);?>
							<option value="<?=$degisken[1];?><?php echo($degisken[2] == true ? '.' : '');?><?=$degisken[2];?>"><?=@$uzanti[$k];?></option>
							<?php endforeach; endif; ?>
						</select>
						<input type="hidden" name="formVeriAL" value="<?=$_SESSION['formVeriAL'];?>" />
						<button class="bttn btn-fill" type="button" id="domainSorgula"><?=@$dil['txt71'];?></button>
					</form>					
				</div>
				<div class="domain-type">
					<?php if (is_array($uzanti)) :
					foreach ($uzanti as $k => $v) : if($k > 4) continue; ?>
					<div class="single-domain-type">
						<h3><?=@$uzanti[$k];?>/ <span><?=@$kayit[$k];?> <?=@$dil['txt72'];?></span></h3>
					</div>
					<?php endforeach; endif; ?>  
				</div>
				<div class="domainBilgileri" id="domainBilgileri"></div>
			</div>
		</div>
	</div>
</section>


<?php }?>
<!-- ***** YAZILIMLAR ***** -->
<?php if($moduller['alan2'] == "1"){?>
<section class="slick sec-normal pt-5">
	<div class="container">
	<div class="col-md-12 text-center mb-3">
		<h2 class="section-heading"><?=@$dil['txt73'];?></h2>
		<p class="section-subheading"><?=@$dil['txt74'];?></p>
	</div>
	<div class="row">
	<?php $Sorgu = $db->prepare("SELECT * FROM yazilimlar WHERE durum = ? AND anasayfa = ? AND dil = ? ORDER BY sira DESC");
	$Sorgu->execute(array("1","1",$_SESSION['k_dil']));
	$islem = $Sorgu->fetchALL(PDO::FETCH_ASSOC);?>
		<?php foreach ( $islem as $Sonuc ){
		    $oldPrice = $Sonuc['tutar'];
            $Sonuc['tutar'] = bayilikindirimi($_SESSION['site_uyeid'],$Sonuc['tutar']);
            ?>
		<?php $izlenme 	= $db->query("SELECT * FROM yazilim_hit WHERE yid='{$Sonuc['id']}'")->rowCount();?>
		<!-- Single Product Start -->
		<div class="col-md-3 col-sm-3 wow fadeInUp" data-wow-delay="0.3s">
			<div class="item-demo">
				<figure class="paketkapak" style="background-image:url(<?php echo tema;?>/uploads/webpaketleri/kapak/<?php echo $Sonuc['resim']?>)">
					<div class="product-caption">
						<div class="caption-cel">
							<div class="product-link">
								<div>
									<div>
										<a href="detay/<?php echo $Sonuc['seo'];?>.html"><?=@$dil['txt75'];?> <span><i class="fa fa-eye"></i></span></a>
									</div>
									<?php if($moduller['alan6'] == "1"){?>
									<div>
										<a href="web-paket-satinal/<?php echo $Sonuc['id']; ?>.html"><?=@$dil['txt76'];?><span><i class="fa fa-shopping-cart"></i></span></a>
									<?php }?>
								</div>
							</div>
						</div>
					</div>
				</figure>
				<div class="product-info">
					<div class="product-header">
						<h3 class="product-name"><a href="detay/<?php echo $Sonuc['seo'];?>.html"><?php echo $Sonuc['adi']?></a></h3>
						<span class="p-author">
							<?php $kategoriler = explode(",", $Sonuc['kategori']);?>
							<?php foreach ($kategoriler as $key) {?>
							<?php
							$ran=array('0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f');
							$color="#".$ran[rand(0,15)].$ran[rand(0,15)].$ran[rand(0,15)].$ran[rand(0,15)].$ran[rand(0,15)].$ran[rand(0,15)];?>
							<?php $kategoriler	= $db->query("SELECT * FROM web_kategori WHERE id = '{$key}'")->fetch(PDO::FETCH_ASSOC);?>
							<a style="color:<?php echo $color;?>" href="paketler.html?kategori=<?php echo $kategoriler['seo'];?>"><?php echo $kategoriler['adi']; ?></a>
							<?php }?>							
						</span>
					</div>
					<div class="product-meta">
						<span class="meta-download">
							<i class="fa fa-eye"></i><?php echo $izlenme;?>
						</span>
						<?php if($Sonuc['tutar'] != $oldPrice){ ?>
						<span class="paketfiyat"><?php echo my_number_format($Sonuc['tutar']); ?> TL</span><span class="paketfiyat_bayi"><del><?php echo $oldPrice ?> TL</del></span>
						<?php } else{ ?>
						<span class="paketfiyat"><?php echo my_number_format($Sonuc['tutar']); ?> TL</span>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
		<!-- Single Product End -->
		<?php }?>
	</div>	
	<div align="center">
		<h4 class="tableslogan aos-init aos-animate mt-3" data-aos="fade-in"><?=@$dil['txt77'];?> <a href="paketler.html" target="_self"><strong><?=@$dil['txt78'];?></strong></a></h4>
	</div>
	</div>
</section>
<?php }?>
<!-- ***** HAKKIMIZDA ***** -->
<?php if($moduller['alan3'] == "1"){?>
<div class="page-content parallax getready" style="background-image:url(<?php echo tema;?>/uploads/arkaplan/footer/<?php echo $arkaplan['footer']?>)">
    <div class="container">
        <div class="row">
            <div class="col-md-12 wow fadeInUp" data-wow-delay="0.3s">
				<div class="row">
					<div class="col-lg-8">
						<div class="column-support-txt">
							<div class="column-support-title"><?=@$dil['txt79'];?></div>
							<div class="column-support-subtitle">
							<?php echo $kurumsal['kisa'];?>
							</div>
						</div>
					</div>
					<div class="col-lg-4 pt-3">
						<div class="btn-floats mt-5">
							<a href="sayfa/<?php echo $kurumsal['seo']?>.html" class="btn btn-2 btn-default-pink-fill"><?=@$dil['txt80'];?></a>
						</div>
					</div>
				</div>
            </div><!-- .col-md-12 end -->
        </div><!-- .row end -->
    </div><!-- .container end -->
</div>
<?php }?>
<!-- ***** HOSTİNGLER ***** -->
<?php if($moduller['alan4'] == "1"){?>
<?php $HOSTKATSorgu = $db->prepare("SELECT * FROM hosting_kategori WHERE durum = ? AND anasayfa = ? AND dil = ?");
$HOSTKATSorgu->execute(array("1","1",$_SESSION['k_dil']));
$HOSTKATislem = $HOSTKATSorgu->fetchALL(PDO::FETCH_ASSOC);?>
<?php foreach ( $HOSTKATislem as $HOSTKATSonuc ){?>
<section class="slick sec-normal pt-4 wow fadeInUp" data-wow-delay="0.3s">
	<div class="col-sm-12 text-center">
		<h2 class="section-heading"><?php echo $HOSTKATSonuc['adi'];?></h2>
		<p class="section-subheading"><?php echo $HOSTKATSonuc['kisa'];?></p>
	</div>
    <div class="slider">
	<?php $Sorgu = $db->prepare("SELECT * FROM hostingler WHERE durum = ? AND anasayfa = ? AND kategori = ? AND dil = ?");
	$Sorgu->execute(array("1","1",$HOSTKATSonuc['id'],$_SESSION['k_dil']));
	$islem = $Sorgu->fetchALL(PDO::FETCH_ASSOC);?>
		<?php foreach ( $islem as $Sonuc ){?>
        <div class="plan-container">
            <div class="wrapper text-center">
				<div class="top-content p-3">
					<img class="svg mb-3" src="<?php echo tema;?>/fonts/svg/dedicated.svg" alt="linux">
					<div class="title"><?php echo $Sonuc['adi']; ?></div>
					<div class="fromer"><?php echo $HOSTKATSonuc['adi']?></div>
					<div class="price"><?php echo $Sonuc['tutar']; ?> TL <span class="period">/<?php echo($Sonuc['zmnt'] == 0 ? 'Aylık' : '');?> <?php echo($Sonuc['zmnt'] == 2 ? '3 Aylık' : '');?> <?php echo($Sonuc['zmnt'] == 1 ? 'Yıllık' : '');?></span></div>
					<?php if($moduller['alan6'] == "1"){?>
					<a href="hosting-satinal/<?php echo $Sonuc['id']; ?>.html" class="btn btn-2 btn-default-gray-fill"><i class="fas fa-shopping-basket" style="font-size: 15px;"></i> <?=@$dil['txt76'];?></a>
					<?php }?>
				</div>
				<ul class="list-info bg-pink p-3 text-left">
					<?php $parcala = preg_split('/,/', $Sonuc['ozellikler'], null, PREG_SPLIT_NO_EMPTY);
					foreach($parcala as $ozellik){?>
						<li class="pt-1 pb-1"><span class="fas fa-check-circle mr-2"></span> <?php echo $ozellik;?></li>
					<?php }?>
				</ul>
			</div>
        </div>
		<?php }?>
    </div>
</section>
<?php }?>
<?php }?>

<!-- ***** REFERANSLAR ***** -->
<?php if($moduller['alan10'] == "1"){?>
<section class="services sec-normal  sec-bg2 wow fadeInUp" data-wow-delay="0.3s">
	<div class="container">
		<div class="service-wrap">
			<div class="row">
				<div class="col-sm-12 text-center">
					<h2 class="section-heading"><?=@$dil['txt84'];?></h2>
					<p class="section-subheading"><?=@$dil['txt85'];?></p>
				</div>
				<?php $Sorgu = $db->prepare("SELECT * FROM referanslar WHERE durum = ? AND anasayfa = ? AND dil = ? ORDER BY a_sira ASC");
				$Sorgu->execute(array("1","1",$_SESSION['k_dil']));
				$islem = $Sorgu->fetchALL(PDO::FETCH_ASSOC);?>
				<?php foreach ( $islem as $Sonuc ){?>
				<div class="col-sm-12 col-md-4 wow animated fadeInUp fast">
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
		</div>
	</div>
</section>	
<?php }?>

<!-- ***** BLOG ***** -->
<?php if($moduller['alan5'] == "1"){?>
<section class="services blog sec-normal sec-bg3 wow fadeInUp" data-wow-delay="0.3s">
	<div class="container">
		<div class="service-wrap">
			<div class="row">
				<div class="col-sm-12 text-center">
					<h2 class="section-heading text-white"><?=@$dil['txt86'];?></h2>
					<p class="section-subheading text-white"><?=@$dil['txt87'];?></p>
				</div>
				<?php $Sorgu = $db->prepare("SELECT * FROM blog WHERE durum = ? AND anasayfa = ? AND dil = ?");
				$Sorgu->execute(array("1","1",$_SESSION['k_dil']));
				$islem = $Sorgu->fetchALL(PDO::FETCH_ASSOC);?>
				<?php foreach ( $islem as $Sonuc ){?>
				<div class="col-md-4">
					<div class="blog-kapak" style="background:url(<?php echo tema;?>/uploads/bloglar/<?php echo $Sonuc['resim']; ?>);"></div>
					<div class="service-section m-0 p-4">
						<div class="title mt-0"><?php echo $Sonuc['adi']; ?></div>
						<p class="subtitle"><?php echo TvERtXpE3w_kisa($Sonuc['aciklama'],100); ?></p>
						<hr>
						<div class="small">
							<span class="icon-calendar text-dark"></span>
							<span class="pl-2 pr-4"> <?php echo TvERtXpE3w_tarih($Sonuc['tarih']); ?></span>
						</div>
						<a href="blog/<?php echo $Sonuc['seo']; ?>.html" class="btn btn-2 btn-default-yellow-fill"><?=@$dil['txt88'];?></a>
					</div>
				</div>
				<?php }?>
			</div>
		</div>
	</div>
</section>
<?php }?>
<!-- ***** BİLGİLENDİRME ***** -->
<?php if($moduller['alan11'] == "1"){?>
<section class="services help pt-4 pb-80 wow fadeInUp" data-wow-delay="0.3s">
	<div class="container">
		<div class="service-wrap">
			<div class="row">
				<div class="col-sm-12 col-md-6 col-lg-4">
					<div class="help-container">
						<a href="javascript:void(0)" class="help-item">
							<div class="img">
								<img class="svg ico" src="<?php echo tema;?>/fonts/svg/livechat.svg" height="65" alt="">
							</div>
							<div class="inform">
								<div class="title"><?=@$dil['txt89'];?></div>
								<div class="description"><?=@$dil['txt90'];?></div>
							</div>
						</a>
					</div>
				</div>
				<div class="col-sm-12 col-md-6 col-lg-4">
					<div class="help-container">
						<a href="javascript:void(0)" class="help-item">
							<div class="img">
								<img class="svg ico" src="<?php echo tema;?>/fonts/svg/emailopen.svg" height="65" alt="">
							</div>
							<div class="inform">
								<div class="title"><?=@$dil['txt91'];?></div>
								<div class="description"><?=@$dil['txt92'];?></div>
							</div>
						</a>
					</div>
				</div>
				<div class="col-sm-12 col-md-6 col-lg-4">
					<div class="help-container">
						<a href="javascript:void(0)" class="help-item">
							<div class="img">
								<img class="svg ico" src="<?php echo tema;?>/fonts/svg/book.svg" height="65" alt="">
							</div>
							<div class="inform">
								<div class="title"><?=@$dil['txt93'];?></div>
								<div class="description"><?=@$dil['txt94'];?></div>
							</div>
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<?php }?>
