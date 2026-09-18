<?php
date_default_timezone_set('Europe/Istanbul');
error_reporting(0);
ini_set('display_errors', E_ALL);

$host = 'localhost'; // Linux sunucularda değiştirmeyiniz
$data = 'dnkreati_isortagim'; // Veri tabanı Adın Yazın.
$user = 'dnkreati_isortagim'; // Veri tabanı Kullanıcı adını yazın
$pass = 'X61yX7.eII;I'; // Veri tabanı Şifrenizi Yazın

try {
	$db = new PDO('mysql:host=' . $host . ';dbname=' . $data . ';charset=UTF8;', $user, $pass);
} catch (PDOException $e) {
	echo 'Hata: ' . $e->getMessage();
}

if (is_numeric(@$_GET['dil'])) {
	$_SESSION['k_dil'] = $_GET['dil'];
}
if (!isset($_SESSION['k_dil'])) {
	$anadil = $db->prepare("SELECT * FROM diller WHERE anadil = ?");
	$anadil->execute([1]);
	if ($anadil->rowCount() == 0) die("Lütfen bir anadil seçiniz !!");
	$anadil = $anadil->fetch(PDO::FETCH_ASSOC);
	$_SESSION['k_dil'] = @$anadil['id'];
} else {
	$mevcutDil = $db->prepare("SELECT * FROM diller WHERE id = ?");
	$mevcutDil->execute([(int) $_SESSION['k_dil']]);
	if ($mevcutDil->rowCount() == 0) {
		$anadil = $db->prepare("SELECT * FROM diller WHERE anadil = ?");
		$anadil->execute([1]);
		if ($anadil->rowCount() == 0) die("Lütfen bir anadil seçiniz !!");
		$anadil = $anadil->fetch(PDO::FETCH_ASSOC);
		$_SESSION['k_dil'] = @$anadil['id'];
	} else {
		$mevcutDil = $mevcutDil->fetch(PDO::FETCH_ASSOC);
		$_SESSION['k_dil'] = @$mevcutDil['id'];
	}
}
$ayar 		= $db->query("SELECT * FROM ayarlar")->fetch();
$bakim_modu = $db->query("SELECT * FROM bakim_modu")->fetch();
$moduller 	= $db->query("SELECT * FROM moduller WHERE id = '1' ORDER BY id ASC LIMIT 1")->fetch();
$mailayar 	= $db->query("SELECT * FROM mail_ayar")->fetch();
$kurumsal 	= $db->query("SELECT * FROM sayfalar WHERE anasayfa = '1' ORDER BY id ASC LIMIT 1")->fetch();
$smsayar 	= $db->query("SELECT * FROM sms")->fetch();
$arkaplan 	= $db->query("SELECT * FROM arka_plan WHERE id = '1' ORDER BY id ASC LIMIT 1")->fetch();
$limitayar 	= $db->query("SELECT * FROM limit_ayarlari WHERE id = '1' ORDER BY id ASC LIMIT 1")->fetch();
$kart 		= $db->query("SELECT * FROM paytr WHERE id = '1' ORDER BY id ASC LIMIT 1")->fetch();
$kredi_uyari = $db->query("SELECT * FROM uyeler WHERE id = '{$_SESSION["site_uyeid"]}' ORDER BY id ASC LIMIT 1")->fetch();
$sepet_count = $db->query("SELECT COUNT(*) FROM sepet WHERE user_id = '{$_SESSION["site_uyeid"]}'")->fetch();

// Kalan Kredi Tutarı
$KrediSorgu = $db->prepare("SELECT * FROM krediler WHERE paytronay = ? AND uyeid = ? ORDER BY id DESC LIMIT 1");
$KrediSorgu->execute(array("1", $_SESSION["site_uyeid"]));
$KrediSonuc = $KrediSorgu->fetch(PDO::FETCH_ASSOC);

