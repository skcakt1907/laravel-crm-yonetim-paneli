<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null;?>
<?php if(!isset($_SESSION["site_uyeid"]))
{
	$_SESSION['devam'] = "bilgilerim";
	header("Location:".$url."/giris.html");
}
else
{
	$islem = strip_tags($_GET['islem']);
	switch ( $islem ) 
	{	 
		case "ozet" :
		$ozet 				= "active";
		$ozet_1 				= 'aria-selected="true"';
		$faturabilgilerim_1 	= 'aria-selected="false"';
		$tercihler_1 		= 'aria-selected="false"';
		$sifredegistir_1 	= 'aria-selected="false"';
		$ozetactive 			= 'show active';
		break;
		 
		case "faturabilgilerim" :
		$faturabilgilerim 		= "active";
		$ozet_1 					= 'aria-selected="false"';
		$faturabilgilerim_1 		= 'aria-selected="true"';
		$tercihler_1 			= 'aria-selected="false"';
		$sifredegistir_1 		= 'aria-selected="false"';
		$faturabilgilerimactive 	= 'show active';
		break;
		
		case "tercihler" :
		$tercihler 			= "active";
		$ozet_1 				= 'aria-selected="false"';
		$faturabilgilerim_1 	= 'aria-selected="false"';
		$tercihler_1 		= 'aria-selected="true"';
		$sifredegistir_1 	= 'aria-selected="false"';
		$tercihleractive 	= 'show active';
		break;
		
		case "sifredegistir" :
		$sifredegistir 		= "active";
		$ozet_1 				= 'aria-selected="false"';
		$faturabilgilerim_1 	= 'aria-selected="false"';
		$tercihler_1 		= 'aria-selected="false"';
		$sifredegistir_1 	= 'aria-selected="true"';
		$sifredegistiractive= 'show active';
		break;

		default: 
		$ozet 				= "active";
		$ozet_1 				= 'aria-selected="true"';
		$faturabilgilerim_1 	= 'aria-selected="false"';
		$tercihler_1 		= 'aria-selected="false"';
		$sifredegistir_1 	= 'aria-selected="false"';
		$ozetactive 			= 'show active';
	}
	unset($_SESSION['devam']);
}
?>
<div class="top-header overlay" style="background-image: url(<?php echo tema;?>/uploads/arkaplan/uyelik/<?php echo $arkaplan['uyelik']?>)">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper">
                    <h1 class="heading"><?=@$dil['txt109'];?></h1>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="ustbanner">
	<div class="container">
		<span><?=@$dil['txt27'];?> <strong><?php echo $Bilgilerim['ad'];?> <?php echo $Bilgilerim['soyad'];?></strong>.
            <?php if($Bilgilerim['bayi']!=0){
                $Bayi = $db->query("SELECT * FROM bayilikler WHERE id='{$Bilgilerim['bayi']}'")->fetch(PDO::FETCH_ASSOC);
            ?>
                (<?=$Bayi['paketadi'];?>)
            <?php } ?>
            <i><?=@$dil['txt28'];?></i></span>

		<div class="ustsil"></div>

		<span class="ustson"><?=@$dil['txt29'];?> <strong> <?php echo TvERtXpE3w_tarih($Bilgilerim['son_giris']);?></strong> <?=@$dil['txt30'];?> <div class="ustsil"></div>
			<?=@$dil['txt31'];?> <strong><?php echo $Bilgilerim['ip'];?></strong></span>
	</div>
</div>

