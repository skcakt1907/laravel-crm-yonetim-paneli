<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null; ?>
<?php if (!isset($_SESSION["site_uyeid"])) {
	$_SESSION['devam'] = "alan_adlarim";
	header("Location:" . $url . "/giris.html");
} else {
	unset($_SESSION['devam']);
}

$sepet = $db->prepare("SELECT * FROM sepet WHERE user_id = ?");
$sepet->execute(array($_SESSION['site_uyeid']));
?>
<div class="top-header overlay" style="background-image: url(<?php echo tema; ?>/uploads/arkaplan/uyelik/<?php echo $arkaplan['uyelik'] ?>)">
	<div class="container">
		<div class="row">
			<div class="col-sm-12 col-md-12">
				<div class="wrapper">
					<h1 class="heading"><?= @$dil['txt393']; ?></h1>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="ustbanner">
	<div class="container">
		<span><?= @$dil['txt27']; ?> <strong><?php echo $Bilgilerim['ad']; ?> <?php echo $Bilgilerim['soyad']; ?></strong>.
			<?php if ($Bilgilerim['bayi'] != 0) {
				$Bayi = $db->query("SELECT * FROM bayilikler WHERE id='{$Bilgilerim['bayi']}'")->fetch(PDO::FETCH_ASSOC);
			?>
				(<?= $Bayi['paketadi']; ?>)
			<?php } ?> <i><?= @$dil['txt28']; ?></i></span>

		<div class="ustsil"></div>

		<span class="ustson"><?= @$dil['txt29']; ?> <strong> <?php echo TvERtXpE3w_tarih($Bilgilerim['son_giris']); ?></strong> <?= @$dil['txt30']; ?> <div class="ustsil"></div>
			<?= @$dil['txt31']; ?> <strong><?php echo $Bilgilerim['ip']; ?></strong></span>
	</div>
</div>
<div class="mixcontainer">
	<div class="container">
		<?php
		$sayfaid = 1;
		$durum = "duzenle";
		$Sorgu = $db->prepare("SELECT * FROM alanadi WHERE id = ?");
		$Sorgu->execute(array($sayfaid));
		if ($Sorgu->rowCount()) {
			$Sonuc = $Sorgu->fetch(PDO::FETCH_ASSOC);
		}
		$uzanti 		= json_decode($Sonuc['uzanti']);
		$kayit 		= json_decode($Sonuc['kayit']);
		$yenileme 	= json_decode($Sonuc['yenileme']); ?>
		<div id="wrapper" class="mt-4">
			<div class="row">
				<?php require_once("sitebar.php"); ?>
				<div class="col-md-9">
					<div class="col-md-12 border-left-3 main-content">
						<div class="title-area mb-4">
							<h5 class="title">
								<i class="fas fa-globe"></i>
								<?= @$dil['txt393']; ?>
							</h5>
							<div class="pull-right">
								<strong><a href="hesabim.html"><?= @$dil['txt32']; ?> </a></strong> /
								<a href="alan_adlarim.html"><?= @$dil['txt393']; ?> </a>
							</div>
						</div>


						<table width="100%" id="datatable" class="table table-bordered table-striped">
							<thead>
								<tr>
									<th scope="col" class="text-left">Ürün Adı</th>
									<th scope="col" class="text-center"><?= @$dil['txt36']; ?></th>
									<th scope="col" class="text-center">İşlemler</th>
								</tr>
							</thead>
							<tbody>
								<?php
								$satirSayisi = $sepet->rowCount();
								if($satirSayisi === 0){ ?>
								<tr class="odd" style="text-align: center;"><td style="text-align: center;" valign="top" colspan="5" class="dataTables_empty">Tabloda herhangi bir veri mevcut değil</td></tr>
								<?php }else{ ?>
								<?php $toplam = 0; foreach ($sepet as $item) { ?>
									<tr>
										<?php if ($item['who'] == "1") : ?>
											<?php
											$Sorgu = $db->prepare("SELECT * FROM yazilimlar WHERE id = ?");
											$Sorgu->execute(array($item['urun_id']));
											if ($Sorgu->rowCount()) {
												$Sonuc = $Sorgu->fetch(PDO::FETCH_ASSOC);
											}
											?>
											<?php $oldPrice = $Sonuc['tutar']; ?>
											<?php $Sonuc['tutar'] = bayilikindirimi($_SESSION['site_uyeid'], $Sonuc['tutar']); ?>
											<td><?php echo $Sonuc['adi']; ?></td>
											<?php $toplam += $Sonuc['tutar']; ?>
											<td><span class="paketfiyat_bayi"><del><?php echo my_number_format($oldPrice) ?> TL</del></span> <?php echo my_number_format($Sonuc['tutar']); ?> TL</td>
											<td>
												<button class="btn btn-danger" data-toggle="popover-x" data-target="#myPopover10a<?php echo $item['id']; ?>"><i class="fas fa-trash-alt"></i></button>
												<div id="myPopover10a<?php echo $item['id']; ?>" class="popover popover-x popover-default">
													<div class="arrow"></div>
													<h3 class="popover-header popover-title"><span class="close pull-right" data-dismiss="popover-x">&times;</span>Dikkat!!!</h3>
													<div class="popover-body popover-content">
														<?= $Sonuc['adi']; ?> (PAKET) Silmek İstediğinizden Eminmisiniz?
													</div>
													<form action="_class/site_islem.php" method="post">
														<input type="text" name="id" required readonly hidden value="<?php echo $item['id']; ?>" id="">
														<div class="popover-footer">
															<button name="sepet_sil" type="submit" class="btn btn-sm btn-primary">Sil</button>
														</div>
													</form>
												</div>
											</td>
										<?php endif; ?>
									</tr>
								<?php } ?>
								<tr>
									<td>KDV:</td>
									<td><?php echo my_number_format($kdv = ($toplam * 20)/100) ?> TL</td>
									<td></td>
								</tr>
								<tr>
									<td>TOPLAM:</td>
									<td><?php echo my_number_format($toplam + $kdv); ?> TL</td>
									<td></td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
						<?php if($satirSayisi != 0){ ?>
							<a href="sepet-satinal.html" type="button" class="btn btn-primary">Ödemeye Geç</a>
						<?php } ?>
						<div class="alert alert-warning alert-dismissible fade show mt-4" role="alert">
							<button type="button" class="close mt-0" data-dismiss="alert" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
							<?= @$dil['txt38']; ?>
						</div>
					</div>
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
			text: 'Ürün Sepetinizden Silindi',
			confirmButtonText: 'Tamam',
			timer: 5000
		})
	</script>
