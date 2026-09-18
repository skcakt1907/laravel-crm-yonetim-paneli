<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null;?>
<?php if($moduller['alan6'] != "1")
{
    header("Location:".$url."/index.html");
}
?>
<?php

if(isset($_POST['kuponuygula'])){
    $_SESSION['coupon'] = $_POST['kuponkodu'];
}

if(!isset($_SESSION["site_uyeid"]))
{
    $_SESSION['devam'] = "alanadi-satinal/".strip_tags($_GET['alanadi'])."-".strip_tags($_GET['uzanti'])."-".strip_tags($_GET['zmnt'])."";
    header("Location:".$url."/giris.html");
}
else
{
    unset($_SESSION['devam']);
    $Sorgu = $db->prepare("SELECT * FROM alanadi WHERE id = ?");
    $Sorgu->execute(array(1));
    $Sonuc = $Sorgu->fetch(PDO::FETCH_ASSOC);
    $suzanti 	= json_decode($Sonuc['uzanti']);
    $skayit 		= json_decode($Sonuc['kayit']);
    $syenileme 	= json_decode($Sonuc['yenileme']);
    $suzantiID 	= array_search(".".strip_tags($_GET['uzanti']), $suzanti);
    $fiyat= 1;

    if($varsayilanadres)
    {
        $user_address 	= $varsayilanadres['adres']." / ".$varsayilanadres['ilce']." / ".$varsayilanadres['il']." - ".$varsayilanadres['pkodu'];
    }

    if($suzantiID !== FALSE)
    {
        $fiyat = (int) @$skayit[$suzantiID];
    }
    else
    {
        header("Location:".$url."/404.html");
    }
    if($_GET['alanadi'] == "" || $_GET['uzanti'] == "" || $_GET['zmnt'] == "")
    {
        header("Location:".$url."/404.html");
    }


    $domainfiyat = $fiyat*strip_tags($_GET['zmnt']);

    if(isset($_SESSION['coupon'])){
        $domainfiyat = kuponuygula($_SESSION['coupon'],$domainfiyat);
    }


    if($ayar['kdv']==1){
        $domainfiyat = ($domainfiyat * 20) / 100 + $domainfiyat;
    }
}
?>

