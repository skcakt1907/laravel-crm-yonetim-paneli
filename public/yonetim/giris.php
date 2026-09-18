<?php
session_start();
require_once('../_class/baglan.php');
require_once('../_class/fonksiyon.php');
require_once('../_class/class.upload.php');
?>
<?php if(isset($_SESSION["Yonetim_Id"]))
{
	header("Location:index.html");
}
?>
<?php
function getBrowser() 
 { 
     $u_agent = $_SERVER['HTTP_USER_AGENT']; 
     $bname = 'Bilinmiyor';
     $platform = 'Bilinmiyor';
     $version= "";

     //Hangi platformdan gelmiş, Linux, Windows, MacOSX?
     if (preg_match('/linux/i', $u_agent)) {
         $platform = 'linux';
     }
     elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
         $platform = 'mac';
     }
     elseif (preg_match('/windows|win32/i', $u_agent)) {
         $platform = 'windows';
     }
     
     //Sonra tarayıcıya göz atalım
     if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) 
     { 
         $bname = 'Internet Explorer'; 
         $ub = "MSIE"; 
     } 
     elseif(preg_match('/Firefox/i',$u_agent)) 
     { 
         $bname = 'Mozilla Firefox'; 
         $ub = "Firefox"; 
     } 
     elseif(preg_match('/Chrome/i',$u_agent)) 
     { 
         $bname = 'Google Chrome'; 
         $ub = "Chrome"; 
     } 
     elseif(preg_match('/Safari/i',$u_agent)) 
     { 
         $bname = 'Apple Safari'; 
         $ub = "Safari"; 
     } 
     elseif(preg_match('/Opera/i',$u_agent)) 
     { 
         $bname = 'Opera'; 
         $ub = "Opera"; 
     } 
     elseif(preg_match('/Netscape/i',$u_agent)) 
     { 
         $bname = 'Netscape'; 
         $ub = "Netscape"; 
     } 
     
     // Tarayıcının versiyon numarasını tespit edelim.
	 //burada düzenli ifadeler kullanarak bakıyoruz.
     $known = array('Version', $ub, 'other');
     $pattern = '#(?<browser>' . join('|', $known) .
     ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
     if (!preg_match_all($pattern, $u_agent, $matches)) {
         // buraya kadar bulamadık, aramaya devam
     }
     

     $i = count($matches['browser']);
     if ($i != 1) {

         if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
             $version= $matches['version'][0];
         }
         else {
             $version= $matches['version'][1];
         }
     }
     else {
         $version= $matches['version'][0];
     }
     
     if ($version==null || $version=="") {$version="?";}
     
     return array(
         'userAgent' => $u_agent,
         'name'      => $bname,
         'version'   => $version,
         'platform'  => $platform,
         'pattern'    => $pattern
     );
 } 
