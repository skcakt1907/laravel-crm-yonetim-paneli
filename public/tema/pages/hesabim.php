<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null;?>
<?php if(!isset($_SESSION["site_uyeid"]))
{
	$_SESSION['devam'] = "hesabim";
	header("Location:".$url."/giris.html");
}
else
{
	unset($_SESSION['devam']);
	$toplam_destek 		= $db->query("SELECT * FROM destek WHERE uyeid = '{$Bilgilerim['id']}' AND ustid = '0'")->rowCount();
	$odenmemis_fatura 	= $db->query("SELECT * FROM faturalar WHERE uyeid = '{$Bilgilerim['id']}' AND durum = '0'")->rowCount();
	$alanadlari 			= $db->query("SELECT * FROM satilanlar WHERE uyeid = '{$Bilgilerim['id']}' AND tipi = '0'")->rowCount();
	$yazilimlar 			= $db->query("SELECT * FROM satilanlar WHERE uyeid = '{$Bilgilerim['id']}' AND tipi = '2'")->rowCount();
	$faturatutar 		= $db->query("SELECT sum(tutar) AS toplagel FROM  faturalar WHERE uyeid = '{$Bilgilerim['id']}' AND durum = '0'")->fetch();

}
?>
<div class="top-header overlay" style="background-image: url(<?php echo tema;?>/uploads/arkaplan/uyelik/<?php echo $arkaplan['uyelik']?>)">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper">
                    <h1 class="heading"><?=@$dil['txt281'];?></h1>
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
            <?php } ?>            <i><?=@$dil['txt28'];?></i></span>

		<div class="ustsil"></div>

		<span class="ustson"><?=@$dil['txt29'];?> <strong> <?php echo TvERtXpE3w_tarih($Bilgilerim['son_giris']);?></strong> <?=@$dil['txt30'];?> <div class="ustsil"></div>
			<?=@$dil['txt31'];?> <strong><?php echo $Bilgilerim['ip'];?></strong></span>
	</div>
</div>

