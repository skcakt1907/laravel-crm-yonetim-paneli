<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null; ?>
<?php if (!isset($_SESSION["site_uyeid"])) {
	$_SESSION['devam'] = "alan_adlarim";
	header("Location:" . $url . "/giris.html");
} else {
	unset($_SESSION['devam']);
}

if (isset($_POST['kuponuygula'])) {
	$_SESSION['coupon'] = $_POST['kuponkodu'];
}

$sepet = $db->prepare("SELECT * FROM sepet WHERE user_id = ?");
$sepet->execute(array($_SESSION['site_uyeid']));

$Sorgu = $db->prepare("SELECT * FROM alanadi WHERE id = ?");
$Sorgu->execute(array(1));
$Sonuc = $Sorgu->fetch(PDO::FETCH_ASSOC);
$suzanti 	= json_decode($Sonuc['uzanti']);
$skayit 		= json_decode($Sonuc['kayit']);
$syenileme 	= json_decode($Sonuc['yenileme']);
$suzantiID 	= array_search("." . strip_tags($_GET['uzanti']), $suzanti);
$fiyat = 1;
if ($varsayilanadres) {
	$user_address 	= $varsayilanadres['adres'] . " / " . $varsayilanadres['ilce'] . " / " . $varsayilanadres['il'] . " - " . $varsayilanadres['pkodu'];
}
$price_test = 10.00;
$satirSayisi = $sepet->rowCount();
if ($satirSayisi === 0) {
	header("Location:" . $url . "/sepet.html");
}
?>
<div class="top-header overlay" style="background-image: url(<?php echo tema; ?>/uploads/arkaplan/uyelik/<?php echo $arkaplan['uyelik'] ?>)">
	<div class="container">
		<div class="row">
			<div class="col-sm-12 col-md-12">
				<div class="wrapper">
					<h1 class="heading"><?= @$dil['txt396']; ?></h1>
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
								<?= @$dil['txt396']; ?>
							</h5>
							<div class="pull-right">
								<strong><a href="hesabim.html"><?= @$dil['txt32']; ?> </a></strong> /
								<a href="alan_adlarim.html"><?= @$dil['txt396']; ?> </a>
							</div>
						</div>
						
						<form action="" method="POST">
							<table width="100%" class="table table-bordered">
								<tbody>
									<td colspan="1">
										<div class="form-group">
											<label for="mesaj"><?= @$dil['txt378']; ?></label>
											<input type="text" name="kuponkodu" class="form-control" value="<?php echo $_SESSION['coupon']; ?>">
										</div>
									</td>
									<td colspan="1">
										<div class="form-group">
											<button type="submit" name="kuponuygula" class="btn btn-success"> <?= @$dil['txt379']; ?></button>
										</div>
									</td>
								</tbody>
							</table>
						</form>

						<ul class="list-group">
							<?php $toplam = 0;
							$paytr_products = array();
							foreach ($sepet as $item) { ?>
								<?php if ($item['who'] == "1") : ?>
									<?php
									$Sorgu = $db->prepare("SELECT * FROM yazilimlar WHERE id = ?");
									$Sorgu->execute(array($item['urun_id']));
									if ($Sorgu->rowCount()) {
										$Sonuc = $Sorgu->fetch(PDO::FETCH_ASSOC);
										$oldPrice = $Sonuc['tutar'];
										if (isset($_SESSION['coupon'])) {
											$Sonuc['tutar'] = kuponuygula($_SESSION['coupon'], $Sonuc['tutar']);
										}
									}
									?>
									<?php $Sonuc['tutar'] = bayilikindirimi($_SESSION['site_uyeid'], $Sonuc['tutar']); ?>
									<?php $toplam += $Sonuc['tutar']; ?>
									<li class="list-group-item"><?php echo $Sonuc['adi']; ?><span style="float:right;"> <?php echo my_number_format($Sonuc['tutar']); ?> TL</span><span><span class="paketfiyat_bayi"><del><?php echo my_number_format($oldPrice) ?> TL</del></span></span></li>
									<?php
									$paytr_products[] = array(
										"product_id" => $Sonuc['id'], // Ürün ID'si
										"price" => $Sonuc['tutar'],   // Ürün fiyatı
										"quantity" => 1,              // Ürün miktarı (örneğin 1 adet)
										"name" => $Sonuc['adi']       // Ürün adı
									);
									?>
								<?php endif; ?>
							<?php } ?>
							<li class="list-group-item"><b>KDV (%20)</b><span style="float:right;"><?php echo my_number_format($kdv = ($toplam * 20) / 100) ?> TL</span></li>
							<li class="list-group-item"><b>TOPLAM</b><span style="float:right;"><?php $toplam_f_paytr = $toplam + $kdv;
																								echo my_number_format($toplam + $kdv); ?> TL</span></li>
						</ul>
						<form action="_class/site_islem.php" method="POST" id="paketsatinal" enctype="multipart/form-data">
							<table width="100%" style="margin-top:10px;" class="table table-bordered">
								<thead>
									<tr>
										<td scope="col" class="text-left"><?= @$dil['txt40']; ?></td>
										<td scope="col" class="text-center"><?= @$dil['txt36']; ?></td>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td colspan="2">
											<div class="form-group mt-4">
												<label for="mesaj"><?= @$dil['txt308']; ?></label> <a href="" data-toggle="modal" data-target="#yeni_adres_ekle" data-backdrop="static" data-keyboard="false" class="green lbtn d-inline-block border border-0 p-0 pl-2">+ <?= @$dil['txt41']; ?></a>
												<select class="form-control" name="adres_bilgisi" id="adres_bilgisi">
													<?php $ADRESSorgu = $db->prepare("SELECT * FROM adresler WHERE uyeid = ? ORDER BY varsayilan DESC");
													$ADRESSorgu->execute(array($Bilgilerim['id']));
													$ADRESislem = $ADRESSorgu->fetchALL(PDO::FETCH_ASSOC); ?>
													<?php foreach ($ADRESislem as $ADRESSonuc) { ?>
														<option value="<?php echo $ADRESSonuc['adres']; ?> / <?php echo $ADRESSonuc['ilce']; ?> / <?php echo $ADRESSonuc['il']; ?> - <?php echo $ADRESSonuc['pkodu']; ?>"><?php echo $ADRESSonuc['adres']; ?> / <?php echo $ADRESSonuc['ilce']; ?> / <?php echo $ADRESSonuc['il']; ?> - <?php echo $ADRESSonuc['pkodu']; ?></option>
													<?php } ?>
												</select>
											</div>
											<!-- Yeni Adres Ekle -->
											<div class="modal fade" id="yeni_adres_ekle" role="dialog">
												<div class="modal-dialog modal-md">

													<!-- Modal content-->
													<div class="modal-content">
														<div class="modal-header pb-2 pt-2" style="background:#38647A;">
															<h5 class="modal-title d-inline-block text-white"><?= @$dil['txt41']; ?></h5>
															<button type="button" class="close p-0 m-1" data-dismiss="modal"><i class="far fa-times-circle"></i></button>
														</div>
														<div class="modal-body">
															<input type="hidden" name="adresurl" value="<?php echo $_SERVER['REQUEST_URI']; ?>" />
															<div class="container-fluid">
																<div class="row">
																	<div class="col-md-4 col-sm-4 col-xs-4">
																		<div class="form-group">
																			<label class="mb-0" for="il"><?= @$dil['txt42']; ?></label>
																			<input type="text" class="w-100" id="il" name="il">
																		</div>
																	</div>
																	<div class="col-md-4 col-sm-4 col-xs-4">
																		<div class="form-group">
																			<label class="mb-0" for="ilce"><?= @$dil['txt43']; ?></label>
																			<input type="text" class="w-100" id="ilce" name="ilce">
																		</div>
																	</div>
																	<div class="col-md-4 col-sm-4 col-xs-4">
																		<div class="form-group">
																			<label class="mb-0" for="pkodu"><?= @$dil['txt44']; ?></label>
																			<input type="text" class="w-100" id="pkodu" name="pkodu">
																		</div>
																	</div>
																</div>
																<div class="row">
																	<div class="col-md-12 col-sm-12 col-xs-12">
																		<div class="form-group">
																			<label class="mb-0" for="adres"><?= @$dil['txt45']; ?></label>
																			<textarea class="w-100" rows="2" name="adres" id="adres"></textarea>
																		</div>
																	</div>
																</div>
																<div class="w-100">
																	<input type="checkbox" name="varsayilan" value="1" class="checkbox-custom" id="varsayilan">
																	<label class="checkbox-custom-label" for="varsayilan"><?= @$dil['txt46']; ?></label>
																</div>
															</div>
														</div>
														<div class="modal-footer">
															<button name="adres_ekle" class="button btn btn-primary p-2"><i class="fas fa-plus-circle"></i> <?= @$dil['txt47']; ?></button>
															<button type="button" class="button btn btn-default p-2" data-dismiss="modal"><i class="far fa-times-circle"></i> <?= @$dil['txt48']; ?></button>
														</div>
													</div>
												</div>
											</div>
										</td>
									</tr>
									<tr>
										<td colspan="2">
											<div class="form-group">
												<label for="domain"><?= @$dil['txt309']; ?> (Yoksa boş bırakınız)</label>
												<input type="text" name="domain" id="domain" class="form-control" value="<?php echo $_SESSION['siparisbilgi']['domain']; ?>" placeholder="example.com">
											</div>
										</td>
									</tr>
									<tr>
										<td colspan="2">
											<div class="form-group">
												<label for="mesaj"><?= @$dil['txt310']; ?></label>
												<textarea rows="2" name="mesaj" id="mesaj" class="form-control" placeholder="<?= @$dil['txt311']; ?>"><?php echo $_SESSION['siparisbilgi']['mesaj']; ?></textarea>
											</div>
										</td>
									</tr>
									<tr>
										<td colspan="3" class="align-middle">
											<div class="mt-3">
												<h4><strong><?= @$dil['txt50']; ?></strong></h4>

												<div class="custom-control custom-checkbox">
													<input type="radio" class="custom-control-input" id="hesapturu1" name="odeme_turu" value="1" <?php echo ($_SESSION['siparisbilgi']['odeme_turu'] == 1 ? 'checked' : ''); ?>>
													<label class="custom-control-label" for="hesapturu1"><?= @$dil['txt51']; ?> <small class="text-warning"><?= @$dil['txt52']; ?> : <?php echo TvERtXpE3w_tl_format($kredi['tutar']); ?> TL)</small></label>
												</div>

												<div class="custom-control custom-checkbox">
													<input type="radio" class="custom-control-input" id="hesapturu2" name="odeme_turu" value="2" <?php echo ($_SESSION['siparisbilgi']['odeme_turu'] == 2 ? 'checked' : ''); ?>>
													<label class="custom-control-label" for="hesapturu2"><?= @$dil['txt53']; ?></label>
												</div>

												<div class="custom-control custom-checkbox">
													<input type="radio" class="custom-control-input" id="hesapturu3" name="odeme_turu" value="3" <?php echo ($_SESSION['siparisbilgi']['odeme_turu'] == 3 ? 'checked' : ''); ?>>
													<label class="custom-control-label" for="hesapturu3"><?= @$dil['txt54']; ?></label>
												</div>

											</div>
											<div class="clear"></div>

											<br>
											<input type="hidden" name="siparisid" value="<?php echo $Sonuc['id']; ?>" />
											<input type="hidden" name="toplam_f" value="<?php echo my_number_format($toplam + $kdv); ?>" />
											<?php $Sonuc['tutar'] = $toplam; ?>
											<input type="hidden" name="url" value="sepet-satinal.html" />
											<button type="submit" name="satinal" class="btn btn-success"> <?= @$dil['txt55']; ?></button>
										</td>
									</tr>
								</tbody>
							</table>
						</form>
						<?php if ($_SESSION['siparis'] == "havale") { ?>
							<?php unset($_SESSION['siparis']); ?>
							<script type="text/javascript">
								$(window).load(function() {
									$('#havaleileode').modal({
										backdrop: 'static',
										keyboard: false
									});
								});
							</script>
							<!-- Havale İle Ödeme -->
							<div class="modal fade" id="havaleileode" role="dialog">
								<div class="modal-dialog modal-lg">

									<!-- Modal content-->
									<div class="modal-content">
										<div class="modal-header pb-2 pt-2" style="background:#38647A;">
											<h5 class="modal-title d-inline-block text-white"><?= @$dil['txt54']; ?></h5>
											<button type="button" class="close p-0 m-1" data-dismiss="modal"><i class="far fa-times-circle"></i></button>
										</div>
										<form action="_class/site_islem.php" method="post" autocomplete="off">
											<div class="modal-body">
												<h4 class="hesapinfobloktitle"><?= @$dil['txt56']; ?></h4>
												<p><?= @$dil['txt57']; ?></p>
												<div class="form-group">
													<label for="havale"><?= @$dil['txt58']; ?></label>
													<select class="form-control" name="havale" id="havale" required>
														<?php $BankaSorgu = $db->prepare("SELECT * FROM banka_hesaplari WHERE durum = ? ORDER BY id ASC");
														$BankaSorgu->execute(array("1"));
														$Bankaislem = $BankaSorgu->fetchALL(PDO::FETCH_ASSOC); ?>
														<?php foreach ($Bankaislem as $BankaSonuc) { ?>
															<option value="<?php echo $BankaSonuc['banka']; ?> , <?php echo $BankaSonuc['sube']; ?> , <?php echo $BankaSonuc['hesap']; ?>"><?php echo $BankaSonuc['banka']; ?> , <?php echo $BankaSonuc['sube']; ?> , <?php echo $BankaSonuc['hesap']; ?></option>
														<?php } ?>
													</select>
												</div>
											</div>
											<div class="modal-footer">
												<input type="hidden" name="id" value="<?php echo $Sonuc['id']; ?>" />
												<input type="hidden" name="satilan" value="webpaket" />
												<input type="hidden" name="url" value="web-paket-satinal/<?php echo $Sonuc['id']; ?>.html" />
												<button name="sepet_odeme_havale" class="button btn btn-primary p-2"><i class="fas fa-plus-circle"></i> <?= @$dil['txt59']; ?></button>
												<button type="button" class="button btn btn-default p-2" data-dismiss="modal"><i class="far fa-times-circle"></i> <?= @$dil['txt60']; ?></button>
											</div>
										</form>
									</div>

								</div>
							</div>
						<?php } ?>
						<?php if ($_SESSION['siparis'] == "kredi_karti") { ?>
							<?php unset($_SESSION['siparis']); ?>

							<script type="text/javascript">
								$(window).load(function() {
									$('#kredikartiileode').modal({
										backdrop: 'static',
										keyboard: false
									});
								});
							</script>
							<!-- Kerdi Kartı İle Ödeme -->
							<div class="modal fade" id="kredikartiileode" role="dialog">
								<div class="modal-dialog modal-lg">

									<!-- Modal content-->
									<div class="modal-content">
										<div class="modal-header pb-2 pt-2" style="background:#38647A;">
											<h5 class="modal-title d-inline-block text-white"><?= @$dil['txt53']; ?></h5>
											<button type="button" class="close p-0 m-1" data-dismiss="modal"><i class="far fa-times-circle"></i></button>
										</div>
										<form action="_class/site_islem.php" method="post" autocomplete="off">
											<div class="modal-body">
												<div style="width: 100%;margin: 0 auto;display: table;">
													<?php
													$mail_total 		= number_format($Sonuc['tutar'], 2, ',', '.');
													$merchant_id 		= magaza_no;
													$merchant_key 		= magaza_parola;
													$merchant_salt		= magaza_anahtar;
													$email				= $Bilgilerim['email'];
													$Sonuc['adi'] = "isim";
													if ($ayar['kdv'] == 1) {
														$stutar = ($Sonuc['tutar'] * 20) / 100 + $Sonuc['tutar'];
														$payment_amount		= intval($stutar * 100); //9.99 için 9.99 * 100 = 999 gönderilmelidir.
													} else {
														$payment_amount		= intval($Sonuc['tutar'] * 100); //9.99 için 9.99 * 100 = 999 gönderilmelidir.
													}
													$genel 				= number_format($Sonuc['tutar'], 2, ',', '.');
													$merchant_oid 		= time();
													$user_name 			= $Bilgilerim['ad'] . " " . $Bilgilerim['soyad'];
													$user_address 		= $_SESSION['siparisbilgi']['adres'];
													$user_phone 			= $Bilgilerim['telefon'];
													$merchant_ok_url 	= "" . url . "siparis-sonuc.html?sonuc=basarili";
													$merchant_fail_url 	= "" . url . "siparis-sonuc.html?sonuc=hata";
													$user_basket 		= "";
													$user_basket		= base64_encode(json_encode(array(
														$paytr_products,
													)));
													## Kullanıcının IP adresi
													if (isset($_SERVER["HTTP_CLIENT_IP"])) {
														$ip = $_SERVER["HTTP_CLIENT_IP"];
													} elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
														$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
													} else {
														$ip = $_SERVER["REMOTE_ADDR"];
													}
													$user_ip = $ip;
													$timeout_limit = "30";
													$debug_on = 0;
													$test_mode = 0;
													$no_installment	= 0;
													$max_installment = 0;
													$currency = "TL";
													$hash_str = $merchant_id . $user_ip . $merchant_oid . $email . $payment_amount . $user_basket . $no_installment . $max_installment . $currency . $test_mode;
													$paytr_token = base64_encode(hash_hmac('sha256', $hash_str . $merchant_salt, $merchant_key, true));
													$post_vals = array(
														'merchant_id' => $merchant_id,
														'user_ip' => $user_ip,
														'merchant_oid' => $merchant_oid,
														'email' => $email,
														'payment_amount' => $payment_amount,
														'paytr_token' => $paytr_token,
														'user_basket' => $user_basket,
														'debug_on' => $debug_on,
														'no_installment' => $no_installment,
														'max_installment' => $max_installment,
														'user_name' => $user_name,
														'user_address' => $user_address,
														'user_phone' => $user_phone,
														'merchant_ok_url' => $merchant_ok_url,
														'merchant_fail_url' => $merchant_fail_url,
														'timeout_limit' => $timeout_limit,
														'currency' => $currency,
														'test_mode' => $test_mode
													);
													$ch = curl_init();
													curl_setopt($ch, CURLOPT_URL, "https://www.paytr.com/odeme/api/get-token");
													curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
													curl_setopt($ch, CURLOPT_POST, 1);
													curl_setopt($ch, CURLOPT_POSTFIELDS, $post_vals);
													curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
													curl_setopt($ch, CURLOPT_TIMEOUT, 20);
													curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // Canlı da bu kısmı yorum satırına alalım SSL kontrol red ediyoruz burda !!!!!
													$result = @curl_exec($ch);
													if (curl_errno($ch))
														die("PAYTR IFRAME connection error. err:" . curl_error($ch));
													curl_close($ch);
													$result = json_decode($result, 1);
													if ($result['status'] == 'success') {
														//verilerin veri tabanına eklenmesi ve mail gönderme işlemi VB...
														$baslangic_tarih 	= date('Y-m-d H:i:s');

														$explode = explode(" ", $baslangic_tarih);
														$explode2 = explode("-", $explode[0]);
														$zaman = substr($explode[1], 0, 5);

														if ($explode2[1] == "01") $ay = "Ocak";
														elseif ($explode2[1] == "02") $ay = "Şubat";
														elseif ($explode2[1] == "03") $ay = "Mart";
														elseif ($explode2[1] == "04") $ay = "Nisan";
														elseif ($explode2[1] == "05") $ay = "Mayıs";
														elseif ($explode2[1] == "06") $ay = "Haziran";
														elseif ($explode2[1] == "07") $ay = "Temmuz";
														elseif ($explode2[1] == "08") $ay = "Ağustos";
														elseif ($explode2[1] == "09") $ay = "Eylül";
														elseif ($explode2[1] == "10") $ay = "Ekim";
														elseif ($explode2[1] == "11") $ay = "Kasım";
														elseif ($explode2[1] == "12") $ay = "Aralık";
														$new_tarih =  $explode2[2] . " " . $ay . " " . $explode2[0] . ", " . $zaman;
														$token = $result['token'];
														$odeme_yontemi 		= 'Kredi Kartı (Online Ödeme)';
														$logo			= url . tema . '/uploads/logo/footer/' . footerlogo;
														$domain_bilgi	= url;
														$mail_baslik = "";
														foreach ($paytr_products as $item) {
															$Sorgu2 = $db->prepare("SELECT * FROM yazilimlar WHERE id = ?");
															$Sorgu2->execute(array($item['product_id']));
															if ($Sorgu2->rowCount()) {
																$Sonuc = $Sorgu2->fetch(PDO::FETCH_ASSOC);
															}
															$sorgu = $db->prepare("INSERT INTO satilanlar SET
															uyeid				= :uyeid,
															tutar				= :tutar,
															tipi				= :tipi,
															tarih 				= :tarih,
															baslangic_tarih		= :baslangic_tarih,
															domain				= :domain,
															paket				= :paket,
															mesaj				= :mesaj,
															adres				= :adres,
															odeme_yontemi		= :odeme_yontemi,
															spno				= :spno,
															paket_baslik		= :paket_baslik,
															ip					= :ip");


															$Ekle = $sorgu->execute(array(
																'uyeid' 				=> $Bilgilerim['id'],
																'tutar' 				=> $Sonuc['tutar'],
																'tipi' 				=> "2",
																'tarih' 				=> $baslangic_tarih,
																'baslangic_tarih' 	=> $baslangic_tarih,
																'domain' 			=> $_SESSION['siparisbilgi']['domain'],
																'paket' 				=> $Sonuc['id'],
																'mesaj' 				=> $_SESSION['siparisbilgi']['mesaj'],
																'adres' 				=> $_SESSION['siparisbilgi']['adres'],
																'odeme_yontemi' 		=> $odeme_yontemi,
																'spno' 				=> "#" . $merchant_oid,
																'paket_baslik' 		=> $Sonuc['adi'],
																'ip'				=> $ip
															));
															$mail_baslik .= $Sonuc['adi'] . '<br>';
														}
														//MAİL
														//Kullanıcı Mail
														require_once "_class/class.phpmailer.php";
														$mail = new PHPMailer();
														$mail->IsSMTP(true);
														$mail->SMTPSecure = m_sertifika;
														$mail->From     = m_adresi;
														$mail->Sender   = m_adresi;
														$mail->AddAddress($Bilgilerim['email'], firma_adi);
														$mail->AddReplyTo = (m_adresi);
														$mail->FromName = firma_adi;
														$mail->Host     = m_server;
														$mail->SMTPAuth = true;
														$mail->Port     = m_port;
														$mail->CharSet = 'UTF-8';
														$mail->Username = m_adresi;
														$mail->Password = m_parola;
														$mail->Subject  = 'Siparişiniz Alındı';
														$mesaj = '
														<div style="background-color: #4b4f52; width: 100%; float: left; text-align: center; padding-bottom: 40px;">
														<div style="background-color: #fff; width: 650px; margin-left: auto; margin-right: auto; text-align: left; margin-top: 40px; border-bottom-width: 6px; border-bottom-style: solid; border-bottom-color: #ed4137;">
														<div style="float: left; margin-bottom: 0px; width: 650px; text-align: center;"><img class="" style="text-align: center; float: none; margin-top: 25px;" src="https://crm.ornek.com/uploads/mailsablon2.png" width="255" height="104" /></div>
														<div style="clear: both;"> </div>
														<div style="padding: 0px 25px;">
														<p style="color: #333; font-family: Calibri, Arial, Helvetica, sans-serif; font-size: 16px;">Sn. <strong>' . $Bilgilerim['ad'] . $Bilgilerim['soyad'] . '</strong></p>
														<p style="color: #333; font-family: Calibri, Arial, Helvetica, sans-serif; font-size: 16px;">Siparişiniz tarafımıza ulaşmıştır. En kısa sürede tarafınıza bilgi verilecektir.</p>
														<p style="color: #333; font-family: Calibri, Arial, Helvetica, sans-serif; font-size: 16px;">Bizi tercih ettiğiniz için teşekkür ederiz.</p>
														<p style="color: #333; font-family: Calibri, Arial, Helvetica, sans-serif; font-size: 16px;"><br /><strong style="color: red;">Sipariş Bilgileriniz;</strong><br /><strong>' . $mail_baslik . '</strong><br />» Sipariş Tarihi: <strong>' . $new_tarih . '</strong> <br />» Ödeme Yöntemi: <strong>Kredi Kartı (Online Ödeme)</strong> <br />» Ödenen Tutar: <strong>' . my_number_format($stutar) . ' TL</strong></p>
														<p><span style="color: #333333; font-family: Calibri, Arial, Helvetica, sans-serif; font-size: 16px;">İyi Çalışmalar Dileriz,</span></p>
														<p><span style="font-family: Calibri, Arial, Helvetica, sans-serif; font-size: 16px; color: #333333;">Saygılarımızla.</span><br /><span style="color: #333333; font-family: Calibri, Arial, Helvetica, sans-serif; font-size: 16px; font-weight: bold;">DN Kreatif İş Ortağım</span><br /><br /><br /></p>
														</div>
														</div>
														</div>
														<p style="color: #333; font-family: Calibri; font-size: 14px; margin-top: 10px; text-align: center; font-weight: bold;"> </p>
														';
														$mail->IsHTML(true);
														$mail->Body = str_replace('../', url . '/', $mesaj);
														$mail->Send();
														//Yönetici Mail
														$mail = new PHPMailer();
														$mail->IsSMTP(true);
														$mail->SMTPSecure = m_sertifika;
														$mail->From     = m_adresi;
														$mail->Sender   = m_adresi;
														$mail->AddAddress('isortagim@ornek.com', firma_adi);
														$mail->AddReplyTo = (m_adresi);
														$mail->FromName = firma_adi;
														$mail->Host     = m_server;
														$mail->SMTPAuth = true;
														$mail->Port     = m_port;
														$mail->CharSet = 'UTF-8';
														$mail->Username = m_adresi;
														$mail->Password = m_parola;
														$mail->Subject  = 'Yeni Sipariş';
														$mesaj = '
														<div style="background-color: #4b4f52; width: 100%; float: left; text-align: center; padding-bottom: 40px;">
														<div style="background-color: #fff; width: 650px; margin-left: auto; margin-right: auto; text-align: left; margin-top: 40px; border-bottom-width: 6px; border-bottom-style: solid; border-bottom-color: #ed4137;">
														<div style="float: left; margin-bottom: 0px; width: 650px; text-align: center;"><img class="" style="text-align: center; float: none; margin-top: 25px;" src="https://crm.ornek.com/uploads/mailsablon2.png" width="255" height="104" /></div>
														<div style="clear: both;"> </div>
														<div style="padding: 0px 25px;">
														<p style="color: #333; font-family: Calibri, Arial, Helvetica, sans-serif; font-size: 16px;">Sn. <strong>Yönetici, ' . $Bilgilerim['ad'] . $Bilgilerim['soyad'] . '</strong> İsimli Kullanıcı</p>
														<p style="color: #333; font-family: Calibri, Arial, Helvetica, sans-serif; font-size: 16px;">Yeni bir sipariş oluşturmuştur..</p>
														<p style="color: #333; font-family: Calibri, Arial, Helvetica, sans-serif; font-size: 16px;"><strong style="color: red;"> Kullanıcı bilgileri :<strong> <br> <strong style="color: #333; font-family: Calibri, Arial, Helvetica, sans-serif; font-size: 16px;">İsim Soyisim : ' . $Bilgilerim['ad'] . $Bilgilerim['soyad'] . '</strong> <br> <strong style="color: #333; font-family: Calibri, Arial, Helvetica, sans-serif; font-size: 16px;">E-Posta : ' . $Bilgilerim['email'] . '</strong> <br> <strong style="color: #333; font-family: Calibri, Arial, Helvetica, sans-serif; font-size: 16px;"> Telefon : ' . $Bilgilerim['telefon'] . '</strong> <br> </p>
														<p style="color: #333; font-family: Calibri, Arial, Helvetica, sans-serif; font-size: 16px;"><br /><strong style="color: red;">Sipariş Bilgileri;</strong><br /><strong>' . $mail_baslik . '</strong><br />» Sipariş Tarihi: <strong>' . $new_tarih . '</strong> <br />» Ödeme Yöntemi: <strong>Kredi Kartı (Online Ödeme)</strong> <br />» Ödenen Tutar: <strong>' . my_number_format($stutar) . ' TL</strong></p>
														<p><span style="color: #333333; font-family: Calibri, Arial, Helvetica, sans-serif; font-size: 16px;">İyi Çalışmalar Dileriz,</span></p>
														<p><span style="font-family: Calibri, Arial, Helvetica, sans-serif; font-size: 16px; color: #333333;">Saygılarımızla.</span><br /><span style="color: #333333; font-family: Calibri, Arial, Helvetica, sans-serif; font-size: 16px; font-weight: bold;">DN Kreatif İş Ortağım</span><br /><br /><br /></p>
														</div>
														</div>
														</div>
														<p style="color: #333; font-family: Calibri; font-size: 14px; margin-top: 10px; text-align: center; font-weight: bold;"> </p>
														';
														$mail->IsHTML(true);
														$mail->Body = str_replace('../', url . '/', $mesaj);
														$mail->Send();
														//end MAİL
														//Sepet Verilerini Silme
														$sepet = $db->prepare("DELETE FROM sepet WHERE user_id = ?");
														$sepet->execute(array($_SESSION['site_uyeid']));
													} else {
														die("PAYTR IFRAME failed. reason:" . $result['reason']);
													}
													?>
													<!-- Ödeme formunun açılması için gereken HTML kodlar / Başlangıç -->
													<script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
													<iframe src="https://www.paytr.com/odeme/guvenli/<?php echo $token; ?>" id="paytriframe" frameborder="0" scrolling="no" style="width: 100%;"></iframe>
													<script>
														iFrameResize({}, '#paytriframe');
													</script>
													<!-- Ödeme formunun açılması için gereken HTML kodlar / Bitiş -->
												</div>
											</div>
											<div class="modal-footer">
												<button type="button" class="button btn btn-default p-2" data-dismiss="modal"><i class="far fa-times-circle"></i> <?= @$dil['txt60']; ?></button>
											</div>
										</form>
									</div>

								</div>
							</div>
						<?php } ?>
						<?php if ($_SESSION['siparis'] == "kredi") { ?>
							<?php unset($_SESSION['siparis']); ?>

							<script type="text/javascript">
								$(window).load(function() {
									$('#krediileode').modal({
										backdrop: 'static',
										keyboard: false
									});
								});
							</script>
							<!-- Kerdi İle Ödeme -->
							<div class="modal fade" id="krediileode" role="dialog">
								<div class="modal-dialog modal-lg">

									<!-- Modal content-->
									<div class="modal-content">
										<div class="modal-header pb-2 pt-2" style="background:#38647A;">
											<h5 class="modal-title d-inline-block text-white"><?= @$dil['txt51']; ?></h5>
											<button type="button" class="close p-0 m-1" data-dismiss="modal"><i class="far fa-times-circle"></i></button>
										</div>
										<form action="_class/site_islem.php" method="post" autocomplete="off">
											<div class="modal-body">
												<h4 class="hesapinfobloktitle"><?= @$dil['txt61']; ?> <?php echo TvERtXpE3w_tl_format($kredi['tutar']); ?> TL</h4>
												<p><?= @$dil['txt62']; ?></p>
											</div>
											<div class="modal-footer">
												<input type="hidden" name="id" value="<?php echo $Sonuc['id']; ?>" />
												<input type="hidden" name="satilan" value="sepet" />
												<input type="hidden" name="url" value="sepet.html" />
												<button name="odeme_kredi" class="button btn btn-primary p-2"><i class="fas fa-plus-circle"></i> <?= @$dil['txt63']; ?></button>
												<button type="button" class="button btn btn-default p-2" data-dismiss="modal"><i class="far fa-times-circle"></i> <?= @$dil['txt60']; ?></button>
											</div>
										</form>
									</div>

								</div>
							</div>
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
<?php
#Yeni Adres Ekle
if ($_SESSION['adres_ekle'] == 'yes') {
	echo "
	<script>
	swal({
		type: 'success',
		title: '" . @$dil['txt11'] . "',
		text: '" . @$dil['txt64'] . "',
		confirmButtonText: '" . @$dil['txt15'] . "',
		timer: 5000
	})
	</script>";
	unset($_SESSION['adres_ekle']);
}
if ($_SESSION['adres_ekle'] == 'no') {
	echo "
	<script>
	swal({
		type:'error',
		title:'" . @$dil['txt16'] . "',
		text: '" . @$dil['txt17'] . "',
		confirmButtonText: '" . @$dil['txt15'] . "',
		timer: 5000
	})
	</script>";
	unset($_SESSION['adres_ekle']);
}
if ($_SESSION['adres_ekle'] == 'bos') {
	echo "
	<script>
	swal({
		type: 'warning',
		title:'" . @$dil['txt13'] . "',
		text: '" . @$dil['txt14'] . "',
		confirmButtonText: '" . @$dil['txt15'] . "',
		timer: 5000
	})
	</script>";
	unset($_SESSION['adres_ekle']);
}
?>