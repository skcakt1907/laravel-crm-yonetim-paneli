<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null;?>
<?php if(!isset($_SESSION["site_uyeid"]))
{
    $_SESSION['devam'] = "bakiyem";
    header("Location:".$url."/giris.html");
}
else
{
    unset($_SESSION['devam']);
    if($varsayilanadres)
    {
        $user_address 	= $varsayilanadres['adres']." / ".$varsayilanadres['ilce']." / ".$varsayilanadres['il']." - ".$varsayilanadres['pkodu'];
    }
    if(isset($_GET['bid'])){
        $Bayilik = $db->query("SELECT * FROM bayilikler WHERE id='{$_GET['bid']}'")->fetch();
        if(isset($Bayilik['fiyat'])){
            $_SESSION['kredi_yukle'] = 'kredi_karti';
            $_SESSION['kredi_tutar'] = $Bayilik['fiyat'];
            $_SESSION['bayid'] = $Bayilik['id'];
            $_SESSION['bid'] = $Bilgilerim['id'];
        }
    }
}

?>
<div class="top-header overlay" style="background-image: url(<?php echo tema;?>/uploads/arkaplan/uyelik/<?php echo $arkaplan['uyelik']?>)">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper">
                    <h1 class="heading"><?=@$dil['txt95'];?></h1>
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
                                <i class="fa fa-money"></i>
                                <?=@$dil['txt95'];?>
                            </h5>
                            <div class="pull-right">
                                <strong><a href="hesabim.html"><?=@$dil['txt32'];?> </a></strong> /
                                <?=@$dil['txt95'];?>
                            </div>
                        </div>

                        <div class="alert alert-success">
                            <div class="row">

                                <div class="col-md-2"><i class="fa fa-info-circle" style="font-size: 90px;margin-top: 25px;text-align: center;display: block;"></i></div>
                                <div class="col-md-10">
                                    <div class="balanceinfo">
                                        <h5><strong><?=@$dil['txt96'];?></strong></h5>
                                        <p style="font-weight:400;">
                                            <?=@$dil['txt97'];?>
                                        </p>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <form method="POST" action="_class/site_islem.php" autocomplete="off">
                            <table class="table table-bordered table-striped">
                                <tbody>
                                <tr>
                                    <th scope="row" style="width:300px;">
                                        <a href="javascript:void(0)" class="link"><?=@$dil['txt98'];?></a>
                                    </th>
                                    <td><?php echo $kredi['tutar'];?> TL</td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <a href="javascript:void(0)" class="link"><?=@$dil['txt99'];?></a>
                                    </th>
                                    <td><?php echo $kredi['tarih'];?></td>
                                </tr>
                                <tr>
                                    <th scope="row" class="align-middle">
                                        <a href="javascript:void(0)" class="link"><?=@$dil['txt100'];?></a>
                                    </th>
                                    <td><?=@$dil['txt101'];?>
                                        <input type="text" class="form-control d-inline-block" name="bakiye" placeholder="Örn: 100" value="<?php echo $Bilgilerim['bakiye'];?>" style="width: 70px;">
                                        <?=@$dil['txt102'];?>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                            <input type="submit" name="bakiye_guncelle"  value="<?=@$dil['txt103'];?>" class="btn btn-success pull-right mb-6">
                            <div class="clear"></div>
                        </form>
                        <?php if(defaultpayment==1 || defaultpayment==4){?>
                            <div style="margin-top: 72px">
                                <h3><?=@$dil['txt104'];?></h3>
                                <form method="POST" action="_class/site_islem.php" autocomplete="off">
                                    <table class="table table-bordered table-striped">
                                        <tbody>
                                        <tr>
                                            <th scope="row" class="align-middle" style="width:300px;"><?=@$dil['txt105'];?></th>
                                            <td><input type="text" class="form-control" name="kredi_tutar" style="width: 100px;display: inline-block;">
                                                TL
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                    <input type="hidden" value="<?php echo $user_address;?>" name="kredi_adres" />
                                    <input type="submit" name="kredi_yukle" value="<?=@$dil['txt104'];?>" class="btn btn-success pull-right">
                                    <div class="clear"></div>
                                </form>
                                <?php if($_SESSION['kredi_yukle'] == "kredi_karti"){?>
                                    <?php unset($_SESSION['kredi_yukle']);?>
                                    <script type="text/javascript">
                                        $(window).load(function(){
                                            $('#krediyukle').modal({backdrop: 'static', keyboard: false});
                                        });
                                    </script>
                                    <!-- Kerdi Kartı İle Ödeme -->
                                    <div class="modal fade" id="krediyukle" role="dialog">
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
                                                                $payment_amount		= intval($_SESSION['kredi_tutar']*100); //9.99 için 9.99 * 100 = 999 gönderilmelidir.
                                                                $genel 				= number_format($_SESSION['kredi_tutar'], 2, ',', '.');
                                                                $merchant_oid 		= rand(100,999)."Y".$Bilgilerim['id']."K".$_SESSION['kredi_tutar'];
                                                                $user_name 			= $Bilgilerim['ad']." ".$Bilgilerim['soyad'];
                                                                $krediaciklama		= "".$genel." TL Kredi Yükleme";
                                                                $user_phone 			= $Bilgilerim['telefon'];
                                                                $merchant_ok_url 	= "".url."/siparis-sonuc.html?sonuc=basarili";
                                                                $merchant_fail_url 	= "".url."/siparis-sonuc.html?sonuc=hata";
                                                                $user_basket 		= "";
                                                                $user_basket		= base64_encode(json_encode(array(
                                                                    array("Kredi Yükleme", $genel , 1) // 1. ürün (Ürün Ad - Birim Fiyat - Adet )
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
                                                                $debug_on = 0; //hata_mesaj;

                                                                ## Mağaza canlı modda iken test işlem yapmak için 1 olarak gönderilebilir.
                                                                
                                                                $test_mode = 0;//test_modu;
                                                                

                                                                $no_installment	= 1;//;taksit; // Taksit yapılmasını istemiyorsanız, sadece tek çekim sunacaksanız 1 yapın

                                                                ## Sayfada görüntülenecek taksit adedini sınırlamak istiyorsanız uygun şekilde değiştirin.
                                                                ## Sıfır (0) gönderilmesi durumunda yürürlükteki en fazla izin verilen taksit geçerli olur.
                                                                $max_installment = 1; //;

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
                                                                    $token	=$result['token'];
                                                                    $tarih	= date('Y-m-d H:i:s');
                                                                    $tarih	= TvERtXpE3w_tarih($tarih);
                                                                    $ip		= TvERtXpE3w_ip();
                                                                    $sorgu = $db->prepare("INSERT INTO paytr_krediler SET
                                                            uyeid 		= ?,
                                                            tutar 		= ?,
                                                            aciklama 	= ?,
                                                            paytronay 	= ?,
                                                            spno 		= ?,
                                                            ip 			= ?,
                                                            tarih 		= ?");
                                                                    $Ekle = $sorgu->execute(array(
                                                                        $Bilgilerim['id'],
                                                                        $_SESSION['kredi_tutar'],
                                                                        $krediaciklama,
                                                                        "0",
                                                                        $merchant_oid,
                                                                        $ip,
                                                                        $tarih
                                                                    ));
                                                                    unset($_SESSION['kredi_tutar']);
                                                                }
                                                                else
                                                                {
                                                                    die("PAYTR IFRAME failed. reason:".$result['reason']);
                                                                }
                                                                ?>
                                                                  <!-- Ödeme formunun açılması için gereken HTML kodlar / Başlangıç -->
                                                            <script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
                                                            <iframe src="https://www.paytr.com/odeme/guvenli/<?php echo $token;?>" id="paytriframe" frameborder="0" scrolling="no" style="width: 100%;"></iframe>
                                                            <script>iFrameResize({},'#paytriframe');</script>
                                                            <!-- Ödeme formunun açılması için gereken HTML kodlar / Bitiş -->
                                                            <?php 
                                                            }elseif(defaultpayment==4){


                                                                $genel 				= number_format($_SESSION['kredi_tutar'], 2, ',', '.');

                                                                $krediaciklama		= "".$genel." TL Kredi Yükleme";


                                                                $tarih	= date('Y-m-d H:i:s');
                                                                $tarih	= TvERtXpE3w_tarih($tarih);
                                                                $ip		= TvERtXpE3w_ip();
                                                                $sorgu = $db->prepare("INSERT INTO paytr_krediler SET
                                                            uyeid 		= ?,
                                                            tutar 		= ?,
                                                            aciklama 	= ?,
                                                            paytronay 	= ?,
                                                            spno 		= ?,
                                                            ip 			= ?,
                                                            tarih 		= ?");
                                                            $merchantID = time();
                                                                $Ekle = $sorgu->execute(array(
                                                                    $Bilgilerim['id'],
                                                                    $_SESSION['kredi_tutar'],
                                                                    $krediaciklama,
                                                                    "0",
                                                                    $merchantID,
                                                                    $ip,
                                                                    $tarih
                                                                ));



                                                                $request = new \Iyzipay\Request\CreateCheckoutFormInitializeRequest();
                                                                $request->setLocale(\Iyzipay\Model\Locale::TR);
                                                                $request->setConversationId($merchantID);
                                                                $request->setBasketId($merchantID);
                                                                $request->setPrice($_SESSION['kredi_tutar']);
                                                                $request->setPaidPrice($_SESSION['kredi_tutar']);
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
                                                                $firstBasketItem->setName("Web Paket");
                                                                $firstBasketItem->setCategory1("Web Paket");
                                                                $firstBasketItem->setItemType(\Iyzipay\Model\BasketItemType::PHYSICAL);
                                                                $firstBasketItem->setPrice($_SESSION['kredi_tutar']);
                                                                $basketItems[0] = $firstBasketItem;
                                                                $request->setBasketItems($basketItems);

                                                                # make request
                                                                $checkoutFormInitialize = \Iyzipay\Model\CheckoutFormInitialize::create($request, Config::options($ayar['iyzico_apikey'],$ayar['iyzico_secret']));
                                                                
                                                                unset($_SESSION['kredi_tutar']);
                                                                
?>
  <!-- Ödeme formunun açılması için gereken HTML kodlar / Başlangıç -->
                                                            
                                                            <iframe src="<?=$checkoutFormInitialize->getPaymentPageUrl();?>"  frameborder="0" scrolling="no" style="width: 100%;height:600px;"></iframe>
                                                            
                                                            <!-- Ödeme formunun açılması için gereken HTML kodlar / Bitiş -->
                                                            <?php
                                                            }
                                                            #########################################################################

                                                            ?>

                                                          

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
                            </div>
                        <?php } ?>
                    </div>

                </div>
            </div>

        </div>

    </div>
</div>
<?php
if($_SESSION['kredi_yukle'] == 'bos')
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
    unset($_SESSION['kredi_yukle']);
}
if($_SESSION['kredi_yukle'] == 'adres')
{
    echo "
	<script>
	swal({
		type: 'warning',
		title: '".@$dil['txt13']."',
		text: '".@$dil['txt106']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";
    unset($_SESSION['kredi_yukle']);
}
if($_SESSION['bakiye_guncelle'] == 'yes')
{
    echo "
	<script>
	swal({
		type: 'success',
		title: '".@$dil['txt11']."',
		text: '".@$dil['txt107']." ".$Bilgilerim['bakiye']." ".@$dil['txt108']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";
    unset($_SESSION['bakiye_guncelle']);
}
if($_SESSION['bakiye_guncelle'] == 'no')
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
    unset($_SESSION['bakiye_guncelle']);
}
if($_SESSION['bakiye_guncelle'] == 'bos')
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
    unset($_SESSION['bakiye_guncelle']);
}
?>