<!-- ***** FILTER PLANS ***** -->
<div class="mixcontainer">
    <div class="container" id="muspanel">

		<div id="wrapper">
		
			<div class="row mb-4 mt-3" style="margin-right: 0px;margin-left: 0px;">

				<div class="col-sm-6 col-6 border-left-3 border-radius col-md tile panelrenk1">
					<a href="web_paketlerim.html">
						<div class="icon"><i class="fa fa-cube"></i></div>
						<div class="stat"><?php echo $yazilimlar;?></div>
						<div class="title"><?=@$dil['txt283'];?></div>
						<div class="highlight bg-color-blue"></div>
					</a>
				</div>
				<div class="col-sm-6 col-6 border-left-3 border-radius col-md tile panelrenk2">
					<a href="alan_adlarim.html">
						<div class="icon"><i class="fa fa-globe"></i></div>
						<div class="stat"><?php echo $alanadlari;?></div>
						<div class="title"><?=@$dil['txt26'];?></div>
						<div class="highlight bg-color-green"></div>
					</a>
				</div>

				<div class="col-sm-6 col-6 border-left-3 border-radius col-md tile panelrenk3">
					<a href="faturalarim.html">
						<div class="icon"><i class="fa fa-file"></i></div>
						<div class="stat"><?php echo $odenmemis_fatura;?></div>
						<div class="title"><?=@$dil['txt285'];?></div>
						<div class="highlight bg-color-red"></div>
					</a>
				</div>

				<div class="col-sm-6 col-6 border-left-3 border-radius col-md tile panelrenk4">
					<a href="destek-talebi-olustur.html">
						<div class="icon"><i class="fa fa-comments"></i></div>
						<div class="stat"><?php echo $toplam_destek;?></div>
						<div class="title"><?=@$dil['txt222'];?></div>
						<div class="highlight bg-color-red"></div>
					</a>
				</div>
				<div class="col-sm-6 col-6 border-left-3 border-radius col-md tile panelrenk5">
					<a href="bakiyem.html">
						<div class="icon"><i class="fa fa-credit-card"></i></div>
						<div class="stat"><?php echo TvERtXpE3w_tl_format($kredi['tutar']);?> TL</div>
						<div class="title"><?=@$dil['txt104'];?></div>
						<div class="highlight bg-color-gold"></div>
					</a>
				</div>
			</div>
			<?php if($odenmemis_fatura > 0){?>
			<div class="alert alert-danger">
				<div class="row">

					<div class="col-md-2"><i class="fa fa-info-circle" style="font-size: 91px;text-align: center;display: block;margin-top: 20px;"></i></div>
					<div class="col-md-10">
						<div class="balanceinfo">
							<h5><strong><?=@$dil['txt287'];?></strong></h5>
							<p style="font-weight:400;">
								<span><?=@$dil['txt288'];?> <strong><?php echo $odenmemis_fatura;?> <?=@$dil['txt289'];?></strong> <?=@$dil['txt290'];?> <strong><?php echo $faturatutar[0];?> TL</strong> <?=@$dil['txt291'];?></span>
							</p>
							<a href="faturalarim.html" class="btn btn-sm btn-outline-primary"><?=@$dil['txt291_1'];?></a>
						</div>
					</div>

				</div>
			</div>
			<?php }?>
			
			<div class="row">
				<?php require_once("sitebar.php");?>

				<div class="col-md-9">
					<div class="col-md-12 border-left-3 main-content">
						<div class="title-area">
							<h5 class="title"><i class="fa fa-clock-o"></i> <?=@$dil['txt292'];?></h5>
							<a href="destek-talebi-olustur.html" class="btn btn-sm btn-outline-primary pull-right">+ <?=@$dil['txt209'];?></a>
						</div>
						<table id="destek" class="table table-bordered table-striped">
							<thead>
							<tr>
								<th scope="col" class="text-left"><?=@$dil['txt40'];?></th>
								<th scope="col" class="text-center" style="width:150px;"><?=@$dil['txt186'];?></th>
								<th scope="col" class="text-center" style="width:100px;"><?=@$dil['txt226'];?></th>
							</tr>
							</thead>
							<tbody>
							<?php $DESTEKSorgu = $db->prepare("SELECT * FROM destek WHERE ustid = ? AND uyeid = ? ORDER BY son_cevap DESC LIMIT 5");
							$DESTEKSorgu->execute(array("0",$Bilgilerim['id']));
							$DESTEKislem = $DESTEKSorgu->fetchALL(PDO::FETCH_ASSOC);?>
								<?php foreach ( $DESTEKislem as $DESTEKSonuc ){?>
								<tr>
									<th scope="row" class="align-middle">
										<a href="destek/<?php echo $DESTEKSonuc['id'];?>.html" class="link"><?php echo $DESTEKSonuc['baslik'];?></a>
										<p class="t-detail"><?php echo $DESTEKSonuc['hizmet'];?></p>
									</th>
									<td class="text-center align-middle">
										<?php if($DESTEKSonuc['durum'] == 0){?>
											<label class="alert alert-danger alert-sm mt-3"><?=@$dil['txt187'];?></label>
										<?php }?>
										<?php if($DESTEKSonuc['durum'] == 1){?>
											<label class="alert alert-success alert-sm mt-3"><?=@$dil['txt188'];?></label>
										<?php }?>
										<?php if($DESTEKSonuc['durum'] == 2){?>
											<label class="alert alert-info alert-sm mt-3"><?=@$dil['txt189'];?></label>
										<?php }?>										
									</td>
									<td class="text-center align-middle">
										<a href="destek/<?php echo $DESTEKSonuc['id'];?>.html" class="btn btn-outline-primary btn-sm m-0 pl-3 pr-3">
											<i class="fa fa-search"></i>
										</a>
									</td>
								</tr>
								<?php }?>
							</tbody>
						</table>
					</div>

					<div class="col-md-12 border-left-3 main-content">
						<div class="title-area">
							<h5 class="title"><i class="fa fa-clock-o"></i> <?=@$dil['txt293'];?></h5>
							<a href="paketler.html" class="btn btn-sm btn-outline-primary pull-right">+ <?=@$dil['txt294'];?></a>
						</div>
						<table id="son_siparis" class="table table-bordered table-striped">
							<thead>
							<tr>
								<th scope="col" class="text-left"><?=@$dil['txt40'];?></th>
								<th scope="col" class="text-center" style="width:150px;"><?=@$dil['txt186'];?></th>
								<th scope="col" class="text-center" style="width:100px;"><?=@$dil['txt226'];?></th>
							</tr>
							</thead>
							<tbody>
							<?php $SIPARISSorgu = $db->prepare("SELECT * FROM satilanlar WHERE uyeid = ? ORDER BY id DESC LIMIT 5");
							$SIPARISSorgu->execute(array($Bilgilerim['id']));
							$SIPARISislem = $SIPARISSorgu->fetchALL(PDO::FETCH_ASSOC);?>
								<?php foreach ( $SIPARISislem as $SIPARISSonuc ){?>
								<?php 
								if($SIPARISSonuc['tipi'] == "0") 
								{
									$baslik = '<strong>'.$SIPARISSonuc['domain'].'</strong> <small>('.@$dil['txt216'].')</small>';
									$yonet = "alan_adlarim.html?filtrele=".$SIPARISSonuc['domain']."";
								}
								if($SIPARISSonuc['tipi'] == "1") 
								{
									$baslik = '<strong>'.$SIPARISSonuc['hosting_baslik'].'</strong> <small>('.@$dil['txt295'].')</small><br><p class="t-detail">'.$SIPARISSonuc['domain'].'</p>';
									$yonet = "hostinglerim.html?filtrele=".$SIPARISSonuc['domain']."";
								}
								if($SIPARISSonuc['tipi'] == "2") 
								{
									$baslik = '<strong>'.$SIPARISSonuc['paket_baslik'].'</strong> <small>('.@$dil['txt296'].')</small><br><p class="t-detail">'.$SIPARISSonuc['domain'].'</p>';
									$yonet = "web_paketlerim.html?filtrele=".$SIPARISSonuc['domain']."";
								}
								?>
								<tr>
									<th scope="row" class="align-middle">
										<?php echo $baslik;?>
									</th>
									<td class="text-center align-middle">
										<?php if($SIPARISSonuc['durum'] == 0){?>
											<label class="alert alert-danger alert-sm mt-3"><?=@$dil['txt297'];?></label>
										<?php }?>
										<?php if($SIPARISSonuc['durum'] == 1){?>
											<label class="alert alert-success alert-sm mt-3"><?=@$dil['txt298'];?></label>
										<?php }?>										
									</td>
									<td class="text-center align-middle">
										<a href="<?php echo $yonet;?>" class="btn btn-outline-primary btn-sm m-0 pl-3 pr-3">
											<i class="fa fa-cog" aria-hidden="true"></i>
										</a>
									</td>
								</tr>
								<?php }?>
							</tbody>
						</table>
					</div>

				</div>
			</div>

		</div>

    </div>
