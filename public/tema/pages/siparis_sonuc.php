<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null;?>
<?php if($_SESSION['siparis'] == 'yes' || $_SESSION['odeme_kredi'] == 'yes' || $_GET['sonuc']){?>
<?php if(!isset($_SESSION["site_uyeid"]))
{
	$_SESSION['devam'] = "siparis-sonuc";
	header("Location:".$url."/giris.html");
}
else
{
    if(isset($_SESSION['bayid'])){
        if(isset($_GET['sonuc'])){
            if($_GET['sonuc']=='basarili'){
                $bayid = $_SESSION['bayid'];
                $db->query("UPDATE uyeler SET bayi='{$bayid}' WHERE id='{$Bilgilerim['id']}'");
                $db->query("INSERT INTO `bayilik_satislar`(`uye_id`, `bayi_id`) VALUES ({$Bilgilerim['id']},{$bayid})");
            }
        }
        unset($_SESSION['bayid']);
    }


	unset($_SESSION['devam']);
	$banka = explode(",", $_SESSION['havale']);
	$havalehesap = $db->query("SELECT * FROM banka_hesaplari WHERE banka = '{$banka[0]}'")->fetch(PDO::FETCH_ASSOC);
}
?>
<div class="top-header overlay" style="background-image: url(<?php echo tema;?>/uploads/arkaplan/uyelik/<?php echo $arkaplan['uyelik']?>)">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper">
                    <h1 class="heading">Sipariş Sonucu</h1>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="ustbanner">
	<div class="container">
		<span>Sn. <strong><?php echo $Bilgilerim['ad'];?> <?php echo $Bilgilerim['soyad'];?></strong>. <i>Müşteri Panelinize Hoşgeldiniz.</i></span>

		<div class="ustsil"></div>

		<span class="ustson">Son girişiniz <strong> <?php echo TvERtXpE3w_tarih($Bilgilerim['son_giris']);?></strong> tarihindeydi. <div class="ustsil"></div>
			Son giriş yapan IP <strong><?php echo $Bilgilerim['ip'];?></strong></span>
	</div>
</div>

<!-- ***** FILTER PLANS ***** -->
<div class="mixcontainer">
    <div class="container">

		<div id="wrapper" class="mt-4">
		
			<div class="row">
				<?php require_once("sitebar.php");?>
				<div class="col-md-9">
					<div class="col-md-12 main-content">
						<div class="title-area mb-4">
							<h5 class="title">
								<i class="fas fa-file-invoice"></i>
								Sipariş Sonucu
							</h5>
							<div class="pull-right">
								<strong><a href="hesabim.html"><?=@$dil['txt32'];?> </a></strong> /
								<a href="hostinglerim.html">Sipariş Sonucu </a>
							</div>
						</div>
						<div class="col-md-12">
							<div style="margin: 60px auto;display: block;text-align: center;line-height: 36px;"> <img src="https://www.paytr.com/img/icons/onay_v1.png" style="margin: 0;width: 80px;" alt="Başarılı"><br>
								<div style="font-size: 20px;padding-bottom: 15px;"><span lang="tr"><strong>Tebrikler!</strong> İşleminiz tamamlanmıştır.</span></div>
								<?php if($_SESSION['odeme_kredi'] == 'yes'){?>
								<div style="font-size: 20px;"><span lang="tr">İşlem sonrası kalan krediniz</span> <span lang="tr" style="font-weight: 600;"><?php echo TvERtXpE3w_tl_format($kredi['tutar']);?> TL</span> Hesabınıza kredi yüklemek için lütfen <span lang="tr" style="font-weight: 600;"> <a href="bakiyem.html"> buraya tıklayınız </a></span></div> <br> 
								<?php }elseif($_SESSION['siparis'] == 'yes'){?>
								<div id="hesaptable">
									<table width="75%" border="0" align="center">
										<tbody>
											<tr style="background:none;">
												<td style="border-bottom-width: 1px;	border-bottom-style: dotted;	border-bottom-color: #CCC;" colspan="2" align="center">
													<h3><strong>Seçtiğiniz Hesap Bilgilerileri</strong></h3>
													<h4>Lütfen seçtiğiniz banka hesabınailgili tutarı yatırınız.</h4>

												</td>
											</tr>
											<tr style="background:none;">
												<td style="border-bottom-width: 1px;	border-bottom-style: dotted;	border-bottom-color: #CCC;" class="mobbankalogo" width="40%" align="right">
													<h4><img style="max-width:200px;" src="<?php echo tema;?>/uploads/bankalar/<?php echo $havalehesap['resim']; ?>"></h4>
												</td>
												<td style="border-bottom-width: 1px;	border-bottom-style: dotted;	border-bottom-color: #CCC;text-align:left;padding: 12px;" width="60%">
													<ul style="padding-left: 40px;">
														<li style="list-style: none;line-height: initial;"><strong style="color:#449628;font-size:16px;"><?php echo $havalehesap['banka']; ?></strong></li>
														<li style="list-style: none;line-height: initial;"><strong>Alıcı:&nbsp;</strong><?php echo $havalehesap['hesap']; ?></li>
														<li style="list-style: none;line-height: initial;"><strong>Şube Kodu:&nbsp;</strong><?php echo $havalehesap['sube']; ?></li>
														<li style="list-style: none;line-height: initial;"><strong>Hesap numarası:&nbsp;</strong><?php echo $havalehesap['hnumara']; ?></li>
														<li style="list-style: none;line-height: initial;"><strong>Iban:&nbsp;</strong><?php echo $havalehesap['iban']; ?></li>
													</ul>
												</td>
											</tr>
										</tbody>
									</table>
								</div>
								<div style="font-size: 20px;"><span lang="tr"><span lang="tr" style="font-weight: 600;">Önemli Hatırlatma!</span></span> </div>
								<div style="font-size: 20px;"><span lang="tr">Siparişinizin tamamlanmasına müteakiben, ödemenizi 1 saat içerisinde yapmanız gerekmektedir. Aksi halde siparişiniz iptal edilir.</span> </div> <br> 
								<div style="font-size: 20px;"><span lang="tr"><span lang="tr" style="font-weight: 600;"><a href="dosyalarim.html"></a></span>  kısmından logo VB... Göndermeyi unutmayınız.</span> </div>
								<?php }?>
								<a target="_parent" style="font-size: 18px;" href="faturalarim.html"><span lang="tr">Faturalarınız için lütfen buraya tıklayın</span></a>
							</div>
						</div>

					</div>

				</div>
			</div>

		</div>

    </div>
</div>
<?php } else{
header("Location:index.html");
}
unset($_SESSION['siparisbilgi']);
unset($_SESSION['siparis']);
unset($_SESSION['odeme_kredi']);
unset($_SESSION['havale']);
?>