<!-- ***** FILTER PLANS ***** -->
<div class="mixcontainer">
    <div class="container">

		<div id="wrapper" class="mt-4">
		
			<div class="row">
				<div class="col-md-12">
					<div class="col-md-12 border-left-3 main-content">
						<div class="title-area mb-4">
							<h5 class="title">
								<i class="fa fa-user"></i>
								<?=@$dil['txt32'];?>
							</h5>
							<div class="pull-right">
								<strong><a href="hesabim.html"><?=@$dil['txt32'];?> </a></strong> /
								<?=@$dil['txt109'];?>
							</div>
						</div>
						<ul class="nav nav-tabs" id="myTab" role="tablist">
							<li class="nav-item col-6 col-sm-6 col-md-3">
								<a class="nav-link <?php echo $ozet;?>" id="home-tab" data-toggle="tab" href="#home" role="tab" aria-controls="home" <?php echo $ozet_1;?>><i class="fas fa-info"></i> <?=@$dil['txt110'];?></a>
							</li>
							<li class="nav-item col-6 col-sm-6 col-md-3">
								<a class="nav-link <?php echo $faturabilgilerim;?>" id="profile-tab" data-toggle="tab" href="#profile" role="tab" aria-controls="profile" <?php echo $faturabilgilerim_1;?>><i class="fas fa-file-invoice"></i> <?=@$dil['txt111'];?></a>
							</li>
							<li class="nav-item col-6 col-sm-6 col-md-3">
								<a class="nav-link <?php echo $tercihler;?>" id="contact-tab" data-toggle="tab" href="#contact" role="tab" aria-controls="contact" <?php echo $tercihler_1;?>><i class="fas fa-user-check"></i> <?=@$dil['txt112'];?></a>
							</li>
							<li class="nav-item col-6 col-sm-6 col-md-3">
								<a class="nav-link <?php echo $sifredegistir;?>" id="password-tab" data-toggle="tab" href="#password" role="tab" aria-controls="contact" <?php echo $sifredegistir_1;?>><i class="fas fa-key"></i> <?=@$dil['txt113'];?></a>
							</li>
						</ul>
						<div class="tab-content" id="myTabContent">
							<div class="tab-pane fade <?php echo $ozetactive;?> pt-3" id="home" role="tabpanel" aria-labelledby="home-tab">
								<form method="POST" action="_class/site_islem.php" autocomplete="off">
									<div class="row">
										<div class="col-md-6 col-xs-12">

											<div class="hesap_bilgi badge bg-pink mb-4"><?=@$dil['txt114'];?></div>
											<div class="clear"></div>

											<label for="hesap"><?=@$dil['txt115'];?></label>
											<div class="cd-filter-block mb-0">
												<ul class="radio-group radios-filter cd-filter-content list mb-0">
													<li class="mb-0">
														<input value="0" type="radio" name="utipi" id="radio6" <?php if($Bilgilerim['utipi'] == '0') {?> checked <?php } ?>>
														<label class="radio-label" for="radio6">Bireysel</label>
													</li>
													<li class="mb-0">
														<input value="1" type="radio" name="utipi" id="radio7" <?php if($Bilgilerim['utipi'] == '1') {?> checked <?php } ?>>
														<label class="radio-label" for="radio7">Kurumsal</label>
													</li>
												</ul>
											</div>
											<div class="form-group">
												<label for="ad"><?=@$dil['txt118'];?></label>
												<input type="text" name="ad" id="ad" class="form-control" value="<?php echo $Bilgilerim['ad']; ?>" placeholder="<?=@$dil['txt118'];?>">
											</div>
											<div class="form-group mt-4">
												<label for="soyad"><?=@$dil['txt119'];?></label>
												<input type="text" name="soyad" id="soyad" class="form-control" value="<?php echo $Bilgilerim['soyad']; ?>" placeholder="<?=@$dil['txt119'];?>">
											</div>
											<div class="form-group mt-4">
												<label for="email"><?=@$dil['txt120'];?></label>
												<input type="email" name="email" id="email" class="form-control" value="<?php echo $Bilgilerim['email']; ?>" placeholder="<?=@$dil['txt120'];?>">
											</div>
											<div class="form-group mt-4">
												<label for="telefon"><?=@$dil['txt121'];?></label>
												<input type="text" name="telefon" id="telefon" class="form-control telefonmask" value="<?php echo $Bilgilerim['telefon']; ?>" placeholder="<?=@$dil['txt121'];?>">
											</div>
											<div class="form-group mt-4">
												<label for="tc"><?=@$dil['txt122'];?></label>
												<input type="text" name="tc" id="tc" class="form-control tc" maxlength="11" value="<?php echo $Bilgilerim['tc']; ?>" placeholder="<?=@$dil['txt122'];?>">
											</div>

										</div>

										<div class="col-md-6 col-xs-12" id="bireysel-bilgiler">

											<div class="hesap_bilgi badge bg-pink mb-4"><?=@$dil['txt123'];?></div>
											<div class="clear"></div>

											<div id="kurumsal-bilgiler" style="display: none">

												<div class="form-group mt-4">
													<label for="firmaadi"><?=@$dil['txt124'];?></label>
													<input type="text" name="firmaadi" id="firmaadi" class="form-control" value="<?php echo $Bilgilerim['firmaadi']; ?>" placeholder="<?=@$dil['txt124'];?>">
												</div>
												<div class="form-group mt-4">
													<label for="unvan"><?=@$dil['txt125'];?></label>
													<input type="text" name="vergino" id="vergino" class="form-control tc" value="<?php echo $Bilgilerim['vergino']; ?>" placeholder="<?=@$dil['txt125'];?>">
												</div>
												<div class="form-group mt-4">
													<label for="vergidairesi"><?=@$dil['txt126'];?></label>
													<input type="text" name="vergidairesi" id="vergidairesi" class="form-control" value="<?php echo $Bilgilerim['vergidairesi']; ?>" placeholder="<?=@$dil['txt126'];?>">
												</div>
											</div>

											<label for="hesap"><?=@$dil['txt127'];?></label>
											<div class="cd-filter-block mb-0">
												<ul class="radio-group radios-filter cd-filter-content list mb-0">
													<li class="mb-0">
														<input value="Erkek" type="radio" name="cinsiyet" id="cinsiyet_1" <?php if($Bilgilerim['cinsiyet'] == 'Erkek') {?> checked <?php } ?>>
														<label class="radio-label" for="cinsiyet_1">Erkek</label>
													</li>
													<li class="mb-0">
														<input value="Kadın" type="radio" name="cinsiyet" id="cinsiyet_2" <?php if($Bilgilerim['cinsiyet'] == 'Kadın') {?> checked <?php } ?>>
														<label class="radio-label" for="cinsiyet_2">Kadın</label>
													</li>
												</ul>
											</div>

											<div class="form-group">
												<label for="dtarih"><?=@$dil['txt130'];?></label>
												<input type="text" name="dtarih" id="dtarih" class="form-control date" value="<?php echo $Bilgilerim['dtarih']; ?>" placeholder="00/00/0000" maxlength="10" />
											</div>
											<div class="form-group mt-4">
												<label for="nereden_duydunuz"><?=@$dil['txt131'];?></label>
												<select class="form-control" name="nereden_duydunuz">
													<option value="">Seçiniz</option>
													<option <?php if($Bilgilerim['nereden_duydunuz'] == 'Google') {?> selected <?php } ?>><?=@$dil['txt132'];?></option>
													<option <?php if($Bilgilerim['nereden_duydunuz'] == 'R10.Net') {?> selected <?php } ?>><?=@$dil['txt133'];?></option>
													<option <?php if($Bilgilerim['nereden_duydunuz'] == 'Diğer Webmaster siteleri') {?> selected <?php } ?>><?=@$dil['txt134'];?></option>
													<option <?php if($Bilgilerim['nereden_duydunuz'] == 'Sosyal Medya') {?> selected <?php } ?>><?=@$dil['txt134_1'];?></option>
													<option <?php if($Bilgilerim['nereden_duydunuz'] == 'Arkadaşımdan') {?> selected <?php } ?>><?=@$dil['txt134_2'];?></option>
													<option <?php if($Bilgilerim['nereden_duydunuz'] == 'Diğer') {?> selected <?php } ?>><?=@$dil['txt134_3'];?></option>
												</select>
											</div>
											<div class="form-group mt-4">
												<label for="notlar"><?=@$dil['txt135'];?></label>
												<textarea rows="5" name="notlar" id="notlar" class="form-control" placeholder="<?=@$dil['txt135'];?>"><?php echo $Bilgilerim['notlar']; ?></textarea>
											</div>
										</div>
										<div class="col-12">
											<input class="btn btn-success pull-right" name="hesap_genel_guncelle" type="submit" value="<?=@$dil['txt103'];?>">
										</div>
									</div>
									
								</form>
							</div>
							<div class="tab-pane fade <?php echo $faturabilgilerimactive;?>" id="profile" role="tabpanel" aria-labelledby="profile-tab">

								<a href="#" data-remodal-target="modal" class="btn btn-outline-primary btn-sm mt-4">
									<i class="fa fa-plus"></i> <?=@$dil['txt41'];?>
								</a>
								<div class="remodal pt-3" data-remodal-id="modal">
									<a data-remodal-action="close" class="remodal-close"></a>
									<h5 class="pb-3 border-bottom"><?=@$dil['txt41'];?></h5>
									<form action="_class/site_islem.php" style="text-align: left" method="post" autocomplete="off">
										<div class="row">
											<input type="hidden" name="adresurl" value="<?php echo $_SERVER['REQUEST_URI'];?>" />
											<div class="form-group col-4">
												<label for="il"><?=@$dil['txt42'];?></label>
												<input class="form-control" type="text" id="il" name="il" placeholder="<?=@$dil['txt42'];?>" required>
											</div>
											<div class="form-group col-4">
												<label for="ilce"><?=@$dil['txt43'];?></label>
												<input class="form-control" type="text" id="ilce" name="ilce" placeholder="<?=@$dil['txt43'];?>" required>
											</div>
											<div class="form-group col-4">
												<label for="pkodu"><?=@$dil['txt44'];?></label>
												<input class="form-control" type="text" id="pkodu" name="pkodu" placeholder="<?=@$dil['txt44'];?>">
											</div>

											<div class="form-group col-12">
												<label for="adres"><?=@$dil['txt45'];?></label>
												<textarea class="form-control" name="adres" placeholder="<?=@$dil['txt45'];?>" id="adres" style="height: 200px;"></textarea>
											</div>

											<div class="form-group col-12">
												<label for="varsayilan"><?=@$dil['txt46'];?></label>
												<input type="checkbox" value="1" name="varsayilan" id="varsayilan">
												<input name="adres_ekle" class="btn btn-primary pull-right" type="submit" value="<?=@$dil['txt46'];?>">
											</div>

										</div>
									</form>
								</div>

								<table class="table table-striped table-bordered mt-3">

									<thead>
									<tr>
										<th scope="col">Adres</th>
										<th scope="col">İşlem</th>
									</tr>
									</thead>
									<tbody>
									<?php $ADRESSorgu = $db->prepare("SELECT * FROM adresler WHERE uyeid = ? ORDER BY varsayilan DESC");
									$ADRESSorgu->execute(array($Bilgilerim['id']));
									$ADRESislem = $ADRESSorgu->fetchALL(PDO::FETCH_ASSOC);?>
									<?php if($ADRESSorgu->rowCount()){?>
									<?php foreach ( $ADRESislem as $ADRESSonuc ){?>
									<tr>
										<th scope="row" class="align-middle">
											<p class="link"><?php echo($Bilgilerim['firmaadi'] != "" ? $Bilgilerim['firmaadi'] : $Bilgilerim['ad']." ".$Bilgilerim['soyad']);?></p>
											<p class="t-detail"><?php echo $ADRESSonuc['adres'];?> / <?php echo $ADRESSonuc['ilce'];?> / <?php echo $ADRESSonuc['il'];?> - <?php echo $ADRESSonuc['pkodu'];?> <?php if($ADRESSonuc['varsayilan'] == '1') {?> <strong>(<?=@$dil['txt46'];?>)</strong> <?php } ?></p>
										</th>

										<td class="text-center align-middle">
											<a href="#" data-remodal-target="adres_duzenle_<?php echo $ADRESSonuc['id'];?>" class="btn btn-outline-primary btn-sm">
												<i class="fa fa-edit"></i>
											</a>
											<a href="_class/site_islem.php?adressil=ok&id=<?php echo $ADRESSonuc['id'];?>" class="btn btn-outline-danger btn-sm">
												<i class="fa fa-trash"></i>
											</a>
										</td>
									</tr>
									<!-- Adres Güncelle -->
									<div class="remodal pt-3" data-remodal-id="adres_duzenle_<?php echo $ADRESSonuc['id'];?>">
										<a data-remodal-action="close" class="remodal-close"></a>
										<h5 class="pb-3 border-bottom"><?=@$dil['txt137'];?></h5>

										<form action="_class/site_islem.php" style="text-align: left" method="post" autocomplete="off">
											<div class="row text-left">
												<input type="hidden" name="url" value="<?php echo $_SERVER['REQUEST_URI'];?>" />
												<div class="form-group col-4">
													<label for="il"><?=@$dil['txt42'];?></label>
													<input class="form-control" type="text" id="il" name="il" value="<?php echo $ADRESSonuc['il'];?>" required placeholder="<?=@$dil['txt42'];?>">
												</div>
												<div class="form-group col-4">
													<label for="ilce"><?=@$dil['txt43'];?></label>
													<input class="form-control" type="text" id="ilce" name="ilce" value="<?php echo $ADRESSonuc['ilce'];?>" required placeholder="<?=@$dil['txt43'];?>">
												</div>
												<div class="form-group col-4">
													<label for="pkodu"><?=@$dil['txt44'];?></label>
													<input class="form-control" type="text" id="pkodu" name="pkodu" value="<?php echo $ADRESSonuc['pkodu'];?>" placeholder="<?=@$dil['txt44'];?>">
												</div>

												<div class="form-group col-12">
													<label for="adres"><?=@$dil['txt45'];?></label>
													<textarea class="form-control" name="adres" id="adres" style="height: 200px;" placeholder="<?=@$dil['txt45'];?>"><?php echo $ADRESSonuc['adres'];?></textarea>
												</div>

												<div class="form-group col-12">
													<label for="varsayilan"><?=@$dil['txt46'];?></label>
													<input type="checkbox" name="varsayilan" id="varsayilan" value="1" <?php if($ADRESSonuc['varsayilan'] == '1') {?> checked <?php } ?>>
													<input type="hidden" name="id" value="<?php echo $ADRESSonuc['id'];?>">
													<input class="btn btn-primary pull-right" name="adres_guncelle" type="submit" value="<?=@$dil['txt155'];?>">
												</div>

											</div>
										</form>
									</div>
									<?php }?>
									<?php }else{?>
									<center>
										<div class="" id="empty_content"><?=@$dil['txt138'];?></div>
									</center>
									<?php }?>
									</tbody>
								</table>								

							</div>
							<div class="tab-pane fade <?php echo $tercihleractive;?>" id="contact" role="tabpanel" aria-labelledby="contact-tab">
								<form method="POST" action="_class/site_islem.php" autocomplete="off">

									<table class="table mt-3">
										<tbody>
										<tr>
											<td><?=@$dil['txt139'];?></td>
											<td>
												<div class="custom-control custom-checkbox">
													<input type="checkbox" class="custom-control-input" id="email_bildirim" name="email_bildirim" value="1" <?php if($Bilgilerim['email_bildirim'] == '1') {?> checked <?php } ?>>
													<label class="custom-control-label" for="email_bildirim"></label>
												</div>
											</td>
										</tr>
										<tr>
											<td><?=@$dil['txt140'];?></td>
											<td>
												<div class="custom-control custom-checkbox">
													<input type="checkbox" class="custom-control-input" id="sms_bildirim" name="sms_bildirim" value="1" <?php if($Bilgilerim['sms_bildirim'] == '1') {?> checked <?php } ?>>
													<label class="custom-control-label" for="sms_bildirim"></label>
												</div>
											</td>
										</tr>
										<tr>
											<td>
												<a href="sayfa/hizmet-ve-kullanim-sozlesmesi.html" target="_blank"><?=@$dil['txt141'];?></a> <?=@$dil['txt142'];?>
											</td>
											<td>
												<div class="custom-control custom-checkbox">
													<input type="checkbox" class="custom-control-input" id="hizmet_sozlesme" name="hizmet_sozlesme" value="1" <?php if($Bilgilerim['hizmet_sozlesme'] == '1') {?> checked <?php } ?>>
													<label class="custom-control-label" for="hizmet_sozlesme"></label>
												</div>
											</td>
										</tr>
										<tr>
											<td><a href="sayfa/gizlilik-politikasi.html" target="_blank"><?=@$dil['txt143'];?></a>
												<?=@$dil['txt142'];?>
											</td>
											<td>
												<div class="custom-control custom-checkbox">
													<input type="checkbox" class="custom-control-input" id="gizlilik_sozlesme" name="gizlilik_sozlesme" value="1" <?php if($Bilgilerim['gizlilik_sozlesme'] == '1') {?> checked <?php } ?>>
													<label class="custom-control-label" for="gizlilik_sozlesme"></label>
												</div>
											</td>
										</tr>
										</tbody>
									</table>
									<input type="hidden" name="ad" value="<?php echo $Bilgilerim['ad'];?>">
									<input type="hidden" name="soyad" value="<?php echo $Bilgilerim['soyad'];?>">
									<input type="submit" name="hesap_tercih_guncelle" class="btn btn-primary pull-right" value="<?=@$dil['txt103'];?>">
									<div class="clear"></div>
								</form>
							</div>
							<div class="tab-pane fade <?php echo $sifredegistiractive;?>" id="password" role="tabpanel" aria-labelledby="password-tab">
								<h5 class="mt-3"><?=@$dil['txt144'];?></h5>

								<form method="POST" action="_class/site_islem.php" autocomplete="off">
									<div class="form-group">
										<label for="password"><?=@$dil['txt145'];?></label>
										<input class="form-control" type="password" id="password" name="password" placeholder="******">
									</div>
									<div class="form-group">
										<label for="password_again"><?=@$dil['txt146'];?></label>
										<input class="form-control" type="password" id="password_again" name="password_again" placeholder="******">
									</div>
									<input type="hidden" name="ad" value="<?php echo $Bilgilerim['ad'];?>">
									<input type="hidden" name="soyad" value="<?php echo $Bilgilerim['soyad'];?>">
									<input type="submit" name="hesap_sifre_guncelle" class="btn btn-primary pull-right" value="<?=@$dil['txt148'];?>">
									<div class="clear"></div>
								</form>

							</div>
						</div>
					</div>
				</div>
			</div>

		</div>

    </div>