<div class="top-header overlay" style="background-image: url(<?php echo tema;?>/uploads/arkaplan/uyelik/<?php echo $arkaplan['uyelik']?>)">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper">
                    <h1 class="heading"><?=@$dil['txt39'];?></h1>
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
                    <div class="col-md-12 main-content">
                        <div class="title-area">
                            <h5 class="title">
                                <i class="fas fa-globe"></i>
                                <?php echo strip_tags($_GET['alanadi']);?>.<?php echo strip_tags($_GET['uzanti']);?> - <?=@$dil['txt39'];?>
                            </h5>
                            <div class="pull-right">
                                <strong><a href="hesabim.html"><?=@$dil['txt32'];?> </a></strong> /
                                <a href="alan_adlarim.html"><?=@$dil['txt26'];?> </a>
                            </div>
                        </div>

                        <form action="" method="POST">
                            <table width="100%" class="table table-bordered">
                                <tbody>
                                <td colspan="1">
                                    <div class="form-group">
                                        <label for="mesaj"><?=@$dil['txt378'];?></label>
                                        <input type="text" name="kuponkodu" class="form-control" value="<?php echo $_SESSION['coupon'];?>">
                                    </div>
                                </td>
                                <td colspan="1">
                                    <div class="form-group">
                                        <button type="submit" name="kuponuygula" class="btn btn-success"> <?=@$dil['txt379'];?></button>
                                    </div>
                                </td>
                                </tbody>
                            </table>
                        </form>

                        <form action="_class/site_islem.php" method="POST" id="paketsatinal">
                            <table width="100%" class="table table-bordered">
                                <thead>
                                <tr>
                                    <td scope="col" class="text-left"><strong><?=@$dil['txt40'];?></strong></td>
                                    <td scope="col" class="text-center"><strong><?=@$dil['txt36'];?></strong></td>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td class="align-middle text-left">
                                        <?php echo $_GET['alanadi'];?>.<?php echo strip_tags($_GET['uzanti']);?>
                                    </td>
                                    <td class="align-middle text-center">
                                         <?php  $fiyat = $domainfiyat;
                                        $fiyat_gorunumu = number_format($fiyat, 0, ',', '.'); ?>
                                        <strong><?php echo $fiyat_gorunumu;?> TL.</strong><br> (<?php echo strip_tags($_GET['zmnt']);?> <?=@$dil['txt65'];?>)
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="2">
                                        <div class="form-group mt-4">
                                            <label for="mesaj"><?=@$dil['txt308'];?></label> <a href="" data-toggle="modal" data-target="#yeni_adres_ekle" data-backdrop="static" data-keyboard="false" class="green lbtn d-inline-block border border-0 p-0 pl-2">+ <?=@$dil['txt41'];?></a>
                                            <select class="form-control" name="adres_bilgisi" id="adres_bilgisi">
                                                <?php $ADRESSorgu = $db->prepare("SELECT * FROM adresler WHERE uyeid = ? ORDER BY varsayilan DESC");
                                                $ADRESSorgu->execute(array($Bilgilerim['id']));
                                                $ADRESislem = $ADRESSorgu->fetchALL(PDO::FETCH_ASSOC);?>
                                                <?php foreach ( $ADRESislem as $ADRESSonuc ){?>
                                                    <option value="<?php echo $ADRESSonuc['adres'];?> / <?php echo $ADRESSonuc['ilce'];?> / <?php echo $ADRESSonuc['il'];?> - <?php echo $ADRESSonuc['pkodu'];?>"><?php echo $ADRESSonuc['adres'];?> / <?php echo $ADRESSonuc['ilce'];?> / <?php echo $ADRESSonuc['il'];?> - <?php echo $ADRESSonuc['pkodu'];?></option>
                                                <?php }?>
                                            </select>
                                        </div>
                                        <!-- Yeni Adres Ekle -->
                                        <div class="modal fade" id="yeni_adres_ekle" role="dialog">
                                            <div class="modal-dialog modal-md">

                                                <!-- Modal content-->
                                                <div class="modal-content">
                                                    <div class="modal-header pb-2 pt-2" style="background:#38647A;">
                                                        <h5 class="modal-title d-inline-block text-white"><?=@$dil['txt41'];?></h5>
                                                        <button type="button" class="close p-0 m-1" data-dismiss="modal"><i class="far fa-times-circle"></i></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="adresurl" value="<?php echo $_SERVER['REQUEST_URI'];?>" />
                                                        <div class="container-fluid">
                                                            <div class="row">
                                                                <div class="col-md-4 col-sm-4 col-xs-4">
                                                                    <div class="form-group">
                                                                        <label class="mb-0" for="il"><?=@$dil['txt42'];?></label>
                                                                        <input type="text" class="w-100" id="il" name="il">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4 col-sm-4 col-xs-4">
                                                                    <div class="form-group">
                                                                        <label class="mb-0" for="ilce"><?=@$dil['txt43'];?></label>
                                                                        <input type="text" class="w-100" id="ilce" name="ilce">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4 col-sm-4 col-xs-4">
                                                                    <div class="form-group">
                                                                        <label class="mb-0" for="pkodu"><?=@$dil['txt44'];?></label>
                                                                        <input type="text" class="w-100" id="pkodu" name="pkodu">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-12 col-sm-12 col-xs-12">
                                                                    <div class="form-group">
                                                                        <label class="mb-0" for="adres"><?=@$dil['txt45'];?></label>
                                                                        <textarea class="w-100" rows="2" name="adres" id="adres"></textarea>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="w-100">
                                                                <input type="checkbox" name="varsayilan" value="1" class="checkbox-custom" id="varsayilan">
                                                                <label class="checkbox-custom-label" for="varsayilan"><?=@$dil['txt46'];?></label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button name="adres_ekle" class="button btn btn-primary p-2"><i class="fas fa-plus-circle"></i> <?=@$dil['txt47'];?></button>
                                                        <button type="button" class="button btn btn-default p-2" data-dismiss="modal"><i class="far fa-times-circle"></i> <?=@$dil['txt48'];?></button>
                                                    </div>

                                                </div>

                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="2">
                                        <div class="form-group">
                                            <label for="mesaj"><?=@$dil['txt49'];?></label>
                                            <input type="hidden" name="domain" value="<?php echo strip_tags($_GET['alanadi']);?>.<?php echo strip_tags($_GET['uzanti']);?>" />
                                            <textarea rows="2" name="mesaj" class="form-control" placeholder="ns1.example.com
