<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null; ?>
<?php if ($moduller['alan6'] != "1") {
    header("Location:" . $url . "/index.html");
}
?>
<?php
if (isset($_POST['kuponuygula'])) {
    $_SESSION['coupon'] = $_POST['kuponkodu'];
}

if (strip_tags(isset($_GET['id']))) {
    $Sorgu = $db->prepare("SELECT * FROM yazilimlar WHERE id = ? AND durum = ? AND dil = ?");
    $Sorgu->execute(array($_GET['id'], 1, $_SESSION['k_dil']));
    if ($Sorgu->rowCount()) {
        $Sonuc     = $Sorgu->fetch(PDO::FETCH_ASSOC);
        $Sonuc['tutar'] = bayilikindirimi($_SESSION['site_uyeid'], $Sonuc['tutar']);
        if (isset($_SESSION['coupon'])) {
            $indirimsiz = $Sonuc['tutar'];
            $Sonuc['tutar'] = kuponuygula($_SESSION['coupon'], $Sonuc['tutar']);
        }
    } else {
        header("Location:" . $url . "/404.html");
    }
} else {
    header("Location:" . $url . "/404.html");
}
?>
<?php if (!isset($_SESSION["site_uyeid"])) {
    $_SESSION['devam'] = "web-paket-satinal/" . $Sonuc['id'] . "";
    header("Location:" . $url . "/giris.html");
} else {
    unset($_SESSION['devam']);
}
?>



<div class="top-header overlay" style="background-image: url(<?php echo tema; ?>/uploads/arkaplan/uyelik/<?php echo $arkaplan['uyelik'] ?>)">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper">
                    <h1 class="heading"><?= @$dil['txt342']; ?></h1>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="ustbanner">
    <div class="container">
        <span><?= @$dil['txt27']; ?> <strong><?php echo $Bilgilerim['ad']; ?> <?php echo $Bilgilerim['soyad']; ?></strong>. <i><?= @$dil['txt28']; ?></i></span>

        <div class="ustsil"></div>

        <span class="ustson"><?= @$dil['txt29']; ?> <strong> <?php echo TvERtXpE3w_tarih($Bilgilerim['son_giris']); ?></strong> <?= @$dil['txt30']; ?> <div class="ustsil"></div>
            <?= @$dil['txt31']; ?> <strong><?php echo $Bilgilerim['ip']; ?></strong></span>
    </div>
</div>