</div>
<script type="text/javascript">
$(document).ready(function() {
	$(".popconfirm").popConfirm();
});
</script>
<script type="text/javascript">
$(document).ready(function(e) {
	$('input[name=utipi]').bind('change', utipi);
});
function utipi(){
	var secilen = $(this).val();
	if (secilen == 1) {
		$("#kurumsal-bilgiler").show();
	} else {
		$("#kurumsal-bilgiler").hide();
	}
}
$('input[name=utipi]').ready(function(){
	var secilen = $("input[name=utipi]:checked").val();
	if (secilen == 1) {
		$("#kurumsal-bilgiler").show();
	} else {
		$("#kurumsal-bilgiler").hide();
	}
});

</script>
<?php 
#Genel Ayarlar
if($_SESSION['hesap_genel_guncelle'] == 'yes')
{
	echo "
	<script>
	swal({
		type: 'success',
		title: '".@$dil['txt11']."',
		text: '".@$dil['txt149']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";
	unset($_SESSION['hesap_genel_guncelle']);
}
if($_SESSION['hesap_genel_guncelle'] == 'no')
{
	echo "
	<script>
	swal({
		type:'error',
		title:'".@$dil['txt16']."',
		text: '".@$dil['txt17']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";	
	unset($_SESSION['hesap_genel_guncelle']);
}
if($_SESSION['hesap_genel_guncelle'] == 'bos')
{
	echo "
	<script>
	swal({
		type: 'warning',
		title:'".@$dil['txt13']."',
		text: '".@$dil['txt14']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";	
	unset($_SESSION['hesap_genel_guncelle']);
}
#Tercih Ayarları
if($_SESSION['hesap_tercih_guncelle'] == 'yes')
{
	echo "
	<script>
	swal({
		type: 'success',
		title: '".@$dil['txt11']."',
		text: '".@$dil['txt149']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";
	unset($_SESSION['hesap_tercih_guncelle']);
}
if($_SESSION['hesap_tercih_guncelle'] == 'no')
{
	echo "
	<script>
	swal({
		type:'error',
		title:'".@$dil['txt16']."',
		text: '".@$dil['txt17']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";	
	unset($_SESSION['hesap_tercih_guncelle']);
}
#Şifre Ayarları
if($_SESSION['hesap_sifre_guncelle'] == 'yes')
{
	echo "
	<script>
	swal({
		type: 'success',
		title: '".@$dil['txt11']."',
		text: '".@$dil['txt150']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";
	unset($_SESSION['hesap_sifre_guncelle']);
}
if($_SESSION['hesap_sifre_guncelle'] == 'no')
{
	echo "
	<script>
	swal({
		type:'error',
		title:'".@$dil['txt16']."',
		text: '".@$dil['txt17']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";	
	unset($_SESSION['hesap_sifre_guncelle']);
}
if($_SESSION['hesap_sifre_guncelle'] == 'sifre')
{
	echo "
	<script>
	swal({
		type: 'warning',
		title:'".@$dil['txt13']."',
		text: '".@$dil['txt151']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";	
	unset($_SESSION['hesap_sifre_guncelle']);
}
if($_SESSION['hesap_sifre_guncelle'] == 'bos')
{
	echo "
	<script>
	swal({
		type: 'warning',
		title:'".@$dil['txt13']."',
		text: '".@$dil['txt14']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";	
	unset($_SESSION['hesap_sifre_guncelle']);
}
#Yeni Adres Ekle
if($_SESSION['adres_ekle'] == 'yes')
{
	echo "
	<script>
	swal({
		type: 'success',
		title: '".@$dil['txt11']."',
		text: '".@$dil['txt152']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";
	unset($_SESSION['adres_ekle']);
}
if($_SESSION['adres_ekle'] == 'no')
{
	echo "
	<script>
	swal({
		type:'error',
		title:'".@$dil['txt16']."',
		text: '".@$dil['txt17']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";	
	unset($_SESSION['adres_ekle']);
}
if($_SESSION['adres_ekle'] == 'bos')
{
	echo "
	<script>
	swal({
		type: 'warning',
		title:'".@$dil['txt13']."',
		text: '".@$dil['txt14']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";	
	unset($_SESSION['adres_ekle']);
}
#Adres Güncelle
if($_SESSION['adres_guncelle'] == 'yes')
{
	echo "
	<script>
	swal({
		type: 'success',
		title: '".@$dil['txt11']."',
		text: '".@$dil['txt153']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";
	unset($_SESSION['adres_guncelle']);
}
if($_SESSION['adres_guncelle'] == 'no')
{
	echo "
	<script>
	swal({
		type:'error',
		title:'".@$dil['txt16']."',
		text: '".@$dil['txt17']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";	
	unset($_SESSION['adres_guncelle']);
}
if($_SESSION['adres_guncelle'] == 'bos')
{
	echo "
	<script>
	swal({
		type: 'warning',
		title:'".@$dil['txt13']."',
		text: '".@$dil['txt14']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";	
	unset($_SESSION['adres_guncelle']);
}
#Adres Sil
if($_SESSION['adressil'] == 'yes')
{
	echo "
	<script>
	swal({
		type: 'success',
		title: '".@$dil['txt11']."',
		text: '".@$dil['txt154']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";
	unset($_SESSION['adressil']);
}
if($_SESSION['adressil'] == 'no')
{
	echo "
	<script>
	swal({
		type:'error',
		title:'".@$dil['txt16']."',
		text: '".@$dil['txt17']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";	
	unset($_SESSION['adressil']);
}
?>
