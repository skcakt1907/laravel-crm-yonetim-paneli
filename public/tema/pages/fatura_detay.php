<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null;?>
<?php
if(strip_tags(isset($_GET['id'])))
{
    $Sorgu = $db->prepare("SELECT * FROM faturalar WHERE id = ?");
    $Sorgu->execute(array($_GET['id']));
    if($Sorgu->rowCount())
    {
        $Sonuc 		= $Sorgu->fetch(PDO::FETCH_ASSOC);
        $uyebilgi 	=$db->query("SELECT * FROM uyeler WHERE id='{$Sonuc['uyeid']}'")->fetch(PDO::FETCH_ASSOC);
        $faturabul 	=$db->query("SELECT * FROM satilanlar WHERE id='{$Sonuc['hizmet']}'")->fetch(PDO::FETCH_ASSOC);
		$adresbul 	=$db->query("SELECT * FROM adresler WHERE uyeid='{$uyebilgi['id']}'")->fetch(PDO::FETCH_ASSOC);
        if($varsayilanadres)
        {
            $user_address 	= $varsayilanadres['adres']." / ".$varsayilanadres['ilce']." / ".$varsayilanadres['il']." - ".$varsayilanadres['pkodu'];
        }
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
    $_SESSION['devam'] = "fatura-detay/".$_GET['id']."";
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
                    <h1 class="heading"><?=@$dil['txt247'];?></h1>
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
            <?php } ?> <i><?=@$dil['txt28'];?></i></span>

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
                                <i class="fas fa-file-invoice"></i>
                                <?=@$dil['txt248'];?> - <?php echo $Sonuc['baslik'];?>
                            </h5>
                            <div class="pull-right">
                                <strong><a href="hesabim.html"><?=@$dil['txt32'];?> </a></strong> /
                                <a href="faturalarim.html"><?=@$dil['txt247'];?> </a>
                            </div>
                        </div>
                        <table id="datatable" class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <td scope="col"><?=@$dil['txt40'];?></td>
                                <td scope="col" class="text-center"><?=@$dil['txt36'];?></td>
                                <td scope="col" class="text-center" style="width:190px;"><?=@$dil['txt186'];?></td>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <th id="secili" scope="row" class="align-middle text-left">
                                    <a href="#" class="link"><?=$Sonuc['baslik'];?></a>
                                    <p class="t-detail"><?=($Sonuc['hizmet'] == 0) ? nl2br($Sonuc['aciklama']) : $Sonuc['aciklama'];?></p>
                                    <br />
                                    --------------
                                    <br />
                                    <p class="t-detail"><?=@$dil['txt249'];?> <?=date("d.m.Y",strtotime($Sonuc['tarih']));?></p>
                                    <p class="t-detail"><?=@$dil['txt250'];?> <?=date("d.m.Y",strtotime($Sonuc['bitis_tarih']));?></p>
                                </th>
                                <td id="secili" class="align-middle text-center"><strong><?php echo $Sonuc['tutar'];?> TL.</strong></td>
                                <td id="secili"class="align-middle text-center">
                                    <?php if($Sonuc['durum'] == 0){ ?>
                                        <label class="alert alert-danger alert-sm mt-3"><?=@$dil['txt251'];?></label>
                                    <?php }else{ ?>
                                        <label class="alert alert-success alert-sm mt-3"><?=@$dil['txt252'];?> (<?=date("d.m.Y",strtotime($Sonuc['odenen_tarih']));?>)</label>
                                    <?php } ?>
                                </td>
                            </tr>

                            <tr>
                                <td colspan="3" class="text-center align-middle">
                                    <? if($Sonuc['durum'] == 1){ ?>
                                        <p>
                                            <?=@$dil['txt50'];?>: <?=$Sonuc['odeme_yontemi'];?> <br>
                                            <span style="font-size: 24px; color: #1c7430;font-weight: bold"><?=@$dil['txt253'];?></span>
                                        </p>
                                    <? } ?>
                                    <? if($Sonuc['durum'] == 0){ ?>
                                        <form action="_class/site_islem.php" class="text-left" method="POST" id="paketsatinal">
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
                                            <input type="hidden" name="domain" value="<?=$Sonuc['baslik'];?>" />
                                            <input type="hidden" name="adres_bilgisi" value="<?=$adresbul['adres'];?> <?=$adresbul['il'];?> / <?=$adresbul['ilce'];?>" />
                                            <input type="hidden" name="url" value="fatura-detay/<?php echo $Sonuc['id']; ?>.html" />
                                            <button type="submit" name="satinal" class="btn btn-success"> <?=@$dil['txt55'];?></button>
                                        </form>
                                    <? }?>

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
                                                            <input type="hidden" name="satilan" value="fatura" />
                                                            <input type="hidden" name="url" value="fatura-detay/<?php echo $Sonuc['id']; ?>.html" />
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
                                        <div class="modal fade text-left" id="kredikartiileode" role="dialog">
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
                                                                $merchant_id 		= magaza_no;
                                                                $merchant_key 		= magaza_parola;
                                                                $merchant_salt		= magaza_anahtar;
                                                                $email				= $Bilgilerim['email'];
                                                                $payment_amount		= intval($Sonuc['tutar']*100); //9.99 için 9.99 * 100 = 999 gönderilmelidir.
                                                                $genel 				= number_format($Sonuc['tutar'], 2, ',', '.');
                                                                $merchant_oid 		= time();
                                                                $user_name 			= $Bilgilerim['ad']." ".$Bilgilerim['soyad'];
                                                                $user_address 		= $_SESSION['siparisbilgi']['adres'];
                                                                $user_phone 			= $Bilgilerim['telefon'];
                                                                $merchant_ok_url 	= "".url."/siparis-sonuc.html?sonuc=basarili";
                                                                $merchant_fail_url 	= "".url."/siparis-sonuc.html?sonuc=hata";
                                                                $user_basket 		= "";
                                                                $user_basket		= base64_encode(json_encode(array(
                                                                    array("".$Sonuc['baslik']."", $genel , 1) // 1. ürün (Ürün Ad - Birim Fiyat - Adet )
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
                                                                if(defaultpayment==1){

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
                                                                        $sorgu = $db->prepare("UPDATE satilanlar SET
																uyeid 			= ?,
																spno 			= ?
																WHERE id 		= ?");
                                                                        $sorgu->execute(array(
                                                                            $Bilgilerim['id'],
                                                                            "#".$merchant_oid,
                                                                            $faturabul['id']
                                                                        ));
                                                                        $sorgu2 = $db->prepare("UPDATE faturalar SET
																uyeid 			= ?,
																spno 			= ?
																WHERE id 		= ?");
                                                                        $sorgu2->execute(array(
                                                                            $Bilgilerim['id'],
                                                                            "#".$merchant_oid,
                                                                            $Sonuc['id']
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


                                                                $sorgu = $db->prepare("UPDATE satilanlar SET
																uyeid 			= ?,
																spno 			= ?
																WHERE id 		= ?");
                                                                $sorgu->execute(array(
                                                                    $Bilgilerim['id'],
                                                                    $merchant_oid,
                                                                    $faturabul['id']
                                                                ));
                                                                $sorgu2 = $db->prepare("UPDATE faturalar SET
																uyeid 			= ?,
																spno 			= ?
																WHERE id 		= ?");
                                                                $sorgu2->execute(array(
                                                                    $Bilgilerim['id'],
                                                                    $merchant_oid,
                                                                    $Sonuc['id']
                                                                ));


                                                                $request = new \Iyzipay\Request\CreateCheckoutFormInitializeRequest();
                                                                $request->setLocale(\Iyzipay\Model\Locale::TR);
                                                                $request->setconversationId($merchant_oid);
                                                                $request->setBasketId($merchant_oid);
                                                                $request->setPrice($Sonuc['tutar']);
                                                                $request->setPaidPrice($Sonuc['tutar']);
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
                                                                $buyer->setRegistrationAddress($user_address);
                                                                $buyer->setIp($ip);
                                                                $buyer->setCity("Istanbul");
                                                                $buyer->setCountry("Turkey");
                                                                $buyer->setZipCode("34732");
                                                                $request->setBuyer($buyer);

                                                                $shippingAddress = new \Iyzipay\Model\Address();
                                                                $shippingAddress->setContactName($Bilgilerim["ad"] .' '.$Bilgilerim["soyad"]);
                                                                $shippingAddress->setCity("Istanbul");
                                                                $shippingAddress->setCountry("Turkey");
                                                                $shippingAddress->setAddress($user_address);
                                                                $shippingAddress->setZipCode("34742");
                                                                $request->setShippingAddress($shippingAddress);

                                                                $billingAddress = new \Iyzipay\Model\Address();
                                                                $billingAddress->setContactName($Bilgilerim["ad"] .' '.$Bilgilerim["soyad"]);
                                                                $billingAddress->setCity("Istanbul");
                                                                $billingAddress->setCountry("Turkey");
                                                                $billingAddress->setAddress($user_address);
                                                                $billingAddress->setZipCode("34742");
                                                                $request->setBillingAddress($billingAddress);

                                                                $basketItems = array();
                                                                $firstBasketItem = new \Iyzipay\Model\BasketItem();
                                                                $firstBasketItem->setId("WP");
                                                                $firstBasketItem->setName("FATURA");
                                                                $firstBasketItem->setCategory1("FATURA");
                                                                $firstBasketItem->setItemType(\Iyzipay\Model\BasketItemType::PHYSICAL);
                                                                $firstBasketItem->setPrice($Sonuc['tutar']);
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
                                        <div class="modal fade text-left" id="krediileode" role="dialog">
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
                                                            <input type="hidden" name="satilan" value="fatura" />
															<input type="hidden" name="domain" value="<?=$Sonuc['baslik'];?>" />
                                                            <input type="hidden" name="fatura_tutar" value="<?php echo $Sonuc['tutar']; ?>" />
                                                            <input type="hidden" name="url" value="fatura-detay/<?php echo $Sonuc['id']; ?>.html" />
                                                            <button name="odeme_kredi" class="button btn btn-primary p-2"><i class="fas fa-plus-circle"></i> <?=@$dil['txt63'];?></button>
                                                            <button type="button" class="button btn btn-default p-2" data-dismiss="modal"><i class="far fa-times-circle"></i> <?=@$dil['txt60'];?></button>
                                                        </div>
                                                    </form>
                                                </div>

                                            </div>
                                        </div>
                                    <?php }?>
                                </td>
                            </tr>

                            </tbody>
                        </table>
                    </div>


                </div>
            </div>

        </div>

    </div>
</div>
<style>
    @media only screen and (max-width: 1024px) and (min-width: 320px){

        #datatable table,
        #datatable thead,
        #datatable tbody,
        #datatable th,
        #datatable td,
        #datatable tr {
            display: block;
            overflow: hidden;
            height: auto;
        }

        #datatable thead tr {
            position: absolute;
            top: -9999px;
            left: -9999px;
        }

        #datatable tr { margin-bottom:10px;border-bottom: 1px solid #ccc; }

        #datatable td#secili {
            border-bottom: none;
            position: relative;
            padding-left: 40%;
            overflow: hidden;
            height: auto;
        }
        #datatable td#secili:before {
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

        #datatable td#secili:nth-of-type(1):before { content: "<?=@$dil['txt36'];?>"; }
        #datatable td#secili:nth-of-type(2):before { content: "<?=@$dil['txt186'];?>"; }
    }
</style>