$route = array_values(array_filter(explode('/', realpath('.'))));
$host = str_replace("www.", "", $_SERVER['HTTP_HOST']);
$testArray = array(
	$host
);

foreach ($testArray as $k => $v) {
	$sub = extract_subdomains($v);
}

function extract_domain($domain)
{
	if (preg_match("/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i", $domain, $matches)) {
		return $matches['domain'];
	} else {
		return $domain;
	}
}

function extract_subdomains($domain)
{
	$subdomains = $domain;
	$domain = extract_domain($subdomains);
	$subdomains = rtrim(strstr($subdomains, $domain, true), '.');
	return $subdomains;
}
if (end($route) != "public_html" && end($route) != "httpdocs") {
	if ($sub) {
		$altklasor = "0";
	} else {
		$altklasor = "1";
	}
} else {
	$altklasor = "0";
}


define("baslik", $ayar["site_baslik"]);
define("url", $ayar["site_url"]);
define("domain_url", $ayar["domain_url"]);
define("tema_dir", $ayar["site_tema"]);
define("tema", "tema/" . $ayar["site_tema"]);
define("tema_url", $ayar["site_url"] . "tema/" . $ayar["site_tema"]);
define("logo", $ayar["firma_logo"]);
define("footerlogo", $ayar["firma_footerlogo"]);
define("fav", $ayar["favicon"]);
define("firma_adi", $ayar["firma_adi"]);
define("telefon", $ayar["firma_telefon"]);
define("fax", $ayar["firma_fax"]);
define("email", $ayar["firma_email"]);
define("adres", $ayar["firma_adres"]);
define("maps", $ayar["google_maps"]);
define("analytics", $ayar["google_analytics"]);
define("dogrulama", $ayar["dogrulama_kodu"]);
define("canli_destek", $ayar["canli_destek"]);
define("whatsapp", $ayar["whatsapp"]);
define("facebook", $ayar["facebook"]);
define("twitter", $ayar["twitter"]);
define("instagram", $ayar["instagram"]);
define("linkedin", $ayar["linkedin"]);
define("youtube", $ayar["youtube"]);
define("copyright", $ayar["copyright"]);
define("site_desc", $ayar["site_desc"]);
define("site_keyw", $ayar["site_keyw"]);
define("durum", $moduller["alan9"]);
//define("altklasor", $moduller["alan8"]);
define("altklasor", $altklasor);
define("renk1", $ayar["renk1"]);
define("renk2", $ayar["renk2"]);
define("renk3", $ayar["renk3"]);
define("defaultsms", $ayar["defaultsms"]);
define("defaultpayment", $ayar["defaultpayment"]);

// Kredi Kartı Sabitler
define("magaza_no", $kart["magaza_no"]);
define("magaza_parola", $kart["magaza_parola"]);
define("magaza_anahtar", $kart["magaza_anahtar"]);
define("hata_mesaj", $kart["hata_mesaj"]);
define("test_modu", $kart["test_modu"]);
define("taksit", $kart["taksit"]);

// Mail Sabitler
define("m_server", 	$mailayar["m_server"]);
define("m_adresi", 	$mailayar["m_adresi"]);
define("m_parola", 	$mailayar["m_parola"]);
define("m_port", 	$mailayar["m_port"]);
define("m_sertifika", 	$mailayar["m_sertifika"]);
define("m_kime", 	$mailayar["m_kime"]);

// SMS Sabitler
define("postUrl", 	$smsayar["postUrl"]);
define("sms_kadi", 	$smsayar["KULLANICIADI"]);
define("sms_sifre", 	$smsayar["SIFRE"]);
define("sms_baslik", $smsayar["ORGINATOR"]);
define("sms_kime", $smsayar["m_kime"]);

// Krediler
define("k_tutar", $KrediSonuc["tutar"]);
define("kredi_uyari", $kredi_uyari["bakiye"]);
define("sepet_count", $sepet_count[0]);

require_once('update.php');