</div>
<style>
@media 
only screen and (max-width: 760px),
(min-device-width: 768px) and (max-device-width: 1024px)  {
 
    #destek table, 
    #destek thead, 
    #destek tbody, 
    #destek th, 
    #destek td, 
    #destek tr { 
        display: block; 
    }
     
    #destek thead tr { 
        position: absolute;
        top: -9999px;
        left: -9999px;
    }
     
    #destek tr { margin-bottom:10px;border-bottom: 1px solid #ccc; }

    #destek td { 
        border-bottom: none; 
        position: relative;
        padding-left: 40%; 
		overflow: hidden;
		height: auto;
		text-align: left !important;
    }
    #destek td:before { 
        position: absolute;
        top: 0px;
        left: 0px;
        width: 35%; 
        padding: 12px 10px 0 5px;
        height: 100%;
        white-space: nowrap;
        background-color:#333;
        color:#fff;
		text-align: left;
    }
     
    #destek td:nth-of-type(1):before { content: "<?=@$dil['txt186'];?>"; }
    #destek td:nth-of-type(2):before { content: "<?=@$dil['txt226'];?>"; }
	
	#son_siparis table, 
    #son_siparis thead, 
    #son_siparis tbody, 
    #son_siparis th, 
    #son_siparis td, 
    #son_siparis tr { 
        display: block; 
    }
     
    #son_siparis thead tr { 
        position: absolute;
        top: -9999px;
        left: -9999px;
    }
     
    #son_siparis tr { margin-bottom:10px;border-bottom: 1px solid #ccc; }

    #son_siparis td { 
        border-bottom: none; 
        position: relative;
        padding-left: 40%; 
		overflow: hidden;
		height: auto;
		text-align: left !important;
    }
    #son_siparis td:before { 
        position: absolute;
        top: 0px;
        left: 0px;
        width: 35%; 
        padding: 12px 10px 0 5px;
        height: 100%;
        white-space: nowrap;
        background-color:#333;
        color:#fff;
		text-align: left;
    }
     
    #son_siparis td:nth-of-type(1):before { content: "<?=@$dil['txt186'];?>"; }
    #son_siparis td:nth-of-type(2):before { content: "<?=@$dil['txt226'];?>"; }


}
</style>
<?php 
if($_SESSION['uyelik'] == 'yes')
{
	echo "
	<script>
	swal({
		type: 'success',
		title: '".@$dil['txt11']."',
		text: '".@$dil['txt299']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";
	unset($_SESSION['uyelik']);
}
?>