ns2.example.com"><?php echo $_SESSION['siparisbilgi']['mesaj'];?></textarea>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="align-middle">
                                        <div class="mt-3">
                                            <h4><strong><?=@$dil['txt50'];?></strong></h4>

                                            <div class="custom-control custom-checkbox">
                                                <input type="radio" class="custom-control-input" id="hesapturu1" name="odeme_turu" value="1" <?php echo($_SESSION['siparisbilgi']['odeme_turu'] == 1 ? 'checked' : '');?>>
                                                <label class="custom-control-label" for="hesapturu1"><?=@$dil['txt51'];?> <small class="text-warning"><?=@$dil['txt52'];?> : <?php echo TvERtXpE3w_tl_format($kredi['tutar']);?> TL)</small></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="radio" class="custom-control-input" id="hesapturu2" name="odeme_turu" value="2" <?php echo($_SESSION['siparisbilgi']['odeme_turu'] == 2 ? 'checked' : '');?>>
                                                <label class="custom-control-label" for="hesapturu2"><?=@$dil['txt53'];?></label>
                                            </div>

                                            <div class="custom-control custom-checkbox">
                                                <input type="radio" class="custom-control-input" id="hesapturu3" name="odeme_turu" value="3" <?php echo($_SESSION['siparisbilgi']['odeme_turu'] == 3 ? 'checked' : '');?>>
                                                <label class="custom-control-label" for="hesapturu3"><?=@$dil['txt54'];?></label>
                                            </div>

                                        </div>
                                        <div class="clear"></div>

                                        <br>
                                        <input type="hidden" name="siparisid" value="<?php echo $Sonuc['id']; ?>" />
                                        <input type="hidden" name="url" value="alanadi-satinal/<?php echo strip_tags($_GET['alanadi']);?>-<?php echo strip_tags($_GET['uzanti']);?>-<?php echo strip_tags($_GET['zmnt']);?>.html" />
                                        <button type="submit" name="satinal" class="btn btn-success"> <?=@$dil['txt55'];?></button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </form>
                        <?php if($_SESSION['siparis'] == "havale"){?>
                            <?php unset($_SESSION['siparis']);?>
                            <script type="text/javascript">
                                $(window).load(function(){
                                    $('#havaleileode').modal({backdrop: 'static', keyboard: false});
                                });
                            </script>
                            <!-- Havale İle Ödeme -->
                            <div class="modal fade" id="havaleileode" role="dialog">
                                <div class="modal-dialog modal-lg">

                                    <!-- Modal content-->
                                    <div class="modal-content">
                                        <div class="modal-header pb-2 pt-2" style="background:#38647A;">
                                            <h5 class="modal-title d-inline-block text-white"><?=@$dil['txt54'];?></h5>
                                            <button type="button" class="close p-0 m-1" data-dismiss="modal"><i class="far fa-times-circle"></i></button>
                                        </div>
                                        <form action="_class/site_islem.php" method="post" autocomplete="off">
                                            <div class="modal-body">
                                                <h4 class="hesapinfobloktitle"><?=@$dil['txt56'];?></h4>
                                                <p><?=@$dil['txt57'];?></p>
                                                <div class="form-group">
                                                    <label for="havale"><?=@$dil['txt58'];?></label>
                                                    <select class="form-control" name="havale" id="havale" required>
                                                        <?php $BankaSorgu = $db->prepare("SELECT * FROM banka_hesaplari WHERE durum = ? ORDER BY id ASC");
                                                        $BankaSorgu->execute(array("1"));
                                                        $Bankaislem = $BankaSorgu->fetchALL(PDO::FETCH_ASSOC);?>
                                                        <?php foreach ( $Bankaislem as $BankaSonuc ){?>
                                                            <option value="<?php echo $BankaSonuc['banka']; ?> , <?php echo $BankaSonuc['sube']; ?> , <?php echo $BankaSonuc['hesap']; ?>"><?php echo $BankaSonuc['banka']; ?> , <?php echo $BankaSonuc['sube']; ?> , <?php echo $BankaSonuc['hesap']; ?></option>
                                                        <?php }?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <input type="hidden" name="id" value="<?php echo $Sonuc['id']; ?>" />
                                                <input type="hidden" name="satilan" value="domain" />
                                                <input type="hidden" name="zmnt" value="<?php echo strip_tags($_GET['zmnt']);?>" />
                                                <input type="hidden" name="tutar" value="<?php echo $domainfiyat;?>" />
                                                <input type="hidden" name="url" value="alanadi-satinal/<?php echo strip_tags($_GET['alanadi']);?>-<?php echo strip_tags($_GET['uzanti']);?>-<?php echo strip_tags($_GET['zmnt']);?>.html" />
                                                <button name="odeme_havale" class="button btn btn-primary p-2"><i class="fas fa-plus-circle"></i> <?=@$dil['txt59'];?></button>
                                                <button type="button" class="button btn btn-default p-2" data-dismiss="modal"><i class="far fa-times-circle"></i> <?=@$dil['txt60'];?></button>
                                            </div>
                                        </form>
                                    </div>

                                </div>
                            </div>
                        <?php }?>

                        <?php if($_SESSION['siparis'] == "kredi_karti"){?>
                            <?php unset($_SESSION['siparis']);?>

                            <script type="text/javascript">
                                $(window).load(function(){
                                    $('#kredikartiileode').modal({backdrop: 'static', keyboard: false});
                                });
                            </script>
                            <!-- Kerdi Kartı İle Ödeme -->
                            <div class="modal fade" id="kredikartiileode" role="dialog">
                                <div class="modal-dialog modal-lg">

                                    <!-- Modal content-->
                                    <div class="modal-content">
                                        <div class="modal-header pb-2 pt-2" style="background:#38647A;">
                                            <h5 class="modal-title d-inline-block text-white"><?=@$dil['txt53'];?></h5>
                                            <button type="button" class="close p-0 m-1" data-dismiss="modal"><i class="far fa-times-circle"></i></button>
                                        </div>
                                        <form action="_class/site_islem.php" method="post" autocomplete="off">
                                            <div class="modal-body">
                                                <div style="width: 100%;margin: 0 auto;display: table;">
                                                    <?php
                                                    if(defaultpayment==1){
                                                        $merchant_id 		= magaza_no;
                                                        $merchant_key 		= magaza_parola;
                                                        $merchant_salt		= magaza_anahtar;
                                                        $email				= $Bilgilerim['email'];
                                                        $payment_amount		= intval($domainfiyat*100); //9.99 için 9.99 * 100 = 999 gönderilmelidir.
                                                        $genel 				= number_format($domainfiyat, 2, ',', '.');
                                                        $merchant_oid 		= time();
                                                        $user_name 			= $Bilgilerim['ad']." ".$Bilgilerim['soyad'];
                                                        $user_address 		= $_SESSION['siparisbilgi']['adres'];
                                                        $user_phone 			= $Bilgilerim['telefon'];
                                                        $merchant_ok_url 	= "".url."siparis-sonuc.html?sonuc=basarili";
                                                        $merchant_fail_url 	= "".url."siparis-sonuc.html?sonuc=hata";
                                                        $user_basket 		= "";
                                                        $bilgiver			= strip_tags($_GET['alanadi']).".".strip_tags($_GET['uzanti']);
                                                        $user_basket		= base64_encode(json_encode(array(
                                                            array("".$bilgiver."", $genel , 1) // 1. ürün (Ürün Ad - Birim Fiyat - Adet )
                                                        )));

                                                        ############################################################################################

                                                        ## Kullanıcının IP adresi
                                                        if( isset( $_SERVER["HTTP_CLIENT_IP"] ) ) {
                                                            $ip = $_SERVER["HTTP_CLIENT_IP"];
                                                        } elseif( isset( $_SERVER["HTTP_X_FORWARDED_FOR"] ) ) {
                                                            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
                                                        } else {
                                                            $ip = $_SERVER["REMOTE_ADDR"];
                                                        }

                                                        ## !!! Eğer bu örnek kodu sunucuda değil local makinanızda çalıştırıyorsanız
                                                        ## buraya dış ip adresinizi (https://www.whatismyip.com/) yazmalısınız. Aksi halde geçersiz paytr_token hatası alırsınız.
                                                        $user_ip=$ip;
                                                        ##


                                                    ## İşlem zaman aşımı süresi - dakika cinsinden
                                                    $timeout_limit = "30";

                                                    ## Hata mesajlarının ekrana basılması için entegrasyon ve test sürecinde 1 olarak bırakın. Daha sonra 0 yapabilirsiniz.
                                                    $debug_on = hata_mesaj;

                                                    ## Mağaza canlı modda iken test işlem yapmak için 1 olarak gönderilebilir.
                                                    $test_mode = test_modu;

                                                    $no_installment	= taksit; // Taksit yapılmasını istemiyorsanız, sadece tek çekim sunacaksanız 1 yapın

                                                    ## Sayfada görüntülenecek taksit adedini sınırlamak istiyorsanız uygun şekilde değiştirin.
                                                    ## Sıfır (0) gönderilmesi durumunda yürürlükteki en fazla izin verilen taksit geçerli olur.
                                                    $max_installment = 0;

                                                    $currency = "TL";

                                                    ####### Bu kısımda herhangi bir değişiklik yapmanıza gerek yoktur. #######
                                                    $hash_str = $merchant_id .$user_ip .$merchant_oid .$email .$payment_amount .$user_basket.$no_installment.$max_installment.$currency.$test_mode;
                                                    $paytr_token=base64_encode(hash_hmac('sha256',$hash_str.$merchant_salt,$merchant_key,true));
                                                    $post_vals=array(
                                                        'merchant_id'=>$merchant_id,
                                                        'user_ip'=>$user_ip,
                                                        'merchant_oid'=>$merchant_oid,
                                                        'email'=>$email,
                                                        'payment_amount'=>$payment_amount,
                                                        'paytr_token'=>$paytr_token,
                                                        'user_basket'=>$user_basket,
                                                        'debug_on'=>$debug_on,
                                                        'no_installment'=>$no_installment,
                                                        'max_installment'=>$max_installment,
                                                        'user_name'=>$user_name,
                                                        'user_address'=>$user_address,
                                                        'user_phone'=>$user_phone,
                                                        'merchant_ok_url'=>$merchant_ok_url,
                                                        'merchant_fail_url'=>$merchant_fail_url,
                                                        'timeout_limit'=>$timeout_limit,
                                                        'currency'=>$currency,
                                                        'test_mode'=>$test_mode
                                                    );

                                                    $ch=curl_init();
                                                    curl_setopt($ch, CURLOPT_URL, "https://www.paytr.com/odeme/api/get-token");
                                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                                    curl_setopt($ch, CURLOPT_POST, 1) ;
                                                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_vals);
                                                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                                                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                                                    curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
                                                    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                                                    $result = @curl_exec($ch);

                                                    if(curl_errno($ch))
                                                        die("PAYTR IFRAME connection error. err:".curl_error($ch));

                                                    curl_close($ch);

                                                    $result=json_decode($result,1);
                                                    if($result['status']=='success')
                                                    {
                                                        $token=$result['token'];
                                                        if(strip_tags($_GET['zmnt']) == 1)
                                                        {
                                                            $bitis_tarih	= date("Y-m-d H:i:s",strtotime("+1 year"));
                                                        }
                                                        if(strip_tags($_GET['zmnt']) == 2)
                                                        {
                                                            $bitis_tarih	= date("Y-m-d H:i:s",strtotime("+2 year"));
                                                        }
                                                        if(strip_tags($_GET['zmnt']) == 3)
                                                        {
                                                            $bitis_tarih	= date("Y-m-d H:i:s",strtotime("+3 year"));
                                                        }
                                                        if(strip_tags($_GET['zmnt']) == 4)
                                                        {
                                                            $bitis_tarih	= date("Y-m-d H:i:s",strtotime("+4 year"));
                                                        }
                                                        if(strip_tags($_GET['zmnt']) == 5)
                                                        {
                                                            $bitis_tarih	= date("Y-m-d H:i:s",strtotime("+5 year"));
                                                        }
                                                        $baslangic_tarih 	= date('Y-m-d H:i:s');
                                                        $odeme_yontemi 		= 'Kredi Kartı (Online Ödeme)';

                                                        $sorgu = $db->prepare("INSERT INTO satilanlar SET
														uyeid				= :uyeid,
														tutar				= :tutar,
														tipi				= :tipi,
														tarih 				= :tarih,
														baslangic_tarih		= :baslangic_tarih,
														bitis_tarih			= :bitis_tarih,
														odenen_tarih		= :odenen_tarih,
														domain				= :domain,
														bilgiler			= :bilgiler,
														adres				= :adres,
														odeme_yontemi		= :odeme_yontemi,
														zmnt				= :zmnt,
														spno				= :spno,
														ip					= :ip");
                                                        $Ekle = $sorgu->execute(array(
                                                            'uyeid' 				=> $Bilgilerim['id'],
                                                            'tutar' 				=> $domainfiyat,
                                                            'tipi' 				=> "0",
                                                            'tarih' 				=> $baslangic_tarih,
                                                            'baslangic_tarih' 	=> $baslangic_tarih,
                                                            'bitis_tarih' 		=> $bitis_tarih,
                                                            'odenen_tarih' 		=> $baslangic_tarih,
                                                            'domain' 			=> $bilgiver,
                                                            'bilgiler' 			=> $_SESSION['siparisbilgi']['mesaj'],
                                                            'adres' 				=> $_SESSION['siparisbilgi']['adres'],
                                                            'odeme_yontemi' 		=> $odeme_yontemi,
                                                            'zmnt' 				=> strip_tags($_GET['zmnt']),
                                                            'spno' 				=> "#".$merchant_oid,
                                                            'ip'				=> $user_ip
                                                        ));
                                                    }
                                                    else
                                                    {
                                                        die("PAYTR IFRAME failed. reason:".$result['reason']);
                                                    }
                                                    #########################################################################

                                                    ?>

                                                    <!-- Ödeme formunun açılması için gereken HTML kodlar / Başlangıç -->
                                                    <script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
                                                    <iframe src="https://www.paytr.com/odeme/guvenli/<?php echo $token;?>" id="paytriframe" frameborder="0" scrolling="no" style="width: 100%;"></iframe>
                                                    <script>iFrameResize({},'#paytriframe');</script>
                                                    <!-- Ödeme formunun açılması için gereken HTML kodlar / Bitiş -->
                                                    <?php }else{
                                                        $email				= $Bilgilerim['email'];
                                                        $payment_amount		= intval($domainfiyat*100); //9.99 için 9.99 * 100 = 999 gönderilmelidir.
                                                        $genel 				= number_format($domainfiyat, 2, ',', '.');
                                                        $merchant_oid 		= time();
                                                        $user_name 			= $Bilgilerim['ad']." ".$Bilgilerim['soyad'];
                                                        $user_address 		= $_SESSION['siparisbilgi']['adres'];
                                                        $user_phone 			= $Bilgilerim['telefon'];
                                                        $merchant_ok_url 	= "".url."siparis-sonuc.html?sonuc=basarili";
                                                        $merchant_fail_url 	= "".url."siparis-sonuc.html?sonuc=hata";
                                                        $user_basket 		= "";
                                                        $bilgiver			= strip_tags($_GET['alanadi']).".".strip_tags($_GET['uzanti']);
                                                        $user_basket		= base64_encode(json_encode(array(
                                                            array("".$bilgiver."", $genel , 1) // 1. ürün (Ürün Ad - Birim Fiyat - Adet )
                                                        )));

                                                        ############################################################################################

                                                        ## Kullanıcının IP adresi
                                                        if( isset( $_SERVER["HTTP_CLIENT_IP"] ) ) {
                                                            $ip = $_SERVER["HTTP_CLIENT_IP"];
                                                        } elseif( isset( $_SERVER["HTTP_X_FORWARDED_FOR"] ) ) {
                                                            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
                                                        } else {
                                                            $ip = $_SERVER["REMOTE_ADDR"];
                                                        }

                                                        ## !!! Eğer bu örnek kodu sunucuda değil local makinanızda çalıştırıyorsanız
                                                        ## buraya dış ip adresinizi (https://www.whatismyip.com/) yazmalısınız. Aksi halde geçersiz paytr_token hatası alırsınız.
                                                        $user_ip=$ip;


                                                        if(strip_tags($_GET['zmnt']) == 1)
                                                        {
                                                            $bitis_tarih	= date("Y-m-d H:i:s",strtotime("+1 year"));
                                                        }
                                                        if(strip_tags($_GET['zmnt']) == 2)
                                                        {
                                                            $bitis_tarih	= date("Y-m-d H:i:s",strtotime("+2 year"));
                                                        }
                                                        if(strip_tags($_GET['zmnt']) == 3)
                                                        {
                                                            $bitis_tarih	= date("Y-m-d H:i:s",strtotime("+3 year"));
                                                        }
                                                        if(strip_tags($_GET['zmnt']) == 4)
                                                        {
                                                            $bitis_tarih	= date("Y-m-d H:i:s",strtotime("+4 year"));
                                                        }
                                                        if(strip_tags($_GET['zmnt']) == 5)
                                                        {
                                                            $bitis_tarih	= date("Y-m-d H:i:s",strtotime("+5 year"));
                                                        }
                                                        $baslangic_tarih 	= date('Y-m-d H:i:s');
                                                        $odeme_yontemi 		= 'Kredi Kartı (Online Ödeme)';

                                                        $sorgu = $db->prepare("INSERT INTO satilanlar SET
														uyeid				= :uyeid,
														tutar				= :tutar,
														tipi				= :tipi,
														tarih 				= :tarih,
														baslangic_tarih		= :baslangic_tarih,
														bitis_tarih			= :bitis_tarih,
														odenen_tarih		= :odenen_tarih,
														domain				= :domain,
														bilgiler			= :bilgiler,
														adres				= :adres,
														odeme_yontemi		= :odeme_yontemi,
														zmnt				= :zmnt,
														spno				= :spno,
														ip					= :ip");
                                                        $Ekle = $sorgu->execute(array(
                                                            'uyeid' 				=> $Bilgilerim['id'],
                                                            'tutar' 				=> $domainfiyat,
                                                            'tipi' 				=> "0",
                                                            'tarih' 				=> $baslangic_tarih,
                                                            'baslangic_tarih' 	=> $baslangic_tarih,
                                                            'bitis_tarih' 		=> $bitis_tarih,
                                                            'odenen_tarih' 		=> $baslangic_tarih,
                                                            'domain' 			=> $bilgiver,
                                                            'bilgiler' 			=> $_SESSION['siparisbilgi']['mesaj'],
                                                            'adres' 				=> $_SESSION['siparisbilgi']['adres'],
                                                            'odeme_yontemi' 		=> $odeme_yontemi,
                                                            'zmnt' 				=> strip_tags($_GET['zmnt']),
                                                            'spno' 				=> $merchant_oid,
                                                            'ip'				=> $user_ip
                                                        ));


                                                    $request = new \Iyzipay\Request\CreateCheckoutFormInitializeRequest();
                                                    $request->setLocale(\Iyzipay\Model\Locale::TR);
                                                    $request->setconversationId($merchant_oid);
                                                    $request->setBasketId($merchant_oid);
                                                    $request->setPrice($domainfiyat);
                                                    $request->setPaidPrice($domainfiyat);
                                                    $request->setCurrency(\Iyzipay\Model\Currency::TL);
                                                    $request->setPaymentGroup(\Iyzipay\Model\PaymentGroup::PRODUCT);
                                                    $request->setCallbackUrl("".url."iyzico.php");
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
                                                    $shippingAddress->setContactName($Bilgilerim["ad"] .' '.$Bilgilerim["soyad"]);
                                                    $shippingAddress->setCity("Istanbul");
                                                    $shippingAddress->setCountry("Turkey");
                                                    $shippingAddress->setAddress($_SESSION['siparisbilgi']['adres']);
                                                    $shippingAddress->setZipCode("34742");
                                                    $request->setShippingAddress($shippingAddress);

                                                    $billingAddress = new \Iyzipay\Model\Address();
                                                    $billingAddress->setContactName($Bilgilerim["ad"] .' '.$Bilgilerim["soyad"]);
                                                    $billingAddress->setCity("Istanbul");
                                                    $billingAddress->setCountry("Turkey");
                                                    $billingAddress->setAddress($_SESSION['siparisbilgi']['adres']);
                                                    $billingAddress->setZipCode("34742");
                                                    $request->setBillingAddress($billingAddress);

                                                    $basketItems = array();
                                                    $firstBasketItem = new \Iyzipay\Model\BasketItem();
                                                    $firstBasketItem->setId("WP");
                                                    $firstBasketItem->setName("DOMAİN");
                                                    $firstBasketItem->setCategory1("DOMAİN");
                                                    $firstBasketItem->setItemType(\Iyzipay\Model\BasketItemType::PHYSICAL);
                                                    $firstBasketItem->setPrice($domainfiyat);
                                                    $basketItems[0] = $firstBasketItem;
                                                    $request->setBasketItems($basketItems);

                                                    # make request
                                                    $checkoutFormInitialize = \Iyzipay\Model\CheckoutFormInitialize::create($request, Config::options($ayar['iyzico_apikey'],$ayar['iyzico_secret']));
                                                    
                                                    ?>
                                                        <iframe src="<?=$checkoutFormInitialize->getPaymentPageUrl();?>"  frameborder="0" scrolling="yes" style="width: 100%;height:500px;"></iframe>
                                                    <?php  } ?>


                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="button btn btn-default p-2" data-dismiss="modal"><i class="far fa-times-circle"></i> <?=@$dil['txt60'];?></button>
                                            </div>
                                        </form>
                                    </div>

                                </div>
                            </div>
                        <?php }?>

                        <?php if($_SESSION['siparis'] == "kredi"){?>
                            <?php unset($_SESSION['siparis']);?>

                            <script type="text/javascript">
                                $(window).load(function(){
                                    $('#krediileode').modal({backdrop: 'static', keyboard: false});
                                });
                            </script>
                            <!-- Kerdi İle Ödeme -->
                            <div class="modal fade" id="krediileode" role="dialog">
                                <div class="modal-dialog modal-lg">

                                    <!-- Modal content-->
                                    <div class="modal-content">
                                        <div class="modal-header pb-2 pt-2" style="background:#38647A;">
                                            <h5 class="modal-title d-inline-block text-white"><?=@$dil['txt51'];?></h5>
                                            <button type="button" class="close p-0 m-1" data-dismiss="modal"><i class="far fa-times-circle"></i></button>
                                        </div>
                                        <form action="_class/site_islem.php" method="post" autocomplete="off">
                                            <div class="modal-body">
                                                <h4 class="hesapinfobloktitle"><?=@$dil['txt61'];?> <?php echo TvERtXpE3w_tl_format($kredi['tutar']);?> TL</h4>
                                                <p><?=@$dil['txt62'];?></p>
                                            </div>
                                            <div class="modal-footer">
                                                <input type="hidden" name="id" value="<?php echo $Sonuc['id']; ?>" />
                                                <input type="hidden" name="satilan" value="domain" />
                                                <input type="hidden" name="zmnt" value="<?php echo strip_tags($_GET['zmnt']);?>" />
                                                <input type="hidden" name="tutar" value="<?php echo $domainfiyat;?>" />
                                                <input type="hidden" name="url" value="alanadi-satinal/<?php echo strip_tags($_GET['alanadi']);?>-<?php echo strip_tags($_GET['uzanti']);?>-<?php echo strip_tags($_GET['zmnt']);?>.html" />
                                                <button name="odeme_kredi" class="button btn btn-primary p-2"><i class="fas fa-plus-circle"></i> <?=@$dil['txt63'];?></button>
                                                <button type="button" class="button btn btn-default p-2" data-dismiss="modal"><i class="far fa-times-circle"></i> <?=@$dil['txt60'];?></button>
                                            </div>
                                        </form>
                                    </div>

                                </div>
                            </div>
                        <?php }?>
                    </div>


                </div>
            </div>

        </div>

    </div>
</div>
</div>
<?php
#Yeni Adres Ekle
if($_SESSION['adres_ekle'] == 'yes')
{
    echo "
	<script>
	swal({
		type: 'success',
		title: '".@$dil['txt11']."',
		text: '".@$dil['txt64']."',
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
?>