$ua=getBrowser();
$tarayici= "Web tarayucınız: " . $ua['name'] . " " . $ua['version'] . " " .$ua['platform'];
//Örneğin mozilla Firefox kullananların girmesini istemiyorsak
if ($ua['name']=='Mozilla Firefox')
{
print_r($tarayici);	
echo "<center>";
echo "<h2>Mozilla Firefox tarayıcısı desteklenmiyor.</h2><br>" ;
echo "<h4>Lütfen Internet Explorer, Opera, Safari, Chrome tarayıcılarından birini kullanınız.</h4>";
echo "</center>";
} else  {
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Yönetim Paneli</title>
	<link rel="stylesheet" href="vendors/iconfonts/mdi/font/css/materialdesignicons.min.css">
	<link rel="stylesheet" href="vendors/iconfonts/simple-line-icon/css/simple-line-icons.css">
	<link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
	<link rel="stylesheet" href="vendors/css/vendor.bundle.addons.css">
	<link rel="stylesheet" href="css/vertical-layout-light/style.css">
	<link rel="shortcut icon" href="images/favicon.png" />
	<style>
		/* Admin giriş input yazı rengini beyaz yap */
		.form-control,
		.form-control-lg {
			color: #fff !important;
		}
		
		.form-control::placeholder,
		.form-control-lg::placeholder {
			color: rgba(255, 255, 255, 0.6) !important;
			opacity: 1 !important;
		}
		
		.form-control::-webkit-input-placeholder,
		.form-control-lg::-webkit-input-placeholder {
			color: rgba(255, 255, 255, 0.6) !important;
			opacity: 1 !important;
		}
		
		.form-control::-moz-placeholder,
		.form-control-lg::-moz-placeholder {
			color: rgba(255, 255, 255, 0.6) !important;
			opacity: 1 !important;
		}
		
		.form-control:-ms-input-placeholder,
		.form-control-lg:-ms-input-placeholder {
			color: rgba(255, 255, 255, 0.6) !important;
			opacity: 1 !important;
		}
		
		.form-control:-moz-placeholder,
		.form-control-lg:-moz-placeholder {
			color: rgba(255, 255, 255, 0.6) !important;
			opacity: 1 !important;
		}
	</style>
</head>
<body>
	<div class="container-scroller">
		<div class="container-fluid page-body-wrapper full-page-wrapper">
			<div class="content-wrapper d-flex align-items-stretch auth auth-img-bg">
				<div class="row flex-grow">
					<div class="col-lg-6 d-flex align-items-center justify-content-center">
						<div class="auth-form-transparent text-left p-3">
							<h3>YÖNETİM PANELİ</h3>
							<form class="pt-3" method="post" action="../_class/yonetim_islem.php" autocomplete="off">
								<div class="form-group">
									<label for="kadi">Kullanıcı Adı</label>
									<div class="input-group">
										<div class="input-group-prepend bg-transparent">
											<span class="input-group-text bg-transparent border-right-0">
											<i class="mdi mdi-account-outline text-primary"></i>
											</span>
										</div>
										<input type="text" class="form-control form-control-lg border-left-0" <?php if(isset($_COOKIE['Yonetim_Kadi'])){?> value="<?php echo $_COOKIE['Yonetim_Kadi'];?>" <?php } ?> name="kadi" id="kadi">
									</div>
								</div>
								<div class="form-group">
									<label for="sifre">Şifre</label>
									<div class="input-group">
										<div class="input-group-prepend bg-transparent">
											<span class="input-group-text bg-transparent border-right-0">
											<i class="mdi mdi-lock-outline text-primary"></i>
											</span>
										</div>
										<input type="password" class="form-control form-control-lg border-left-0" <?php if(isset($_COOKIE['Yonetim_Sifre'])){?> value="<?php echo $_COOKIE['Yonetim_Sifre'];?>" <?php } ?> name="sifre" id="sifre">                        
									</div>
								</div>
								<div class="my-2 d-flex justify-content-between align-items-center">
									<div class="form-check">
										<label class="form-check-label text-muted">
										<input type="checkbox" <?php echo(isset($_COOKIE['Yonetim_Kadi']) ? "checked" : "");?> name="beni_hatirla" class="form-check-input">
										Beni Hatırla
										</label>
									</div>
									<a href="#" data-toggle="modal" data-target="#sifremi-unuttum" data-backdrop="static" data-keyboard="false" data-whatever="@mdo" class="auth-link text-black">Parolamı Unuttum</a>
								</div>
								<div class="my-3">
									<button type="submit" name="kullanici_giris" class="btn btn-block btn-primary btn-lg font-weight-medium auth-form-btn"><i class="icon-lock"></i> GİRİŞ YAP</button>
								</div>
							</form>
						</div>
					</div>
					<div class="col-lg-6 login-half-bg d-flex flex-row">
						<p class="text-white font-weight-medium text-center flex-grow align-self-end">Copyright © 2019 Tüm hakları saklıdır.</p>
					</div>
				</div>
			</div>
			<!-- content-wrapper ends -->
		</div>
		<!-- page-body-wrapper ends -->
	</div>
	<!-- container-scroller -->
	<!-- plugins:js -->
	<script src="vendors/js/vendor.bundle.base.js"></script>
	<script src="vendors/js/vendor.bundle.addons.js"></script>
	<!-- endinject -->
	<!-- inject:js -->
	<script src="js/off-canvas.js"></script>
	<script src="js/hoverable-collapse.js"></script>
	<script src="js/template.js"></script>
	<script src="js/settings.js"></script>
	<script src="js/todolist.js"></script>
	<div class="modal fade" id="sifremi-unuttum" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="ModalLabel">Parolamı Unutum?</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<form method="post" action="../_class/yonetim_islem.php" autocomplete="off">
					<div class="modal-body">					
						<div class="form-group mb-0">
							<label for="email" class="col-form-label mb-0">E-Posta Adresiniz</label>
							<input type="text" class="form-control" name="email" id="email" />
						</div>					
					</div>
					<div class="modal-footer">
						<button type="submit" name="sifirla" class="btn btn-success"><i class="icon-refresh"></i> Sıfırla</button>
						<button type="button" class="btn btn-light" data-dismiss="modal"><i class="icon-close"></i> İptal</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	<?php 
	if($_SESSION['kullanici_giris'] == 'bos')
	{					
		echo "
		<script>
			$.toast({
		      heading: 'Uyarı!',
		      text: 'Boş alan bıraktınız.',
		      showHideTransition: 'slide',
		      icon: 'warning',
		      loaderBg: '#fff',
		      position: 'top-right'
		    })
		</script>";
		unset($_SESSION['kullanici_giris']);
	}
	if($_SESSION['kullanici_giris'] == 'no')
	{	
		echo "
		<script>
			$.toast({
		      heading: 'Hata!',
		      text: 'Kullanıcı Adı veya Şifreniz Yanlış.',
		      showHideTransition: 'slide',
		      icon: 'error',
		      loaderBg: '#fff',
		      position: 'top-right'
		    })
		</script>";
		unset($_SESSION['kullanici_giris']);
	}
	if($_SESSION['sifirla'] == 'yes')
	{
		echo "
		<script>
			$.toast({
		      heading: 'Başarılı',
		      text: 'Kullanıcı adınız ve şifreniz sistemde kayıtlı mail adresinize gönderilmiştir.',
		      showHideTransition: 'slide',
		      icon: 'success',
		      loaderBg: '#fff',
		      position: 'top-right'
		    })
		</script>";
		unset($_SESSION['sifirla']);
	}
	if($_SESSION['sifirla'] == 'no')
	{
		echo "
		<script>
			$.toast({
		      heading: 'Hata!',
		      text: 'E-Mail adresiniz sistemde kayıtlı değildir.',
		      showHideTransition: 'slide',
		      icon: 'error',
		      loaderBg: '#fff',
		      position: 'top-right'
		    })
		</script>";
		unset($_SESSION['sifirla']);
	}
	if($_SESSION['demohesap'] == 'no')
	{
		echo "
		<script>
			$.toast({
		      heading: 'Uyarı!',
		      text: 'Demo hesapta işlem yapamassınız.!',
		      showHideTransition: 'slide',
		      icon: 'warning',
		      loaderBg: '#fff',
		      position: 'top-right'
		    })
		</script>";
		unset($_SESSION['demohesap']);
	}
	?>
	<!-- endinject -->
</body>
</html>
<?php } ?>