<?php endif; ?>
<style>
@media only screen and (max-width: 1024px) and (min-width: 320px){
 
    #datatable table, 
    #datatable thead, 
    #datatable tbody, 
    #datatable th, 
    #datatable td, 
    #datatable tr { 
        display: block; 
    }
     
    #datatable thead tr { 
        position: absolute;
        top: -9999px;
        left: -9999px;
    }
     
    #datatable tr { margin-bottom:10px;border-bottom: 1px solid #ccc; }

    #datatable td { 
        border-bottom: none; 
        position: relative;
        padding-left: 40%; 
		overflow: hidden;
		height: auto;
    }
    #datatable td:before { 
        position: absolute;
        top: 0px;
        left: 0px;
        width: 30%; 
        padding: 12px 10px 0 5px;
        height: 100%;
        white-space: nowrap;
        background-color:#333;
        color:#fff;
		text-align: left;
    }
     
    #datatable td:nth-of-type(1):before { content: "<?=@$dil['txt40'];?>"; }
    #datatable td:nth-of-type(2):before { content: "<?=@$dil['txt36'];?>"; }
    #datatable td:nth-of-type(3):before { content: "<?=@$dil['txt186'];?>"; }
    #datatable td:nth-of-type(4):before { content: "<?=@$dil['txt37'];?>"; }
}
</style>
<script type="text/javascript">
$(document).ready(function(){
	var dataTable=$('#datatable').DataTable({
		"serverSide":true,
		"aLengthMenu": [
			[5, 10, 15, -1],
			[5, 10, 15, "Tümü"]
		],
		"columnDefs": [
			{ "orderable": false, "targets": [3] },
			{  "className": "text-left align-middle", targets: [0] },
			{  "className": "text-center align-middle", targets: [1,2,3] }
		],
		"iDisplayLength": 10,
		responsive: true,
		"language": {
			"url":"<?php echo tema;?>/js/lang.json"
		}
	});
});
</script>
