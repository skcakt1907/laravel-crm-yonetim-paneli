<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null;?>
<?php
if(strip_tags(isset($_GET['id'])))
{
	$Sorgu = $db->prepare("SELECT * FROM destek WHERE id = ?");
	$Sorgu->execute(array($_GET['id']));
	if($Sorgu->rowCount())
	{
		$Sonuc = $Sorgu->fetch(PDO::FETCH_ASSOC);
		$uyebilgi =$db->query("SELECT * FROM uyeler WHERE id='{$Sonuc['uyeid']}'")->fetch(PDO::FETCH_ASSOC);
	}
	else
	{
		header("Location:".$url."/404.html");
	}
}
else
{
	header("Location:".$url."/404.html");
}	
?>
<?php if(!isset($_SESSION["site_uyeid"]))
{
	$_SESSION['devam'] = "destek/".$_GET['id']."";
	header("Location:".$url."/giris.html");
}
else
{
	unset($_SESSION['devam']);
}
?>
<div class="top-header overlay" style="background-image: url(<?php echo tema;?>/uploads/arkaplan/uyelik/<?php echo $arkaplan['uyelik']?>)">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper">
                    <h1 class="heading"><?=@$dil['txt182'];?></h1>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="ustbanner">
	<div class="container">
		<span><?=@$dil['txt27'];?> <strong><?php echo $Bilgilerim['ad'];?> <?php echo $Bilgilerim['soyad'];?></strong>. <i><?=@$dil['txt28'];?></i></span>

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
				<?php require_once("sitebar.php");?>
				<div class="col-md-9">
					<div class="col-md-12 main-content" style="padding: 20px;">
						<div class="title-area mb-4">
							<h5 class="title">
								<i class="fa fa-life-ring" aria-hidden="true"></i> <?php echo $Sonuc['baslik'];?> (#<?php echo $Sonuc['id'];?>)
							</h5>
							<div class="pull-right">
								<strong><a href="hesabim.html"><?=@$dil['txt32'];?> </a></strong> /
								<strong><a href="destek-taleplerim.html"><?=@$dil['txt183'];?> </a></strong>
							</div>
						</div>
						<div class="row destek-ozet">
							<div class="col-md-3 col-sm-6 col-6 mb-sm-3 mb-3">
								<div class="d-box">
									<strong><?=@$dil['txt184'];?></strong>
									<p><?php echo $Sonuc['departman'];?></p>
								</div>
							</div>
							<div class="col-md-3 col-sm-6 col-6 mb-sm-3 mb-3">
								<div class="d-box">
									<strong><?=@$dil['txt185'];?></strong>
									<p><?php echo TvERtXpE3w_tarihcevir($Sonuc['son_cevap']);?></p>
								</div>
							</div>
							<div class="col-md-3 col-sm-6 col-6 mb-sm-3 mb-3">
								<div class="d-box">
									<p><strong><?=@$dil['txt186'];?></strong></p>
									<?php if($Sonuc['durum'] == "0"){?>
									<span class="box-danger"><?=@$dil['txt187'];?></span>
									<?php }?>
									<?php if($Sonuc['durum'] == "1"){?>
									<span class="box-success"><?=@$dil['txt188'];?></span>
									<?php }?>
									<?php if($Sonuc['durum'] == "2"){?>
									<span class="box-info"><?=@$dil['txt189'];?></span>
									<?php }?>
								</div>
							</div>
							<div class="col-md-3 col-sm-6 col-6 mb-sm-3 mb-xs-3">
								<div class="d-box">
									<strong><?=@$dil['txt190'];?></strong>
									<p><?php echo $Sonuc['oncelik'];?></p>
								</div>
							</div>

						</div>
						<div class="row mt-4">
							<div class="col-6">
								<a href="javascript:void(0);" onclick="cozumlendi_kapat()" class="btn btn-success" style="display: block"><?=@$dil['txt191'];?></a>
								<script>
								function cozumlendi_kapat(){
									swal({
									  title: "<?=@$dil['txt192'];?>",
									  text: "<?=@$dil['txt193'];?>",
									  type: 'warning',
									  showCancelButton: true,
									  confirmButtonColor: '#3085d6',
									  cancelButtonColor: '#d33',
									  cancelButtonText: "<?=@$dil['txt9'];?>", 
									  confirmButtonText: "<?=@$dil['txt194'];?>"
									}).then((result) => {
									  if (result.value) {
										swal({
										  title: "<?=@$dil['txt11'];?>",
										  text: "<?=@$dil['txt195'];?>",
										  type: "success",
										  icon: 'success',
										  timer: 5000
										}).then(function() {
										  window.location.href = "_class/site_islem.php?destek_yanit_cozumlendi=ok&destekid=<?php echo $Sonuc['id'];?>";
										});
									  }
									});
								}
								</script>
							</div>
							<div class="col-6">
								<a class="btn btn-primary" style="display: block" data-toggle="collapse"
								   href="#collapseExample" role="button" aria-expanded="false"
								   aria-controls="collapseExample"><?=@$dil['txt196'];?></a>
							</div>
							<div class="col-12">

								<div class="collapse" id="collapseExample">

									<form action="_class/site_islem.php" method="post" enctype="multipart/form-data">
										<div class="form-group">
											<textarea class="form-control mt-3" style="height: 200px;" name="mesaj" id="mesaj" placeholder="<?=@$dil['txt197'];?>"></textarea>
										</div>
										<div class="form-group">
											<label for="dosya"><?=@$dil['txt198'];?></label>
											<input id="dosya" name="dosya" type="file">
											<div class="clear"></div>
											<span style="font-size:13px;"><?=@$dil['txt199'];?></span>
											<input type="hidden" name="destekid" value="<?php echo $Sonuc['id'];?>" />
											<input class="btn btn-primary pull-right" name="destek_yanit_btn" type="submit" value="<?=@$dil['txt200'];?>">
										</div>
									</form>
								</div>
							</div>
						</div>

						<div class="mt-4">
							<?php $BSorgu = $db->prepare("SELECT * FROM destek WHERE ustid = ?  ORDER BY id DESC");
							$BSorgu->execute(array($Sonuc['id']));
							$islem = $BSorgu->fetchALL(PDO::FETCH_ASSOC);?>
							<?php foreach ( $islem as $BSonuc ){?>
							<?php 
							$uyecek = $db->query("SELECT * FROM uyeler WHERE id='{$BSonuc['uyeid']}'")->fetch(PDO::FETCH_ASSOC); 
							if($uyecek > 0){?>
							<div class="col-12 message-box-m">
								<h4 class="pull-left"><?php echo $uyecek['ad'];?> <?php echo $uyecek['soyad'];?>
									<small><?=@$dil['txt201'];?></small>
								</h4>
								<h4 class="pull-right"><?php echo TvERtXpE3w_tarihcevir($BSonuc['tarih']);?></h4>
								<br>
								<hr>
								<p><?php echo $BSonuc['mesaj'];?></p>								
								<br>
								<div class="clear"></div>
								<?php if($BSonuc['dosya'] != ""){?>
								<div class="ticket-attachment-file">
									<a target="_blank" href="<?php echo tema;?>/uploads/destek/<?php echo $BSonuc['dosya'];?>" class="ticket-attachment-file"><i class="fas fa-cloud-download-alt"></i> <?=@$dil['txt202'];?></a>
								</div>
								<?php }else{?>
									<br>
								<?php }?>
								<p>-----------</p>
								<p><?=@$dil['txt206'];?> <?php echo $BSonuc['ip'];?></p>
							</div>
							<?php } else {?>
							<div class="col-12 message-box-y">
								<h4 class="pull-left"><?=@$dil['txt203'];?></h4>
								<h4 class="pull-right"><?php echo TvERtXpE3w_tarihcevir($BSonuc['tarih']);?></h4>
								<br>
								<hr>
								<p><?php echo $BSonuc['mesaj'];?></p>

								<br>
								<div class="clear"></div>
								<?php if($BSonuc['dosya'] != ""){?>
								<div class="ticket-attachment-file">
									<a target="_blank" href="<?php echo tema;?>/uploads/destek/<?php echo $BSonuc['dosya'];?>" class="ticket-attachment-file"><i class="fas fa-cloud-download-alt"></i> <?=@$dil['txt202'];?></a>
								</div>
								<?php }else{?>
									<br>
								<?php }?>
								<p>-----------</p>
								<p><?=@$dil['txt204'];?></p>
								<p><strong><?php echo firma_adi;?> <?=@$dil['txt205'];?></strong></p>
							</div>
							<?php }?>													
							<?php }?>
							<div class="col-12 message-box-m">
								<h4 class="pull-left"><?php echo $uyebilgi['ad'];?> <?php echo $uyebilgi['soyad'];?>
									<small><?=@$dil['txt201'];?></small>
								</h4>
								<h4 class="pull-right"><?php echo TvERtXpE3w_tarihcevir($Sonuc['tarih']);?></h4>
								<br>
								<hr>
								<p><?php echo $Sonuc['mesaj'];?></p>								
								<br>
								<div class="clear"></div>
								<?php if($Sonuc['dosya'] != ""){?>
								<div class="ticket-attachment-file">
									<a target="_blank" href="<?php echo tema;?>/uploads/destek/<?php echo $Sonuc['dosya'];?>" class="ticket-attachment-file"><i class="fas fa-cloud-download-alt"></i> <?=@$dil['txt202'];?></a>
								</div>
								<?php }else{?>
									<br>
								<?php }?>
								<p>-----------</p>
								<p><?=@$dil['txt206'];?> <?php echo $Sonuc['ip'];?></p>
							</div>

					</div>
				</div>
			</div>

		</div>

    </div>
</div>
<?php 
if($_SESSION['destek_yanit_btn'] == 'yes')
{
	echo "
	<script>
	swal({
		type: 'success',
		title: '".@$dil['txt11']."',
		text: '".@$dil['txt207']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";
	unset($_SESSION['destek_yanit_btn']);
}
			
if($_SESSION['destek_yanit_btn'] == 'no')
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
	unset($_SESSION['destek_yanit_btn']);
}

if($_SESSION['destek_yanit_btn'] == 'bos')
{
	echo "
	<script>
	swal({
		type: 'error',
		title: '".@$dil['txt16']."',
		text: '".@$dil['txt208']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";	
	unset($_SESSION['destek_yanit_btn']);
}
?>