<!-- ***** FILTER PLANS ***** -->
<div class="mixcontainer">
    <div class="container">

        <div id="wrapper" class="mt-4">

            <div class="row">
                <?php require_once("sitebar.php"); ?>
                <div class="col-md-9">
                    <div class="col-md-12 main-content">
                        <div class="title-area">
                            <h5 class="title">
                                <i class="fab fa-chrome"></i>
                                <?php echo $Sonuc['adi'] ?>
                            </h5>
                            <div class="pull-right">
                                <strong><a href="hesabim.html"><?= @$dil['txt32']; ?> </a></strong> /
                                <a href="web_paketlerim.html"><?= @$dil['txt283']; ?> </a>
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



                        <form action="_class/site_islem.php" method="POST" id="paketsatinal">
                            <table width="100%" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <td scope="col" class="text-left"><?= @$dil['txt40']; ?></td>
                                        <td scope="col" class="text-center"><?= @$dil['txt36']; ?></td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="align-middle text-left">
                                            <strong> <?php echo $Sonuc['adi'] ?></strong>
                                        </td>
                                        <td class="align-middle text-center">
                                            <?php if ((isset($indirimsiz)) && ($indirimsiz != $Sonuc['tutar'])) { ?>
                                                <strong style="text-decoration: line-through;color: red;"><?php echo my_number_format($indirimsiz); ?> TL.</strong>
                                            <?php } ?>
                                            <strong><?php echo my_number_format($Sonuc['tutar']); ?> TL.</strong>
                                        </td>
                                    </tr>
                                    <?php if ($ayar['kdv'] == 1) { ?>
                                        <tr>
                                            <td class="align-middle text-left">
                                                <strong> KDV (%20)</strong>
                                            </td>
                                            <td class="align-middle text-center">
                                                <?php
                                                $fiyat = ($Sonuc['tutar'] * 20) / 100;
                                                ?>
                                                <strong><?php echo my_number_format($fiyat); ?> TL.</strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="align-middle text-left">
                                                <strong> Toplam</strong>
                                            </td>
                                            <td class="align-middle text-center">
                                                <?php
                                                $fiyat = ($Sonuc['tutar'] * 20) / 100;
                                                $fiyat_toplam = $fiyat + $Sonuc['tutar'];
                                                ?>
                                                <strong><?php echo my_number_format($fiyat_toplam); ?> TL.</strong>
                                            </td>
                                        </tr>
                                    <?php } ?>

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
                                                <label for="domain"><?= @$dil['txt309']; ?></label>
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
                                            <input type="hidden" name="url" value="web-paket-satinal/<?php echo $Sonuc['id']; ?>.html" />
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
                                                <button name="odeme_havale" class="button btn btn-primary p-2"><i class="fas fa-plus-circle"></i> <?= @$dil['txt59']; ?></button>
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
                                                    if (defaultpayment == 1) {
                                                        $merchant_id         = magaza_no;
                                                        $merchant_key         = magaza_parola;
                                                        $merchant_salt        = magaza_anahtar;
                                                        $email                = $Bilgilerim['email'];

                                                        if ($ayar['kdv'] == 1) {
                                                            $stutar = ($Sonuc['tutar'] * 20) / 100 + $Sonuc['tutar'];
                                                            $payment_amount        = intval($stutar * 100); //9.99 için 9.99 * 100 = 999 gönderilmelidir.
                                                        } else {
                                                            $payment_amount        = intval($Sonuc['tutar'] * 100); //9.99 için 9.99 * 100 = 999 gönderilmelidir.
                                                        }
                                                        $genel                 = number_format($Sonuc['tutar'], 2, ',', '.');
                                                        $merchant_oid         = time();
                                                        $user_name             = $Bilgilerim['ad'] . " " . $Bilgilerim['soyad'];
                                                        $user_address         = $_SESSION['siparisbilgi']['adres'];
                                                        $user_phone             = $Bilgilerim['telefon'];
                                                        $merchant_ok_url     = "" . url . "siparis-sonuc.html?sonuc=basarili";
                                                        $merchant_fail_url     = "" . url . "siparis-sonuc.html?sonuc=hata";
                                                        $user_basket         = "";
                                                        $user_basket        = base64_encode(json_encode(array(
                                                            array("" . $Sonuc['adi'] . "", $genel, 1) // 1. ürün (Ürün Ad - Birim Fiyat - Adet )
                                                        )));

                                                        ############################################################################################

                                                        ## Kullanıcının IP adresi
                                                        if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                                                            $ip = $_SERVER["HTTP_CLIENT_IP"];
                                                        } elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                                                            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
                                                        } else {
                                                            $ip = $_SERVER["REMOTE_ADDR"];
                                                        }

                                                        ## !!! Eğer bu örnek kodu sunucuda değil local makinanızda çalıştırıyorsanız
                                                        ## buraya dış ip adresinizi (https://www.whatismyip.com/) yazmalısınız. Aksi halde geçersiz paytr_token hatası alırsınız.
                                                        $user_ip = $ip;
                                                        ##

                                                        ## İşlem zaman aşımı süresi - dakika cinsinden
                                                        $timeout_limit = "30";

                                                        ## Hata mesajlarının ekrana basılması için entegrasyon ve test sürecinde 1 olarak bırakın. Daha sonra 0 yapabilirsiniz.
                                                        $debug_on = hata_mesaj;

                                                        ## Mağaza canlı modda iken test işlem yapmak için 1 olarak gönderilebilir.
                                                        $test_mode = test_modu;

                                                        $no_installment    = taksit; // Taksit yapılmasını istemiyorsanız, sadece tek çekim sunacaksanız 1 yapın

                                                        ## Sayfada görüntülenecek taksit adedini sınırlamak istiyorsanız uygun şekilde değiştirin.
                                                        ## Sıfır (0) gönderilmesi durumunda yürürlükteki en fazla izin verilen taksit geçerli olur.
                                                        $max_installment = 0;

                                                        $currency = "TL";

                                                        ####### Bu kısımda herhangi bir değişiklik yapmanıza gerek yoktur. #######
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
                                                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                                                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                                                        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
                                                        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                                                        $result = @curl_exec($ch);

                                                        if (curl_errno($ch))
                                                            die("PAYTR IFRAME connection error. err:" . curl_error($ch));

                                                        curl_close($ch);

                                                        $result = json_decode($result, 1);
                                                        if ($result['status'] == 'success') {
                                                            $token = $result['token'];
                                                            $baslangic_tarih     = date('Y-m-d H:i:s');
                                                            $odeme_yontemi         = 'Kredi Kartı (Online Ödeme)';

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
                                                                'uyeid'                 => $Bilgilerim['id'],
                                                                'tutar'                 => floor($payment_amount / 100),
                                                                'tipi'                 => "2",
                                                                'tarih'                 => $baslangic_tarih,
                                                                'baslangic_tarih'     => $baslangic_tarih,
                                                                'domain'             => $_SESSION['siparisbilgi']['domain'],
                                                                'paket'                 => $Sonuc['id'],
                                                                'mesaj'                 => $_SESSION['siparisbilgi']['mesaj'],
                                                                'adres'                 => $_SESSION['siparisbilgi']['adres'],
                                                                'odeme_yontemi'         => $odeme_yontemi,
                                                                'spno'                 => "#" . $merchant_oid,
                                                                'paket_baslik'         => $Sonuc['adi'],
                                                                'ip'                => $user_ip
                                                            ));
                                                            
                                                            //MAİL
														//Kullanıcı Mail
														require_once "_class/class.phpmailer.php";
														$mail_baslik = "Siparişiniz Alındı";
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
														<p style="color: #333; font-family: Calibri, Arial, Helvetica, sans-serif; font-size: 16px;"><br /><strong style="color: red;">Sipariş Bilgileriniz;</strong><br /><strong>' . $mail_baslik . '</strong><br />» Sipariş Tarihi: <strong>' . $baslangic_tarih . '</strong> <br />» Ödeme Yöntemi: <strong>Kredi Kartı (Online Ödeme)</strong> <br />» Ödenen Tutar: <strong>' . floor($payment_amount / 100) . ' TL</strong></p>
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
														<p style="color: #333; font-family: Calibri, Arial, Helvetica, sans-serif; font-size: 16px;"><br /><strong style="color: red;">Sipariş Bilgileri;</strong><br /><strong>' . $mail_baslik . '</strong><br />» Sipariş Tarihi: <strong>' . $baslangic_tarih . '</strong> <br />» Ödeme Yöntemi: <strong>Kredi Kartı (Online Ödeme)</strong> <br />» Ödenen Tutar: <strong>' . floor($payment_amount / 100) . ' TL</strong></p>
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
                                                        } else {
                                                            die("PAYTR IFRAME failed. reason:" . $result['reason']);
                                                        }
                                                        #########################################################################

                                                    ?>

                                                        <!-- Ödeme formunun açılması için gereken HTML kodlar / Başlangıç -->
                                                        <script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
                                                        <iframe src="https://www.paytr.com/odeme/guvenli/<?php echo $token; ?>" id="paytriframe" frameborder="0" scrolling="no" style="width: 100%;"></iframe>
                                                        <script>
                                                            iFrameResize({}, '#paytriframe');
                                                        </script>
                                                        <!-- Ödeme formunun açılması için gereken HTML kodlar / Bitiş -->
                                                    <?php } elseif (defaultpayment == 2) {
                                                        $merchant_oid         = time();
                                                        $baslangic_tarih     = date('Y-m-d H:i:s');
                                                        $odeme_yontemi         = 'Kredi Kartı (Shopier)';
                                                        ## Kullanıcının IP adresi
                                                        if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                                                            $ip = $_SERVER["HTTP_CLIENT_IP"];
                                                        } elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                                                            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
                                                        } else {
                                                            $ip = $_SERVER["REMOTE_ADDR"];
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

                                                        if ($ayar['kdv'] == 1) {
                                                            $stutar = ($Sonuc['tutar'] * 20) / 100 + $Sonuc['tutar'];
                                                            $payment_amount        = $stutar; //9.99 için 9.99 * 100 = 999 gönderilmelidir.
                                                        } else {
                                                            $payment_amount        = $Sonuc['tutar']; //9.99 için 9.99 * 100 = 999 gönderilmelidir.
                                                        }


                                                        $Ekle = $sorgu->execute(array(
                                                            'uyeid'                 => $Bilgilerim['id'],
                                                            'tutar'                 => floor($payment_amount / 100),
                                                            'tipi'                 => "2",
                                                            'tarih'                 => $baslangic_tarih,
                                                            'baslangic_tarih'     => $baslangic_tarih,
                                                            'domain'             => $_SESSION['siparisbilgi']['domain'],
                                                            'paket'                 => $Sonuc['id'],
                                                            'mesaj'                 => $_SESSION['siparisbilgi']['mesaj'],
                                                            'adres'                 => $_SESSION['siparisbilgi']['adres'],
                                                            'odeme_yontemi'         => $odeme_yontemi,
                                                            'spno'                 => "#" . $merchant_oid,
                                                            'paket_baslik'         => $Sonuc['adi'],
                                                            'ip'                => $ip
                                                        ));

                                                        //Shopier
                                                        header('Location:https://shopier.com/ShowProductNew/products.php?id=' . $Sonuc['shopierid']);
                                                    } elseif (defaultpayment == 3) {

                                                        $merchant_oid         = time();
                                                        $baslangic_tarih     = date('Y-m-d H:i:s');
                                                        $odeme_yontemi         = 'Kredi Kartı (iyziLink)';
                                                        ## Kullanıcının IP adresi
                                                        if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                                                            $ip = $_SERVER["HTTP_CLIENT_IP"];
                                                        } elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                                                            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
                                                        } else {
                                                            $ip = $_SERVER["REMOTE_ADDR"];
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
                                                        if ($ayar['kdv'] == 1) {
                                                            $stutar = ($Sonuc['tutar'] * 20) / 100 + $Sonuc['tutar'];
                                                            $payment_amount        = $stutar; //9.99 için 9.99 * 100 = 999 gönderilmelidir.
                                                        } else {
                                                            $payment_amount        = $Sonuc['tutar']; //9.99 için 9.99 * 100 = 999 gönderilmelidir.
                                                        }
                                                        $Ekle = $sorgu->execute(array(
                                                            'uyeid'                 => $Bilgilerim['id'],
                                                            'tutar'                 => floor($payment_amount / 100),
                                                            'tipi'                 => "2",
                                                            'tarih'                 => $baslangic_tarih,
                                                            'baslangic_tarih'     => $baslangic_tarih,
                                                            'domain'             => $_SESSION['siparisbilgi']['domain'],
                                                            'paket'                 => $Sonuc['id'],
                                                            'mesaj'                 => $_SESSION['siparisbilgi']['mesaj'],
                                                            'adres'                 => $_SESSION['siparisbilgi']['adres'],
                                                            'odeme_yontemi'         => $odeme_yontemi,
                                                            'spno'                 => "#" . $merchant_oid,
                                                            'paket_baslik'         => $Sonuc['adi'],
                                                            'ip'                => $ip
                                                        ));
                                                        //Iyzico
                                                        header('Location:' . $Sonuc['iyzilink']);
                                                    } elseif (defaultpayment == 4) {


                                                        $merchant_oid         = time();
                                                        $baslangic_tarih     = date('Y-m-d H:i:s');
                                                        $odeme_yontemi         = 'Kredi Kartı (iyzico)';
                                                        ## Kullanıcının IP adresi
                                                        if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                                                            $ip = $_SERVER["HTTP_CLIENT_IP"];
                                                        } elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                                                            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
                                                        } else {
                                                            $ip = $_SERVER["REMOTE_ADDR"];
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

                                                        if ($ayar['kdv'] == 1) {
                                                            $stutar = ($Sonuc['tutar'] * 20) / 100 + $Sonuc['tutar'];
                                                            $payment_amount        = $stutar; //9.99 için 9.99 * 100 = 999 gönderilmelidir.
                                                        } else {
                                                            $payment_amount        = $Sonuc['tutar']; //9.99 için 9.99 * 100 = 999 gönderilmelidir.
                                                        }

                                                        $Ekle = $sorgu->execute(array(
                                                            'uyeid'                 => $Bilgilerim['id'],
                                                            'tutar'                 => floor($payment_amount / 100),
                                                            'tipi'                 => "2",
                                                            'tarih'                 => $baslangic_tarih,
                                                            'baslangic_tarih'     => $baslangic_tarih,
                                                            'domain'             => $_SESSION['siparisbilgi']['domain'],
                                                            'paket'                 => $Sonuc['id'],
                                                            'mesaj'                 => $_SESSION['siparisbilgi']['mesaj'],
                                                            'adres'                 => $_SESSION['siparisbilgi']['adres'],
                                                            'odeme_yontemi'         => $odeme_yontemi,
                                                            'spno'                 => $merchant_oid,
                                                            'paket_baslik'         => $Sonuc['adi'],
                                                            'ip'                => $ip
                                                        ));
                                                        $request = new \Iyzipay\Request\CreateCheckoutFormInitializeRequest();
                                                        $request->setLocale(\Iyzipay\Model\Locale::TR);
                                                        $request->setConversationId($merchant_oid);
                                                        $request->setBasketId($merchant_oid);
                                                        $request->setPrice($payment_amount);
                                                        $request->setPaidPrice($payment_amount);
                                                        $request->setCurrency(\Iyzipay\Model\Currency::TL);
                                                        $request->setPaymentGroup(\Iyzipay\Model\PaymentGroup::PRODUCT);
                                                        $request->setCallbackUrl("" . url . "iyzico.php");
                                                        $request->setEnabledInstallments(array(2, 3, 6, 9));

                                                        $buyer = new \Iyzipay\Model\Buyer();
                                                        $buyer->setId($Bilgilerim["id"]);
                                                        $buyer->setName($Bilgilerim['ad']);
                                                        $buyer->setSurname($Bilgilerim['soyad']);
                                                        $buyer->setGsmNumber($Bilgilerim['telefon']);
                                                        $buyer->setIdentityNumber($Bilgilerim['tc']);
                                                        $buyer->setEmail($Bilgilerim['email']);
                                                        $buyer->setRegistrationAddress($_SESSION['siparisbilgi']['adres']);
                                                        $buyer->setIp($ip);
                                                        $buyer->setCity("Istanbul");
                                                        $buyer->setCountry("Turkey");
                                                        $buyer->setZipCode("34732");
                                                        $request->setBuyer($buyer);

                                                        $shippingAddress = new \Iyzipay\Model\Address();
                                                        $shippingAddress->setContactName($Bilgilerim["ad"] . ' ' . $Bilgilerim["soyad"]);
                                                        $shippingAddress->setCity("Istanbul");
                                                        $shippingAddress->setCountry("Turkey");
                                                        $shippingAddress->setAddress($_SESSION['siparisbilgi']['adres']);
                                                        $shippingAddress->setZipCode("34742");
                                                        $request->setShippingAddress($shippingAddress);

                                                        $billingAddress = new \Iyzipay\Model\Address();
                                                        $billingAddress->setContactName($Bilgilerim["ad"] . ' ' . $Bilgilerim["soyad"]);
                                                        $billingAddress->setCity("Istanbul");
                                                        $billingAddress->setCountry("Turkey");
                                                        $billingAddress->setAddress($_SESSION['siparisbilgi']['adres']);
                                                        $billingAddress->setZipCode("34742");
                                                        $request->setBillingAddress($billingAddress);

                                                        $basketItems = array();
                                                        $firstBasketItem = new \Iyzipay\Model\BasketItem();
                                                        $firstBasketItem->setId("WP");
                                                        $firstBasketItem->setName("Web Paket");
                                                        $firstBasketItem->setCategory1("Web Paket");
                                                        $firstBasketItem->setItemType(\Iyzipay\Model\BasketItemType::PHYSICAL);
                                                        $firstBasketItem->setPrice($payment_amount);
                                                        $basketItems[0] = $firstBasketItem;
                                                        $request->setBasketItems($basketItems);

                                                        # make request
                                                        $checkoutFormInitialize = \Iyzipay\Model\CheckoutFormInitialize::create($request, Config::options($ayar['iyzico_apikey'], $ayar['iyzico_secret']));

                                                    ?>
                                                        <iframe src="<?= $checkoutFormInitialize->getPaymentPageUrl(); ?>" frameborder="0" scrolling="yes" style="width: 100%;height:500px;"></iframe>
                                                    <?php  } ?>
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
                                                <?php
                                                if ($ayar['kdv'] == 1) {
                                                    $stutar = ($Sonuc['tutar'] * 20) / 100 + $Sonuc['tutar'];
                                                    $payment_amount        = intval($stutar * 100); //9.99 için 9.99 * 100 = 999 gönderilmelidir.
                                                } else {
                                                    $payment_amount        = intval($Sonuc['tutar'] * 100); //9.99 için 9.99 * 100 = 999 gönderilmelidir.
                                                }
                                                $genel                 = number_format($Sonuc['tutar'], 2, ',', '.');
                                                ?>
                                                <input type="hidden" name="id" value="<?php echo $Sonuc['id']; ?>" />
                                                <input type="hidden" name="satilan" value="webpaket" />
                                                <input type="hidden" name="url" value="web-paket-satinal/<?php echo $Sonuc['id']; ?>.html" />
                                                <button name="odeme_kredi" class="button btn btn-primary p-2"><i class="fas fa-plus-circle"></i> <?= @$dil['txt63']; ?></button>
                                                <button type="button" class="button btn btn-default p-2" data-dismiss="modal"><i class="far fa-times-circle"></i> <?= @$dil['txt60']; ?></button>
                                            </div>
                                        </form>
                                    </div>

                                </div>
                            </div>
                        <?php } ?>
                    </div>


                </div>
            </div>

        </div>

    </div>
</div>
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