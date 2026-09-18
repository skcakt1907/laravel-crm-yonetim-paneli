<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null;?>
<?php if(!isset($_SESSION["site_uyeid"]))
{
	$_SESSION['devam'] = "destek-talebi-olustur";
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
					<div class="col-md-12 border-left-3 main-content">
						<div class="title-area mb-4">
							<h5 class="title">
								<i class="fa fa-life-ring"></i>
								<?=@$dil['txt209'];?>
							</h5>
							<div class="pull-right">
								<strong><a href="hesabim.html"><?=@$dil['txt32'];?> </a></strong> /
								<strong><a href="destek-taleplerim.html"><?=@$dil['txt183'];?> </a></strong>
							</div>
						</div>
						<form action="_class/site_islem.php" method="post" enctype="multipart/form-data">
							<div class="row">
								<div class="form-group col-6">
									<label for="baslik"><?=@$dil['txt210'];?></label>
									<input type="text" id="baslik" name="baslik" class="form-control" placeholder="<?=@$dil['txt210'];?>">
								</div>
								<div class="form-group col-6">
									<label for="departman"><?=@$dil['txt184'];?></label>

									<select name="departman" class="form-control" id="departman">
										<option value="Genel"><?=@$dil['txt211'];?></option>
										<option value="Sipariş"><?=@$dil['txt212'];?></option>
										<option value="Muhasebe"><?=@$dil['txt213'];?></option>
										<option value="Teknik Destek"><?=@$dil['txt214'];?></option>
									</select>
								</div>

								<div class="form-group col-6">
									<label for="hizmet"><?=@$dil['txt215'];?></label>
									<select id="hizmet" name="hizmet" class="form-control" tabindex="-1" aria-hidden="true">
										<?php
										$sql	= $db->query("SELECT * FROM satilanlar WHERE uyeid=".$Bilgilerim['id']." ORDER BY id ASC");
										if($sql->rowCount() > 0){?>
										<optgroup label="Alan Adı, Web Hosting, Web Paketler">
										<?php while($row	= $sql->fetch(PDO::FETCH_OBJ)){
										if($row->tipi == 0){
										$bslk	= $row->domain.' '."".@$dil['txt216']."";
										}elseif($row->tipi == 1){
										$bslk	= $row->hosting_baslik.' ('.$row->domain.')';
										}elseif($row->tipi == 2){
										$bslk	= $row->paket_baslik.' ('.$row->domain.')';
										}
										?>
										<option value="<?=$bslk;?>"><?=$bslk;?></option>
										<?php }?>
										</optgroup>
										<?php }
									  
										$sql = $db->query("SELECT * FROM musteri_hizmetler WHERE uyeid=".$Bilgilerim['id']." ORDER BY id ASC");
										if($sql->rowCount() > 0){?>
										<optgroup label="Hizmetler">
										<?php while($row	= $sql->fetch(PDO::FETCH_OBJ)){?>
										<option value="<?=$row->baslik;?>"><?=$row->baslik;?></option>
										<?php } ?>
										</optgroup>
										<?php }?>
										<option value="Yok"><?=@$dil['txt217'];?></option>
									</select>
								</div>
								<div class="form-group col-6">
									<label for="oncelik"><?=@$dil['txt190'];?></label>
									<select name="oncelik" class="form-control" id="oncelik">
										<option value="Düşük"><?=@$dil['txt218'];?></option>
										<option value="Orta"><?=@$dil['txt219'];?></option>
										<option value="Yüksek"><?=@$dil['txt220'];?></option>
									</select>
								</div>
								<div class="form-group col-12">
									<label for="mesaj">Mesaj</label>
									<textarea name="mesaj" style="height: 200px" placeholder="<?=@$dil['txt221'];?>" class="form-control"></textarea>
								</div>
								<div class="col-12">
									<label for="dosya"><?=@$dil['txt198'];?></label>
									<input type="file" id="dosya" name="dosya">
									<div class="clear"></div>
									<span style="font-size:13px;"><?=@$dil['txt199'];?></span>
									<input type="submit" name="destek_btn" class="btn btn-primary pull-right" value="+ <?=@$dil['txt222'];?>">
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>

		</div>

    </div>
</div>
<?php 
if($_SESSION['destek_btn'] == 'no')
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
	unset($_SESSION['destek_btn']);
}

if($_SESSION['destek_btn'] == 'bos')
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
	unset($_SESSION['destek_btn']);
}
?>