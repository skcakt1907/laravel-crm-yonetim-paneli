<?php
ob_start();
session_start();

require_once "baglan.php";
require_once "fonksiyon.php";
require_once('class.upload.php');
require_once("class.phpmailer.php");
$logo	= url.tema.'/uploads/logo/footer/'.footerlogo;
$domain_bilgi	= url;


signature_f();

##Şifre Sıfırla ##
if(isset($_POST['sifirla']))
{
    $email = $_POST['email'];
    $varmi = $db->prepare("SELECT * FROM kullanici WHERE email = ?");
    $varmi->execute(array($email));
    if($varmi->rowCount())
    {
        $YSonuc = $varmi->fetch(PDO::FETCH_ASSOC);
        if($YSonuc['rutbe'] == 0)
        {
            $isim 		= $YSonuc['isim'];
            $kullanici 	= $YSonuc['kadi'];
            $parola 		= $YSonuc['sifre'];
            $tarih		= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $ip			= TvERtXpE3w_ip();

            $sablon 			= $db->query("SELECT * FROM bildirim_sablonu WHERE id = '10'")->fetch(PDO::FETCH_ASSOC);
            $gelendegisken 	= explode(",", $sablon['degiskenler']);
            $yenitarih		= TvERtXpE3w_tarih($tarih);
            $panel_url		= $domain_bilgi."yonetim/";
            $gidendegisken	= [$isim,$kullanici,$parola,$panel_url,$yenitarih,$ip,$logo,$domain_bilgi];

            $last_id 		= $YSonuc['id'];
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Şifre Sıfırlama",
                'icon' 		=> "icon-lock",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> şifre sıfırlama talebinde bulundu.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            if($sablon["ubildirim"] == "1")
            {
                $uyekonu 	= $sablon['konu'];//TvERtXpE3w_turkce($sablon['konu']);
                $uyesablon 	= $sablon['icerik'];
                mailgonder($gelendegisken,$gidendegisken,$uyesablon,$YSonuc['email']," ".$uyekonu."",$uyesablon);
            }
            if($sablon["abildirim"] == "1")
            {
                $adminkonu 	= $sablon['konu2']; //TvERtXpE3w_turkce($sablon['konu2']);
                $adminsablon= $sablon['icerik2'];
                mailgonder($gelendegisken,$gidendegisken,$adminsablon,m_kime," ".$adminkonu."",$adminsablon);
            }
            $_SESSION['sifirla'] = 'yes';
            header("Location:../yonetim/giris.html");
        }
        else
        {
            $_SESSION['demohesap'] = 'no';
            header("Location:../yonetim/giris.html");
        }
    }
    else
    {
        $_SESSION['sifirla'] = 'no';
        header("Location:../yonetim/giris.html");
    }
}

##Giriş Yap ##
if (isset($_POST['kullanici_giris']))
{
    $kadi 		= $_POST['kadi'];
    $sifre 		= $_POST['sifre'];
    $son_giris	= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');

    if(empty($kadi) || empty($sifre))
    {
        $_SESSION['kullanici_giris'] = 'bos';
        header("Location:../yonetim/giris.html");
    }
    else
    {
        $varmi = $db->prepare("SELECT * FROM kullanici WHERE kadi = ? AND sifre = ?");
        $varmi->execute(array($kadi,$sifre));
        if($varmi->rowCount())
        {
            $KSonuc = $varmi->fetch(PDO::FETCH_ASSOC);
            $sorgu = $db->prepare("UPDATE kullanici SET
				son_giris = ?
				WHERE id = ?");
            $guncelle = $sorgu->execute(array(
                $son_giris,
                $KSonuc['id']
            ));
            $_SESSION['Yonetim_Id'] 		= $KSonuc['id'];
            $_SESSION['Yonetim_Kadi'] 	= $KSonuc['kadi'];
            $_SESSION['Yonetim_Sifre']	= $KSonuc['sifre'];
            $_SESSION['rutbe']       		= $KSonuc['rutbe'];

            if(isset($_POST['beni_hatirla']))
            {
                setcookie("Yonetim_Kadi",$KSonuc['kadi'],strtotime("+1 day"),"/");
                setcookie("Yonetim_Sifre",$KSonuc['sifre'],strtotime("+1 day"),"/");
            }
            else
            {
                setcookie("Yonetim_Kadi",$KSonuc['kadi'],strtotime("-1 day"),"/");
                setcookie("Yonetim_Sifre",$KSonuc['sifre'],strtotime("-1 day"),"/");
            }

            $_SESSION['kullanici_giris'] = 'yes';
            header("Location:../yonetim/index.html");
        }
        else
        {
            $_SESSION['kullanici_giris'] = 'no';
            header("Location:../yonetim/giris.html");
        }
    }
}

##Sayfa Kaydet ##
if(isset($_POST['sayfa_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $adi 		= $_POST['adi'];
        $seoo		= TvERtXpE3w_seo($adi);
        if($seoo)
        {
            $seo 	= TvERtXpE3w_seo($adi);
        }
        else
        {
            $seo 	= rand();
        }
        if($_POST['durum']){$durum = 1;}else{$durum = 0;}
        if($_POST['anasayfa'])
        {
            $anasayfa	= 1;
            $kisa 		= $_POST['kisa'];
        }
        else
        {
            $anasayfa	= 0;
            $kisa 		= "";
        }
        $aciklama 	= $_POST['aciklama'];
        $keywords	= $_POST['keywords'];
        $description= $_POST['description'];
        $tarih		= date('Y-m-d H:i:s');
        $tarih		= TvERtXpE3w_tr_tarih($tarih);

        $upload = new upload($_FILES['resim']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/sayfalar");
            if ($upload->processed)
            {
                $Resim=''.$upload->file_dst_name.'';
            }
        }
        $gitti=$Resim=''.$upload->file_dst_name.'';

        $sorgu = $db->prepare("INSERT INTO sayfalar SET
				adi 	= ?,
				seo 	= ?,
				kisa 	= ?,
				aciklama= ?,
				keywords= ?,
				description	= ?,
				durum 	= ?,
				anasayfa= ?,
				resim 	= ?,
				dil 	= ?,
				tarih 	= ?");
        $Ekle = $sorgu->execute(array(
            $adi,
            $seo,
            $kisa,
            $aciklama,
            $keywords,
            $description,
            $durum,
            $anasayfa,
            $Resim,
            $_SESSION['admin_dil'],
            $tarih
        ));
        if($Ekle)
        {
            $last_id 		= $db->lastInsertId();
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Sayfa Ekledi",
                'icon' 		=> "icon-note",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$adi."</strong> adında sayfa ekledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['sayfa_ekle'] = 'yes';
            header("Location:../yonetim/sayfa-listele.html");
        }
        else
        {
            $_SESSION['sayfa_ekle'] = 'no';
            header("Location:../yonetim/sayfa-ekle.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/sayfa-ekle.html");
    }
}

##Sayfa Güncelle ##
if(isset($_POST['sayfa_guncelle']))
{
    TvERtXpE3w_panelislemkontrol();
    $d_id 	= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $adi 		= $_POST['adi'];
        $seoo		= TvERtXpE3w_seo($adi);
        if($seoo)
        {
            $seo 	= TvERtXpE3w_seo($adi);
        }
        else
        {
            $seo 	= rand();
        }
        if($_POST['durum']){$durum = 1;}else{$durum = 0;}
        if($_POST['anasayfa'])
        {
            $anasayfa	= 1;
            $kisa 		= $_POST['kisa'];
        }
        else
        {
            $anasayfa	= 0;
            $kisa 		= "";
        }
        $aciklama 	= $_POST['aciklama'];
        $keywords 	= $_POST['keywords'];
        $description= $_POST['description'];
        $tarih		= date('Y-m-d H:i:s');
        $tarih		= TvERtXpE3w_tr_tarih($tarih);

        $upload = new upload($_FILES['resim']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/sayfalar");
            if ($upload->processed)
            {
                $Resim=''.$upload->file_dst_name.'';
            }
        }

        if(isset($Resim)){
            $resim_bul= $db->query("SELECT * FROM sayfalar WHERE id = '{$d_id}'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/sayfalar/".$resim_bul['resim']);
            $guncelle = $db->prepare("UPDATE sayfalar SET resim = ? WHERE id = ?");
            $guncelle->execute([$Resim,$d_id]);
            $Resim=''.$upload->file_dst_name.'';
        }

        $sorgu = $db->prepare("UPDATE sayfalar SET
			adi 	= ?,
			seo 	= ?,
			kisa 	= ?,
			aciklama= ?,
			keywords= ?,
			description	= ?,
			durum 	= ?,
			anasayfa= ?,
			tarih 	= ?
			WHERE id = ?");
        $guncelle = $sorgu->execute(array(
            $adi,
            $seo,
            $kisa,
            $aciklama,
            $keywords,
            $description,
            $durum,
            $anasayfa,
            $tarih,
            $d_id
        ));
        if($guncelle)
        {
            $last_id 		= $d_id;
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Sayfa Güncellendi",
                'icon' 		=> "icon-note",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$adi."</strong> başlıklı sayfayı güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['sayfa_guncelle'] = 'yes';
            header("Location:../yonetim/sayfa-duzenle/".$d_id.".html");
        }
        else
        {
            $_SESSION['sayfa_guncelle'] = 'no';
            header("Location:../yonetim/sayfa-duzenle/".$d_id.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/sayfa-duzenle/".$d_id.".html");
    }
}

##Sayfa Resim Sil##
if(@$_GET['sayfaresimsil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    @$resimid 	= $_GET['sid'];
    if($_SESSION['rutbe'] == 0)
    {
        $resim_bul	= $db->query("SELECT * FROM sayfalar WHERE id = '{$resimid}'")->fetch(PDO::FETCH_ASSOC);
        unlink("../".tema."/uploads/sayfalar/".$resim_bul['resim']);
        $sorgu = $db->prepare("UPDATE sayfalar SET
					resim	= ?
					WHERE id = ?");
        $guncelle = $sorgu->execute(array(
            "",
            $resimid
        ));
        if($guncelle)
        {
            $last_id 		= $resim_bul['id'];
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Sayfa Resim Silindi",
                'icon' 		=> "icon-trash",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$resim_bul['adi']."</strong> başlıklı sayfanın resmini sildi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['sayfaresimsil'] = 'yes';
            header("Location:../yonetim/sayfa-duzenle/".$resimid.".html");
        }
        else
        {
            $_SESSION['sayfaresimsil'] = 'no';
            header("Location:../yonetim/sayfa-duzenle/".$resimid.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/sayfa-duzenle/".$resimid.".html");
    }
}

##Sayfa Sil##
if(@$_GET['sayfasil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $resim_bul= $db->query("SELECT * FROM sayfalar WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        unlink("../".tema."/uploads/sayfalar/".$resim_bul['resim']);
        $Sorgu = $db->prepare("DELETE FROM sayfalar WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                $last_id 		= $resim_bul['id'];
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 	= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Sayfa Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$resim_bul['adi']."</strong> başlıklı sayfayı sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['sayfasil'] = 'yes';
                header("Location:../yonetim/sayfa-listele.html");
            }
            else
            {
                $_SESSION['sayfasil'] = 'no';
                header("Location:../yonetim/sayfa-listele.html");
            }
        }
        else
        {
            echo '<meta http-equiv="refresh" content="0; url=404.html">';
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/sayfa-listele.html");
    }
}

##Sayfa Toplu Sil ##
if(isset($_POST['sayfa_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $resim_bul= $db->query("SELECT * FROM sayfalar WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM sayfalar WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Sayfa Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$resim_bul['adi']."</strong> başlıklı sayfayı sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    unlink("../".tema."/uploads/sayfalar/".$resim_bul['resim']);
                    $_SESSION['sayfa_tumu'] = 'yes';
                    header("Location:../yonetim/sayfa-listele.html");
                }
                else
                {
                    $_SESSION['sayfa_tumu'] = 'no';
                    header("Location:../yonetim/sayfa-listele.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/sayfa-listele.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/sayfa-listele.html");
    }
}

##Sayfa Toplu Aktif ##
if(isset($_POST['sayfa_aktif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $sayfa_bul= $db->query("SELECT * FROM sayfalar WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE sayfalar SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "1",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Sayfa Aktif",
                        'icon' 		=> "icon-check",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$sayfa_bul['adi']."</strong> başlıklı sayfayı aktif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['sayfa_aktif'] = 'yes';
                    header("Location:../yonetim/sayfa-listele.html");
                }
                else
                {
                    $_SESSION['sayfa_aktif'] = 'no';
                    header("Location:../yonetim/sayfa-listele.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/sayfa-listele.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/sayfa-listele.html");
    }
}

##Sayfa Toplu Pasif ##
if(isset($_POST['sayfa_pasif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $sayfa_bul= $db->query("SELECT * FROM sayfalar WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE sayfalar SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "0",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Sayfa Pasif",
                        'icon' 		=> "icon-close",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$sayfa_bul['adi']."</strong> başlıklı sayfayı pasif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['sayfa_pasif'] = 'yes';
                    header("Location:../yonetim/sayfa-listele.html");
                }
                else
                {
                    $_SESSION['sayfa_pasif'] = 'no';
                    header("Location:../yonetim/sayfa-listele.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/sayfa-listele.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/sayfa-listele.html");
    }
}

##Hizmet Kaydet ##
if(isset($_POST['hizmet_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $adi 		= $_POST['adi'];
        $seoo		= TvERtXpE3w_seo($adi);
        if($seoo)
        {
            $seo 	= TvERtXpE3w_seo($adi);
        }
        else
        {
            $seo 	= rand();
        }
        if($_POST['durum']){$durum = 1;}else{$durum = 0;}
        $aciklama 	= $_POST['aciklama'];
        $keywords	= $_POST['keywords'];
        $description= $_POST['description'];
        $tarih		= date('Y-m-d H:i:s');
        $tarih		= TvERtXpE3w_tr_tarih($tarih);

        $upload = new upload($_FILES['resim']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/hizmetler");
            if ($upload->processed)
            {
                $Resim=''.$upload->file_dst_name.'';
            }
        }
        $gitti=$Resim=''.$upload->file_dst_name.'';

        $sorgu = $db->prepare("INSERT INTO hizmetler SET
				adi 	= ?,
				seo 	= ?,
				kisa 	= ?,
				aciklama= ?,
				keywords= ?,
				description	= ?,
				durum 	= ?,
				resim 	= ?,
				dil 	= ?,
				tarih 	= ?");
        $Ekle = $sorgu->execute(array(
            $adi,
            $seo,
            $kisa,
            $aciklama,
            $keywords,
            $description,
            $durum,
            $Resim,
            $_SESSION['admin_dil'],
            $tarih
        ));
        if($Ekle)
        {
            $last_id 		= $db->lastInsertId();
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Hizmet Ekledi",
                'icon' 		=> "icon-note",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$adi."</strong> adında hizmet ekledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['hizmet_ekle'] = 'yes';
            header("Location:../yonetim/hizmet-listele.html");
        }
        else
        {
            $_SESSION['hizmet_ekle'] = 'no';
            header("Location:../yonetim/hizmet-ekle.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/hizmet-ekle.html");
    }
}

##Hizmet Güncelle ##
if(isset($_POST['hizmet_guncelle']))
{
    TvERtXpE3w_panelislemkontrol();
    $d_id 	= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $adi 		= $_POST['adi'];
        $seoo		= TvERtXpE3w_seo($adi);
        if($seoo)
        {
            $seo 	= TvERtXpE3w_seo($adi);
        }
        else
        {
            $seo 	= rand();
        }
        if($_POST['durum']){$durum = 1;}else{$durum = 0;}
        $aciklama 	= $_POST['aciklama'];
        $keywords 	= $_POST['keywords'];
        $description= $_POST['description'];
        $tarih		= date('Y-m-d H:i:s');
        $tarih		= TvERtXpE3w_tr_tarih($tarih);

        $upload = new upload($_FILES['resim']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/hizmetler");
            if ($upload->processed)
            {
                $Resim=''.$upload->file_dst_name.'';
            }
        }

        if(isset($Resim)){
            $resim_bul= $db->query("SELECT * FROM hizmetler WHERE id = '{$d_id}'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/hizmetler/".$resim_bul['resim']);
            $guncelle = $db->prepare("UPDATE hizmetler SET resim = ? WHERE id = ?");
            $guncelle->execute([$Resim,$d_id]);
            $Resim=''.$upload->file_dst_name.'';
        }

        $sorgu = $db->prepare("UPDATE hizmetler SET
			adi 	= ?,
			seo 	= ?,
			kisa 	= ?,
			aciklama= ?,
			keywords= ?,
			description	= ?,
			durum 	= ?,
			tarih 	= ?
			WHERE id = ?");
        $guncelle = $sorgu->execute(array(
            $adi,
            $seo,
            $kisa,
            $aciklama,
            $keywords,
            $description,
            $durum,
            $tarih,
            $d_id
        ));
        if($guncelle)
        {
            $last_id 		= $d_id;
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Hizmet Güncellendi",
                'icon' 		=> "icon-note",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$adi."</strong> başlıklı hizmeti güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['hizmet_guncelle'] = 'yes';
            header("Location:../yonetim/hizmet-duzenle/".$d_id.".html");
        }
        else
        {
            $_SESSION['hizmet_guncelle'] = 'no';
            header("Location:../yonetim/hizmet-duzenle/".$d_id.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/hizmet-duzenle/".$d_id.".html");
    }
}

##Hizmet Resim Sil##
if(@$_GET['hizmetresimsil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    @$resimid 	= $_GET['sid'];
    if($_SESSION['rutbe'] == 0)
    {
        $hizmet_resim_bul	= $db->query("SELECT * FROM hizmetler WHERE id = '{$resimid}'")->fetch(PDO::FETCH_ASSOC);
        unlink("../".tema."/uploads/hizmetler/".$hizmet_resim_bul['resim']);
        $hizmet_sorgu = $db->prepare("UPDATE hizmetler SET
				resim	= ?
				WHERE id = ?");
        $hizmet_guncelle = $hizmet_sorgu->execute(array(
            "",
            $resimid
        ));
        if($hizmet_guncelle)
        {
            $last_id 		= $hizmet_resim_bul['id'];
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 		= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Hizmet Resim Silindi",
                'icon' 		=> "icon-trash",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$hizmet_resim_bul['adi']."</strong> başlıklı hizmetin resmini sildi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['hizmetresimsil'] = 'yes';
            header("Location:../yonetim/hizmet-duzenle/".$resimid.".html");
        }
        else
        {
            $_SESSION['hizmetresimsil'] = 'no';
            header("Location:../yonetim/hizmet-duzenle/".$resimid.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/hizmet-duzenle/".$resimid.".html");
    }
}

##Hizmet Sil##
if(@$_GET['hizmetsil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $hizmet_resim_bul	= $db->query("SELECT * FROM hizmetler WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        unlink("../".tema."/uploads/hizmetler/".$hizmet_resim_bul['resim']);
        $hizmet_sorgu	= $db->prepare("DELETE FROM hizmetler WHERE id = :id");
        $hizmet_sil 		= $hizmet_sorgu->execute(array('id' => $id));
        if($hizmet_sorgu->rowCount())
        {
            if($hizmet_sil)
            {
                $last_id 		= $hizmet_resim_bul['id'];
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 		= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Hizmet Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$hizmet_resim_bul['adi']."</strong> başlıklı hizmeti sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['hizmetsil'] = 'yes';
                header("Location:../yonetim/hizmet-listele.html");
            }
            else
            {
                $_SESSION['hizmetsil'] = 'no';
                header("Location:../yonetim/hizmet-listele.html");
            }
        }
        else
        {
            echo '<meta http-equiv="refresh" content="0; url=404.html">';
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/hizmet-listele.html");
    }
}

##Hizmet Toplu Sil ##
if(isset($_POST['hizmet_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $hizmet_resim_bul	= $db->query("SELECT * FROM hizmetler WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $HizmetTopluSorgu 	= $db->prepare("DELETE FROM hizmetler WHERE id = :id");
                $HizmetTopluSil		= $HizmetTopluSorgu->execute(array('id' => $i));
                if($HizmetTopluSil)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 		= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Hizmet Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$hizmet_resim_bul['adi']."</strong> başlıklı hizmeti sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    unlink("../".tema."/uploads/hizmetler/".$hizmet_resim_bul['resim']);
                    $_SESSION['hizmet_tumu'] = 'yes';
                    header("Location:../yonetim/hizmet-listele.html");
                }
                else
                {
                    $_SESSION['hizmet_tumu'] = 'no';
                    header("Location:../yonetim/hizmet-listele.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/hizmet-listele.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/hizmet-listele.html");
    }
}

##Hizmet Toplu Aktif ##
if(isset($_POST['hizmet_aktif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $sayfa_bul= $db->query("SELECT * FROM hizmetler WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE hizmetler SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "1",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Hizmet Aktif",
                        'icon' 		=> "icon-check",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$sayfa_bul['adi']."</strong> başlıklı hizmeti aktif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['hizmet_aktif'] = 'yes';
                    header("Location:../yonetim/hizmet-listele.html");
                }
                else
                {
                    $_SESSION['hizmet_aktif'] = 'no';
                    header("Location:../yonetim/hizmet-listele.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/hizmet-listele.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/hizmet-listele.html");
    }
}

##Hizmet Toplu Pasif ##
if(isset($_POST['hizmet_pasif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $sayfa_bul= $db->query("SELECT * FROM hizmetler WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE hizmetler SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "0",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Hizmet Pasif",
                        'icon' 		=> "icon-close",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$sayfa_bul['adi']."</strong> başlıklı hizmeti pasif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['hizmet_pasif'] = 'yes';
                    header("Location:../yonetim/hizmet-listele.html");
                }
                else
                {
                    $_SESSION['hizmet_pasif'] = 'no';
                    header("Location:../yonetim/hizmet-listele.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/hizmet-listele.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/hizmet-listele.html");
    }
}

##Referans Kaydet ##
if(isset($_POST['referans_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $adi 		= $_POST['adi'];
        $sira 		= $_POST['sira'];
        $anasayfa 	= $_POST['anasayfa'];
        $seoo		= TvERtXpE3w_seo($adi);
        if($seoo)
        {
            $seo 	= TvERtXpE3w_seo($adi);
        }
        else
        {
            $seo 	= rand();
        }
        if($_POST['durum']){$durum = 1;}else{$durum = 0;}
        $kisa 		= $_POST['kisa'];
        $aciklama 	= $_POST['aciklama'];
        $keywords	= $_POST['keywords'];
        $description= $_POST['description'];
        $tarih		= date('Y-m-d H:i:s');
        $tarih		= TvERtXpE3w_tr_tarih($tarih);

        $upload = new upload($_FILES['resim']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/referanslar");
            if ($upload->processed)
            {
                $Resim=''.$upload->file_dst_name.'';
            }
        }
        $gitti=$Resim=''.$upload->file_dst_name.'';

        $sorgu = $db->prepare("INSERT INTO referanslar SET
				adi 	= ?,
				sira 	= ?,
				anasayfa 	= ?,
				seo 	= ?,
				kisa	= ?,
				aciklama= ?,
				keywords= ?,
				description	= ?,
				durum 	= ?,
				resim 	= ?,
				dil 	= ?,
				tarih 	= ?");
        $Ekle = $sorgu->execute(array(
            $adi,
            $sira,
            $anasayfa,
            $seo,
            $kisa,
            $aciklama,
            $keywords,
            $description,
            $durum,
            $Resim,
            $_SESSION['admin_dil'],
            $tarih
        ));
        if($Ekle)
        {
            $last_id 		= $db->lastInsertId();
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Referans Ekledi",
                'icon' 		=> "icon-note",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$adi."</strong> adında referans ekledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['referans_ekle'] = 'yes';
            header("Location:../yonetim/referans-listele.html");
        }
        else
        {
            $_SESSION['referans_ekle'] = 'no';
            header("Location:../yonetim/referans-ekle.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/referans-ekle.html");
    }
}

##Referans Güncelle ##
if(isset($_POST['referans_guncelle']))
{
    TvERtXpE3w_panelislemkontrol();
    $d_id 	= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $adi 		= $_POST['adi'];
        $sira 		= $_POST['sira'];
        $anasayfa 	= $_POST['anasayfa'];
        $seoo		= TvERtXpE3w_seo($adi);
        if($seoo)
        {
            $seo 	= TvERtXpE3w_seo($adi);
        }
        else
        {
            $seo 	= rand();
        }
        if($_POST['durum']){$durum = 1;}else{$durum = 0;}
        $kisa	 	= $_POST['kisa'];
        $aciklama 	= $_POST['aciklama'];
        $keywords 	= $_POST['keywords'];
        $description= $_POST['description'];
        $tarih		= date('Y-m-d H:i:s');
        $tarih		= TvERtXpE3w_tr_tarih($tarih);

        $upload = new upload($_FILES['resim']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/referanslar");
            if ($upload->processed)
            {
                $Resim=''.$upload->file_dst_name.'';
            }
        }

        if(isset($Resim)){
            $resim_bul= $db->query("SELECT * FROM referanslar WHERE id = '{$d_id}'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/referanslar/".$resim_bul['resim']);
            $guncelle = $db->prepare("UPDATE referanslar SET resim = ? WHERE id = ?");
            $guncelle->execute([$Resim,$d_id]);
            $Resim=''.$upload->file_dst_name.'';
        }

        $sorgu = $db->prepare("UPDATE referanslar SET
			adi 	= ?,
			sira 	= ?,
			anasayfa = ?,
			seo 	= ?,
			kisa	= ?,
			aciklama= ?,
			keywords= ?,
			description	= ?,
			durum 	= ?,
			tarih 	= ?
			WHERE id = ?");
        $guncelle = $sorgu->execute(array(
            $adi,
            $sira,
            $anasayfa,
            $seo,
            $kisa,
            $aciklama,
            $keywords,
            $description,
            $durum,
            $tarih,
            $d_id
        ));
        if($guncelle)
        {
            $last_id 		= $d_id;
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Referans Güncellendi",
                'icon' 		=> "icon-note",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$adi."</strong> başlıklı referansı güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['referans_guncelle'] = 'yes';
            header("Location:../yonetim/referans-duzenle/".$d_id.".html");
        }
        else
        {
            $_SESSION['referans_guncelle'] = 'no';
            header("Location:../yonetim/referans-duzenle/".$d_id.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/referans-duzenle/".$d_id.".html");
    }
}

##Referans Resim Sil##
if(@$_GET['referansresimsil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    @$resimid 	= $_GET['sid'];
    if($_SESSION['rutbe'] == 0)
    {
        $resim_bul	= $db->query("SELECT * FROM referanslar WHERE id = '{$resimid}'")->fetch(PDO::FETCH_ASSOC);
        unlink("../".tema."/uploads/referanslar/".$resim_bul['resim']);
        $sorgu = $db->prepare("UPDATE referanslar SET
					resim	= ?
					WHERE id = ?");
        $guncelle = $sorgu->execute(array(
            "",
            $resimid
        ));
        if($guncelle)
        {
            $last_id 		= $resim_bul['id'];
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Referans Resim Silindi",
                'icon' 		=> "icon-trash",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$resim_bul['adi']."</strong> başlıklı referansın resmini sildi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['referansresimsil'] = 'yes';
            header("Location:../yonetim/referans-duzenle/".$resimid.".html");
        }
        else
        {
            $_SESSION['referansresimsil'] = 'no';
            header("Location:../yonetim/referans-duzenle/".$resimid.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/referans-duzenle/".$resimid.".html");
    }
}

##Referans Sil##
if(@$_GET['referanssil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $resim_bul= $db->query("SELECT * FROM referanslar WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        unlink("../".tema."/uploads/referanslar/".$resim_bul['resim']);
        $Sorgu = $db->prepare("DELETE FROM referanslar WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                $last_id 		= $resim_bul['id'];
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 	= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Referans Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$resim_bul['adi']."</strong> başlıklı referansı sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['referanssil'] = 'yes';
                header("Location:../yonetim/referans-listele.html");
            }
            else
            {
                $_SESSION['referanssil'] = 'no';
                header("Location:../yonetim/referans-listele.html");
            }
        }
        else
        {
            echo '<meta http-equiv="refresh" content="0; url=404.html">';
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/referans-listele.html");
    }
}

##Referans Toplu Sil ##
if(isset($_POST['referans_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $resim_bul= $db->query("SELECT * FROM referanslar WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM referanslar WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Referans Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$resim_bul['adi']."</strong> başlıklı referansı sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    unlink("../".tema."/uploads/referanslar/".$resim_bul['resim']);
                    $_SESSION['referans_tumu'] = 'yes';
                    header("Location:../yonetim/referans-listele.html");
                }
                else
                {
                    $_SESSION['referans_tumu'] = 'no';
                    header("Location:../yonetim/referans-listele.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/referans-listele.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/referans-listele.html");
    }
}

##Referans Toplu Aktif ##
if(isset($_POST['referans_aktif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $sayfa_bul= $db->query("SELECT * FROM referanslar WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE referanslar SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "1",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Referans Aktif",
                        'icon' 		=> "icon-check",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$sayfa_bul['adi']."</strong> başlıklı referansı aktif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['referans_aktif'] = 'yes';
                    header("Location:../yonetim/referans-listele.html");
                }
                else
                {
                    $_SESSION['referans_aktif'] = 'no';
                    header("Location:../yonetim/referans-listele.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/referans-listele.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/referans-listele.html");
    }
}

##Referans Toplu Pasif ##
if(isset($_POST['referans_pasif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $sayfa_bul= $db->query("SELECT * FROM referanslar WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE referanslar SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "0",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Referans Pasif",
                        'icon' 		=> "icon-close",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$sayfa_bul['adi']."</strong> başlıklı referansı pasif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['referans_pasif'] = 'yes';
                    header("Location:../yonetim/referans-listele.html");
                }
                else
                {
                    $_SESSION['referans_pasif'] = 'no';
                    header("Location:../yonetim/referans-listele.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/referans-listele.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/referans-listele.html");
    }
}

##Slider Kaydet ##
if(isset($_POST['slider_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $sira 		= $_POST['sira'];
        $adi 		= $_POST['adi'];
        $url 		= $_POST['url'];
        if($_POST['sekme']){$sekme = 1;}else{$sekme = 0;}
        if($_POST['durum']){$durum = 1;}else{$durum = 0;}
        $aciklama 	= $_POST['aciklama'];
        $tarih		= date('Y-m-d H:i:s');
        $tarih		= TvERtXpE3w_tr_tarih($tarih);

        $upload = new upload($_FILES['resim']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/slider");
            if ($upload->processed)
            {
                $Resim=''.$upload->file_dst_name.'';
            }
        }
        $gitti=$Resim=''.$upload->file_dst_name.'';

        $sorgu = $db->prepare("INSERT INTO slider SET
				sira 	= ?,
				adi 	= ?,
				url 	= ?,
				sekme 	= ?,
				aciklama= ?,
				durum 	= ?,
				resim 	= ?,
				dil 	= ?,
				tarih 	= ?");
        $Ekle = $sorgu->execute(array(
            $sira,
            $adi,
            $url,
            $sekme,
            $aciklama,
            $durum,
            $Resim,
            $_SESSION['admin_dil'],
            $tarih
        ));
        if($Ekle)
        {
            $last_id 		= $db->lastInsertId();
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Yeni Slider",
                'icon' 		=> "icon-picture",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkgoldenrod;'>".$adi."</strong> başlıklı slider ekledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['slider_ekle'] = 'yes';
            header("Location:../yonetim/slider-listele.html");
        }
        else
        {
            $_SESSION['slider_ekle'] = 'no';
            header("Location:../yonetim/slider-ekle.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/slider-ekle.html");
    }
}

##Slider Güncelle ##
if(isset($_POST['slider_guncelle']))
{
    TvERtXpE3w_panelislemkontrol();
    $d_id 		= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $sira 		= $_POST['sira'];
        $adi 		= $_POST['adi'];
        $url 		= $_POST['url'];
        if($_POST['sekme']){$sekme = 1;}else{$sekme = 0;}
        if($_POST['durum']){$durum = 1;}else{$durum = 0;}
        $aciklama 	= $_POST['aciklama'];
        $tarih		= date('Y-m-d H:i:s');
        $tarih		= TvERtXpE3w_tr_tarih($tarih);

        $upload = new upload($_FILES['resim']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/slider");
            if ($upload->processed)
            {
                $Resim=''.$upload->file_dst_name.'';
            }
        }
        if(isset($Resim)){
            $resim_bul= $db->query("SELECT * FROM slider WHERE id = '{$d_id}'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/slider/".$resim_bul['resim']);
            $guncelle = $db->prepare("UPDATE slider SET resim = ? WHERE id = ?");
            $guncelle->execute([$Resim,$d_id]);
        }
        $sorgu = $db->prepare("UPDATE slider SET
			sira 	= ?,
			adi 	= ?,
			url 	= ?,
			sekme 	= ?,
			aciklama= ?,
			durum 	= ?,
			tarih 	= ?
			WHERE id = ?");
        $guncelle = $sorgu->execute(array(
            $sira,
            $adi,
            $url,
            $sekme,
            $aciklama,
            $durum,
            $tarih,
            $d_id
        ));
        if($guncelle)
        {
            $last_id 		= $d_id;
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Slider Güncellendi",
                'icon' 		=> "icon-picture",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkgoldenrod;'>".$adi."</strong> başlıklı slideri güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['slider_guncelle'] = 'yes';
            header("Location:../yonetim/slider-duzenle/".$d_id.".html");
        }
        else
        {
            $_SESSION['slider_guncelle'] = 'no';
            header("Location:../yonetim/slider-duzenle/".$d_id.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/slider-duzenle/".$d_id.".html");
    }
}

##Slider Resim Sil##
if(@$_GET['sliderresimsil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    @$resimid 	= $_GET['sid'];
    if($_SESSION['rutbe'] == 0)
    {
        $resim_bul	= $db->query("SELECT * FROM slider WHERE id = '{$resimid}'")->fetch(PDO::FETCH_ASSOC);
        unlink("../".tema."/uploads/slider/".$resim_bul['resim']);
        $sorgu = $db->prepare("UPDATE slider SET
					resim	= ?
					WHERE id = ?");
        $guncelle = $sorgu->execute(array(
            "",
            $resimid
        ));
        if($guncelle)
        {
            $last_id 		= $resim_bul['id'];
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Slider Resim Silindi",
                'icon' 		=> "icon-picture",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkgoldenrod;'>".$resim_bul['adi']."</strong> başlıklı sliderin resmini sildi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['sliderresimsil'] = 'yes';
            header("Location:../yonetim/slider-duzenle/".$resimid.".html");
        }
        else
        {
            $_SESSION['sliderresimsil'] = 'no';
            header("Location:../yonetim/slider-duzenle/".$resimid.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/slider-duzenle/".$resimid.".html");
    }
}

##Slider Sil##
if(@$_GET['slidersil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $resim_bul= $db->query("SELECT * FROM slider WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        unlink("../".tema."/uploads/slider/".$resim_bul['resim']);
        $Sorgu = $db->prepare("DELETE FROM slider WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                $last_id 		= $resim_bul['id'];
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 	= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Slider Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkgoldenrod;'>".$resim_bul['adi']."</strong> başlıklı slideri sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['slidersil'] = 'yes';
                header("Location:../yonetim/slider-listele.html");
            }
            else
            {
                $_SESSION['slidersil'] = 'no';
                header("Location:../yonetim/slider-listele.html");
            }
        }
        else
        {
            echo '<meta http-equiv="refresh" content="0; url=404.html">';
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/slider-listele.html");
    }
}

##Slider Toplu Sil ##
if(isset($_POST['slider_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $resim_bul= $db->query("SELECT * FROM slider WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM slider WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Slider Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkgoldenrod;'>".$resim_bul['adi']."</strong> başlıklı slideri sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    unlink("../".tema."/uploads/slider/".$resim_bul['resim']);
                    $_SESSION['slider_tumu'] = 'yes';
                    header("Location:../yonetim/slider-listele.html");
                }
                else
                {
                    $_SESSION['slider_tumu'] = 'no';
                    header("Location:../yonetim/slider-listele.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/slider-listele.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/slider-listele.html");
    }
}

##Slider Toplu Aktif ##
if(isset($_POST['slider_aktif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $slider_bul= $db->query("SELECT * FROM slider WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE slider SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "1",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Slider Aktif",
                        'icon' 		=> "icon-check",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkgoldenrod;'>".$slider_bul['adi']."</strong> başlıklı slideri aktif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['slider_aktif'] = 'yes';
                    header("Location:../yonetim/slider-listele.html");
                }
                else
                {
                    $_SESSION['slider_aktif'] = 'no';
                    header("Location:../yonetim/slider-listele.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/slider-listele.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/slider-listele.html");
    }
}

##Slider Toplu Pasif ##
if(isset($_POST['slider_pasif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $slider_bul= $db->query("SELECT * FROM slider WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE slider SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "0",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Slider Pasif",
                        'icon' 		=> "icon-close",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkgoldenrod;'>".$slider_bul['adi']."</strong> başlıklı slideri pasif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['slider_pasif'] = 'yes';
                    header("Location:../yonetim/slider-listele.html");
                }
                else
                {
                    $_SESSION['slider_pasif'] = 'no';
                    header("Location:../yonetim/slider-listele.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/slider-listele.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/slider-listele.html");
    }
}

##Blog Kaydet ##
if(isset($_POST['blog_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $adi 		= $_POST['adi'];
        $seoo		= TvERtXpE3w_seo($adi);
        if($seoo)
        {
            $seo 	= TvERtXpE3w_seo($adi);
        }
        else
        {
            $seo 	= rand();
        }
        if($_POST['durum']){$durum = 1;}else{$durum = 0;}
        if($_POST['anasayfa']){$anasayfa = 1;}else{$anasayfa = 0;}
        $aciklama 	= $_POST['aciklama'];
        $keywords	= $_POST['keywords'];
        $description= $_POST['description'];
        $tarih		= date('Y-m-d H:i:s');
        $tarih		= TvERtXpE3w_tr_tarih($tarih);

        $upload = new upload($_FILES['resim']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/bloglar");
            if ($upload->processed)
            {
                $Resim=''.$upload->file_dst_name.'';
            }
        }
        $gitti=$Resim=''.$upload->file_dst_name.'';

        $sorgu = $db->prepare("INSERT INTO blog SET
				adi 	= ?,
				seo 	= ?,
				aciklama= ?,
				keywords= ?,
				description	= ?,
				durum 	= ?,
				anasayfa 	= ?,
				resim 	= ?,
				dil 	= ?,
				tarih 	= ?");
        $Ekle = $sorgu->execute(array(
            $adi,
            $seo,
            $aciklama,
            $keywords,
            $description,
            $durum,
            $anasayfa,
            $Resim,
            $_SESSION['admin_dil'],
            $tarih
        ));
        if($Ekle)
        {
            $last_id 		= $db->lastInsertId();
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Yeni Blog",
                'icon' 		=> "icon-book-open",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkturquoise;'>".$adi."</strong> başlıklı blog ekledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['blog_ekle'] = 'yes';
            header("Location:../yonetim/blog-listele.html");
        }
        else
        {
            $_SESSION['blog_ekle'] = 'no';
            header("Location:../yonetim/blog-ekle.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/blog-ekle.html");
    }
}

##Blog Güncelle ##
if(isset($_POST['blog_guncelle']))
{
    TvERtXpE3w_panelislemkontrol();
    $d_id 	= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $adi 		= $_POST['adi'];
        $seoo		= TvERtXpE3w_seo($adi);
        if($seoo)
        {
            $seo 	= TvERtXpE3w_seo($adi);
        }
        else
        {
            $seo 	= rand();
        }
        if($_POST['durum']){$durum = 1;}else{$durum = 0;}
        if($_POST['anasayfa']){$anasayfa = 1;}else{$anasayfa = 0;}
        $aciklama 	= $_POST['aciklama'];
        $keywords 	= $_POST['keywords'];
        $description= $_POST['description'];
        $tarih		= date('Y-m-d H:i:s');
        $tarih		= TvERtXpE3w_tr_tarih($tarih);

        $upload = new upload($_FILES['resim']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/bloglar");
            if ($upload->processed)
            {
                $Resim=''.$upload->file_dst_name.'';
            }
        }

        if(isset($Resim)){
            $resim_bul= $db->query("SELECT * FROM blog WHERE id = '{$d_id}'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/bloglar/".$resim_bul['resim']);
            $guncelle = $db->prepare("UPDATE blog SET resim = ? WHERE id = ?");
            $guncelle->execute([$Resim,$d_id]);
            $Resim=''.$upload->file_dst_name.'';
        }

        $sorgu = $db->prepare("UPDATE blog SET
			adi 	= ?,
			seo 	= ?,
			aciklama= ?,
			keywords= ?,
			description	= ?,
			durum 	= ?,
			anasayfa 	= ?,
			tarih 	= ?
			WHERE id = ?");
        $guncelle = $sorgu->execute(array(
            $adi,
            $seo,
            $aciklama,
            $keywords,
            $description,
            $durum,
            $anasayfa,
            $tarih,
            $d_id
        ));
        if($guncelle)
        {
            $last_id 		= $d_id;
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Blog Güncellendi",
                'icon' 		=> "icon-book-open",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkturquoise;'>".$adi."</strong> başlıklı blog güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['blog_guncelle'] = 'yes';
            header("Location:../yonetim/blog-duzenle/".$d_id.".html");
        }
        else
        {
            $_SESSION['blog_guncelle'] = 'no';
            header("Location:../yonetim/blog-duzenle/".$d_id.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/blog-duzenle/".$d_id.".html");
    }
}

##Blog Resim Sil##
if(@$_GET['blogresimsil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    @$resimid 	= $_GET['sid'];
    if($_SESSION['rutbe'] == 0)
    {
        $resim_bul	= $db->query("SELECT * FROM blog WHERE id = '{$resimid}'")->fetch(PDO::FETCH_ASSOC);
        unlink("../".tema."/uploads/bloglar/".$resim_bul['resim']);
        $sorgu = $db->prepare("UPDATE blog SET
					resim	= ?
					WHERE id = ?");
        $guncelle = $sorgu->execute(array(
            "",
            $resimid
        ));
        if($guncelle)
        {
            $last_id 		= $resim_bul['id'];
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Blog Resim Silindi",
                'icon' 		=> "icon-book-open",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkturquoise;'>".$resim_bul['adi']."</strong> başlıklı blog resmini sildi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['blogresimsil'] = 'yes';
            header("Location:../yonetim/blog-duzenle/".$resimid.".html");
        }
        else
        {
            $_SESSION['blogresimsil'] = 'no';
            header("Location:../yonetim/blog-duzenle/".$resimid.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/blog-duzenle/".$resimid.".html");
    }
}

##Blog Sil##
if(@$_GET['blogsil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $resim_bul= $db->query("SELECT * FROM blog WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        unlink("../".tema."/uploads/bloglar/".$resim_bul['resim']);
        $Sorgu = $db->prepare("DELETE FROM blog WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                $last_id 		= $resim_bul['id'];
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 	= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Blog Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkturquoise;'>".$resim_bul['adi']."</strong> başlıklı blog'u sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['blogsil'] = 'yes';
                header("Location:../yonetim/blog-listele.html");
            }
            else
            {
                $_SESSION['blogsil'] = 'no';
                header("Location:../yonetim/blog-listele.html");
            }
        }
        else
        {
            echo '<meta http-equiv="refresh" content="0; url=404.html">';
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/blog-listele.html");
    }
}

##Blog Toplu Sil ##
if(isset($_POST['blog_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $resim_bul= $db->query("SELECT * FROM blog WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM blog WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Blog Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkturquoise;'>".$resim_bul['adi']."</strong> başlıklı blog'u sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    unlink("../".tema."/uploads/bloglar/".$resim_bul['resim']);
                    $_SESSION['blog_tumu'] = 'yes';
                    header("Location:../yonetim/blog-listele.html");
                }
                else
                {
                    $_SESSION['blog_tumu'] = 'no';
                    header("Location:../yonetim/blog-listele.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/blog-listele.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/blog-listele.html");
    }
}

##Blog Toplu Aktif ##
if(isset($_POST['blog_aktif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $blog_bul= $db->query("SELECT * FROM blog WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE blog SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "1",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Blog Aktif",
                        'icon' 		=> "icon-check",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkturquoise;'>".$blog_bul['adi']."</strong> başlıklı blog'u aktif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['blog_aktif'] = 'yes';
                    header("Location:../yonetim/blog-listele.html");
                }
                else
                {
                    $_SESSION['blog_aktif'] = 'no';
                    header("Location:../yonetim/blog-listele.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/blog-listele.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/blog-listele.html");
    }
}

##Blog Toplu Pasif ##
if(isset($_POST['blog_pasif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $blog_bul= $db->query("SELECT * FROM blog WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE blog SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "0",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Blog Pasif",
                        'icon' 		=> "icon-close",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkturquoise;'>".$blog_bul['adi']."</strong> başlıklı blog'u pasif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['blog_pasif'] = 'yes';
                    header("Location:../yonetim/blog-listele.html");
                }
                else
                {
                    $_SESSION['blog_pasif'] = 'no';
                    header("Location:../yonetim/blog-listele.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/blog-listele.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/blog-listele.html");
    }
}

##Yönetici Kaydet ##
if(isset($_POST['yonetici_kaydet']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $isim 	= $_POST['isim'];
        $email 	= $_POST['email'];
        $kadi 	= $_POST['kadi'];
        $sifre 	= $_POST['sifre'];
        $tarih	= date("Y-m-d H:i:s");
        $tarih	= TvERtXpE3w_tr_tarih($tarih);

        $upload = new upload($_FILES['resim']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../yonetim/images/users");
            if ($upload->processed)
            {
                $Resim=''.$upload->file_dst_name.'';
            }
        }
        $gitti=$Resim=''.$upload->file_dst_name.'';

        $sorgu = $db->prepare("INSERT INTO kullanici SET
				isim 	= ?,
				email 	= ?,
				kadi	= ?,
				sifre	= ?,
				resim	= ?,
				son_giris= ?");
        $Ekle = $sorgu->execute(array(
            $isim,
            $email,
            $kadi,
            $sifre,
            $Resim,
            $tarih
        ));

        if($Ekle)
        {
            $last_id 		= $db->lastInsertId();
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Yeni Yönetici",
                'icon' 		=> "icon-user-follow",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkviolet;'>".$isim."</strong> adında yönetici ekledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['yonetici_kaydet'] = 'yes';
            header("Location:../yonetim/yonetici-listele.html");
        }
        else
        {
            $_SESSION['yonetici_kaydet'] = 'no';
            header("Location:../yonetim/yonetici-ekle.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/yonetici-ekle.html");
    }
}

##Yönetici Güncelle ##
if(isset($_POST['yonetici_guncelle']))
{
    TvERtXpE3w_panelislemkontrol();
    $d_id 	= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $isim 	= $_POST['isim'];
        $email 	= $_POST['email'];
        $kadi 	= $_POST['kadi'];
        $sifre 	= $_POST['sifre'];

        $upload = new upload($_FILES['resim']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../yonetim/images/users");
            if ($upload->processed)
            {
                $Resim=''.$upload->file_dst_name.'';
            }
        }
        if(isset($Resim)){
            $resim_bul= $db->query("SELECT * FROM kullanici WHERE id = '{$d_id}'")->fetch(PDO::FETCH_ASSOC);
            unlink("../yonetim/images/users/".$resim_bul['resim']);
            $guncelle = $db->prepare("UPDATE kullanici SET resim = ? WHERE id = ?");
            $guncelle->execute([$Resim,$d_id]);
        }

        $sorgu = $db->prepare("UPDATE kullanici SET
				isim 	= ?,
				email 	= ?,
				kadi	= ?,
				sifre	= ?
				WHERE id = ?");
        $guncelle = $sorgu->execute(array(
            $isim,
            $email,
            $kadi,
            $sifre,
            $d_id
        ));
        if($guncelle)
        {
            $last_id 		= $d_id;
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Yönetici Güncellendi",
                'icon' 		=> "icon-user-follow",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkviolet;'>".$isim."</strong> isimli yönetici hesabını güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['yonetici_guncelle'] = 'yes';
            header("Location:../yonetim/yonetici-duzenle/".$d_id.".html");
        }
        else
        {
            $_SESSION['yonetici_guncelle'] = 'no';
            header("Location:../yonetim/yonetici-duzenle/".$d_id.".html");
        }

    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/yonetici-duzenle/".$d_id.".html");
    }
}

##Yönetici Resim Sil##
if(@$_GET['yoneticiresimsil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    @$resimid 	= $_GET['sid'];
    if($_SESSION['rutbe'] == 0)
    {
        $resim_bul	= $db->query("SELECT * FROM kullanici WHERE id = '{$resimid}'")->fetch(PDO::FETCH_ASSOC);
        unlink("../yonetim/assets/images/users/".$resim_bul['resim']);
        $sorgu = $db->prepare("UPDATE kullanici SET
				resim	= ?
				WHERE id = ?");
        $guncelle = $sorgu->execute(array(
            "",
            $resimid
        ));
        if($guncelle)
        {
            $last_id 		= $resim_bul['id'];
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "yönetici Resmi Silindi",
                'icon' 		=> "icon-user-follow",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkviolet;'>".$resim_bul['isim']."</strong> isimli yöneticinin resmini sildi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['yoneticiresimsil'] = 'yes';
            header("Location:../yonetim/yonetici-duzenle/".$resimid.".html");
        }
        else
        {
            $_SESSION['yoneticiresimsil'] = 'no';
            header("Location:../yonetim/yonetici-duzenle/".$resimid.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/yonetici-duzenle/".$resimid.".html");
    }
}

##Yönetici Sil##
if(@$_GET['yoneticisil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $resim_bul= $db->query("SELECT * FROM kullanici WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        unlink("../yonetim/images/users/".$resim_bul['resim']);
        $Sorgu = $db->prepare("DELETE FROM kullanici WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                $last_id 		= $resim_bul['id'];
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 	= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Yönetici Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkviolet;'>".$resim_bul['isim']."</strong> isimli yöneticiyi sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['yoneticisil'] = 'yes';
                header("Location:../yonetim/yonetici-listele.html");
            }
            else
            {
                $_SESSION['yoneticisil'] = 'no';
                header("Location:../yonetim/yonetici-listele.html");
            }
        }
        else
        {
            echo '<meta http-equiv="refresh" content="0; url=404.html">';
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/yonetici-listele.html");
    }
}

##Yönetici Toplu Sil ##
if(isset($_POST['yonetici_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $resim_bul= $db->query("SELECT * FROM kullanici WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM kullanici WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Yönetici Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkviolet;'>".$resim_bul['isim']."</strong> isimli yöneticiyi sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    unlink("../yonetim/images/users/".$resim_bul['resim']);
                    $_SESSION['yonetici_tumu'] = 'yes';
                    header("Location:../yonetim/yonetici-listele.html");
                }
                else
                {
                    $_SESSION['yonetici_tumu'] = 'no';
                    header("Location:../yonetim/yonetici-listele.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/yonetici-listele.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/yonetici-listele.html");
    }
}

##Header Menü Kaydet ##
if(isset($_POST['MenuKaydet']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $menu_sira 		= $_POST['menu_sira'];
        $menu_ust 		= $_POST['menu_ust'];
        $menu_isim 		= $_POST['menu_isim'];
        $menu_url 		= $_POST['menu_url'];
        $link	 		= $_POST['link'];
        if($_POST['sekme']){$sekme = 1;}else{$sekme = 0;}
        $menu_durum 	= $_POST['menu_durum'];

        $menu_sorgu = $db->prepare("INSERT INTO menu SET
			menu_sira 	= ?,
			menu_ust 	= ?,
			menu_isim 	= ?,
			menu_url 	= ?,
			link 		= ?,
			sekme 		= ?,
			dil 		= ?,
			menu_durum 	= ?");
        $menu_ekle = $menu_sorgu->execute(array(
            $menu_sira,
            $menu_ust,
            $menu_isim,
            $menu_url,
            $link,
            $sekme,
            $_SESSION['admin_dil'],
            $menu_durum
        ));

        if($menu_ekle)
        {
            $last_id 		= $db->lastInsertId();
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 		= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Yeni Header Menü",
                'icon' 		=> "icon-menu",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkseagreen;'>".$menu_isim."</strong> adında siteye üst menü ekledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['MenuKaydet'] = 'yes';
            header("Location:../yonetim/header-menu.html");
        }
        else
        {
            $_SESSION['MenuKaydet'] = 'no';
            header("Location:../yonetim/header-menu.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/header-menu.html");
    }
}

##Header Menü Güncelle ##
if(isset($_POST['MenuGuncelle']))
{
    TvERtXpE3w_panelislemkontrol();
    $d_id 	= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $menu_sira 		= $_POST['menu_sira'];
        $menu_ust 		= $_POST['menu_ust'];
        $menu_isim 		= $_POST['menu_isim'];
        $menu_url 		= $_POST['menu_url'];
        if($_POST['sekme']){$sekme = 1;}else{$sekme = 0;}
        if($_POST['menu_url'] != "0")
        {
            $link = " ";
        }
        if($_POST['menu_url'] == "0")
        {
            $link	= $_POST['link'];
        }
        $menu_durum 	= $_POST['menu_durum'];

        $menu_sorgu = $db->prepare("UPDATE menu SET
				menu_sira 	= ?,
				menu_ust 	= ?,
				menu_isim 	= ?,
				menu_url 	= ?,
				link 		= ?,
				sekme 		= ?,
				menu_durum 	= ?
				WHERE id = ?");
        $menu_guncelle = $menu_sorgu->execute(array(
            $menu_sira,
            $menu_ust,
            $menu_isim,
            $menu_url,
            $link,
            $sekme,
            $menu_durum,
            $d_id
        ));
        if($menu_guncelle)
        {
            $last_id 		= $d_id;
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Üst Menü Güncellendi",
                'icon' 		=> "icon-menu",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkseagreen;'>".$menu_isim."</strong> başlıklı üst menüyü güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['MenuGuncelle'] = 'yes';
            header("Location:../yonetim/header-menu-duzenle/".$d_id.".html");
        }
        else
        {
            $_SESSION['MenuGuncelle'] = 'no';
            header("Location:../yonetim/header-menu-duzenle/".$d_id.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/header-menu-duzenle/".$d_id.".html");
    }
}

##Header Menü Sil##
if(@$_GET['menusil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    $id = $_GET['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $menu_bul		= $db->query("SELECT * FROM menu WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        $menu_sil_sorgu 	= $db->prepare("DELETE FROM menu WHERE id = :id");
        $menu_sil 		= $menu_sil_sorgu->execute(array('id' => $id));
        if($menu_sil_sorgu->rowCount())
        {
            $TopluSorgu = $db->prepare("SELECT * FROM menu WHERE menu_ust = ?");
            $TopluSorgu->execute(array($_GET['id']));
            $Topluislem = $TopluSorgu->fetchALL(PDO::FETCH_ASSOC);
            foreach ( $Topluislem as $TopluSonuc )
            {
                $last_id 		= $TopluSonuc['id'];
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 	= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Üst Menü Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkseagreen;'>".$TopluSonuc['menu_isim']."</strong> başlıklı üst menüyü sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $TSorgu = $db->prepare("DELETE FROM menu WHERE id = :id");
                $TSorgu->execute(array('id' => $TopluSonuc['id']));
            }
            $last_id 		= $id ;
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Üst Menü Silindi",
                'icon' 		=> "icon-trash",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkseagreen;'>".$menu_bul['menu_isim']."</strong> başlıklı üst menüyü sildi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['menusil'] = 'yes';
            header("Location:../yonetim/header-menu-duzenle/".$id.".html");
        }
        else
        {
            $_SESSION['menusil'] = 'no';
            header("Location:../yonetim/header-menu-duzenle/".$id.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/header-menu-duzenle/".$id.".html");
    }
}

##Footer Menü Kaydet ##
if(isset($_POST['FMenuKaydet']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $menu_sira 		= $_POST['menu_sira'];
        $menu_ust 		= $_POST['menu_ust'];
        $menu_isim 		= $_POST['menu_isim'];
        $menu_url 		= $_POST['menu_url'];
        $link	 		= $_POST['link'];
        if($_POST['sekme']){$sekme = 1;}else{$sekme = 0;}
        $menu_durum 	= $_POST['menu_durum'];

        $sorgu = $db->prepare("INSERT INTO footermenu SET
			menu_sira 	= ?,
			menu_ust 	= ?,
			menu_isim 	= ?,
			menu_url 	= ?,
			link 		= ?,
			sekme 		= ?,
			dil 		= ?,
			menu_durum 	= ?");
        $Ekle = $sorgu->execute(array(
            $menu_sira,
            $menu_ust,
            $menu_isim,
            $menu_url,
            $link,
            $sekme,
            $_SESSION['admin_dil'],
            $menu_durum
        ));

        if($Ekle)
        {
            $last_id 		= $db->lastInsertId();
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Yeni Alt Menü",
                'icon' 		=> "icon-menu",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkseagreen;'>".$menu_isim."</strong> adında siteye alt menü ekledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['FMenuKaydet'] = 'yes';
            header("Location:../yonetim/footer-menu.html");
        }
        else
        {
            $_SESSION['FMenuKaydet'] = 'no';
            header("Location:../yonetim/footer-menu.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/footer-menu.html");
    }
}

##Footer Menü Güncelle ##
if(isset($_POST['FMenuGuncelle']))
{
    TvERtXpE3w_panelislemkontrol();
    $d_id 	= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $menu_sira 		= $_POST['menu_sira'];
        $menu_ust 		= $_POST['menu_ust'];
        $menu_isim 		= $_POST['menu_isim'];
        $menu_url 		= $_POST['menu_url'];
        if($_POST['sekme']){$sekme = 1;}else{$sekme = 0;}
        if($_POST['menu_url'] != "0")
        {
            $link = " ";
        }
        if($_POST['menu_url'] == "0")
        {
            $link	= $_POST['link'];
        }
        $menu_durum 	= $_POST['menu_durum'];


        $sorgu = $db->prepare("UPDATE footermenu SET
				menu_sira 	= ?,
				menu_ust 	= ?,
				menu_isim 	= ?,
				menu_url 	= ?,
				link 		= ?,
				sekme 		= ?,
				menu_durum 	= ?
				WHERE id = ?");
        $guncelle = $sorgu->execute(array(
            $menu_sira,
            $menu_ust,
            $menu_isim,
            $menu_url,
            $link,
            $sekme,
            $menu_durum,
            $d_id
        ));
        if($guncelle)
        {
            $last_id 		= $d_id;
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Alt Menü Güncellendi",
                'icon' 		=> "icon-menu",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkseagreen;'>".$menu_isim."</strong> başlıklı alt menüyü güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['FMenuGuncelle'] = 'yes';
            header("Location:../yonetim/footer-menu-duzenle/".$d_id.".html");
        }
        else
        {
            $_SESSION['FMenuGuncelle'] = 'no';
            header("Location:../yonetim/footer-menu-duzenle/".$d_id.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/footer-menu-duzenle/".$d_id.".html");
    }
}

##Footer Menü Sil##
if(@$_GET['fmenusil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    $id = $_GET['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $footer_menu_bul	= $db->query("SELECT * FROM footermenu WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        $footer_menu_sorgu 	= $db->prepare("DELETE FROM footermenu WHERE id = :id");
        $footer_menu_sil 	= $footer_menu_sorgu->execute(array('id' => $id));
        if($footer_menu_sorgu->rowCount())
        {
            if($footer_menu_sil)
            {
                $TopluSorgu = $db->prepare("SELECT * FROM footermenu WHERE menu_ust = ?");
                $TopluSorgu->execute(array($_GET['id']));
                $Topluislem = $TopluSorgu->fetchALL(PDO::FETCH_ASSOC);
                foreach ( $Topluislem as $TopluSonuc )
                {
                    $last_id 		= $TopluSonuc['id'];
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 		= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Alt Menü Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkseagreen;'>".$TopluSonuc['menu_isim']."</strong> başlıklı alt menüyü sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $TSorgu = $db->prepare("DELETE FROM footermenu WHERE id = :id");
                    $TSorgu->execute(array('id' => $TopluSonuc['id']));
                }
                $last_id 		= $id ;
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 	= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Alt Menü Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkseagreen;'>".$menu_bul['menu_isim']."</strong> başlıklı alt menüyü sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['fmenusil'] = 'yes';
                header("Location:../yonetim/footer-menu-duzenle/".$id.".html");
            }
            else
            {
                $_SESSION['fmenusil'] = 'no';
                header("Location:../yonetim/footer-menu-duzenle/".$id.".html");
            }
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/footer-menu-duzenle/".$id.".html");
    }
}

##Genel Ayarlar ##
if(isset($_POST['genel_ayarlar']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $site_title		= $_POST['site_title'];
        $site_url 		= $_POST['site_url'];
        $domain_url 	= $_POST['domain_url'];
        $site_keyw 		= $_POST['site_keyw'];
        $site_desc 		= $_POST['site_desc'];
        $copyright 		= $_POST['copyright'];
        $kodlar 		= $_POST['ekstra'];
        $renk1 			= $_POST['renk1'];
        $renk2 			= $_POST['renk2'];
        $renk3 			= $_POST['renk3'];
        $defaultsms     = $_POST['defaultsms'];
        $defaultpayment = $_POST['defaultpayment'];

        $upload = new upload($_FILES['resim']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/logo");
            if ($upload->processed)
            {
                $firmalogo=''.$upload->file_dst_name.'';
            }
        }

        $upload2 = new upload($_FILES['footer']);
        if ($upload2->uploaded)
        {
            $upload2->file_auto_rename = true;
            $upload2->process("../".tema."/uploads/logo/footer");
            if ($upload2->processed)
            {
                $footerlogo=''.$upload2->file_dst_name.'';
            }
        }

        $upload = new upload($_FILES['favicon']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/favicon");
            if ($upload->processed)
            {
                $favicon=''.$upload->file_dst_name.'';
            }
        }

        if(isset($firmalogo)){
            $resim_bul= $db->query("SELECT * FROM ayarlar WHERE id = '1'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/logo/".$resim_bul['firma_logo']);
            $guncelle = $db->prepare("UPDATE ayarlar SET firma_logo = ? WHERE id = ?");
            $guncelle->execute([$firmalogo,1]);
            $firmalogo=''.$upload->file_dst_name.'';
        }

        if(isset($footerlogo)){
            $resim_bul= $db->query("SELECT * FROM ayarlar WHERE id = '1'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/logo/footer/".$resim_bul['firma_footerlogo']);
            $guncelle = $db->prepare("UPDATE ayarlar SET firma_footerlogo = ? WHERE id = ?");
            $guncelle->execute([$footerlogo,1]);
            $footerlogo=''.$upload2->file_dst_name.'';
        }

        if(isset($favicon)){
            $resim_bul= $db->query("SELECT * FROM ayarlar WHERE id = '1'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/favicon/".$resim_bul['favicon']);
            $guncelle = $db->prepare("UPDATE ayarlar SET favicon = ? WHERE id = ?");
            $guncelle->execute([$favicon,1]);
            $favicon=''.$upload->file_dst_name.'';
        }
        $sorgu = $db->prepare("UPDATE ayarlar SET
			site_baslik	    = ?,
			site_url 	    = ?,
			domain_url 	    = ?,
			site_keyw	    = ?,
			site_desc	    = ?,
			renk1		    = ?,
			renk2		    = ?,
			renk3		    = ?,
			copyright	    = ?,
			defaultsms      = ?,
			defaultpayment  = ?
			WHERE id 	= ?");
        $guncelle = $sorgu->execute(array(
            $site_title,
            $site_url,
            $domain_url,
            $site_keyw,
            $site_desc,
            $renk1,
            $renk2,
            $renk3,
            $copyright,
            $defaultsms,
            $defaultpayment,
            "1"
        ));
        if($guncelle)
        {
            $degisken = "dil";
            if (strlen($kodlar) == 0) return false;

            $yaz = fopen('../language/admin_'.$degisken.".php", "w") or die("Dosya açılamadı lütfen dizin yetkilerini kontrol edin!");
            fwrite($yaz, $kodlar);
            fclose($yaz);

            $last_id 		= "1";
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 		= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Genel Ayarlar Güncellendi",
                'icon' 		=> "icon-settings",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> genel ayarları güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['genel_ayarlar'] = 'yes';
            header("Location:../yonetim/genel-ayarlar.html");
        }
        else
        {
            $_SESSION['genel_ayarlar'] = 'no';
            header("Location:../yonetim/genel-ayarlar.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/genel-ayarlar.html");
    }
}

##Api Ayarlar ##
if(isset($_POST['api_ayarlar']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $google_analytics	= $_POST['google_analytics'];
        $dogrulama_kodu 	= $_POST['dogrulama_kodu'];
        $google_maps 		= $_POST['google_maps'];
        $canli_destek 		= $_POST['canli_destek'];
        $whatsapp 			= $_POST['whatsapp'];
        $rcaptha 			= $_POST['rcaptha'];

        $sorgu = $db->prepare("UPDATE ayarlar SET
			google_analytics= ?,
			dogrulama_kodu 	= ?,
			google_maps		= ?,
			whatsapp			= ?,
			rcaptha			= ?,
			canli_destek	= ?
			WHERE id 		= ?");
        $guncelle = $sorgu->execute(array(
            $google_analytics,
            $dogrulama_kodu,
            $google_maps,
            $whatsapp,
            $rcaptha,
            $canli_destek,
            "1"
        ));
        if($guncelle)
        {
            $last_id 		= "1";
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Api Ayarları Güncellendi",
                'icon' 		=> "icon-settings",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> api ayarlarını güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['api_ayarlar'] = 'yes';
            header("Location:../yonetim/api-ayarlari.html");
        }
        else
        {
            $_SESSION['api_ayarlar'] = 'no';
            header("Location:../yonetim/api-ayarlari.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/api-ayarlari.html");
    }
}

##İletişim Ayarlar ##
if(isset($_POST['iletisim_ayarlar']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $firma_adi		= $_POST['firma_adi'];
        $firma_telefon 	= $_POST['firma_telefon'];
        $firma_fax 		= $_POST['firma_fax'];
        $firma_email 	= $_POST['firma_email'];
        $firma_adres 	= $_POST['firma_adres'];

        $sorgu = $db->prepare("UPDATE ayarlar SET
			firma_adi		= ?,
			firma_telefon 	= ?,
			firma_fax		= ?,
			firma_email		= ?,
			firma_adres		= ?
			WHERE id 		= ?");
        $guncelle = $sorgu->execute(array(
            $firma_adi,
            $firma_telefon,
            $firma_fax,
            $firma_email,
            $firma_adres,
            "1"
        ));
        if($guncelle)
        {
            $last_id 		= "1";
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "İletişim Ayarları Güncellendi",
                'icon' 		=> "icon-settings",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> iletişim ayarlarını güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['iletisim_ayarlar'] = 'yes';
            header("Location:../yonetim/iletisim-ayarlari.html");
        }
        else
        {
            $_SESSION['iletisim_ayarlar'] = 'no';
            header("Location:../yonetim/iletisim-ayarlari.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/iletisim-ayarlari.html");
    }
}

##Sanal Pos Ayarları ##
if(isset($_POST['paytr_ayarlar']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $magaza_no		= $_POST['magaza_no'];
        $magaza_parola 	= $_POST['magaza_parola'];
        $magaza_anahtar	= $_POST['magaza_anahtar'];
        $hata_mesaj		= $_POST['hata_mesaj'];
        $test_modu		= $_POST['test_modu'];
        $taksit			= $_POST['taksit'];

        $sorgu = $db->prepare("UPDATE paytr SET
			magaza_no		= ?,
			magaza_parola 	= ?,
			hata_mesaj		= ?,
			test_modu		= ?,
			taksit			= ?,
			magaza_anahtar	= ?
			WHERE id 		= ?");
        $guncelle = $sorgu->execute(array(
            $magaza_no,
            $magaza_parola,
            $hata_mesaj,
            $test_modu,
            $taksit,
            $magaza_anahtar,
            "1"
        ));
        if($guncelle)
        {
            $last_id 		= "1";
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Sanal Pos Ayarları Güncellendi",
                'icon' 		=> "icon-credit-card",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> Sanalpos ayarlarını güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['paytr_ayarlar'] = 'yes';
            header("Location:../yonetim/sanal-poslar.html");
        }
        else
        {
            $_SESSION['paytr_ayarlar'] = 'no';
            header("Location:../yonetim/sanal-poslar.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/sanal-poslar.html");
    }
}

##Sosyal Medya Ayarları ##
if(isset($_POST['sosyal_ayarlar']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $facebook	= $_POST['facebook'];
        $twitter 	= $_POST['twitter'];
        $instagram 	= $_POST['instagram'];
        $linkedin 	= $_POST['linkedin'];
        $youtube 	= $_POST['youtube'];

        $sorgu = $db->prepare("UPDATE ayarlar SET
			facebook	= ?,
			twitter 	= ?,
			instagram	= ?,
			linkedin	= ?,
			youtube		= ?
			WHERE id 	= ?");
        $guncelle = $sorgu->execute(array(
            $facebook,
            $twitter,
            $instagram,
            $linkedin,
            $youtube,
            "1"
        ));
        if($guncelle)
        {
            $last_id 		= "1";
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Sosyal Medya Ayarları Güncellendi",
                'icon' 		=> "icon-settings",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> sosyal medya ayarlarını güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['sosyal_ayarlar'] = 'yes';
            header("Location:../yonetim/sosyal-medya-ayarlari.html");
        }
        else
        {
            $_SESSION['sosyal_ayarlar'] = 'no';
            header("Location:../yonetim/sosyal-medya-ayarlari.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/sosyal-medya-ayarlari.html");
    }
}

##Limit Güncelle ##
if(isset($_POST['limit_ayarlar']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $limit_paket			= $_POST['limit_paket'];
        $limit_hosting			= $_POST['limit_hosting'];
        $limit_referanslar		= $_POST['limit_referanslar'];
        $limit_banka			= $_POST['limit_banka'];
        $limit_blog				= $_POST['limit_blog'];
        $limit_sayfapaketler	= $_POST['limit_sayfapaketler'];
        $limit_sayfahosting		= $_POST['limit_sayfahosting'];
        $limit_sayfareferans	= $_POST['limit_sayfareferans'];
        $limit_sayfabanka		= $_POST['limit_sayfabanka'];
        $limit_sayfablog		= $_POST['limit_sayfablog'];

        $sorgu = $db->prepare("UPDATE limit_ayarlari SET
			limit_paket			= ?,
			limit_hosting		= ?,
			limit_referanslar	= ?,
			limit_banka			= ?,
			limit_blog			= ?,
			limit_sayfapaketler	= ?,
			limit_sayfahosting	= ?,
			limit_sayfareferans	= ?,
			limit_sayfabanka	= ?,
			limit_sayfablog		= ?
			WHERE id 			= ?");
        $guncelle = $sorgu->execute(array(
            $limit_paket,
            $limit_hosting,
            $limit_referanslar,
            $limit_banka,
            $limit_blog,
            $limit_sayfapaketler,
            $limit_sayfahosting,
            $limit_sayfareferans,
            $limit_sayfabanka,
            $limit_sayfablog,
            "1"
        ));
        if($guncelle)
        {
            $last_id 		= "1";
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Limit Ayarları Güncellendi",
                'icon' 		=> "icon-settings",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> limit ayarlarını güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['limit_ayarlar'] = 'yes';
            header("Location:../yonetim/limit-ayarlari.html");
        }
        else
        {
            $_SESSION['limit_ayarlar'] = 'no';
            header("Location:../yonetim/limit-ayarlari.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/limit-ayarlari.html");
    }
}

##Mail Ayarları ##
if(isset($_POST['mail_ayarlar']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $m_server	= $_POST['m_server'];
        $m_adresi 	= $_POST['m_adresi'];
        $m_parola 	= $_POST['m_parola'];
        $m_port 		= $_POST['m_port'];
        $m_kime 		= $_POST['m_kime'];
        $m_sertifika = $_POST['m_sertifika'];
        $durum 		= $_POST['durum'];

        $sorgu = $db->prepare("UPDATE mail_ayar SET
			m_server	= ?,
			m_adresi 	= ?,
			m_parola	= ?,
			m_port		= ?,
			m_kime		= ?,
			m_sertifika	= ?,
			durum		= ?
			WHERE id 	= ?");
        $guncelle = $sorgu->execute(array(
            $m_server,
            $m_adresi,
            $m_parola,
            $m_port,
            $m_kime,
            $m_sertifika,
            $durum,
            "1"
        ));
        if($guncelle)
        {
            $last_id 		= "1";
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Mail Ayarları Güncellendi",
                'icon' 		=> "icon-settings",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> mail ayarlarını güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['mail_ayarlar'] = 'yes';
            header("Location:../yonetim/mail-ayarlari.html");
        }
        else
        {
            $_SESSION['mail_ayarlar'] = 'no';
            header("Location:../yonetim/mail-ayarlari.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/mail-ayarlari.html");
    }
}

##SMS Ayarları ##
if(isset($_POST['sms_ayarlar']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $postUrl		= $_POST['postUrl'];
        $KULLANICIADI 	= $_POST['KULLANICIADI'];
        $SIFRE 			= $_POST['SIFRE'];
        $ORGINATOR 		= $_POST['ORGINATOR'];
        $m_kime 			= $_POST['m_kime'];

        $sorgu = $db->prepare("UPDATE sms SET
			postUrl		= ?,
			KULLANICIADI= ?,
			SIFRE		= ?,
			m_kime		= ?,
			ORGINATOR	= ?
			WHERE id 	= ?");
        $guncelle = $sorgu->execute(array(
            $postUrl,
            $KULLANICIADI,
            $SIFRE,
            $m_kime,
            $ORGINATOR,
            "1"
        ));
        if($guncelle)
        {
            $last_id 		= "1";
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "SMS Ayarları Güncellendi",
                'icon' 		=> "icon-settings",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> sms ayarlarını güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['sms_ayarlar'] = 'yes';
            header("Location:../yonetim/sms-ayarlari.html");
        }
        else
        {
            $_SESSION['sms_ayarlar'] = 'no';
            header("Location:../yonetim/sms-ayarlari.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/sms-ayarlari.html");
    }
}

##Arka Plan Görseli Güncelle ##
if(isset($_POST['arkaplan_ayarlar']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $upload = new upload($_FILES['sayfalar']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/arkaplan/sayfalar");
            if ($upload->processed)
            {
                $sayfalar=''.$upload->file_dst_name.'';
            }
        }

        $upload = new upload($_FILES['blog']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/arkaplan/blog");
            if ($upload->processed)
            {
                $blog=''.$upload->file_dst_name.'';
            }
        }

        $upload = new upload($_FILES['paketler']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/arkaplan/paketler");
            if ($upload->processed)
            {
                $paketler=''.$upload->file_dst_name.'';
            }
        }

        $upload = new upload($_FILES['alanadi']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/arkaplan/alanadi");
            if ($upload->processed)
            {
                $alanadi=''.$upload->file_dst_name.'';
            }
        }

        $upload = new upload($_FILES['hosting']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/arkaplan/hosting");
            if ($upload->processed)
            {
                $hosting=''.$upload->file_dst_name.'';
            }
        }

        $upload = new upload($_FILES['hizmetler']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/arkaplan/hizmetler");
            if ($upload->processed)
            {
                $hizmetler=''.$upload->file_dst_name.'';
            }
        }

        $upload = new upload($_FILES['referanslar']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/arkaplan/referanslar");
            if ($upload->processed)
            {
                $referanslar=''.$upload->file_dst_name.'';
            }
        }

        $upload = new upload($_FILES['banka_hesaplari']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/arkaplan/banka_hesaplari");
            if ($upload->processed)
            {
                $banka_hesaplari=''.$upload->file_dst_name.'';
            }
        }

        $upload = new upload($_FILES['iletisim']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/arkaplan/iletisim");
            if ($upload->processed)
            {
                $iletisim=''.$upload->file_dst_name.'';
            }
        }

        $upload = new upload($_FILES['footer']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/arkaplan/footer");
            if ($upload->processed)
            {
                $footer=''.$upload->file_dst_name.'';
            }
        }

        $upload = new upload($_FILES['uyelik']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/arkaplan/uyelik");
            if ($upload->processed)
            {
                $uyelik=''.$upload->file_dst_name.'';
            }
        }

        if(isset($sayfalar)){
            $resim_bul= $db->query("SELECT * FROM arka_plan WHERE id = '1'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/arkaplan/sayfalar/".$resim_bul['sayfalar']);
            $guncelle = $db->prepare("UPDATE arka_plan SET sayfalar = ? WHERE id = ?");
            $guncelle->execute([$sayfalar,1]);
            $sayfalar=''.$upload->file_dst_name.'';
        }

        if(isset($blog)){
            $resim_bul= $db->query("SELECT * FROM arka_plan WHERE id = '1'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/arkaplan/blog/".$resim_bul['blog']);
            $guncelle = $db->prepare("UPDATE arka_plan SET blog = ? WHERE id = ?");
            $guncelle->execute([$blog,1]);
            $blog=''.$upload->file_dst_name.'';
        }

        if(isset($paketler)){
            $resim_bul= $db->query("SELECT * FROM arka_plan WHERE id = '1'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/arkaplan/paketler/".$resim_bul['paketler']);
            $guncelle = $db->prepare("UPDATE arka_plan SET paketler = ? WHERE id = ?");
            $guncelle->execute([$paketler,1]);
            $paketler=''.$upload->file_dst_name.'';
        }

        if(isset($alanadi)){
            $resim_bul= $db->query("SELECT * FROM arka_plan WHERE id = '1'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/arkaplan/alanadi/".$resim_bul['alanadi']);
            $guncelle = $db->prepare("UPDATE arka_plan SET alanadi = ? WHERE id = ?");
            $guncelle->execute([$alanadi,1]);
            $alanadi=''.$upload->file_dst_name.'';
        }

        if(isset($hosting)){
            $resim_bul= $db->query("SELECT * FROM arka_plan WHERE id = '1'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/arkaplan/hosting/".$resim_bul['hosting']);
            $guncelle = $db->prepare("UPDATE arka_plan SET hosting = ? WHERE id = ?");
            $guncelle->execute([$hosting,1]);
            $hosting=''.$upload->file_dst_name.'';
        }

        if(isset($hizmetler)){
            $resim_bul= $db->query("SELECT * FROM arka_plan WHERE id = '1'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/arkaplan/hizmetler/".$resim_bul['hizmetler']);
            $guncelle = $db->prepare("UPDATE arka_plan SET hizmetler = ? WHERE id = ?");
            $guncelle->execute([$hizmetler,1]);
            $hizmetler=''.$upload->file_dst_name.'';
        }

        if(isset($referanslar)){
            $resim_bul= $db->query("SELECT * FROM arka_plan WHERE id = '1'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/arkaplan/referanslar/".$resim_bul['referanslar']);
            $guncelle = $db->prepare("UPDATE arka_plan SET referanslar = ? WHERE id = ?");
            $guncelle->execute([$referanslar,1]);
            $referanslar=''.$upload->file_dst_name.'';
        }

        if(isset($banka_hesaplari)){
            $resim_bul= $db->query("SELECT * FROM arka_plan WHERE id = '1'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/arkaplan/banka_hesaplari/".$resim_bul['banka_hesaplari']);
            $guncelle = $db->prepare("UPDATE arka_plan SET banka_hesaplari = ? WHERE id = ?");
            $guncelle->execute([$banka_hesaplari,1]);
            $banka_hesaplari=''.$upload->file_dst_name.'';
        }

        if(isset($iletisim)){
            $resim_bul= $db->query("SELECT * FROM arka_plan WHERE id = '1'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/arkaplan/iletisim/".$resim_bul['iletisim']);
            $guncelle = $db->prepare("UPDATE arka_plan SET iletisim = ? WHERE id = ?");
            $guncelle->execute([$iletisim,1]);
            $iletisim=''.$upload->file_dst_name.'';
        }

        if(isset($footer)){
            $resim_bul= $db->query("SELECT * FROM arka_plan WHERE id = '1'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/arkaplan/footer/".$resim_bul['footer']);
            $guncelle = $db->prepare("UPDATE arka_plan SET footer = ? WHERE id = ?");
            $guncelle->execute([$footer,1]);
            $footer=''.$upload->file_dst_name.'';
        }

        if(isset($uyelik)){
            $resim_bul= $db->query("SELECT * FROM arka_plan WHERE id = '1'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/arkaplan/uyelik/".$resim_bul['uyelik']);
            $guncelle = $db->prepare("UPDATE arka_plan SET uyelik = ? WHERE id = ?");
            $guncelle->execute([$uyelik,1]);
            $uyelik=''.$upload->file_dst_name.'';
        }

        if($guncelle)
        {
            $last_id 		= "1";
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Arka Plan Görselleri Güncellendi",
                'icon' 		=> "icon-settings",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> site arka plan görsellerini güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['arkaplan_ayarlar'] = 'yes';
            header("Location:../yonetim/arkaplan-ayarlari.html");
        }
        else
        {
            $_SESSION['arkaplan_ayarlar'] = 'no';
            header("Location:../yonetim/arkaplan-ayarlari.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/arkaplan-ayarlari.html");
    }
}

##Site Bakım Modu Ayarları ##
if(isset($_POST['site_bakim_modu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $acilis_tarih	= $_POST['acilis_tarih'];
        $acilis_zaman 	= $_POST['acilis_zaman'];
        $baslik 		= $_POST['baslik'];
        $aciklama 		= $_POST['aciklama'];

        $sorgu = $db->prepare("UPDATE bakim_modu SET
			acilis_tarih= ?,
			acilis_zaman= ?,
			baslik		= ?,
			aciklama	= ?
			WHERE id 	= ?");
        $guncelle = $sorgu->execute(array(
            $acilis_tarih,
            $acilis_zaman,
            $baslik,
            $aciklama,
            "1"
        ));
        if($guncelle)
        {
            $last_id 		= "1";
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Bakım Modu Ayarları Güncellendi",
                'icon' 		=> "icon-settings",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> bakım modu ayarları güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['site_bakim_modu'] = 'yes';
            header("Location:../yonetim/site-bakim-modu.html");
        }
        else
        {
            $_SESSION['site_bakim_modu'] = 'no';
            header("Location:../yonetim/site-bakim-modu.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/site-bakim-modu.html");
    }
}

#Dil Kaydet ##
if(isset($_POST['dil_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $sira 		= (int) $_POST['sira'];
        $adi 		= $_POST['adi'];
        $bayrak 	= $_POST['bayrak'];
        $anadil 	= (int) $_POST['anadil'];
        $durum 		= (int) $_POST['durum'];

        $anadil = $db->query("SELECT * FROM diller WHERE anadil = 1")->fetch(PDO::FETCH_ASSOC);

        $sorgu = $db->prepare("INSERT INTO diller SET
			sira 	= ?,
			adi 	= ?,
			anadil 	= ?,
			durum 	= ?,
			bayrak	= ?");
        $Ekle = $sorgu->execute(array(
            $sira,
            $adi,
            $anadil,
            $durum,
            $bayrak
        ));
        $lastid = $db->lastInsertId();
        if ($anadil == 1)
        {
            $db->query("UPDATE diller SET anadil = 0 WHERE id NOT IN($lastid)");
        }

        if($Ekle)
        {
            $last_id 		= $lastid;
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Yeni Dil Eklendi",
                'icon' 		=> "icon-globe",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: deeppink;'>".$adi."</strong> dilini sisteme ekledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['dil_ekle'] = 'yes';
            copy("../".tema."/../../language/dil_".@$anadil['id'].".php", "../".tema."/../../language/dil_".$lastid.".php");
            header("Location:../yonetim/dil-listele.html");
        }
        else
        {
            $_SESSION['dil_ekle'] = 'no';
            header("Location:../yonetim/dil-ekle.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/dil-ekle.html");
    }
}

##Dil Güncelle ##
if(isset($_POST['dil_guncelle']))
{
    TvERtXpE3w_panelislemkontrol();
    $d_id 		= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $sira 		= (int) $_POST['sira'];
        $adi 		= $_POST['adi'];
        $bayrak 	= $_POST['bayrak'];
        $anadil 	= (int) $_POST['anadil'];
        $durum 		= (int) $_POST['durum'];

        $sorgu = $db->prepare("UPDATE diller SET
			sira 	= ?,
			adi 	= ?,
			bayrak 	= ?,
			durum 	= ?
			WHERE id = ?");
        $guncelle = $sorgu->execute(array(
            $sira,
            $adi,
            $bayrak,
            $durum,
            $d_id
        ));
        if($anadil == 0)
        {
            $_SESSION['dil_guncelle'] = 'anadil';
            header("Location:../yonetim/dil-duzenle/".$d_id.".html");
        }
        else
        {
            $db->query("UPDATE diller SET anadil = 0");
            $db->query("UPDATE diller SET anadil = 1 WHERE id = {$d_id}");
        }

        $kodlar = $_POST['ekstra'];
        if (strlen($kodlar) == 0) return false;

        $yaz = fopen('../language/dil_'.$d_id.".php", "w") or die("Dosya açılamadı lütfen dizin yetkilerini kontrol edin!");
        fwrite($yaz, $kodlar);
        fclose($yaz);

        if($guncelle)
        {
            $last_id 		= $d_id;
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Dil Güncellendi",
                'icon' 		=> "icon-globe",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: deeppink;'>".$adi."</strong> dilini güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['dil_guncelle'] = 'yes';
            header("Location:../yonetim/dil-duzenle/".$d_id.".html");
        }
        else
        {
            $_SESSION['dil_guncelle'] = 'no';
            header("Location:../yonetim/dil-duzenle/".$d_id.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/dil-duzenle/".$d_id.".html");
    }
}

##Dil Sil##
if(@$_GET['dilsil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $dil_bul= $db->query("SELECT * FROM diller WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        unlink('../language/dil_'.@$dil_bul['id'].".php");
        $Sorgu = $db->prepare("DELETE FROM diller WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                $last_id 		= $dil_bul['id'];
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 	= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Dil Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: deeppink;'>".$dil_bul['adi']."</strong> dilini sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['dilsil'] = 'yes';
                header("Location:../yonetim/dil-listele.html");
            }
            else
            {
                $_SESSION['dilsil'] = 'no';
                header("Location:../yonetim/dil-listele.html");
            }
        }
        else
        {
            echo '<meta http-equiv="refresh" content="0; url=404.html">';
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/dil-listele.html");
    }
}

##Dil Toplu Sil ##
if(isset($_POST['dil_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $dil_bul= $db->query("SELECT * FROM diller WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM diller WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Dil Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: deeppink;'>".$dil_bul['adi']."</strong> dilini sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    unlink('../language/dil_'.@$dil_bul['id'].".php");
                    $_SESSION['dil_tumu'] = 'yes';
                    header("Location:../yonetim/dil-listele.html");
                }
                else
                {
                    $_SESSION['dil_tumu'] = 'no';
                    header("Location:../yonetim/dil-listele.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/dil-listele.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/dil-listele.html");
    }
}

##Dil Toplu Aktif ##
if(isset($_POST['dil_aktif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $dil_bul= $db->query("SELECT * FROM diller WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE diller SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "1",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Dil Aktif Edildi",
                        'icon' 		=> "icon-check",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: deeppink;'>".$dil_bul['adi']."</strong> dilini aktif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['dil_aktif'] = 'yes';
                    header("Location:../yonetim/dil-listele.html");
                }
                else
                {
                    $_SESSION['dil_aktif'] = 'no';
                    header("Location:../yonetim/dil-listele.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/dil-listele.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/dil-listele.html");
    }
}

##Dil Toplu Pasif ##
if(isset($_POST['dil_pasif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $dil_bul= $db->query("SELECT * FROM diller WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE diller SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "0",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Dil Pasif Edildi",
                        'icon' 		=> "icon-close",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: deeppink;'>".$dil_bul['adi']."</strong> dilini pasif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['dil_pasif'] = 'yes';
                    header("Location:../yonetim/dil-listele.html");
                }
                else
                {
                    $_SESSION['dil_pasif'] = 'no';
                    header("Location:../yonetim/dil-listele.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/dil-listele.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/dil-listele.html");
    }
}

##Müşteri Kaydet ##
if(isset($_POST['musteri_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $utipi 			= $_POST['utipi'];
        $ad 				= TvERtXpE3w_ilkbuyuk($_POST['ad']);
        $soyad 			= TvERtXpE3w_buyuk($_POST['soyad']);
        $tc 				= $_POST['tc'];
        $dtarih 			= $_POST['dtarih'];
        $firmaadi 		= $_POST['firmaadi'];
        $vergino 		= $_POST['vergino'];
        $vergidairesi 	= $_POST['vergidairesi'];
        $email 			= $_POST['email'];
        $telefon 		= $_POST['telefon'];
        $email_bildirim 	= $_POST['email_bildirim'];
        $sms_bildirim 	= $_POST['sms_bildirim'];
        $durum 			= $_POST['durum'];
        $cinsiyet 		= $_POST['cinsiyet'];

        $il 				= $_POST['il'];
        $ilce 			= $_POST['ilce'];
        $pkodu 			= $_POST['pkodu'];
        $adres 			= $_POST['adres'];

        $sifre 			= $_POST['sifre'];
        $ip				= TvERtXpE3w_ip();
        $tarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');

        $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
        $bildirimkt 		= strtotime($kayitt);
        $bildirimt 		= strtotime($btarih);

        $varmi = $db->prepare("SELECT * FROM uyeler WHERE email = ? AND tc = ?");
        $varmi->execute(array($email,$tc));
        if(!$varmi->rowCount())
        {
            $sorgu = $db->prepare("INSERT INTO uyeler SET
					utipi 			= ?,
					ad 				= ?,
					soyad 			= ?,
					tc 				= ?,
					dtarih 			= ?,
					firmaadi 		= ?,
					vergino 		= ?,
					vergidairesi 	= ?,
					email 			= ?,				
					telefon 		= ?,
					email_bildirim 	= ?,
					sms_bildirim 	= ?,
					durum 			= ?,
					cinsiyet 		= ?,
					sifre 			= ?,
					ip 				= ?,
					tarih 			= ?");
            $Ekle = $sorgu->execute(array(
                $utipi,
                $ad,
                $soyad,
                $tc,
                $dtarih,
                $firmaadi,
                $vergino,
                $vergidairesi,
                $email,
                $telefon,
                $email_bildirim,
                $sms_bildirim,
                $durum,
                $cinsiyet,
                $sifre,
                $ip,
                $bildirimt
            ));
            if($Ekle)
            {
                $last_id  = $db->lastInsertId();

                if($il != "" && $ilce != "" && $adres != "")
                {
                    $ADRESSorgu = $db->prepare("INSERT INTO adresler SET
						uyeid		= :uyeid,
						il			= :il,
						ilce		= :ilce,
						pkodu		= :pkodu,
						adres		= :adres,
						varsayilan	= :varsayilan,
						tarih 		= :tarih");
                    $ADRESEkle = $ADRESSorgu->execute(array(
                        'uyeid' 		=> $last_id,
                        'il' 		=> $il,
                        'ilce' 		=> $ilce,
                        'pkodu' 		=> $pkodu,
                        'adres' 		=> $adres,
                        'varsayilan'=> "1",
                        'tarih'		=> $tarih
                    ));
                }

                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Yeni Müşteri Eklendi",
                    'icon' 		=> "icon-people",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$ad." ".$soyad."</strong> adında müşteri ekledi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));

                $RSorgu = $db->prepare("INSERT INTO rehber SET
					adi			= :adi,
					email		= :email,
					telefon		= :telefon,
					durum		= :durum,
					tarih 		= :tarih");
                $REkle = $RSorgu->execute(array(
                    'adi' 		=> $ad." ".$soyad,
                    'email' 		=> $email,
                    'telefon' 	=> $telefon,
                    'durum' 		=> "1",
                    'tarih'		=> $tarih
                ));
                $_SESSION['musteri_ekle'] = 'yes';
                header("Location:../yonetim/tum-musteriler.html");
            }
            else
            {
                $_SESSION['musteri_ekle'] = 'no';
                header("Location:../yonetim/musteri-ekle.html");
            }
        }
        else
        {
            $_SESSION['musteri_ekle'] = 'var';
            header("Location:../yonetim/musteri-ekle.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/musteri-ekle.html");
    }
}

##Müşteri Güncelle ##
if(isset($_POST['musteri_guncelle']))
{
    TvERtXpE3w_panelislemkontrol();
    $d_id 	= $_POST['d_id'];
    if($_SESSION['rutbe'] == 0)
    {
        $utipi 				= $_POST['utipi'];
        $ad 					= TvERtXpE3w_ilkbuyuk($_POST['ad']);
        $soyad 				= TvERtXpE3w_buyuk($_POST['soyad']);
        $tc 					= $_POST['tc'];
        $dtarih 				= $_POST['dtarih'];
        if($utipi == 0)
        {
            $firmaadi		= "";
            $vergino		= "";
            $vergidairesi	= "";
        }
        else
        {
            $firmaadi		= $_POST['firmaadi'];
            $vergino		= $_POST['vergino'];
            $vergidairesi	= $_POST['vergidairesi'];
        }
        $email 				= $_POST['email'];
        $bayi               = $_POST['bayi'];
        $telefon 			= $_POST['telefon'];
        $email_bildirim 		= $_POST['email_bildirim'];
        $sms_bildirim 		= $_POST['sms_bildirim'];
        $durum 				= $_POST['durum'];
        $cinsiyet 			= $_POST['cinsiyet'];
        $nereden_duydunuz	= $_POST['nereden_duydunuz'];
        $notlar				= $_POST['notlar'];
        $sifre 				= $_POST['sifre'];
        $ip					= TvERtXpE3w_ip();
        $tarih				= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $btarih				= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $kayitt				= TvERtXpE3w_tr_tarih('Y-m-d');
        $bildirimkt 			= strtotime($kayitt);
        $bildirimt 			= strtotime($btarih);

        if($sifre != ""){
            $guncelle = $db->prepare("UPDATE uyeler SET sifre = ? WHERE id = ?");
            $guncelle->execute([$sifre,$d_id]);
        }

        $sorgu = $db->prepare("UPDATE uyeler SET
			utipi 			= ?,
			bayi            = ?,
			ad 				= ?,
			soyad 			= ?,
			tc 				= ?,
			dtarih 			= ?,
			firmaadi 		= ?,
			vergino 		= ?,
			vergidairesi 	= ?,
			email 			= ?,				
			telefon 		= ?,
			email_bildirim 	= ?,
			sms_bildirim 	= ?,
			durum 			= ?,
			cinsiyet 		= ?,
			nereden_duydunuz= ?,
			notlar 			= ?
			WHERE id 		= ?");
        $guncelle = $sorgu->execute(array(
            $utipi,
            $bayi,
            $ad,
            $soyad,
            $tc,
            $dtarih,
            $firmaadi,
            $vergino,
            $vergidairesi,
            $email,
            $telefon,
            $email_bildirim,
            $sms_bildirim,
            $durum,
            $cinsiyet,
            $nereden_duydunuz,
            $notlar,
            $d_id
        ));
        if($guncelle)
        {
            $last_id 		= $d_id;
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Müşteri Güncellendi",
                'icon' 		=> "icon-people",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$ad." ".$soyad."</strong> isimli müşteriyi güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['musteri_guncelle'] = 'yes';
            header("Location:../yonetim/musteri-duzenle/".$d_id.".html");
        }
        else
        {
            $_SESSION['musteri_guncelle'] = 'no';
            header("Location:../yonetim/musteri-duzenle/".$d_id.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/musteri-duzenle/".$d_id.".html");
    }
}

##Müşteri Adres Ekle ##
if(isset($_POST['adres_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid 			= $_POST['uyeid'];
    $ad				= $_POST['ad'];
    $soyad			= $_POST['soyad'];
    $il				= $_POST['il'];
    $ilce			= $_POST['ilce'];
    $pkodu			= $_POST['pkodu'];
    $adres			= $_POST['adres'];
    $varsayilan		= $_POST['varsayilan'];
    $tarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
    $bildirimkt 		= strtotime($kayitt);
    $bildirimt 		= strtotime($tarih);

    if($_SESSION['rutbe'] == 0)
    {
        $sorgu = $db->prepare("INSERT INTO adresler SET
			uyeid		= :uyeid,
			il 			= :il,
			ilce 		= :ilce,
			pkodu 		= :pkodu,
			adres 		= :adres,
			varsayilan 	= :varsayilan,
			tarih 		= :tarih");
        $Ekle = $sorgu->execute(array(
            "uyeid" 		=> $uyeid,
            "il"		 => $il,
            "ilce" 		=> $ilce,
            "pkodu" 		=> $pkodu,
            "adres" 		=> $adres,
            "varsayilan"=> $varsayilan,
            "tarih" 		=> $tarih
        ));
        if($Ekle)
        {
            $id = $db->lastInsertId();
            if($varsayilan == 1)
            {
                $sorgu = $db->prepare("UPDATE adresler SET
					varsayilan	= ?
					WHERE uyeid = ?");
                $adresguncelle = $sorgu->execute(array(
                    "",
                    $uyeid
                ));

                $sorgu = $db->prepare("UPDATE adresler SET
					varsayilan	= ?
					WHERE id = ?");
                $adresguncelle = $sorgu->execute(array(
                    "1",
                    $id
                ));
            }
            $_SESSION['adres_ekle'] = 'yes';
            header("Location:../yonetim/musteri-duzenle/".$uyeid.".html");
        }
        else
        {
            $_SESSION['adres_ekle'] = 'no';
            header("Location:../yonetim/musteri-duzenle/".$uyeid.".html");
        }

    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/musteri-duzenle/".$uyeid.".html");
    }
}

##Müşteri Adres Sil##
if(@$_GET['adressil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $TSorgu = $db->prepare("DELETE FROM adresler WHERE id = :id");
        $TSil	= $TSorgu->execute(array('id' => $_GET['id']));
        if($TSil)
        {
            $_SESSION['adressil'] = 'yes';
            header("Location:../yonetim/musteri-duzenle/".$_GET['uyeid'].".html");
        }
        else
        {
            $_SESSION['adressil'] = 'no';
            header("Location:../yonetim/musteri-duzenle/".$_GET['uyeid'].".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/musteri-duzenle/".$_GET['uyeid'].".html");
    }
}

##Müşteri Adres Güncelle ##
if(isset($_POST['adres_guncelle']))
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid 			= $_POST['uyeid'];
    $id				= $_POST['id'];
    $ad				= $_POST['ad'];
    $soyad			= $_POST['soyad'];
    $il				= $_POST['il'];
    $ilce			= $_POST['ilce'];
    $pkodu			= $_POST['pkodu'];
    $adres			= $_POST['adres'];
    $varsayilan		= $_POST['varsayilan'];
    $tarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
    $bildirimkt 		= strtotime($kayitt);
    $bildirimt 		= strtotime($tarih);

    if($_SESSION['rutbe'] == 0)
    {
        $Sorgu = $db->prepare("UPDATE adresler SET
			il			= ?,
			ilce		= ?,
			pkodu		= ?,
			adres		= ?,
			varsayilan	= ?,
			tarih		= ?
			WHERE id 	= ?");
        $guncelle = $Sorgu->execute(array(
            $il,
            $ilce,
            $pkodu,
            $adres,
            $varsayilan,
            $tarih,
            $id
        ));
        if($guncelle)
        {
            if($varsayilan == 1)
            {
                $sorgu = $db->prepare("UPDATE adresler SET
					varsayilan	= ?
					WHERE uyeid = ?");
                $adresguncelle = $sorgu->execute(array(
                    "",
                    $uyeid
                ));

                $sorgu = $db->prepare("UPDATE adresler SET
					varsayilan	= ?
					WHERE id = ?");
                $adresguncelle = $sorgu->execute(array(
                    "1",
                    $id
                ));
            }
            $_SESSION['adres_guncelle'] = 'yes';
            header("Location:../yonetim/musteri-duzenle/".$uyeid.".html");
        }
        else
        {
            $_SESSION['adres_guncelle'] = 'no';
            header("Location:../yonetim/musteri-duzenle/".$uyeid.".html");
        }

    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/musteri-duzenle/".$uyeid.".html");
    }
}

##Müşteri Sil##
if(@$_GET['musterisil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    $url 	= $_GET['url'];
    if($_SESSION['rutbe'] == 0)
    {
        $id 		= $_GET['id'];
        $musteri_bul= $db->query("SELECT * FROM uyeler WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        $Sorgu = $db->prepare("DELETE FROM uyeler WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                $REHBERSorgu = $db->prepare("SELECT * FROM adresler WHERE uyeid = ?");
                $REHBERSorgu->execute(array($_GET['id']));
                $REHBERIslem = $REHBERSorgu->fetchALL(PDO::FETCH_ASSOC);
                foreach ( $REHBERIslem as $REHBERSonuc )
                {
                    $REHSorgu = $db->prepare("DELETE FROM adresler WHERE id = :id");
                    $REHSorgu->execute(array('id' => $REHBERSonuc['id']));
                }
                $last_id 		= $musteri_bul["id"];
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 		= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Müşteri Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$musteri_bul['ad']." ".$musteri_bul['soyad']."</strong> isimli müşteriyi sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['musterisil'] = 'yes';
                header("Location:../yonetim/".$url."");
            }
            else
            {
                $_SESSION['musterisil'] = 'no';
                header("Location:../yonetim/".$url."");
            }
        }
        else
        {
            echo '<meta http-equiv="refresh" content="0; url=404.html">';
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/".$url."");
    }
}

##Müşteri Toplu Sil ##
if(isset($_POST['musteri_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $url = $_POST['url'];
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $musteri_bul= $db->query("SELECT * FROM uyeler WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM uyeler WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    $REHBERSorgu = $db->prepare("SELECT * FROM adresler WHERE uyeid = ?");
                    $REHBERSorgu->execute(array($i));
                    $REHBERIslem = $REHBERSorgu->fetchALL(PDO::FETCH_ASSOC);
                    foreach ( $REHBERIslem as $REHBERSonuc )
                    {
                        $REHSorgu = $db->prepare("DELETE FROM adresler WHERE id = :id");
                        $REHSorgu->execute(array('id' => $REHBERSonuc['id']));
                    }
                    $last_id 		= $musteri_bul["id"];
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 		= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Müşteri Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$musteri_bul['ad']." ".$musteri_bul['soyad']."</strong> isimli müşteriyi sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['musteri_tumu'] = 'yes';
                    header("Location:".$url."");
                }
                else
                {
                    $_SESSION['musteri_tumu'] = 'no';
                    header("Location:".$url."");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:".$url."");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:".$url."");
    }
}

##Müşteri Toplu Aktif ##
if(isset($_POST['musteri_aktif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $url = $_POST['url'];
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $uye_bul= $db->query("SELECT * FROM uyeler WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu 	= $db->prepare("UPDATE uyeler SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "1",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Müşteri aktif edildi",
                        'icon' 		=> "icon-check",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$uye_bul['ad']." ".$uye_bul['soyad']."</strong> isimli müşteriyi aktif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['musteri_aktif'] = 'yes';
                    header("Location:".$url."");
                }
                else
                {
                    $_SESSION['musteri_aktif'] = 'no';
                    header("Location:".$url."");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:".$url."");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:".$url."");
    }
}

##Müşteri Toplu Pasif ##
if(isset($_POST['musteri_pasif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $url = $_POST['url'];
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $uye_bul= $db->query("SELECT * FROM uyeler WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE uyeler SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "0",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Müşteri Pasif Edildi",
                        'icon' 		=> "icon-close",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$uye_bul['ad']." ".$uye_bul['soyad']."</strong> isimli müşteriyi pasif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['musteri_pasif'] = 'yes';
                    header("Location:".$url."");
                }
                else
                {
                    $_SESSION['musteri_pasif'] = 'no';
                    header("Location:".$url."");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:".$url."");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:".$url."");
    }
}

##Müşteri Toplu Engelle ##
if(isset($_POST['musteri_engel']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $url = $_POST['url'];
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $uye_bul= $db->query("SELECT * FROM uyeler WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE uyeler SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "2",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Müşteri Engellendi",
                        'icon' 		=> "icon-control-pause",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$uye_bul['ad']." ".$uye_bul['soyad']."</strong> isimli müşteriyi engelledi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['musteri_engel'] = 'yes';
                    header("Location:".$url."");
                }
                else
                {
                    $_SESSION['musteri_engel'] = 'no';
                    header("Location:".$url."");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:".$url."");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:".$url."");
    }
}

##Destek Yanıtla ##
if(isset($_POST['destek_yanit_btn']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $destekid 	= (int) $_POST['destekid'];
        $destekcek 	= $db->query("SELECT * FROM destek WHERE id='{$destekid}'")->fetch(PDO::FETCH_ASSOC);
        $baslik 	= $destekcek["baslik"];
        $departman 	= $destekcek["departman"];
        $oncelik 	= $destekcek["oncelik"];
        $mesaj 		= $_POST['mesaj'];
        $tarihfrm	= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $tarihbgn	= TvERtXpE3w_tr_tarih('Y-m-d');
        $son_cevap 	= strtotime($tarihfrm);
        $son_tarih 	= strtotime($tarihfrm);
        $tarih 		= strtotime($tarihfrm);
        $yztarih 	= strtotime($tarihbgn);
        $ip			= TvERtXpE3w_ip();

        $upload = new upload($_FILES['dosya']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/destek");
            if ($upload->processed)
            {
                $Dosya=''.$upload->file_dst_name.'';
            }
        }
        $gitti=$Dosya=''.$upload->file_dst_name.'';

        $uyebul = $db->query("SELECT * FROM uyeler WHERE id = '{$destekcek['uyeid']}'")->fetch(PDO::FETCH_ASSOC);
        $sablon = $db->query("SELECT * FROM bildirim_sablonu WHERE id = '6'")->fetch(PDO::FETCH_ASSOC);
        $gelendegisken 	= explode(",", $sablon['degiskenler']);
        $yenitarih		= TvERtXpE3w_tarih($tarihfrm);
        $panel_url		= url."destek/".$destekid.".html";
        $gidendegisken	= [$uyebul["ad"]." ".$uyebul["soyad"],$baslik,$mesaj,$yenitarih,$panel_url,$logo,$domain_bilgi];

        if(empty($destekid))
        {
            $_SESSION['destek_yanit_btn'] = 'bos';
            header("Location:../yonetim/destek/".$destekid.".html");
        }
        else
        {

            $sorgu = $db->prepare("INSERT INTO destek SET
				uyeid		= :uyeid,
				baslik		= :baslik,
				departman 	= :departman,
				oncelik 	= :oncelik,
				son_cevap	= :son_cevap,
				son_tarih	= :son_tarih,
				mesaj		= :mesaj,
				durum		= :durum,
				ustid		= :ustid,
				dosya		= :dosya,
				yztarih		= :yztarih,
				ip			= :ip,
				tarih 		= :tarih");
            $Ekle = $sorgu->execute(array(
                'uyeid' 	=> "0",
                'baslik' 	=> $baslik,
                'departman' => $departman,
                'oncelik' 	=> $oncelik,
                'son_cevap' => $son_cevap,
                'son_tarih' => $son_tarih,
                'mesaj' 	=> $mesaj,
                'durum' 	=> "1",
                'dosya' 	=> $Dosya,
                'yztarih' 	=> $yztarih,
                'ip'		=> $ip,
                'ustid'		=> $destekid,
                'tarih'		=> $tarih
            ));
            if($Ekle)
            {
                $last_id 		= $destekid;
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 	= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Destek Cevaplandı",
                    'icon' 		=> "icon-support",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: tomato;'>".$baslik ."</strong> başlıklı destek talebine cevap yazdı.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));


                if($sablon["sbildirim"] == "1")
                {
                    smsgonder($gelendegisken,$gidendegisken,$sablon['icerik3'],$uyebul["telefon"],$sablon['icerik3']);
                }

                if($sablon["ubildirim"] == "1")
                {
                    $uyekonu 	= $sablon['konu'];//TvERtXpE3w_turkce($sablon['konu']);
                    $uyesablon 	= $sablon['icerik'];
                    mailgonder($gelendegisken,$gidendegisken,$uyesablon,$uyebul["email"]," ".$uyekonu."",$uyesablon);
                }
                if($sablon["abildirim"] == "1")
                {
                    $adminkonu 	= $sablon['konu2'];//TvERtXpE3w_turkce($sablon['konu2']);
                    $adminsablon= $sablon['icerik2'];
                    mailgonder($gelendegisken,$gidendegisken,$adminsablon,m_kime," ".$adminkonu."",$adminsablon);
                }
                $db->query("UPDATE destek SET son_cevap = '{$tarih}', yztarih = '{$yztarih}', durum = '1', son_tarih = '{$tarih}' WHERE id = {$destekid}");
                $_SESSION['destek_yanit_btn'] = 'yes';
                header("Location:../yonetim/destek/".$destekid.".html");
            }
            else
            {
                $_SESSION['destek_yanit_btn'] = 'no';
                header("Location:../yonetim/destek/".$destekid.".html");
            }
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/destek/".$destekid.".html");
    }
}

##Destek Çözümlendi ##
if(isset($_POST['destek_yanit_cozumlendi']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $destekid 	= (int) $_POST['destekid'];
        $destekcek 	= $db->query("SELECT * FROM destek WHERE id='{$destekid}'")->fetch(PDO::FETCH_ASSOC);
        $baslik 	= $destekcek["baslik"];
        $tarihfrm	= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $tarihbgn	= TvERtXpE3w_tr_tarih('Y-m-d');
        $son_cevap 	= strtotime($tarihfrm);
        $son_tarih 	= strtotime($tarihfrm);
        $tarih 		= strtotime($tarihfrm);
        $yztarih 	= strtotime($tarihbgn);
        $ip			= TvERtXpE3w_ip();

        if(empty($destekid))
        {
            $_SESSION['destek_yanit_cozumlendi'] = 'bos';
            header("Location:../yonetim/destek/".$destekid.".html");
        }
        else
        {
            $guncellendi = $db->query("UPDATE destek SET son_cevap = '{$tarih}', yztarih = '{$yztarih}', durum = '2', son_tarih = '{$tarih}' WHERE id = {$destekid}");
            if($guncellendi)
            {
                $last_id 		= $destekid;
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 	= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Destek Çözümlendi",
                    'icon' 		=> "icon-support",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: tomato;'>".$baslik ."</strong> başlıklı destek talebini çözümlendi olarak ayarladı.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['destek_yanit_cozumlendi'] = 'yes';
                header("Location:../yonetim/destek/".$destekid.".html");
            }
            else
            {
                $_SESSION['destek_yanit_cozumlendi'] = 'no';
                header("Location:../yonetim/destek/".$destekid.".html");
            }
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/destek/".$destekid.".html");
    }
}

##Destek Sil##
if(@$_GET['desteksil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $resim_bul= $db->query("SELECT * FROM destek WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        $baslik 	= $resim_bul["baslik"];
        unlink("../".tema."/uploads/destek/".$resim_bul['dosya']);
        $Sorgu = $db->prepare("DELETE FROM destek WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                $TopluSorguAlt = $db->prepare("SELECT * FROM destek WHERE ustid = ?");
                $TopluSorguAlt->execute(array($id));
                $TopluislemAlt = $TopluSorguAlt->fetchALL(PDO::FETCH_ASSOC);
                foreach ( $TopluislemAlt as $TopluSonucAlt )
                {
                    $TSorgu = $db->prepare("DELETE FROM destek WHERE id = :id");
                    $TSorgu->execute(array('id' => $TopluSonucAlt['id']));
                    unlink("../".tema."/uploads/destek/".$TopluSonucAlt['dosya']);
                }
                $_SESSION['desteksil'] = 'yes';
                header("Location:../yonetim/destek-merkezi.html");
            }
            else
            {
                $_SESSION['desteksil'] = 'no';
                header("Location:../yonetim/destek-merkezi.html");
            }

            $last_id 		= $id;
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Destek Silindi",
                'icon' 		=> "icon-trash",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: tomato;'>".$baslik ."</strong> başlıklı destek talebini sildi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
        }
        else
        {
            echo '<meta http-equiv="refresh" content="0; url=404.html">';
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/destek-merkezi.html");
    }
}

##Destek Toplu Çözümlendi ##
if(isset($_POST['destek_cozumlendi']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            $tarihfrm	= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $tarihbgn	= TvERtXpE3w_tr_tarih('Y-m-d');
            $son_cevap 	= strtotime($tarihfrm);
            $son_tarih 	= strtotime($tarihfrm);
            $yztarih 	= strtotime($tarihbgn);
            foreach($_POST['id'] as $i)
            {
                $destekcek 	= $db->query("SELECT * FROM destek WHERE id='{$i}'")->fetch(PDO::FETCH_ASSOC);
                $baslik 	= $destekcek["baslik"];
                $sorgu = $db->prepare("UPDATE destek SET
					durum 		= ?,
					son_cevap 	= ?,
					son_tarih 	= ?,
					yztarih 	= ?
					WHERE id 	= ?");
                $guncelle = $sorgu->execute(array(
                    "2",
                    $son_cevap,
                    $son_tarih,
                    $yztarih,
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Destek Çözümlendi",
                        'icon' 		=> "icon-support",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: tomato;'>".$baslik ."</strong> başlıklı destek talebini çözümlendi olarak ayarladı.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['destek_cozumlendi'] = 'yes';
                    header("Location:../yonetim/destek-merkezi.html");
                }
                else
                {
                    $_SESSION['destek_cozumlendi'] = 'no';
                    header("Location:../yonetim/destek-merkezi.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/destek-merkezi.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/destek-merkezi.html");
    }
}

##Destek Toplu Sil ##
if(isset($_POST['destek_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $url = $_POST['url'];
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $resim_bul	= $db->query("SELECT * FROM destek WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $baslik 	= $resim_bul["baslik"];
                $TopluSorgu = $db->prepare("DELETE FROM destek WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    unlink("../".tema."/uploads/profil/".$resim_bul['profil']);
                    $TopluSorguAlt = $db->prepare("SELECT * FROM destek WHERE ustid = ?");
                    $TopluSorguAlt->execute(array($i));
                    $TopluislemAlt = $TopluSorguAlt->fetchALL(PDO::FETCH_ASSOC);
                    foreach ( $TopluislemAlt as $TopluSonucAlt )
                    {
                        $TSorgu = $db->prepare("DELETE FROM destek WHERE id = :id");
                        $TSorgu->execute(array('id' => $TopluSonucAlt['id']));
                        unlink("../".tema."/uploads/destek/".$TopluSonucAlt['dosya']);
                    }
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Destek Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: tomato;'>".$baslik ."</strong> başlıklı destek talebini sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['destek_tumu'] = 'yes';
                    header("Location:../yonetim/destek-merkezi.html");
                }
                else
                {
                    $_SESSION['destek_tumu'] = 'no';
                    header("Location:../yonetim/destek-merkezi.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/destek-merkezi.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/destek-merkezi.html");
    }
}

##Çoklu Mesaj Sil ##
if(isset($_POST['mesaj_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $mesaj_bul	= $db->query("SELECT * FROM mesajlar WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM mesajlar WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Mesaj Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: thistle;'>".$mesaj_bul['konu']."</strong> konulu mesajı sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['mesaj_tumu'] = 'yes';
                    header("Location:../yonetim/mesajlar.html");
                }
                else
                {
                    $_SESSION['mesaj_tumu'] = 'no';
                    header("Location:../yonetim/mesajlar.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/mesajlar.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/mesajlar.html");
    }
}

##Çoklu Mesaj Okundu ##
if(isset($_POST['mesaj_okundu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $mesaj_bul	= $db->query("SELECT * FROM mesajlar WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE mesajlar SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "1",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Mesaj Okundu",
                        'icon' 		=> "icon-check",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: thistle;'>".$mesaj_bul['konu']."</strong> konulu mesajı okundu olarak ayarladı.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['mesaj_okundu'] = 'yes';
                    header("Location:../yonetim/mesajlar.html");
                }
                else
                {
                    $_SESSION['mesaj_okundu'] = 'no';
                    header("Location:../yonetim/mesajlar.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/mesajlar.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/mesajlar.html");
    }
}

##Çoklu Mesaj Okunmadı ##
if(isset($_POST['mesaj_okunmadi']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $mesaj_bul	= $db->query("SELECT * FROM mesajlar WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE mesajlar SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "0",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Mesaj Okunmadı",
                        'icon' 		=> "icon-close",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: thistle;'>".$mesaj_bul['konu']."</strong> konulu mesajı okunmadı olarak ayarladı.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['mesaj_okunmadi'] = 'yes';
                    header("Location:../yonetim/mesajlar.html");
                }
                else
                {
                    $_SESSION['mesaj_okunmadi'] = 'no';
                    header("Location:../yonetim/mesajlar.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/mesajlar.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/mesajlar.html");
    }
}

##Mesaj Sil##
if(@$_GET['mesajsil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $mesaj_bul	= $db->query("SELECT * FROM mesajlar WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        $Sorgu = $db->prepare("DELETE FROM mesajlar WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                $last_id 		= $i;
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 	= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Mesaj Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: thistle;'>".$mesaj_bul['konu']."</strong> konulu mesajı sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['mesajsil'] = 'yes';
                header("Location:../yonetim/mesajlar.html");
            }
            else
            {
                $_SESSION['mesajsil'] = 'no';
                header("Location:../yonetim/mesajlar.html");
            }
        }
        else
        {
            echo '<meta http-equiv="refresh" content="0; url=404.html">';
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/mesajlar.html");
    }
}

##Detay Mesaj Okundu##
if(@$_GET['mesajokundu'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_POST['id'];
        $guncellendi = $db->query("UPDATE mesajlar SET durum = '1' WHERE id = {$id}");
    }
}

##Yorum Sil##
if(@$_GET['yorumsil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $yorum_bul	= $db->query("SELECT * FROM yorumlar WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        $Sorgu = $db->prepare("DELETE FROM yorumlar WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                $last_id 		= $id;
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 	= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Yorum Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: steelblue;'>".$yorum_bul['adi']."</strong> kişisinin yorumunu sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['yorumsil'] = 'yes';
                header("Location:../yonetim/yorumlar.html");
            }
            else
            {
                $_SESSION['yorumsil'] = 'no';
                header("Location:../yonetim/yorumlar.html");
            }
        }
        else
        {
            echo '<meta http-equiv="refresh" content="0; url=404.html">';
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/yorumlar.html");
    }
}

##Çoklu Yorum Sil ##
if(isset($_POST['yorum_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $yorum_bul	= $db->query("SELECT * FROM yorumlar WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM yorumlar WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Yorum Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: steelblue;'>".$yorum_bul['adi']."</strong> kişisinin yorumunu sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['yorum_tumu'] = 'yes';
                    header("Location:../yonetim/yorumlar.html");
                }
                else
                {
                    $_SESSION['yorum_tumu'] = 'no';
                    header("Location:../yonetim/yorumlar.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/yorumlar.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/yorumlar.html");
    }
}

##Çoklu Yorum Onayla ##
if(isset($_POST['yorum_onayli']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $yorum_bul	= $db->query("SELECT * FROM yorumlar WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE yorumlar SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "1",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Yorum Onaylandı",
                        'icon' 		=> "ti-control-play",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: steelblue;'>".$yorum_bul['adi']."</strong> kişisinin yorumunu onayladı.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['yorum_onayli'] = 'yes';
                    header("Location:../yonetim/yorumlar.html");
                }
                else
                {
                    $_SESSION['yorum_onayli'] = 'no';
                    header("Location:../yonetim/yorumlar.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/yorumlar.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/yorumlar.html");
    }
}

##Çoklu Yorum Onayı Kaldır ##
if(isset($_POST['yorum_onaysiz']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $yorum_bul	= $db->query("SELECT * FROM yorumlar WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE yorumlar SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "0",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Yorum Onayı Kaldırıldı",
                        'icon' 		=> "ti-control-pause",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: steelblue;'>".$yorum_bul['adi']."</strong> kişisinin yorum onayını kaldırdı.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['yorum_onaysiz'] = 'yes';
                    header("Location:../yonetim/yorumlar.html");
                }
                else
                {
                    $_SESSION['yorum_onaysiz'] = 'no';
                    header("Location:../yonetim/yorumlar.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/yorumlar.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/yorumlar.html");
    }
}

##Yorum Onayını Kaldır ##
if(@$_GET['yorumonaykaldir'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        @$id 		= $_GET['id'];
        $yorumbul	= $db->query("SELECT * FROM yorumlar WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        $sorgu = $db->prepare("UPDATE yorumlar SET
				durum	= ?
				WHERE id = ?");
        $guncelle = $sorgu->execute(array(
            "0",
            $id
        ));
        if($guncelle)
        {
            $last_id 		= $id;
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Yorum Onayı Kaldırıldı",
                'icon' 		=> "ti-control-pause",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: steelblue;'>".$yorumbul['adi']."</strong> kişisinin yorum onayını kaldırdı.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['yorumonaykaldir'] = 'yes';
            header("Location:../yonetim/yorumlar.html");
        }
        else
        {
            $_SESSION['yorumonaykaldir'] = 'no';
            header("Location:../yonetim/yorumlar.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/yorumlar.html");
    }
}

##Yorum Onayla ##
if(@$_GET['yorumonayla'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        @$id 		= $_GET['id'];
        $yorumbul	= $db->query("SELECT * FROM yorumlar WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        $sorgu = $db->prepare("UPDATE yorumlar SET
				durum	= ?
				WHERE id = ?");
        $guncelle = $sorgu->execute(array(
            "1",
            $id
        ));
        if($guncelle)
        {
            $last_id 		= $id;
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Yorum Onaylandı",
                'icon' 		=> "ti-control-play",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: steelblue;'>".$yorumbul['adi']."</strong> kişisinin yorumunu onayladı.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['yorumonayla'] = 'yes';
            header("Location:../yonetim/yorumlar.html");
        }
        else
        {
            $_SESSION['yorumonayla'] = 'no';
            header("Location:../yonetim/yorumlar.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/yorumlar.html");
    }
}

## Modül Güncelle ##
if(isset($_POST['modul_guncelle']))
{
    TvERtXpE3w_panelislemkontrol();
    $url		= $_POST['url'];
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['alan1']){$alan1 = 1;}else{$alan1 = 0;}
        if($_POST['alan2']){$alan2 = 1;}else{$alan2 = 0;}
        if($_POST['alan3']){$alan3 = 1;}else{$alan3 = 0;}
        if($_POST['alan4']){$alan4 = 1;}else{$alan4 = 0;}
        if($_POST['alan5']){$alan5 = 1;}else{$alan5 = 0;}
        if($_POST['alan6']){$alan6 = 1;}else{$alan6 = 0;}
        if($_POST['alan7']){$alan7 = 1;}else{$alan7 = 0;}
        if($_POST['alan8']){$alan8 = 1;}else{$alan8 = 0;}
        if($_POST['alan9']){$alan9 = 1;}else{$alan9 = 0;}
        if($_POST['alan10']){$alan10 = 1;}else{$alan10 = 0;}
        if($_POST['alan11']){$alan11 = 1;}else{$alan11 = 0;}

        $sorgu = $db->prepare("UPDATE moduller SET
			alan1	= ?,
			alan2	= ?,
			alan3	= ?,
			alan4	= ?,
			alan5	= ?,
			alan6	= ?,
			alan7	= ?,
			alan8	= ?,
			alan9	= ?,
			alan10	= ?,
			alan11	= ?
			WHERE id= ?");
        $guncelle = $sorgu->execute(array(
            $alan1,
            $alan2,
            $alan3,
            $alan4,
            $alan5,
            $alan6,
            $alan7,
            $alan8,
            $alan9,
            $alan10,
            $alan11,
            "1"
        ));
        if($guncelle)
        {
            $last_id 		= "1";
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Modül Güncellendi",
                'icon' 		=> "icon-settings",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> modül ayarlarını güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['modul_guncelle'] = 'yes';
            header("Location:".$url."");
        }
        else
        {
            $_SESSION['modul_guncelle'] = 'no';
            header("Location:".$url."");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:".$url."");
    }
}

##Şablon Güncelle ##
if(isset($_POST['sablon_guncelle']))
{
    TvERtXpE3w_panelislemkontrol();
    $d_id 	= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $ubildirim 	= $_POST['ubildirim'];
        $sbildirim 	= $_POST['sbildirim'];
        $abildirim 	= $_POST['abildirim'];
        $ysbildirim = $_POST['ysbildirim'];
        $konu 		= $_POST['konu'];
        $konu2 		= $_POST['konu2'];
        $icerik		= $_POST['icerik'];
        $icerik2	= $_POST['icerik2'];
        $icerik3	= $_POST['icerik3'];
        $icerik4	= $_POST['icerik4'];

        $sorgu = $db->prepare("UPDATE bildirim_sablonu SET
			ubildirim 	= ?,
			sbildirim 	= ?,
			abildirim 	= ?,
			ysbildirim	= ?,
			konu		= ?,
			konu2		= ?,
			icerik 		= ?,
			icerik2 	= ?,
			icerik3 	= ?,
			icerik4 	= ?
			WHERE id 	= ?");
        $guncelle = $sorgu->execute(array(
            $ubildirim,
            $sbildirim,
            $abildirim,
            $ysbildirim,
            $konu,
            $konu2,
            $icerik,
            $icerik2,
            $icerik3,
            $icerik4,
            $d_id
        ));
        if($guncelle)
        {
            $last_id 		= "1";
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Bildirim Şablonu Güncellendi",
                'icon' 		=> "icon-notebook",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> bildirim şablonlarını güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['sablon_guncelle'] = 'yes';
            header("Location:../yonetim/sablon-duzenle/".$d_id.".html");
        }
        else
        {
            $_SESSION['sablon_guncelle'] = 'no';
            header("Location:../yonetim/sablon-duzenle/".$d_id.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/sablon-duzenle/".$d_id.".html");
    }
}

##Not Ekle ##
if(isset($_POST['not_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $title 	= $_POST['title'];
        $start  = $_POST["start"];
        $end 	= $_POST['end'];
        $color 	= $_POST['color'];
        $ekleyen= $_POST['ekleyen'];

        if(empty($title) || empty($start) || empty($end) || empty($color))
        {
            $_SESSION['not_ekle'] = 'bos';
            header("Location:../yonetim/not-defteri.html");
        }
        else
        {
            $sorgu = $db->prepare("INSERT INTO not_defteri SET
				baslik		= :baslik,
				baslangic 	= :baslangic,
				bitis 		= :bitis,
				ekleyen 	= :ekleyen,
				renk 		= :renk");
            $Ekle = $sorgu->execute(array(
                "baslik" 	=> $title,
                "baslangic" => $start,
                "bitis" 	=> $end,
                "ekleyen" 	=> $ekleyen,
                "renk" 		=> $color
            ));
            if($Ekle)
            {
                $last_id 		= $db->lastInsertId();
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 	= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Not Eklendi",
                    'icon' 		=> "icon-calendar",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: steelblue;'>".$title."</strong> başlıklı not ekledi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['not_ekle'] = 'yes';
                header("Location:../yonetim/not-defteri.html");
            }
            else
            {
                $_SESSION['not_ekle'] = 'no';
                header("Location:../yonetim/not-defteri.html");
            }
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/not-defteri.html");
    }
}

##Not Düzenle ##
if(isset($_POST['not_guncelle']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if (isset($_POST['delete']) && isset($_POST['id']))
        {
            $id 	= $_POST['id'];
            $notbul	= $db->query("SELECT * FROM not_defteri WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
            $title 	= $notbul['baslik'];
            $Sorgu 	= $db->prepare("DELETE FROM not_defteri WHERE id = :id");
            $Sil	= $Sorgu->execute(array('id' => $id));
            if($Sil)
            {
                $last_id 		= $id;
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 	= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Notu Sil",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: steelblue;'>".$title."</strong> başlıklı notu sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['not_guncelle'] = 'sil_yes';
                header("Location:../yonetim/not-defteri.html");
            }
            else
            {
                $_SESSION['not_guncelle'] = 'sil_no';
                header("Location:../yonetim/not-defteri.html");
            }

        }
        elseif (isset($_POST['title']) && isset($_POST['color']) && isset($_POST['id']))
        {
            $id 	= $_POST['id'];
            $title 	= $_POST['title'];
            $color 	= $_POST['color'];

            $sorgu = $db->prepare("UPDATE not_defteri SET
				baslik 	= ?,
				renk	= ?
				WHERE id= ?");
            $guncelle = $sorgu->execute(array(
                $title,
                $color,
                $id
            ));
            if($guncelle)
            {
                $last_id 		= $id;
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 	= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Notu Güncelle",
                    'icon' 		=> "icon-calendar",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: steelblue;'>".$title."</strong> başlıklı notu güncelledi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['not_guncelle'] = 'guncelle_yes';
                header("Location:../yonetim/not-defteri.html");
            }
            else
            {
                $_SESSION['not_guncelle'] = 'guncelle_no';
                header("Location:../yonetim/not-defteri.html");
            }

        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/not-defteri.html");
    }
}

##Ajax Not Güncelle##
if(@$_GET['notguncelle'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if (isset($_POST['Event'][0]) && isset($_POST['Event'][1]) && isset($_POST['Event'][2]))
        {
            $id 	= $_POST['Event'][0];
            $notbul	= $db->query("SELECT * FROM not_defteri WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
            $title 	= $notbul['baslik'];
            $start 	= $_POST['Event'][1];
            $end 	= $_POST['Event'][2];

            $sorgu = $db->prepare("UPDATE not_defteri SET
				baslangic 	= ?,
				bitis		= ?
				WHERE id	= ?");
            $guncelle = $sorgu->execute(array(
                $start,
                $end,
                $id
            ));
            if($guncelle)
            {
                $last_id 		= $id;
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 	= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Notu Güncelle",
                    'icon' 		=> "icon-calendar",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: steelblue;'>".$title."</strong> başlıklı notu güncelledi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                die ('OK');
            }
            else
            {
                $_SESSION['notguncelle'] = 'no';
                header("Location:../yonetim/not-defteri.html");
            }
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/not-defteri.html");
    }
}

##Bildirim Sil##
if(@$_GET['bildirimsil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $Sorgu = $db->prepare("DELETE FROM bildirimler WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                $_SESSION['bildirimsil'] = 'yes';
                header("Location:../yonetim/index.html");
            }
            else
            {
                $_SESSION['bildirimsil'] = 'no';
                header("Location:../yonetim/index.html");
            }
        }
        else
        {
            echo '<meta http-equiv="refresh" content="0; url=404.html">';
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/index.html");
    }
}

## Rehber Kaydet ##
if(isset($_POST['rehber_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $adi 		= $_POST['adi'];
        if($_POST['durum']){$durum = 1;}else{$durum = 0;}
        $email 		= $_POST['email'];
        $telefon	= $_POST['telefon'];
        $notunuz	= $_POST['notunuz'];
        $tarih		= date('Y-m-d H:i:s');
        $tarih		= TvERtXpE3w_tr_tarih($tarih);

        $sorgu = $db->prepare("INSERT INTO rehber SET
				adi 	= ?,
				email	= ?,
				telefon	= ?,
				notunuz	= ?,
				durum 	= ?,
				tarih 	= ?");
        $Ekle = $sorgu->execute(array(
            $adi,
            $email,
            $telefon,
            $notunuz,
            $durum,
            $tarih
        ));
        if($Ekle)
        {
            $last_id 		= $db->lastInsertId();
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Yeni Rehber",
                'icon' 		=> "icon-notebook",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkturquoise;'>".$adi."</strong> isimli rehber kaydı ekledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['rehber_ekle'] = 'yes';
            header("Location:../yonetim/rehberim.html");
        }
        else
        {
            $_SESSION['rehber_ekle'] = 'no';
            header("Location:../yonetim/rehberim.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/rehberim.html");
    }
}

##Rehber Güncelle ##
if(isset($_POST['rehber_guncelle']))
{
    TvERtXpE3w_panelislemkontrol();
    $d_id 	= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $adi 		= $_POST['adi'];
        if($_POST['durum']){$durum = 1;}else{$durum = 0;}
        $email 		= $_POST['email'];
        $telefon 	= $_POST['telefon'];
        $notunuz	= $_POST['notunuz'];
        $tarih		= date('Y-m-d H:i:s');
        $tarih		= TvERtXpE3w_tr_tarih($tarih);

        $sorgu = $db->prepare("UPDATE rehber SET
			adi 	= ?,
			email	= ?,
			telefon	= ?,
			notunuz	= ?,
			durum 	= ?,
			tarih 	= ?
			WHERE id = ?");
        $guncelle = $sorgu->execute(array(
            $adi,
            $email,
            $telefon,
            $notunuz,
            $durum,
            $tarih,
            $d_id
        ));
        if($guncelle)
        {
            $last_id 		= $d_id;
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Rehber Güncellendi",
                'icon' 		=> "icon-notebook",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkturquoise;'>".$adi."</strong> isimli rehber kaydını güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['rehber_guncelle'] = 'yes';
            header("Location:../yonetim/rehber-duzenle/".$d_id.".html");
        }
        else
        {
            $_SESSION['rehber_guncelle'] = 'no';
            header("Location:../yonetim/rehber-duzenle/".$d_id.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/rehber-duzenle/".$d_id.".html");
    }
}

##Rehber Sil##
if(@$_GET['rehbersil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $rehber_bul= $db->query("SELECT * FROM rehber WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        $Sorgu = $db->prepare("DELETE FROM rehber WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                $last_id 		= $rehber_bul['id'];
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 	= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Rehber Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkturquoise;'>".$rehber_bul['adi']."</strong> isimli rehber kaydını sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['rehbersil'] = 'yes';
                header("Location:../yonetim/rehberim.html");
            }
            else
            {
                $_SESSION['rehbersil'] = 'no';
                header("Location:../yonetim/rehberim.html");
            }
        }
        else
        {
            echo '<meta http-equiv="refresh" content="0; url=404.html">';
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/rehberim.html");
    }
}

##Rehber Toplu Sil ##
if(isset($_POST['rehber_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $rehber_bul= $db->query("SELECT * FROM rehber WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM rehber WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Rehber Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkturquoise;'>".$rehber_bul['adi']."</strong> isimli rehber kaydını sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['rehber_tumu'] = 'yes';
                    header("Location:../yonetim/rehberim.html");
                }
                else
                {
                    $_SESSION['rehber_tumu'] = 'no';
                    header("Location:../yonetim/rehberim.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/rehberim.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/rehberim.html");
    }
}

##Rehber Toplu Aktif ##
if(isset($_POST['rehber_aktif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $rehber_bul= $db->query("SELECT * FROM rehber WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE rehber SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "1",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Rehber Aktif",
                        'icon' 		=> "icon-check",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkturquoise;'>".$rehber_bul['adi']."</strong> isimli  rehber kaydını aktif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['rehber_aktif'] = 'yes';
                    header("Location:../yonetim/rehberim.html");
                }
                else
                {
                    $_SESSION['rehber_aktif'] = 'no';
                    header("Location:../yonetim/rehberim.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/rehberim.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/rehberim.html");
    }
}

##Rehber Toplu Pasif ##
if(isset($_POST['rehber_pasif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $rehber_bul= $db->query("SELECT * FROM rehber WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE rehber SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "0",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Rehber Pasif",
                        'icon' 		=> "icon-close",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: darkturquoise;'>".$rehber_bul['adi']."</strong> isimli rehber kaydını pasif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['rehber_pasif'] = 'yes';
                    header("Location:../yonetim/rehberim.html");
                }
                else
                {
                    $_SESSION['rehber_pasif'] = 'no';
                    header("Location:../yonetim/rehberim.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/rehberim.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/rehberim.html");
    }
}

##Toplu Email Gönder##
if(isset($_POST['toplu_email_gonder']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $uyeler 	= $_POST["uyeler"];
        $diger 		= $_POST["diger"];
        $digermail 	= explode(",", $diger);
        $konu		= $_POST['konu'];
        $aciklama	= $_POST['aciklama'];

        if($uyeler == true && $diger == true)
        {
            $uyemail = array_merge($uyeler, $digermail);
        }
        elseif($uyeler == true)
        {
            $uyemail = $uyeler;
        }
        elseif($diger == true)
        {
            $uyemail = $digermail;
        }

        if(empty($aciklama) || empty($konu))
        {
            $_SESSION['toplu_email_gonder'] = 'bos';
            header("Location:../yonetim/toplu-email.html");
        }
        else
        {
            $from		= m_adresi;
            $gonderici	= m_adresi;
            $m_host		= m_server;
            $m_pass		= m_parola;
            $m_sertifika = m_sertifika;
            $m_port 	= m_port;

            $mail = new PHPMailer();
            $mail->IsSMTP(true);
            $mail->From     = $from;
            $mail->SMTPSecure = $m_sertifika;
            $mail->Sender   = $from;
            $mail->AddReplyTo =($from);
            $mail->FromName = firma_adi;
            $mail->Host     = $m_host;
            $mail->SMTPAuth = true;
            $mail->Port     = $m_port;
            foreach($uyemail as $uye)
            {
                $mail->AddBCC(''.$uye.'', ''.firma_adi.'');
            }
            $mail->CharSet = 'UTF-8';
            $mail->Username = $from;
            $mail->Password = $m_pass;
            $mail->Subject = $konu;
            $mail->Body = $aciklama;
            $mail->IsHTML(true);
            $mail->Send();

            $_SESSION['toplu_email_gonder'] = 'yes';
            header("Location:../yonetim/toplu-email.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/toplu-email.html");
    }
}

##Toplu SMS Gönder##
if(isset($_POST['toplu_sms_gonder']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $uyeler 		= $_POST["uyeler"];
        $diger 		= $_POST["diger"];
        $digeruyeler= explode(",", $diger);
        $aciklama	= $_POST['aciklama'];

        if($uyeler != "" && $diger != "")
        {
            $uyetelefon = array_merge($uyeler, $digeruyeler);
        }
        elseif($uyeler != "")
        {
            $uyetelefon = $uyeler;
        }
        elseif($diger != "")
        {
            $uyetelefon = $digeruyeler;
        }

        if(empty($aciklama))
        {
            $_SESSION['toplu_sms_gonder'] = 'bos';
            header("Location:../yonetim/toplu-sms.html");
        }
        else
        {
            foreach($uyetelefon as $uye)
            {
                toplusmsgonder($uye,$aciklama);
            }
            $_SESSION['toplu_sms_gonder'] = 'yes';
            header("Location:../yonetim/toplu-sms.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/toplu-sms.html");
    }
}

##Hosting Kategori Kaydet ##
if(isset($_POST['hosting_kategori_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $adi 		= $_POST['adi'];
        $sira 		= $_POST['sira'];
        $seoo		= TvERtXpE3w_seo($adi);
        if($seoo)
        {
            $seo 	= TvERtXpE3w_seo($adi);
        }
        else
        {
            $seo 	= rand();
        }
        if($_POST['durum']){$durum = 1;}else{$durum = 0;}
        if($_POST['anasayfa']){$anasayfa = 1;}else{$anasayfa = 0;}
        $kisa 		= $_POST['kisa'];
        $aciklama 	= $_POST['aciklama'];
        $keywords	= $_POST['keywords'];
        $description= $_POST['description'];
        $tarih		= date('Y-m-d H:i:s');
        $tarih		= TvERtXpE3w_tr_tarih($tarih);

        $sorgu = $db->prepare("INSERT INTO hosting_kategori SET
				adi 	= ?,
				sira 	= ?,
				seo 	= ?,
				kisa 	= ?,
				aciklama= ?,
				keywords= ?,
				description	= ?,
				durum 	= ?,
				anasayfa 	= ?,
				dil 	= ?,
				tarih 	= ?");
        $Ekle = $sorgu->execute(array(
            $adi,
            $sira,
            $seo,
            $kisa,
            $aciklama,
            $keywords,
            $description,
            $durum,
            $anasayfa,
            $_SESSION['admin_dil'],
            $tarih
        ));
        if($Ekle)
        {
            $last_id 		= $db->lastInsertId();
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Hosting Kategorisi Ekledi",
                'icon' 		=> "icon-note",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$adi."</strong> başlıklı hosting kategorisi ekledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['hosting_kategori_ekle'] = 'yes';
            header("Location:../yonetim/hosting-kategoriler.html");
        }
        else
        {
            $_SESSION['hosting_kategori_ekle'] = 'no';
            header("Location:../yonetim/hosting-kategori-ekle.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/hosting-kategori-ekle.html");
    }
}

##Hosting Kategori Güncelle ##
if(isset($_POST['hosting_kategori_guncelle']))
{
    TvERtXpE3w_panelislemkontrol();
    $d_id 	= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $adi 		= $_POST['adi'];
        $sira 		= $_POST['sira'];
        $seoo		= TvERtXpE3w_seo($adi);
        if($seoo)
        {
            $seo 	= TvERtXpE3w_seo($adi);
        }
        else
        {
            $seo 	= rand();
        }
        if($_POST['durum']){$durum = 1;}else{$durum = 0;}
        if($_POST['anasayfa']){$anasayfa = 1;}else{$anasayfa = 0;}
        $kisa 		= $_POST['kisa'];
        $aciklama 	= $_POST['aciklama'];
        $keywords 	= $_POST['keywords'];
        $description= $_POST['description'];
        $tarih		= date('Y-m-d H:i:s');
        $tarih		= TvERtXpE3w_tr_tarih($tarih);

        $sorgu = $db->prepare("UPDATE hosting_kategori SET
			adi 	= ?,
			sira 	= ?,
			seo 	= ?,
			kisa 	= ?,
			aciklama= ?,
			keywords= ?,
			description	= ?,
			durum 	= ?,
			anasayfa = ?,
			tarih 	= ?
			WHERE id = ?");
        $guncelle = $sorgu->execute(array(
            $adi,
            $sira,
            $seo,
            $kisa,
            $aciklama,
            $keywords,
            $description,
            $durum,
            $anasayfa,
            $tarih,
            $d_id
        ));
        if($guncelle)
        {
            $last_id 		= $d_id;
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Hosting Kategorisi Güncellendi",
                'icon' 		=> "icon-note",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$adi."</strong> başlıklı hosting kategorisini güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['hosting_kategori_guncelle'] = 'yes';
            header("Location:../yonetim/hosting-kategori-duzenle/".$d_id.".html");
        }
        else
        {
            $_SESSION['hosting_kategori_guncelle'] = 'no';
            header("Location:../yonetim/hosting-kategori-duzenle/".$d_id.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/hosting-kategori-duzenle/".$d_id.".html");
    }
}

##Hosting Kategori Sil##
if(@$_GET['hostingkatsil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $resim_bul= $db->query("SELECT * FROM hosting_kategori WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        $Sorgu = $db->prepare("DELETE FROM hosting_kategori WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                $last_id 		= $resim_bul['id'];
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 	= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Hosting Kategorisi Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$resim_bul['adi']."</strong> başlıklı hosting kategorisini sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['hostingkatsil'] = 'yes';
                header("Location:../yonetim/hosting-kategoriler.html");
            }
            else
            {
                $_SESSION['hostingkatsil'] = 'no';
                header("Location:../yonetim/hosting-kategoriler.html");
            }
        }
        else
        {
            echo '<meta http-equiv="refresh" content="0; url=404.html">';
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/hosting-kategoriler.html");
    }
}

##Hosting Kategori Toplu Sil ##
if(isset($_POST['hosting_kat_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $resim_bul= $db->query("SELECT * FROM hosting_kategori WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM hosting_kategori WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Hosting Kategorisi Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$resim_bul['adi']."</strong> başlıklı hosting kategorisini sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['hosting_kat_tumu'] = 'yes';
                    header("Location:../yonetim/hosting-kategoriler.html");
                }
                else
                {
                    $_SESSION['hosting_kat_tumu'] = 'no';
                    header("Location:../yonetim/hosting-kategoriler.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/hosting-kategoriler.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/hosting-kategoriler.html");
    }
}

##Hosting Kategori Toplu Aktif ##
if(isset($_POST['hosting_kat_aktif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $sayfa_bul= $db->query("SELECT * FROM hosting_kategori WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE hosting_kategori SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "1",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Hosting Kategori Aktif",
                        'icon' 		=> "icon-check",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$sayfa_bul['adi']."</strong> başlıklı hosting kategorisini aktif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['hosting_kat_aktif'] = 'yes';
                    header("Location:../yonetim/hosting-kategoriler.html");
                }
                else
                {
                    $_SESSION['hosting_kat_aktif'] = 'no';
                    header("Location:../yonetim/hosting-kategoriler.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/hosting-kategoriler.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/hosting-kategoriler.html");
    }
}

##Hosting Kategori Toplu Pasif ##
if(isset($_POST['hosting_kat_pasif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $sayfa_bul= $db->query("SELECT * FROM hosting_kategori WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE hosting_kategori SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "0",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Hosting Kategori Pasif",
                        'icon' 		=> "icon-close",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$sayfa_bul['adi']."</strong> başlıklı hosting kategorisini pasif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['hosting_kat_pasif'] = 'yes';
                    header("Location:../yonetim/hosting-kategoriler.html");
                }
                else
                {
                    $_SESSION['hosting_kat_pasif'] = 'no';
                    header("Location:../yonetim/hosting-kategoriler.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/hosting-kategoriler.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/hosting-kategoriler.html");
    }
}

##Hosting Kaydet ##
if(isset($_POST['hosting_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $kategori 			= $_POST['kategori'];
        $adi 				= $_POST['adi'];
        $tutar 				= $_POST['tutar'];
        $yil2 				= $_POST['2yil'];
        $yil3 				= $_POST['3yil'];
        $yil4 				= $_POST['4yil'];
        $yil5 				= $_POST['5yil'];
        $sira 				= $_POST['sira'];
        $zmnt 				= $_POST['zmnt'];
        $ozellikler			= $_POST["ozellikler"];
        $ozellikler			= explode("\n",$ozellikler);
        $ozellikler			= implode(",",$ozellikler);
        $whm_plan 			= $_POST['whm_plan'];
        $whm_alan 			= $_POST['whm_alan'];
        $whm_atrafik		= $_POST['whm_atrafik'];
        $whm_max_ftp		= $_POST['whm_max_ftp'];
        $whm_max_subdomain	= $_POST['whm_max_subdomain'];
        $whm_max_add_domain	= $_POST['whm_max_add_domain'];
        $whm_max_domain_park= $_POST['whm_max_domain_park'];
        $whm_max_veritabani	= $_POST['whm_max_veritabani'];
        $whm_max_email		= $_POST['whm_max_email'];
        $shopierid          = $_POST['shopierid'];
        $iyzilink           = $_POST['iyzilink'];

        if($_POST['otohesap']){$otohesap = 1;}else{$otohesap = 0;}
        if($_POST['durum']){$durum = 1;}else{$durum = 0;}
        if($_POST['anasayfa']){$anasayfa = 1;}else{$anasayfa = 0;}
        $tarih				= date('Y-m-d H:i:s');
        $tarih				= TvERtXpE3w_tr_tarih($tarih);

        $sorgu = $db->prepare("INSERT INTO hostingler SET
				kategori 			= ?,
				adi 				= ?,
				tutar 				= ?,
			2yil 				= ?,
			3yil 				= ?,
			4yil 				= ?,
			5yil 				= ?,
				sira 				= ?,
				zmnt 				= ?,
				ozellikler 			= ?,
				whm_plan 			= ?,
				whm_alan 			= ?,
				whm_atrafik			= ?,
				whm_max_ftp			= ?,
				whm_max_subdomain	= ?,
				whm_max_add_domain	= ?,
				whm_max_domain_park	= ?,
				whm_max_veritabani	= ?,
				whm_max_email		= ?,
				otohesap			= ?,
				durum 				= ?,
				anasayfa 			= ?,
				dil 				= ?,
				shopierid 		    = ?,
				iyzilink 			= ?,
				tarih 				= ?");
        $Ekle = $sorgu->execute(array(
            $kategori,
            $adi,
            $tutar,
            $yil2,
            $yil3,
            $yil4,
            $yil5,
            $sira,
            $zmnt,
            $ozellikler,
            $whm_plan,
            $whm_alan,
            $whm_atrafik,
            $whm_max_ftp,
            $whm_max_subdomain,
            $whm_max_add_domain,
            $whm_max_domain_park,
            $whm_max_veritabani,
            $whm_max_email,
            $otohesap,
            $durum,
            $anasayfa,
            $_SESSION['admin_dil'],
            $shopierid,
            $iyzilink,
            $tarih
        ));
        if($Ekle)
        {
            $last_id 		= $db->lastInsertId();
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Hosting Ekledi",
                'icon' 		=> "icon-note",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$adi."</strong> başlıklı hosting paketi ekledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['hosting_ekle'] = 'yes';
            header("Location:../yonetim/hosting-paketler.html");
        }
        else
        {
            $_SESSION['hosting_ekle'] = 'no';
            header("Location:../yonetim/hosting-paket-ekle.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/hosting-paket-ekle.html");
    }
}

##Hosting Güncelle ##
if(isset($_POST['hosting_guncelle']))
{
    TvERtXpE3w_panelislemkontrol();
    $d_id 	= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $kategori 			= $_POST['kategori'];
        $adi 				= $_POST['adi'];
        $tutar 				= $_POST['tutar'];
        $yil2 				= $_POST['2yil'];
        $yil3 				= $_POST['3yil'];
        $yil4 				= $_POST['4yil'];
        $yil5 				= $_POST['5yil'];
        $sira 				= $_POST['sira'];
        $zmnt 				= $_POST['zmnt'];
        $ozellikler			= $_POST["ozellikler"];
        $ozellikler			= explode("\n",$ozellikler);
        $ozellikler			= implode(",",$ozellikler);
        $whm_plan 			= $_POST['whm_plan'];
        $whm_alan 			= $_POST['whm_alan'];
        $whm_atrafik		= $_POST['whm_atrafik'];
        $whm_max_ftp		= $_POST['whm_max_ftp'];
        $whm_max_subdomain	= $_POST['whm_max_subdomain'];
        $whm_max_add_domain	= $_POST['whm_max_add_domain'];
        $whm_max_domain_park= $_POST['whm_max_domain_park'];
        $whm_max_veritabani	= $_POST['whm_max_veritabani'];
        $whm_max_email		= $_POST['whm_max_email'];
        $shopierid          = $_POST['shopierid'];
        $iyzilink           = $_POST['iyzilink'];
        if($_POST['otohesap']){$otohesap = 1;}else{$otohesap = 0;}
        if($_POST['durum']){$durum = 1;}else{$durum = 0;}
        if($_POST['anasayfa']){$anasayfa = 1;}else{$anasayfa = 0;}
        $tarih				= date('Y-m-d H:i:s');
        $tarih				= TvERtXpE3w_tr_tarih($tarih);

        $sorgu = $db->prepare("UPDATE hostingler SET
			kategori 			= ?,
			adi 				= ?,
			tutar 				= ?,
			2yil 				= ?,
			3yil 				= ?,
			4yil 				= ?,
			5yil 				= ?,
			sira 				= ?,
			zmnt 				= ?,
			ozellikler 			= ?,
			whm_plan 			= ?,
			whm_alan 			= ?,
			whm_atrafik			= ?,
			whm_max_ftp			= ?,
			whm_max_subdomain	= ?,
			whm_max_add_domain	= ?,
			whm_max_domain_park	= ?,
			whm_max_veritabani	= ?,
			whm_max_email		= ?,
			otohesap			= ?,
			durum 				= ?,
			anasayfa 			= ?,
            shopierid 		    = ?,
            iyzilink 			= ?,
			tarih 				= ?
			WHERE id 			= ?");
        $guncelle = $sorgu->execute(array(
            $kategori,
            $adi,
            $tutar,
            $yil2,
            $yil3,
            $yil4,
            $yil5,
            $sira,
            $zmnt,
            $ozellikler,
            $whm_plan,
            $whm_alan,
            $whm_atrafik,
            $whm_max_ftp,
            $whm_max_subdomain,
            $whm_max_add_domain,
            $whm_max_domain_park,
            $whm_max_veritabani,
            $whm_max_email,
            $otohesap,
            $durum,
            $anasayfa,
            $shopierid,
            $iyzilink,
            $tarih,
            $d_id
        ));
        if($guncelle)
        {
            $last_id 		= $d_id;
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Hosting Güncellendi",
                'icon' 		=> "icon-note",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$adi."</strong> başlıklı hosting paketini güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['hosting_guncelle'] = 'yes';
            header("Location:../yonetim/hosting-paket-duzenle/".$d_id.".html");
        }
        else
        {
            $_SESSION['hosting_guncelle'] = 'no';
            header("Location:../yonetim/hosting-paket-duzenle/".$d_id.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/hosting-paket-duzenle/".$d_id.".html");
    }
}

##Hosting Sil##
if(@$_GET['hostingpaketsil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $resim_bul= $db->query("SELECT * FROM hostingler WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        $Sorgu = $db->prepare("DELETE FROM hostingler WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                $last_id 		= $resim_bul['id'];
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 	= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Hosting Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$resim_bul['adi']."</strong> başlıklı hosting paketini sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['hostingpaketsil'] = 'yes';
                header("Location:../yonetim/hosting-paketler.html");
            }
            else
            {
                $_SESSION['hostingpaketsil'] = 'no';
                header("Location:../yonetim/hosting-paketler.html");
            }
        }
        else
        {
            echo '<meta http-equiv="refresh" content="0; url=404.html">';
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/hosting-paketler.html");
    }
}

##Hosting Toplu Sil ##
if(isset($_POST['hosting_paket_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $resim_bul= $db->query("SELECT * FROM hostingler WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM hostingler WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Hosting Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$resim_bul['adi']."</strong> başlıklı hosting paketini sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['hosting_paket_tumu'] = 'yes';
                    header("Location:../yonetim/hosting-paketler.html");
                }
                else
                {
                    $_SESSION['hosting_paket_tumu'] = 'no';
                    header("Location:../yonetim/hosting-paketler.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/hosting-paketler.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/hosting-paketler.html");
    }
}

##Hosting Toplu Aktif ##
if(isset($_POST['hosting_paket_aktif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $sayfa_bul= $db->query("SELECT * FROM hostingler WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE hostingler SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "1",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Hosting Aktif",
                        'icon' 		=> "icon-check",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$sayfa_bul['adi']."</strong> başlıklı hosting paketini aktif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['hosting_paket_aktif'] = 'yes';
                    header("Location:../yonetim/hosting-paketler.html");
                }
                else
                {
                    $_SESSION['hosting_paket_aktif'] = 'no';
                    header("Location:../yonetim/hosting-paketler.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/hosting-paketler.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/hosting-paketler.html");
    }
}

##Hosting Toplu Pasif ##
if(isset($_POST['hosting_paket_pasif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $sayfa_bul= $db->query("SELECT * FROM hostingler WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE hostingler SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "0",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Hosting Pasif",
                        'icon' 		=> "icon-close",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$sayfa_bul['adi']."</strong> başlıklı hosting paketini pasif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['hosting_paket_pasif'] = 'yes';
                    header("Location:../yonetim/hosting-paketler.html");
                }
                else
                {
                    $_SESSION['hosting_paket_pasif'] = 'no';
                    header("Location:../yonetim/hosting-paketler.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/hosting-paketler.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/hosting-paketler.html");
    }
}

##Web Kategori Kaydet ##
if(isset($_POST['web_kategori_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $adi 		= $_POST['adi'];
        $sira 		= $_POST['sira'];
        $seoo		= TvERtXpE3w_seo($adi);
        if($seoo)
        {
            $seo 	= TvERtXpE3w_seo($adi);
        }
        else
        {
            $seo 	= rand();
        }
        if($_POST['durum']){$durum = 1;}else{$durum = 0;}
        $aciklama 	= $_POST['aciklama'];
        $keywords	= $_POST['keywords'];
        $description= $_POST['description'];
        $tarih		= date('Y-m-d H:i:s');
        $tarih		= TvERtXpE3w_tr_tarih($tarih);

        $sorgu = $db->prepare("INSERT INTO web_kategori SET
				adi 	= ?,
				sira 	= ?,
				seo 	= ?,
				aciklama= ?,
				keywords= ?,
				description	= ?,
				durum 	= ?,
				dil 	= ?,
				tarih 	= ?");
        $Ekle = $sorgu->execute(array(
            $adi,
            $sira,
            $seo,
            $aciklama,
            $keywords,
            $description,
            $durum,
            $_SESSION['admin_dil'],
            $tarih
        ));
        if($Ekle)
        {
            $last_id 		= $db->lastInsertId();
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Hosting Kategorisi Ekledi",
                'icon' 		=> "icon-note",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$adi."</strong> başlıklı web kategorisi ekledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['web_kategori_ekle'] = 'yes';
            header("Location:../yonetim/web-kategoriler.html");
        }
        else
        {
            $_SESSION['web_kategori_ekle'] = 'no';
            header("Location:../yonetim/web-kategori-ekle.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/web-kategori-ekle.html");
    }
}

##Web Kategori Güncelle ##
if(isset($_POST['web_kategori_guncelle']))
{
    TvERtXpE3w_panelislemkontrol();
    $d_id 	= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $adi 		= $_POST['adi'];
        $sira 		= $_POST['sira'];
        $seoo		= TvERtXpE3w_seo($adi);
        if($seoo)
        {
            $seo 	= TvERtXpE3w_seo($adi);
        }
        else
        {
            $seo 	= rand();
        }
        if($_POST['durum']){$durum = 1;}else{$durum = 0;}
        $aciklama 	= $_POST['aciklama'];
        $keywords 	= $_POST['keywords'];
        $description= $_POST['description'];
        $tarih		= date('Y-m-d H:i:s');
        $tarih		= TvERtXpE3w_tr_tarih($tarih);

        $sorgu = $db->prepare("UPDATE web_kategori SET
			adi 	= ?,
			sira 	= ?,
			seo 	= ?,
			aciklama= ?,
			keywords= ?,
			description	= ?,
			durum 	= ?,
			tarih 	= ?
			WHERE id = ?");
        $guncelle = $sorgu->execute(array(
            $adi,
            $sira,
            $seo,
            $aciklama,
            $keywords,
            $description,
            $durum,
            $tarih,
            $d_id
        ));
        if($guncelle)
        {
            $last_id 		= $d_id;
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Web Kategorisi Güncellendi",
                'icon' 		=> "icon-note",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$adi."</strong> başlıklı web kategorisini güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['web_kategori_guncelle'] = 'yes';
            header("Location:../yonetim/web-kategori-duzenle/".$d_id.".html");
        }
        else
        {
            $_SESSION['web_kategori_guncelle'] = 'no';
            header("Location:../yonetim/web-kategori-duzenle/".$d_id.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/web-kategori-duzenle/".$d_id.".html");
    }
}

##Web Kategori Sil##
if(@$_GET['webkatsil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $resim_bul= $db->query("SELECT * FROM web_kategori WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        $Sorgu = $db->prepare("DELETE FROM web_kategori WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                $last_id 		= $resim_bul['id'];
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 	= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Web Kategorisi Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$resim_bul['adi']."</strong> başlıklı web kategorisini sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['webkatsil'] = 'yes';
                header("Location:../yonetim/web-kategoriler.html");
            }
            else
            {
                $_SESSION['webkatsil'] = 'no';
                header("Location:../yonetim/web-kategoriler.html");
            }
        }
        else
        {
            echo '<meta http-equiv="refresh" content="0; url=404.html">';
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/web-kategoriler.html");
    }
}

##Web Kategori Toplu Sil ##
if(isset($_POST['web_kat_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $resim_bul= $db->query("SELECT * FROM web_kategori WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM web_kategori WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Web Kategorisi Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$resim_bul['adi']."</strong> başlıklı web kategorisini sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['web_kat_tumu'] = 'yes';
                    header("Location:../yonetim/web-kategoriler.html");
                }
                else
                {
                    $_SESSION['web_kat_tumu'] = 'no';
                    header("Location:../yonetim/web-kategoriler.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/web-kategoriler.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/web-kategoriler.html");
    }
}

##Web Kategori Toplu Aktif ##
if(isset($_POST['web_kat_aktif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $sayfa_bul= $db->query("SELECT * FROM web_kategori WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE web_kategori SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "1",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "web Kategori Aktif",
                        'icon' 		=> "icon-check",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$sayfa_bul['adi']."</strong> başlıklı web kategorisini aktif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['web_kat_aktif'] = 'yes';
                    header("Location:../yonetim/web-kategoriler.html");
                }
                else
                {
                    $_SESSION['web_kat_aktif'] = 'no';
                    header("Location:../yonetim/web-kategoriler.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/web-kategoriler.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/web-kategoriler.html");
    }
}

##Web Kategori Toplu Pasif ##
if(isset($_POST['web_kat_pasif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $sayfa_bul= $db->query("SELECT * FROM web_kategori WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE web_kategori SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "0",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Web Kategori Pasif",
                        'icon' 		=> "icon-close",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$sayfa_bul['adi']."</strong> başlıklı web kategorisini pasif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['web_kat_pasif'] = 'yes';
                    header("Location:../yonetim/web-kategoriler.html");
                }
                else
                {
                    $_SESSION['web_kat_pasif'] = 'no';
                    header("Location:../yonetim/web-kategoriler.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/web-kategoriler.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/web-kategoriler.html");
    }
}

##Web Paket Kaydet ##
if(isset($_POST['web_paket_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $kategori		= implode(",", $_POST["kategori"]);
        $adi 			= $_POST['adi'];
        $sira 			= $_POST['sira'];
        $seoo			= TvERtXpE3w_seo($adi);
        if($seoo){$seo 	= TvERtXpE3w_seo($adi);}else{$seo = rand();}
        $demo_link 		= $_POST['demo_link'];
        $demo_admin_link= $_POST['demo_admin_link'];
        $download_link	= $_POST['download_link'];
        if($_POST['durum']){$durum = 1;}else{$durum = 0;}
        if($_POST['anasayfa']){$anasayfa = 1;}else{$anasayfa = 0;}
        if($_POST['auto_approval']){$auto_approval = 1;}else{$auto_approval = 0;}
        $etiketler 		= $_POST['etiketler'];
        $kisa 			= $_POST['kisa'];
        $ozellik 		= $_POST['ozellik'];
        $aciklama 		= $_POST['aciklama'];
        $talimat 		= $_POST['talimat'];
        $tutar 			= $_POST['tutar'];
        $keywords		= $_POST['keywords'];
        $description	= $_POST['description'];
        $tarih			= date('Y-m-d H:i:s');
        $tarih			= TvERtXpE3w_tr_tarih($tarih);
        $shopierid          = $_POST['shopierid'];

        $upload = new upload($_FILES['resim']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/webpaketleri/kapak");
            if ($upload->processed)
            {
                $Resim=''.$upload->file_dst_name.'';
            }
        }

        $upload2 = new upload($_FILES['download_file']);
        if ($upload2->uploaded)
        {
            $upload2->file_auto_rename = true;
            $upload2->process("../".tema."/uploads/webpaketleri/dosya");
            if ($upload2->processed)
            {
                $Dosya=''.$upload2->file_dst_name.'';
            }
        }
        $Resim=''.$upload->file_dst_name.'';
        $Dosya=''.$upload2->file_dst_name.'';

        $files = array();
        foreach ($_FILES['resimler'] as $k => $l) {
            foreach ($l as $i => $v) {
                if (!array_key_exists($i, $files))
                    $files[$i] = array();
                $files[$i][$k] = $v;
            }
        }

        $sorgu = $db->prepare("INSERT INTO yazilimlar SET
				kategori 		= ?,
				adi 			= ?,
				sira 			= ?,
				seo 			= ?,
				demo_link 		= ?,
				demo_admin_link = ?,
				download_link 	= ?,
				anasayfa 		= ?,
				auto_approval 	= ?,
				etiketler 		= ?,
				kisa 			= ?,
				ozellik 		= ?,
				aciklama		= ?,
				talimat			= ?,
				keywords		= ?,
				description		= ?,
				tutar			= ?,
				durum 			= ?,
				resim 			= ?,
				download_file 	= ?,
				dil 			= ?,
				shopierid 		    = ?,
				iyzilink 			= ?,
				tarih 			= ?");
        $Ekle = $sorgu->execute(array(
            $kategori,
            $adi,
            $sira,
            $seo,
            $demo_link,
            $demo_admin_link,
            $download_link,
            $anasayfa,
            $auto_approval,
            $etiketler,
            $kisa,
            $ozellik,
            $aciklama,
            $talimat,
            $keywords,
            $description,
            $tutar,
            $durum,
            $Resim,
            $Dosya,
            $_SESSION['admin_dil'],
            $shopierid,
            $iyzilink,
            $tarih,
        ));
        if($Ekle)
        {
            $sonid = $db->lastInsertId();
            foreach ($files as $file)
            {
                $yukle = new Upload($file);
                if($yukle->uploaded)
                {
                    $yukle->file_auto_rename = true;
                    $yukle->process("../".tema."/uploads/webpaketleri/");

                    $yukle->file_auto_rename = true;
                    $yukle->image_resize = true;
                    $yukle->image_ratio_crop = true;
                    $yukle->image_x = 400;
                    $yukle->image_y = 230;
                    $yukle->process("../".tema."/uploads/webpaketleri/kucuk/");

                    $yukle->allowed = array ( 'image/*' );
                    if ($yukle->processed)
                    {
                        $DigerResim=''.$yukle->file_dst_name.'';

                        $sorgu = $db->prepare("INSERT INTO webpaketresim SET
							rid 	= ?,
							resim 	= ?
							");
                        $yap = $sorgu->execute(array(
                            $sonid,
                            $DigerResim
                        ));
                    }
                }
            }
            $last_id 		= $db->lastInsertId();
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Web Paketi Ekledi",
                'icon' 		=> "icon-note",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$adi."</strong> adında web paketi ekledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['web_paket_ekle'] = 'yes';
            header("Location:../yonetim/web-paketler.html");
        }
        else
        {
            $_SESSION['web_paket_ekle'] = 'no';
            header("Location:../yonetim/web-paket-ekle.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/web-paket-ekle.html");
    }
}

##Web Paket Güncelle ##
if(isset($_POST['web_paket_guncelle']))
{
    TvERtXpE3w_panelislemkontrol();
    $d_id 	= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $kategori		= implode(",", $_POST["kategori"]);
        $adi 			= $_POST['adi'];
        $sira 			= $_POST['sira'];
        $seoo			= TvERtXpE3w_seo($adi);
        if($seoo){$seo 	= TvERtXpE3w_seo($adi);}else{$seo = rand();}
        $demo_link 		= $_POST['demo_link'];
        $demo_admin_link= $_POST['demo_admin_link'];
        $download_link	= $_POST['download_link'];
        if($_POST['durum']){$durum = 1;}else{$durum = 0;}
        if($_POST['anasayfa']){$anasayfa = 1;}else{$anasayfa = 0;}
        if($_POST['auto_approval']){$auto_approval = 1;}else{$auto_approval = 0;}
        $etiketler 		= $_POST['etiketler'];
        $kisa 			= $_POST['kisa'];
        $ozellik 		= $_POST['ozellik'];
        $aciklama 		= $_POST['aciklama'];
        $talimat 		= $_POST['talimat'];
        $tutar 			= $_POST['tutar'];
        $keywords		= $_POST['keywords'];
        $description	= $_POST['description'];
        $tarih			= date('Y-m-d H:i:s');
        $tarih			= TvERtXpE3w_tr_tarih($tarih);
        $shopierid          = $_POST['shopierid'];
        $iyzilink          = $_POST['iyzilink'];

        $upload = new upload($_FILES['resim']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/webpaketleri/kapak");
            if ($upload->processed)
            {
                $Resim=''.$upload->file_dst_name.'';
            }
        }

        $upload = new upload($_FILES['download_file']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/webpaketleri/dosya");
            if ($upload->processed)
            {
                $Dosya=''.$upload->file_dst_name.'';
            }
        }

        $files = array();
        foreach ($_FILES['resimler'] as $k => $l) {
            foreach ($l as $i => $v) {
                if (!array_key_exists($i, $files))
                    $files[$i] = array();
                $files[$i][$k] = $v;
            }
        }

        if(isset($Resim)){
            $resim_bul= $db->query("SELECT * FROM yazilimlar WHERE id = '{$d_id}'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/webpaketleri/kapak/".$resim_bul['resim']);
            $guncelle = $db->prepare("UPDATE yazilimlar SET resim = ? WHERE id = ?");
            $guncelle->execute([$Resim,$d_id]);
            $Resim=''.$upload->file_dst_name.'';
        }

        if(isset($Dosya)){
            $dosya_bul= $db->query("SELECT * FROM yazilimlar WHERE id = '{$d_id}'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/webpaketleri/dosya/".$dosya_bul['download_file']);
            $guncelle = $db->prepare("UPDATE yazilimlar SET download_file = ? WHERE id = ?");
            $guncelle->execute([$Dosya,$d_id]);
            $Dosya=''.$upload->file_dst_name.'';
        }

        $sorgu = $db->prepare("UPDATE yazilimlar SET
			kategori 		= ?,
			adi 			= ?,
			sira 			= ?,
			seo 			= ?,
			demo_link 		= ?,
			demo_admin_link = ?,
			download_link 	= ?,
			anasayfa 		= ?,
			auto_approval 	= ?,
			etiketler 		= ?,
			kisa 			= ?,
			ozellik 		= ?,
			aciklama		= ?,
			talimat			= ?,
			keywords		= ?,
			description		= ?,
			tutar			= ?,
			durum 			= ?,
			tarih 			= ?,
			
				shopierid 		    = ?,
				iyzilink 			= ?
			WHERE id 		= ?");
        $guncelle = $sorgu->execute(array(
            $kategori,
            $adi,
            $sira,
            $seo,
            $demo_link,
            $demo_admin_link,
            $download_link,
            $anasayfa,
            $auto_approval,
            $etiketler,
            $kisa,
            $ozellik,
            $aciklama,
            $talimat,
            $keywords,
            $description,
            $tutar,
            $durum,
            $tarih,
            $shopierid,
            $iyzilink,
            $d_id
        ));
        if($guncelle)
        {
            $sonid = $d_id;
            foreach ($files as $file)
            {
                $yukle = new Upload($file);
                if($yukle->uploaded)
                {
                    $yukle->file_auto_rename = true;
                    $yukle->process("../".tema."/uploads/webpaketleri/");

                    $yukle->file_auto_rename = true;
                    $yukle->image_resize = true;
                    $yukle->image_ratio_crop = true;
                    $yukle->image_x = 400;
                    $yukle->image_y = 230;
                    $yukle->process("../".tema."/uploads/webpaketleri/kucuk/");

                    $yukle->allowed = array ( 'image/*' );
                    if ($yukle->processed)
                    {
                        $DigerResim=''.$yukle->file_dst_name.'';

                        $sorgu = $db->prepare("INSERT INTO webpaketresim SET
							rid 	= ?,
							resim 	= ?
							");
                        $yap = $sorgu->execute(array(
                            $sonid,
                            $DigerResim
                        ));
                    }
                }
            }
            $last_id 		= $d_id;
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Web Paketi Güncellendi",
                'icon' 		=> "icon-note",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$adi."</strong> başlıklı web paketini güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['web_paket_guncelle'] = 'yes';
            header("Location:../yonetim/web-paket-duzenle/".$d_id.".html");
        }
        else
        {
            $_SESSION['web_paket_guncelle'] = 'no';
            header("Location:../yonetim/web-paket-duzenle/".$d_id.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/web-paket-duzenle/".$d_id.".html");
    }
}

##Web Paketleri Toplu Resim Sil##
if(@$_GET['webpakettopluresimsil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    @$resimid 	= $_GET['sid'];
    if($_SESSION['rutbe'] == 0)
    {
        $resim_bul	= $db->query("SELECT * FROM webpaketresim WHERE id = '{$resimid}'")->fetch(PDO::FETCH_ASSOC);
        unlink("../".tema."/uploads/webpaketleri/".$resim_bul['resim']);
        unlink("../".tema."/uploads/webpaketleri/kucuk/".$resim_bul['resim']);
        $TSorgu = $db->prepare("DELETE FROM webpaketresim WHERE id = :id");
        $TSil	= $TSorgu->execute(array('id' => $resimid));
        if($TSil)
        {
            $_SESSION['webpakettopluresimsil'] = 'yes';
            header("Location:../yonetim/web-paket-duzenle/".$_GET['id'].".html");
        }
        else
        {
            $_SESSION['webpakettopluresimsil'] = 'no';
            header("Location:../yonetim/web-paket-duzenle/".$_GET['id'].".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/web-paket-duzenle/".$_GET['id'].".html");
    }
}

##Web Paket Resim Sil##
if(@$_GET['webpaketresimsil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    @$resimid 	= $_GET['sid'];
    if($_SESSION['rutbe'] == 0)
    {
        $resim_bul	= $db->query("SELECT * FROM yazilimlar WHERE id = '{$resimid}'")->fetch(PDO::FETCH_ASSOC);
        unlink("../".tema."/uploads/webpaketleri/kapak/".$resim_bul['resim']);
        $sorgu = $db->prepare("UPDATE yazilimlar SET
					resim	= ?
					WHERE id = ?");
        $guncelle = $sorgu->execute(array(
            "",
            $resimid
        ));
        if($guncelle)
        {
            $last_id 		= $resim_bul['id'];
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 		= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Web Paket Kapak Resim Silindi",
                'icon' 		=> "icon-trash",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$resim_bul['adi']."</strong> başlıklı web paketinin kapak resmini sildi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['webpaketresimsil'] = 'yes';
            header("Location:../yonetim/web-paket-duzenle/".$resimid.".html");
        }
        else
        {
            $_SESSION['webpaketresimsil'] = 'no';
            header("Location:../yonetim/web-paket-duzenle/".$resimid.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/web-paket-duzenle/".$resimid.".html");
    }
}

##Web Paket Dosya Sil##
if(@$_GET['webpaketdosyasil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    @$resimid 	= $_GET['sid'];
    if($_SESSION['rutbe'] == 0)
    {
        $resim_bul	= $db->query("SELECT * FROM yazilimlar WHERE id = '{$resimid}'")->fetch(PDO::FETCH_ASSOC);
        unlink("../".tema."/uploads/webpaketleri/dosya/".$resim_bul['download_file']);
        $sorgu = $db->prepare("UPDATE yazilimlar SET
					download_file = ?
					WHERE id = ?");
        $guncelle = $sorgu->execute(array(
            "",
            $resimid
        ));
        if($guncelle)
        {
            $last_id 		= $resim_bul['id'];
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 		= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Web Paket Dosyası Silindi",
                'icon' 		=> "icon-trash",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$resim_bul['adi']."</strong> başlıklı web paketinin dosyasını sildi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['webpaketdosyasil'] = 'yes';
            header("Location:../yonetim/web-paket-duzenle/".$resimid.".html");
        }
        else
        {
            $_SESSION['webpaketdosyasil'] = 'no';
            header("Location:../yonetim/web-paket-duzenle/".$resimid.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/web-paket-duzenle/".$resimid.".html");
    }
}

##Web Paket Sil##
if(@$_GET['webpaketsil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $resimbul	= $db->query("SELECT * FROM yazilimlar WHERE id = '{$_GET['id']}'")->fetch(PDO::FETCH_ASSOC);
        $TSorgu 		= $db->prepare("DELETE FROM yazilimlar WHERE id = :id");
        $TSil		= $TSorgu->execute(array('id' => $_GET['id']));
        if($TSil)
        {
            unlink("../".tema."/uploads/webpaketleri/kapak/".$resimbul['resim']);
            unlink("../".tema."/uploads/webpaketleri/dosya/".$resimbul['download_file']);
            $TopluSorgu = $db->prepare("SELECT * FROM webpaketresim WHERE rid = ?");
            $TopluSorgu->execute(array($_GET['id']));
            $Topluislem = $TopluSorgu->fetchALL(PDO::FETCH_ASSOC);
            foreach ( $Topluislem as $TopluSonuc )
            {
                $TSorgu = $db->prepare("DELETE FROM webpaketresim WHERE id = :id");
                $TSorgu->execute(array('id' => $TopluSonuc['id']));
                unlink("../".tema."/uploads/webpaketleri/".$TopluSonuc['resim']);
                unlink("../".tema."/uploads/webpaketleri/kucuk/".$TopluSonuc['resim']);
            }
            $last_id 		= $resimbul['id'];
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 		= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Web Paketi Silindi",
                'icon' 		=> "icon-trash",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: steelblue;'>".$resimbul['adi']."</strong> başlıklı web paketini sildi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['webpaketsil'] = 'yes';
            header("Location:../yonetim/web-paketler.html");
        }
        else
        {
            $_SESSION['webpaketsil'] = 'no';
            header("Location:../yonetim/web-paketler.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/web-paketler.html");
    }
}

##Web Paket Toplu Sil ##
if(isset($_POST['web_paket_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    $url = $_POST['url'];
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $resimbul	= $db->query("SELECT * FROM yazilimlar WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM yazilimlar WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    unlink("../".tema."/uploads/webpaketleri/kapak/".$resimbul['resim']);
                    unlink("../".tema."/uploads/webpaketleri/dosya/".$resimbul['download_file']);
                    $TopluSorguAlt = $db->prepare("SELECT * FROM webpaketresim WHERE rid = ?");
                    $TopluSorguAlt->execute(array($i));
                    $TopluislemAlt = $TopluSorguAlt->fetchALL(PDO::FETCH_ASSOC);
                    foreach ( $TopluislemAlt as $TopluSonucAlt )
                    {
                        $TSorgu = $db->prepare("DELETE FROM webpaketresim WHERE id = :id");
                        $TSorgu->execute(array('id' => $TopluSonucAlt['id']));
                        unlink("../".tema."/uploads/webpaketleri/".$TopluSonucAlt['resim']);
                        unlink("../".tema."/uploads/webpaketleri/kucuk/".$TopluSonucAlt['resim']);
                    }
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Web Paketi Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: steelblue;'>".$resimbul['adi']."</strong> başlıklı web paketini sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['web_paket_tumu'] = 'yes';
                    header("Location:../yonetim/web-paketler.html");
                }
                else
                {
                    $_SESSION['web_paket_tumu'] = 'no';
                    header("Location:../yonetim/web-paketler.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/web-paketler.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/web-paketler.html");
    }
}

##Web Paket Toplu Aktif ##
if(isset($_POST['web_paket_aktif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $sayfa_bul= $db->query("SELECT * FROM yazilimlar WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE yazilimlar SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "1",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Web Paket Aktif",
                        'icon' 		=> "icon-check",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$sayfa_bul['adi']."</strong> başlıklı web paketini aktif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['web_paket_aktif'] = 'yes';
                    header("Location:../yonetim/web-paketler.html");
                }
                else
                {
                    $_SESSION['web_paket_aktif'] = 'no';
                    header("Location:../yonetim/web-paketler.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/web-paketler.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/web-paketler.html");
    }
}

##Web Paket Toplu Pasif ##
if(isset($_POST['web_paket_pasif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $sayfa_bul= $db->query("SELECT * FROM yazilimlar WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE yazilimlar SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "0",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "web Paket Pasif",
                        'icon' 		=> "icon-close",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$sayfa_bul['adi']."</strong> başlıklı web paketini pasif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['web_paket_pasif'] = 'yes';
                    header("Location:../yonetim/web-paketler.html");
                }
                else
                {
                    $_SESSION['web_paket_pasif'] = 'no';
                    header("Location:../yonetim/web-paketler.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/web-paketler.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/web-paketler.html");
    }
}

##alan Adı Fiyatları Güncelle ##
if(isset($_POST['alanadi_guncelle']))
{
    TvERtXpE3w_panelislemkontrol();
    $uzanti 			= json_encode($_POST["uzanti"]);
    $kayit 			= json_encode($_POST["kayit"]);
    $yenileme 		= json_encode($_POST["yenileme"]);

    if($_SESSION['rutbe'] == 0)
    {
        $sorgu = $db->prepare("UPDATE alanadi SET
			uzanti		= :uzanti,
			kayit		= :kayit,
			yenileme	= :yenileme	
			WHERE id 	= :id");
        $guncelle = $sorgu->execute(array(
            'uzanti'	=> $uzanti,
            'kayit'		=> $kayit,
            'yenileme'	=> $yenileme,
            'id'		=> "1"
        ));

        if($guncelle)
        {
            $last_id 		= 1;
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Alan Adı Fiyatları Güncellendi.",
                'icon' 		=> "icon-grid",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: steelblue;'>Yönetici</strong> alan adı fyatlarını güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['alanadi_guncelle'] = 'yes';
            header("Location:../yonetim/alanadi-fiyatlari.html");
        }
        else
        {
            $_SESSION['alanadi_guncelle'] = 'no';
            header("Location:../yonetim/alanadi-fiyatlari.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/alanadi-fiyatlari.html");
    }

}

##Tekrar Fatura Bildirimi Gönder ##
if(isset($_GET['fatura_gonder']))
{
    TvERtXpE3w_panelislemkontrol();
    $id 		= $_GET['id'];

    if($_SESSION['rutbe'] == 0)
    {



        $fatura = $db->query("SELECT * FROM faturalar WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        $musteri = $db->query("SELECT * FROM uyeler WHERE id = '{$fatura['uyeid']}'")->fetch(PDO::FETCH_ASSOC);

        $baslik 			= $fatura['baslik'];
        $bitis_tarih	= $fatura["bitis_tarih"];
        $tutar			= $fatura["tutar"];
        $odenen_tarih	= $fatura["odenen_tarih"];
        $durum			= $fatura["durum"];
        $hizmet 			= $fatura['hizmet'];
        $aciklama		= $fatura["aciklama"];
        $tarih 			= $fatura['tarih'];
        $odeme_yontemi	= $fatura["odeme_yontemi"];
        $mail			= $fatura["mail"];
        $tarih			= date("Y-m-d",strtotime($tarih));
        $bitis_tarih	= date("Y-m-d",strtotime($bitis_tarih));
        $odenen_tarih	= date("Y-m-d H:i:s",strtotime($odenen_tarih));

        $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
        $bildirimkt 		= strtotime($kayitt);
        $bildirimt 		= strtotime($btarih);

        $sablon = $db->query("SELECT * FROM bildirim_sablonu WHERE id = '15'")->fetch(PDO::FETCH_ASSOC);
        $gelendegisken 	= explode(",", $sablon['degiskenler']);
        $panel_url		= url."hesabim.html";
        $gidendegisken	= [$musteri['ad']." ".$musteri['soyad'],$baslik,$aciklama,TvERtXpE3w_tarih($tarih),$tutar,TvERtXpE3w_tarih($bitis_tarih),$panel_url,$musteri['email'],$musteri['sifre'],$logo,$domain_bilgi];


            $last_id  = $db->lastInsertId();
                if($sablon["sbildirim"] == "1")
                {
                    $uyesmssablon = $sablon['icerik3'];
                    smsgonder($gelendegisken,$gidendegisken,$uyesmssablon,$musteri['telefon'],$uyesmssablon);
                }
                if($sablon["ysbildirim"] == "1")
                {
                    $adminsmssablon = $sablon['icerik4'];
                    smsgonder($gelendegisken,$gidendegisken,$adminsmssablon,sms_kime,$adminsmssablon);
                }
                if($sablon["ubildirim"] == "1")
                {
                    $uyekonu 	= $sablon['konu'];// TvERtXpE3w_turkce($sablon['konu']);
                    $uyesablon 	= $sablon['icerik'];
                    mailgonder($gelendegisken,$gidendegisken,$uyesablon,$musteri['email']," ".$uyekonu."",$uyesablon);
                }
                if($sablon["abildirim"] == "1")
                {
                    $adminkonu 	= $sablon['konu2'];//TvERtXpE3w_turkce($sablon['konu2']);
                    $adminsablon= $sablon['icerik2'];
                    mailgonder($gelendegisken,$gidendegisken,$adminsablon,m_kime," ".$adminkonu."",$adminsablon);
                }
            $_SESSION['fatura_gonder'] = 'yes';
            header("Location:../yonetim/fatura-duzenle/".$id.".html");
    }
    else
    {
        $_SESSION['fatura_gonder'] = 'no';
        header("Location:../yonetim/fatura-duzenle/".$id.".html");
    }
}



##Fatura Ekle ##
if(isset($_POST['fatura_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid 	= $_POST['uyeid'];
    if($_SESSION['rutbe'] == 0)
    {
        $baslik 			= $_POST['baslik'];
        $bitis_tarih	= $_POST["bitis_tarih"];
        $tutar			= $_POST["tutar"];
        $odenen_tarih	= $_POST["odenen_tarih"];
        $durum			= $_POST["durum"];
        $hizmet 			= $_POST['hizmet'];
        $aciklama		= $_POST["aciklama"];
        $tarih 			= $_POST['tarih'];
        $odeme_yontemi	= $_POST["odeme_yontemi"];
        $mail			= $_POST["mail"];
        $tarih			= date("Y-m-d",strtotime($tarih));
        $bitis_tarih	= date("Y-m-d",strtotime($bitis_tarih));
        $odenen_tarih	= date("Y-m-d H:i:s",strtotime($odenen_tarih));

        $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
        $bildirimkt 		= strtotime($kayitt);
        $bildirimt 		= strtotime($btarih);


        $musteri = $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);

        $sablon = $db->query("SELECT * FROM bildirim_sablonu WHERE id = '14'")->fetch(PDO::FETCH_ASSOC);
        $gelendegisken 	= explode(",", $sablon['degiskenler']);
        $panel_url		= url."hesabim.html";
        $gidendegisken	= [$musteri['ad']." ".$musteri['soyad'],$baslik,$aciklama,TvERtXpE3w_tarih($tarih),$tutar,TvERtXpE3w_tarih($bitis_tarih),$panel_url,$musteri['email'],$musteri['sifre'],$logo,$domain_bilgi];

        $sorgu = $db->prepare("INSERT INTO faturalar SET
				uyeid 			= ?,
				baslik 			= ?,
				bitis_tarih 	= ?,
				tutar 			= ?,
				odenen_tarih 	= ?,
				durum 			= ?,
				hizmet 			= ?,
				aciklama 		= ?,
				tarih 			= ?,				
				odeme_yontemi 	= ?,
				mail 			= ?");
        $Ekle = $sorgu->execute(array(
            $uyeid,
            $baslik,
            $bitis_tarih,
            $tutar,
            $odenen_tarih,
            $durum,
            $hizmet,
            $aciklama,
            $tarih,
            $odeme_yontemi,
            $mail
        ));
        if($Ekle)
        {
            $last_id  = $db->lastInsertId();

            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Yeni Fatura Oluşturuldu.",
                'icon' 		=> "icon-people",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$musteri['ad']." ".$musteri['soyad']."</strong> adında müşteriye fatura oluşturdu.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            if($mail == 1)
            {
                if($sablon["sbildirim"] == "1")
                {
                    $uyesmssablon = $sablon['icerik3'];
                    smsgonder($gelendegisken,$gidendegisken,$uyesmssablon,$musteri['telefon'],$uyesmssablon);
                }
                if($sablon["ysbildirim"] == "1")
                {
                    $adminsmssablon = $sablon['icerik4'];
                    smsgonder($gelendegisken,$gidendegisken,$adminsmssablon,sms_kime,$adminsmssablon);
                }
                if($sablon["ubildirim"] == "1")
                {
                    $uyekonu 	= $sablon['konu'];// TvERtXpE3w_turkce($sablon['konu']);
                    $uyesablon 	= $sablon['icerik'];
                    mailgonder($gelendegisken,$gidendegisken,$uyesablon,$musteri['email']," ".$uyekonu."",$uyesablon);
                }
                if($sablon["abildirim"] == "1")
                {
                    $adminkonu 	= $sablon['konu2'];//TvERtXpE3w_turkce($sablon['konu2']);
                    $adminsablon= $sablon['icerik2'];
                    mailgonder($gelendegisken,$gidendegisken,$adminsablon,m_kime," ".$adminkonu."",$adminsablon);
                }
            }
            $_SESSION['fatura_ekle'] = 'yes';
            header("Location:../yonetim/musteri-duzenle/".$uyeid.".html?islemler=faturalar");
        }
        else
        {
            $_SESSION['fatura_ekle'] = 'no';
            header("Location:../yonetim/fatura-ekle/".$uyeid.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/fatura-ekle/".$uyeid.".html");
    }
}

##Fatura Düzenle ##
if(isset($_POST['fatura_duzenle']))
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid 	= $_POST['uyeid'];
    $url 	= $_POST['url'];
    $id 		= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $baslik 			= $_POST['baslik'];
        $bitis_tarih	= $_POST["bitis_tarih"];
        $tutar			= $_POST["tutar"];
        $odenen_tarih	= $_POST["odenen_tarih"];
        $durum			= $_POST["durum"];
        $hizmet 			= $_POST['hizmet'];
        $aciklama		= $_POST["aciklama"];
        $tarih 			= $_POST['tarih'];
        $odeme_yontemi	= $_POST["odeme_yontemi"];
        $mail			= $_POST["mail"];
        $tarih			= date("Y-m-d H:i:s",strtotime($tarih));
        $bitis_tarih	= date("Y-m-d H:i:s",strtotime($bitis_tarih));
        $odenen_tarih	= date("Y-m-d H:i:s",strtotime($odenen_tarih));

        $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
        $bildirimkt 		= strtotime($kayitt);
        $bildirimt 		= strtotime($btarih);

        $musteri 		= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);
        $faturalar 		= $db->query("SELECT * FROM faturalar WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);

        if($faturalar['hizmet'] != 0) // eğer hizmeti varsa faturanın
        {
            $hizmet		= $db->query("SELECT * FROM satilanlar WHERE id=".$faturalar['hizmet'])->fetch(PDO::FETCH_ASSOC);
            if( date("d.m.Y",strtotime($bitis_tarih)) != date("d.m.Y",strtotime($faturalar['bitis_tarih'])) )// eğer bitiş tarihiyle oynamışsa
            {
                $db->query("UPDATE satilanlar SET bitis_tarih='".$bitis_tarih."' WHERE id=".$hizmet['id']);
            }
            else // eğer bitiş tarihiyle oynamamışsa.
            {
                if($hizmet['tipi'] == 0) // eğer tipi domain ise
                {
                    $asd	= $hizmet['zmnt'];
                    $asd	= ($asd == 0) ? 1 : $asd;
                    $yeni_tarih	= date("Y-m-d H:i:s",strtotime($hizmet['bitis_tarih']." +".$asd." year"));
                }
                else // diğerleri ise
                {
                    if($hizmet['zmnt'] == 0) // eğer hizmetin periyodu aylıksa
                    {
                        $yeni_tarih	= date("Y-m-d H:i:s",strtotime($hizmet['bitis_tarih']." +1 month"));
                    }
                    elseif($hizmet['zmnt'] == 1) // eğer hizmetin periyodu yıllıksa
                    {
                        $yeni_tarih	= date("Y-m-d H:i:s",strtotime($hizmet['bitis_tarih']." +1 year"));
                    }
                }

                $db->query("UPDATE satilanlar SET bitis_tarih='".$yeni_tarih."' WHERE id=".$hizmet['id']);
            }
        }

        $sorgu = $db->prepare("UPDATE faturalar SET
				baslik 			= ?,
				aciklama 		= ?,
				tutar 			= ?,
				tarih 			= ?,
				bitis_tarih 	= ?,
				durum 			= ?,
				odenen_tarih 	= ?,	
				odeme_yontemi 	= ?
				WHERE id 		= ?");
        $guncelle = $sorgu->execute(array(
            $baslik,
            $aciklama,
            $tutar,
            $tarih,
            $bitis_tarih,
            $durum,
            $odenen_tarih,
            $odeme_yontemi,
            $id
        ));
        if($guncelle)
        {
            $last_id  = $db->lastInsertId();

            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Fatura Düzenlendi",
                'icon' 		=> "icon-people",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$musteri['ad']." ".$musteri['soyad']."</strong> isimli müşterinin faturasını düzenledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['fatura_duzenle'] = 'yes';
            header("Location:".$url."");
        }
        else
        {
            $_SESSION['fatura_duzenle'] = 'no';
            header("Location:".$url."");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/fatura-duzenle/".$id.".html");
    }
}

##Fatura Sil##
if(@$_GET['faturasil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid = $_GET['uyeid'];
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $musteri_bul= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);
        $Sorgu = $db->prepare("DELETE FROM faturalar WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                $last_id 		= $id;
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 		= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Fatura Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$musteri_bul['ad']." ".$musteri_bul['soyad']."</strong> müşteriye ait faturayı sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['faturasil'] = 'yes';
                header("Location:../yonetim/".$_GET['url']."");
            }
            else
            {
                $_SESSION['faturasil'] = 'no';
                header("Location:../yonetim/".$_GET['url']."");
            }
        }
        else
        {
            exit;
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/".$_GET['url']."");
    }
}

##Fatura Toplu Sil ##
if(isset($_POST['fatura_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $fatura_bul= $db->query("SELECT * FROM faturalar WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $musteri_bul= $db->query("SELECT * FROM uyeler WHERE id = '{$fatura_bul['uyeid']}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM faturalar WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 		= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Fatura Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$musteri_bul['ad']." ".$musteri_bul['soyad']."</strong> müşteriye ait faturayı sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['fatura_tumu'] = 'yes';
                    header("Location:../yonetim/".$_POST['url']."");
                }
                else
                {
                    $_SESSION['fatura_tumu'] = 'no';
                    header("Location:../yonetim/".$_POST['url']."");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/".$_POST['url']."");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/".$_POST['url']."");
    }
}

##Ms Hosting Ekle ##
if(isset($_POST['ms_hosting_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid 	= $_POST['uyeid'];
    if($_SESSION['rutbe'] == 0)
    {
        $hosting 			= $_POST['hosting'];
        $domain 				= $_POST['domain'];
        $domain				= strtolower($domain);
        $domain				= str_replace("www.","",$domain);
        $bilgiler 			= $_POST['bilgiler'];
        $adres_bilgisi 		= $_POST['adres_bilgisi'];
        $tutar 				= $_POST['tutar'];
        $odeme_yontemi 		= $_POST['odeme_yontemi'];
        $otohesap 			= $_POST['otohesap'];
        $baslangic_tarih 	= $_POST['baslangic_tarih'];
        $bitis_tarih 		= $_POST['bitis_tarih'];
        $tarih				= date('Y-m-d H:i:s');
        $baslangic_tarih	= date("Y-m-d H:i:s",strtotime($baslangic_tarih));
        $bitis_tarih		= date("Y-m-d H:i:s",strtotime($bitis_tarih));

        $btarih				= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $kayitt				= TvERtXpE3w_tr_tarih('Y-m-d');
        $bildirimkt 			= strtotime($kayitt);
        $bildirimt 			= strtotime($btarih);

        $musteri 		= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);
        $host 			= $db->query("SELECT * FROM hostingler WHERE id = '{$hosting}'")->fetch(PDO::FETCH_ASSOC);

        $sorgu = $db->prepare("INSERT INTO satilanlar SET
				uyeid 			= ?,
				tipi 			= ?,
				durum 			= ?,
				domain 			= ?,
				bilgiler 		= ?,
				tutar 			= ?,
				baslangic_tarih = ?,
				bitis_tarih 	= ?,
				tarih 			= ?,				
				hosting 		= ?,				
				odenen_tarih 	= ?,				
				onaylanan_tarih = ?,				
				odeme_yontemi 	= ?,
				adres		 	= ?,
				zmnt 			= ?,
				hosting_baslik 	= ?,
				ozellikler 		= ?");
        $Ekle = $sorgu->execute(array(
            $uyeid,
            "1",
            "1",
            $domain,
            $bilgiler,
            $tutar,
            $baslangic_tarih,
            $bitis_tarih,
            $tarih,
            $hosting,
            $baslangic_tarih,
            $baslangic_tarih,
            $odeme_yontemi,
            $adres_bilgisi,
            $host['zmnt'],
            $host['adi'],
            $host['ozellikler']
        ));
        if($Ekle)
        {
            $last_id  = $db->lastInsertId();

            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Yeni Hosting Tanımlandı.",
                'icon' 		=> "icon-people",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$musteri['ad']." ".$musteri['soyad']."</strong> isimli müşteriye hosting tanımlandı.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));

            $aciklama		= explode(",",$host['ozellikler']);
            $aciklama		= implode("<br />",$aciklama);
            $bslk			= $host['adi'];
            $bslk			.= ($domain == '') ? '' : ' ('.$domain.')';
            $FATURASorgu = $db->prepare("INSERT INTO faturalar SET
				uyeid			= :uyeid,
				baslik			= :baslik,
				aciklama		= :aciklama,
				tutar			= :tutar,
				tarih			= :tarih,
				bitis_tarih		= :bitis_tarih,
				durum			= :durum,
				hizmet			= :hizmet,
				odenen_tarih	= :odenen_tarih,
				odeme_yontemi 	= :odeme_yontemi");
            $FATURASorgu->execute(array(
                'uyeid' 			=> $uyeid,
                'baslik' 		=> $bslk,
                'aciklama' 		=> $aciklama,
                'tutar' 			=> $tutar,
                'tarih' 			=> $tarih,
                'bitis_tarih'	=> $tarih,
                'durum' 			=> "1",
                'hizmet'		=> $last_id,
                'odenen_tarih'	=> $tarih,
                'odeme_yontemi'	=> $odeme_yontemi
            ));

            $_SESSION['ms_hosting_ekle'] = 'yes';
            header("Location:../yonetim/musteri-duzenle/".$uyeid.".html?islemler=hostingler");
        }
        else
        {
            $_SESSION['ms_hosting_ekle'] = 'no';
            header("Location:../yonetim/ms-hosting-ekle/".$uyeid.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/ms-hosting-ekle/".$uyeid.".html");
    }
}

##Ms Hosting Düzenle ##
if(isset($_POST['ms_hosting_duzenle']))
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid 	= $_POST['uyeid'];
    $url 	= $_POST['url'];
    $id 		= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $domain				= $_POST["domain"];
        $hosting			= $_POST["hosting"];
        $odeme_yontemi		= $_POST["odeme_yontemi"];
        $durum				= $_POST["durum"];
        $sdurum				= $_POST["sdurum"];
        $bilgiler			= $_POST["bilgiler"];
        $adres_bilgisi		= $_POST["adres_bilgisi"];
        $tutar				= $_POST["tutar"];
        $ozellikler			= $_POST["ozellikler"];
        $ozellikler			= explode("\n",$ozellikler);
        $ozellikler			= implode(",",$ozellikler);
        $baslangic_tarih 	= $_POST["baslangic_tarih"];
        $bitis_tarih		= $_POST["bitis_tarih"];
        $odenen_tarih		= $_POST["odenen_tarih"];
        $onaylanan_tarih	= $_POST["onaylanan_tarih"];
        $baslangic_tarih	= date("Y-m-d H:i:s",strtotime($baslangic_tarih));
        $bitis_tarih		= date("Y-m-d H:i:s",strtotime($bitis_tarih));
        $odenen_tarih		= date("Y-m-d H:i:s",strtotime($odenen_tarih));
        $onaylanan_tarih	= date("Y-m-d H:i:s",strtotime($onaylanan_tarih));

        $btarih				= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $kayitt				= TvERtXpE3w_tr_tarih('Y-m-d');
        $bildirimkt 			= strtotime($kayitt);
        $bildirimt 			= strtotime($btarih);

        $musteri 		    = $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);
        $satilanlar 		= $db->query("SELECT * FROM satilanlar WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);

        if($satilanlar['durum'] == 0 AND $durum == 1 AND $sdurum == 0)// eğer durumu onaysızsa
        {
            // Fatura oluşturuyoruz...
            $faturatarih	= date('Y-m-d H:i:s');
            $aciklama		= explode(",",$satilanlar['ozellikler']);
            $aciklama		= implode("<br />",$aciklama);
            $bslk			= $satilanlar['hosting_baslik'];
            $bslk			.= ($satilanlar['domain'] == '') ? '' : ' ('.$satilanlar['domain'].')';

            $ekle = $db->prepare("INSERT INTO faturalar SET
				uyeid 			= ?,
				baslik 			= ?,
				aciklama 		= ?,
				tutar 			= ?,
				tarih 			= ?,
				bitis_tarih 	= ?,
				durum 			= ?,
				hizmet 			= ?,
				odenen_tarih 	= ?,				
				odeme_yontemi 	= ?");
            $ekle->execute(array(
                $uyeid,
                $bslk,
                $aciklama,
                $tutar,
                $faturatarih,
                $faturatarih,
                1,
                $satilanlar['id'],
                $faturatarih,
                $odeme_yontemi
            ));
        }

        $sorgu = $db->prepare("UPDATE satilanlar SET
				durum 			= ?,
				domain 			= ?,
				bilgiler 		= ?,
				tutar 			= ?,
				baslangic_tarih = ?,
				bitis_tarih 	= ?,
				hosting 		= ?,	
				odenen_tarih 	= ?,	
				onaylanan_tarih = ?,	
				odeme_yontemi 	= ?,	
				adres 			= ?,	
				sdurum 			= ?,	
				ozellikler 		= ?
				WHERE id 		= ?");
        $guncelle = $sorgu->execute(array(
            $durum,
            $domain,
            $bilgiler,
            $tutar,
            $baslangic_tarih,
            $bitis_tarih,
            $hosting,
            $odenen_tarih,
            $onaylanan_tarih,
            $odeme_yontemi,
            $adres_bilgisi,
            $sdurum,
            $ozellikler,
            $id
        ));
        if($guncelle)
        {
            $last_id  = $db->lastInsertId();

            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Müşteri Hosting Hesabı Düzenlendi",
                'icon' 		=> "icon-people",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$musteri['ad']." ".$musteri['soyad']."</strong> isimli müşterinin hosting hesabını düzenledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $sablon 			= $db->query("SELECT * FROM bildirim_sablonu WHERE id = '7'")->fetch(PDO::FETCH_ASSOC);
            $gelendegisken 	= explode(",", $sablon['degiskenler']);
            $musteri_paneli_url	= url."hesabim.html";
            $gidendegisken	= [$musteri['ad']." ".$musteri['soyad'],TvERtXpE3w_tarih($onaylanan_tarih),$bslk,$musteri_paneli_url,$musteri['email'],$musteri['sifre'],$logo,$domain_bilgi];

            if($sablon["sbildirim"] == "1")
            {
                $uyesmssablon2 = $sablon['icerik3'];
                smsgonder($gelendegisken,$gidendegisken,$uyesmssablon2,$musteri['telefon'],$uyesmssablon2);
            }
            if($sablon["ysbildirim"] == "1")
            {
                $adminsmssablon2 = $sablon['icerik4'];
                smsgonder($gelendegisken,$gidendegisken,$adminsmssablon2,sms_kime,$adminsmssablon2);
            }
            if($sablon["ubildirim"] == "1")
            {
                $uyekonu2 	= $sablon['konu'];// TvERtXpE3w_turkce($sablon['konu']);
                $uyesablon2 	= $sablon['icerik'];
                mailgonder($gelendegisken,$gidendegisken,$uyesablon2,$musteri['email']," ".$uyekonu2."",$uyesablon2);
            }
            if($sablon["abildirim"] == "1")
            {
                $adminkonu2 	= $sablon['konu2'];// TvERtXpE3w_turkce($sablon['konu2']);
                $adminsablon2= $sablon['icerik2'];
                mailgonder($gelendegisken,$gidendegisken,$adminsablon2,m_kime," ".$adminkonu2."",$adminsablon2);
            }
            $_SESSION['ms_hosting_duzenle'] = 'yes';
            header("Location:".$url."");
        }
        else
        {
            $_SESSION['ms_hosting_duzenle'] = 'no';
            header("Location:".$url."");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/ms-hosting-duzenle/".$id.".html");
    }
}

##Alanadı Ekle ##
if(isset($_POST['alanadi_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid 	= $_POST['uyeid'];
    if($_SESSION['rutbe'] == 0)
    {
        $domain 				= $_POST['domain'];
        $domain				= strtolower($domain);
        $domain				= str_replace("www.","",$domain);
        $bilgiler 			= $_POST['bilgiler'];
        $adres_bilgisi 		= $_POST['adres_bilgisi'];
        $tutar 				= $_POST['tutar'];
        $odeme_yontemi 		= $_POST['odeme_yontemi'];
        $baslangic_tarih 	= $_POST['baslangic_tarih'];
        $bitis_tarih 		= $_POST['bitis_tarih'];
        $tarih				= date('Y-m-d H:i:s');
        $baslangic_tarih	= date("Y-m-d H:i:s",strtotime($baslangic_tarih));
        $bitis_tarih		= date("Y-m-d H:i:s",strtotime($bitis_tarih));

        $btarih				= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $kayitt				= TvERtXpE3w_tr_tarih('Y-m-d');
        $bildirimkt 			= strtotime($kayitt);
        $bildirimt 			= strtotime($btarih);

        $musteri 		= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);

        $sorgu = $db->prepare("INSERT INTO satilanlar SET
				uyeid 			= ?,
				tipi 			= ?,
				durum 			= ?,
				domain 			= ?,
				bilgiler 		= ?,
				adres 			= ?,
				tutar 			= ?,
				baslangic_tarih = ?,
				bitis_tarih 	= ?,
				tarih 			= ?,				
				zmnt 			= ?");
        $Ekle = $sorgu->execute(array(
            $uyeid,
            "0",
            "1",
            $domain,
            $bilgiler,
            $adres_bilgisi,
            $tutar,
            $baslangic_tarih,
            $bitis_tarih,
            $tarih,
            "1"
        ));
        if($Ekle)
        {
            $last_id  = $db->lastInsertId();

            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Yeni Alanadı Tanımlandı.",
                'icon' 		=> "icon-people",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$musteri['ad']." ".$musteri['soyad']."</strong> isimli müşteriye alanadı tanımlandı.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $FATURASorgu = $db->prepare("INSERT INTO faturalar SET
				uyeid			= :uyeid,
				baslik			= :baslik,
				aciklama		= :aciklama,
				tutar			= :tutar,
				tarih			= :tarih,
				bitis_tarih		= :bitis_tarih,
				durum			= :durum,
				hizmet			= :hizmet,
				odenen_tarih	= :odenen_tarih,
				odeme_yontemi 	= :odeme_yontemi");
            $FATURASorgu->execute(array(
                'uyeid' 			=> $uyeid,
                'baslik' 		=> $domain.' Alan Adı (Yeni Sipariş)',
                'aciklama' 		=> "",
                'tutar' 			=> $tutar,
                'tarih' 			=> $tarih,
                'bitis_tarih'	=> $tarih,
                'durum' 			=> "1",
                'hizmet'		=> $last_id,
                'odenen_tarih'	=> $tarih,
                'odeme_yontemi'	=> "Banka Havale/EFT"
            ));

            $_SESSION['alanadi_ekle'] = 'yes';
            header("Location:../yonetim/musteri-duzenle/".$uyeid.".html?islemler=alan-adlari");
        }
        else
        {
            $_SESSION['alanadi_ekle'] = 'no';
            header("Location:../yonetim/alanadi-ekle/".$uyeid.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/alanadi-ekle/".$uyeid.".html");
    }
}

##Alanadı Düzenle ##
if(isset($_POST['alanadi_duzenle']))
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid 	= $_POST['uyeid'];
    $url 	= $_POST['url'];
    $id 		= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $domain				= $_POST["domain"];
        $bilgiler			= $_POST["bilgiler"];
        $odeme_yontemi		= $_POST["odeme_yontemi"];
        $durum				= $_POST["durum"];
        $sdurum				= $_POST["sdurum"];
        $bilgiler			= $_POST["bilgiler"];
        $adres_bilgisi		= $_POST["adres_bilgisi"];
        $tutar				= $_POST["tutar"];
        $baslangic_tarih 	= $_POST["baslangic_tarih"];
        $bitis_tarih		= $_POST["bitis_tarih"];
        $odenen_tarih		= $_POST["odenen_tarih"];
        $onaylanan_tarih	= $_POST["onaylanan_tarih"];
        $zmnt				= $_POST["zmnt"];

        $btarih				= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $kayitt				= TvERtXpE3w_tr_tarih('Y-m-d');
        $bildirimkt 			= strtotime($kayitt);
        $bildirimt 			= strtotime($btarih);

        $musteri 		= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);
        $satilanlar 		= $db->query("SELECT * FROM satilanlar WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);

        if($zmnt != $satilanlar['zmnt'] AND date("d.m.Y",strtotime($satilanlar['bitis_tarih'])) == $bitis_tarih)
        {
            $bitis_tarih	= date("Y-m-d H:i:s",strtotime('+'.$zmnt.' year'));
            $db->query("UPDATE satilanlar SET zmnt='".$zmnt."',bitis_tarih='".$bitis_tarih."' WHERE id=".$satilanlar['id']);
        }
        elseif($zmnt == $satilanlar['zmnt'] AND date("d.m.Y",strtotime($satilanlar['bitis_tarih'])) != $bitis_tarih)
        {
            $bitis_tarih	= date("Y-m-d H:i:s",strtotime($bitis_tarih));
            $db->query("UPDATE satilanlar SET bitis_tarih='".$bitis_tarih."' WHERE id=".$satilanlar['id']);
        }
        elseif($zmnt != $satilanlar['zmnt'] AND date("d.m.Y",strtotime($satilanlar['bitis_tarih'])) != $bitis_tarih)
        {
            $bitis_tarih		= date("Y-m-d H:i:s",strtotime($bitis_tarih));
            $db->query("UPDATE satilanlar SET zmnt='".$zmnt."',bitis_tarih='".$bitis_tarih."' WHERE id=".$satilanlar['id']);
        }
        else
        {
            $bitis_tarih		= date("Y-m-d H:i:s",strtotime($bitis_tarih));
        }
        $baslangic_tarih	= date("Y-m-d H:i:s",strtotime($baslangic_tarih));
        $odenen_tarih		= date("Y-m-d H:i:s",strtotime($odenen_tarih));
        $onaylanan_tarih	= date("Y-m-d H:i:s",strtotime($onaylanan_tarih));

        if($satilanlar['durum'] == 0 AND $durum == 1 AND $sdurum == 0)// eğer durumu onaysızsa
        {
            // Fatura oluşturuyoruz...
            $faturatarih	= date('Y-m-d H:i:s');
            $aciklama 		= '';
            $bslk			= $satilanlar['domain'].' ('.$satilanlar['zmnt'].' Yıllık)';

            $ekle = $db->prepare("INSERT INTO faturalar SET
				uyeid 			= ?,
				baslik 			= ?,
				aciklama 		= ?,
				tutar 			= ?,
				tarih 			= ?,
				bitis_tarih 	= ?,
				durum 			= ?,
				hizmet 			= ?,
				odenen_tarih 	= ?,				
				odeme_yontemi 	= ?");
            $ekle->execute(array(
                $uyeid,
                $bslk,
                $aciklama,
                $tutar,
                $faturatarih,
                $faturatarih,
                "1",
                $satilanlar['id'],
                $faturatarih,
                $odeme_yontemi
            ));
        }

        $sorgu = $db->prepare("UPDATE satilanlar SET
				domain 			= ?,
				bilgiler 		= ?,
				adres 			= ?,
				tutar 			= ?,
				baslangic_tarih = ?,
				bitis_tarih 	= ?,	
				odenen_tarih 	= ?,	
				onaylanan_tarih = ?,	
				odeme_yontemi 	= ?,	
				sdurum 			= ?,	
				durum 			= ?,	
				zmnt 			= ?
				WHERE id 		= ?");
        $guncelle = $sorgu->execute(array(
            $domain,
            $bilgiler,
            $adres_bilgisi,
            $tutar,
            $baslangic_tarih,
            $bitis_tarih,
            $odenen_tarih,
            $onaylanan_tarih,
            $odeme_yontemi,
            $sdurum,
            $durum,
            $zmnt,
            $id
        ));
        if($guncelle)
        {
            $last_id  = $db->lastInsertId();

            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Müşterinin alan adı bilgisi düzenlendi",
                'icon' 		=> "icon-people",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$musteri['ad']." ".$musteri['soyad']."</strong> isimli müşterinin alanadı bilgisini düzenledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $sablon 			= $db->query("SELECT * FROM bildirim_sablonu WHERE id = '7'")->fetch(PDO::FETCH_ASSOC);
            $gelendegisken 	= explode(",", $sablon['degiskenler']);
            $musteri_paneli_url	= url."hesabim.html";
            $gidendegisken	= [$musteri['ad']." ".$musteri['soyad'],TvERtXpE3w_tarih($onaylanan_tarih),$domain,$musteri_paneli_url,$musteri['email'],$musteri['sifre'],$logo,$domain_bilgi];

            if($sablon["sbildirim"] == "1")
            {
                $uyesmssablon2 = $sablon['icerik3'];
                smsgonder($gelendegisken,$gidendegisken,$uyesmssablon2,$musteri['telefon'],$uyesmssablon2);
            }
            if($sablon["ysbildirim"] == "1")
            {
                $adminsmssablon2 = $sablon['icerik4'];
                smsgonder($gelendegisken,$gidendegisken,$adminsmssablon2,sms_kime,$adminsmssablon2);
            }
            if($sablon["ubildirim"] == "1")
            {
                $uyekonu2 	= $sablon['konu'];// TvERtXpE3w_turkce($sablon['konu']);
                $uyesablon2 	= $sablon['icerik'];
                mailgonder($gelendegisken,$gidendegisken,$uyesablon2,$musteri['email']," ".$uyekonu2."",$uyesablon2);
            }
            if($sablon["abildirim"] == "1")
            {
                $adminkonu2 	= $sablon['konu2']; //TvERtXpE3w_turkce($sablon['konu2']);
                $adminsablon2= $sablon['icerik2'];
                mailgonder($gelendegisken,$gidendegisken,$adminsablon2,m_kime," ".$adminkonu2."",$adminsablon2);
            }
            $_SESSION['alanadi_duzenle'] = 'yes';
            header("Location:".$url."");
        }
        else
        {
            $_SESSION['alanadi_duzenle'] = 'no';
            header("Location:".$url."");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/alanadi-duzenle/".$id.".html");
    }
}

##Ms Web Paketi Ekle ##
if(isset($_POST['ms_webpaket_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid 	= $_POST['uyeid'];
    if($_SESSION['rutbe'] == 0)
    {
        $pakt				= $_POST["paket"];
        $domain				= $_POST["domain"];
        $download			= $_POST["download"];
        $tutar				= $_POST["tutar"];
        $adres_bilgisi		= $_POST["adres_bilgisi"];
        $tarih			 	= date("Y-m-d H:i:s");
        $baslangic_tarih 	= $_POST["baslangic_tarih"];
        $baslangic_tarih	= date("Y-m-d H:i:s",strtotime($baslangic_tarih));
        $odeme_yontemi		= $_POST["odeme_yontemi"];

        $btarih				= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $kayitt				= TvERtXpE3w_tr_tarih('Y-m-d');
        $bildirimkt 			= strtotime($kayitt);
        $bildirimt 			= strtotime($btarih);

        $musteri 		= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);
        $paket 			= $db->query("SELECT * FROM yazilimlar WHERE id = '{$pakt}'")->fetch(PDO::FETCH_ASSOC);

        $sorgu = $db->prepare("INSERT INTO satilanlar SET
				uyeid 			= ?,
				tipi 			= ?,
				durum 			= ?,
				domain 			= ?,
				download 		= ?,
				tutar 			= ?,
				baslangic_tarih = ?,
				tarih 			= ?,				
				paket 			= ?,				
				adres 			= ?,				
				odenen_tarih 	= ?,				
				onaylanan_tarih = ?,				
				odeme_yontemi 	= ?,
				paket_baslik 	= ?");
        $Ekle = $sorgu->execute(array(
            $uyeid,
            "2",
            "1",
            $domain,
            $download,
            $tutar,
            $baslangic_tarih,
            $tarih,
            $pakt,
            $adres_bilgisi,
            $baslangic_tarih,
            $baslangic_tarih,
            $odeme_yontemi,
            $paket['adi']
        ));
        if($Ekle)
        {
            $last_id  = $db->lastInsertId();

            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Yeni Web Paketi Tanımlandı.",
                'icon' 		=> "icon-people",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$musteri['ad']." ".$musteri['soyad']."</strong> isimli müşteriye web paketi tanımlandı.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));

            $aciklama		= '';
            $bslk			= $paket['adi'];
            $bslk			.= ($domain == '') ? '' : ' ('.$domain.')';
            $FATURASorgu = $db->prepare("INSERT INTO faturalar SET
				uyeid			= :uyeid,
				baslik			= :baslik,
				aciklama		= :aciklama,
				tutar			= :tutar,
				tarih			= :tarih,
				bitis_tarih		= :bitis_tarih,
				durum			= :durum,
				hizmet			= :hizmet,
				odenen_tarih	= :odenen_tarih,
				odeme_yontemi 	= :odeme_yontemi");
            $FATURASorgu->execute(array(
                'uyeid' 			=> $uyeid,
                'baslik' 		=> $bslk,
                'aciklama' 		=> $aciklama,
                'tutar' 			=> $tutar,
                'tarih' 			=> $tarih,
                'bitis_tarih'	=> $tarih,
                'durum' 			=> "1",
                'hizmet'		=> $last_id,
                'odenen_tarih'	=> $tarih,
                'odeme_yontemi'	=> $odeme_yontemi
            ));

            $_SESSION['ms_webpaket_ekle'] = 'yes';
            header("Location:../yonetim/musteri-duzenle/".$uyeid.".html?islemler=web-paketleri");
        }
        else
        {
            $_SESSION['ms_webpaket_ekle'] = 'no';
            header("Location:../yonetim/ms-web-paket-ekle/".$uyeid.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/ms-web-paket-ekle/".$uyeid.".html");
    }
}

##Ms Web Paketi Düzenle ##
if(isset($_POST['ms_webpaket_duzenle']))
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid 	= $_POST['uyeid'];
    $url 	= $_POST['url'];
    $id 		= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $domain				= $_POST["domain"];
        $pakt				= $_POST["paket"];
        $odeme_yontemi		= $_POST["odeme_yontemi"];
        $durum				= $_POST["durum"];
        $tutar				= $_POST["tutar"];
        $adres_bilgisi		= $_POST["adres_bilgisi"];
        $baslangic_tarih 	= $_POST["baslangic_tarih"];
        $odenen_tarih		= $_POST["odenen_tarih"];
        $onaylanan_tarih	= $_POST["onaylanan_tarih"];
        $download			= $_POST["download"];
        $baslangic_tarih	= date("Y-m-d H:i:s",strtotime($baslangic_tarih));
        $odenen_tarih		= date("Y-m-d H:i:s",strtotime($odenen_tarih));
        $onaylanan_tarih	= date("Y-m-d H:i:s",strtotime($onaylanan_tarih));

        $btarih				= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $kayitt				= TvERtXpE3w_tr_tarih('Y-m-d');
        $bildirimkt 			= strtotime($kayitt);
        $bildirimt 			= strtotime($btarih);

        $musteri 		= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);
        $satilanlar 		= $db->query("SELECT * FROM satilanlar WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        $paket 			= $db->query("SELECT * FROM yazilimlar WHERE id = '{$pakt}'")->fetch(PDO::FETCH_ASSOC);

        if($paket['adi'] != '' AND $satilanlar['paket_baslik'] != $paket['adi'])
        {
            $a		= $db->prepare("UPDATE satilanlar SET paket_baslik=? WHERE id=".$satilanlar['id']);
            $a->execute(array($paket['adi']));
        }

        if($satilanlar['durum'] == 0 AND $durum == 1)// eğer durumu onaysızsa
        {
            // Fatura oluşturuyoruz...
            $faturatarih	= date('Y-m-d H:i:s');
            $aciklama		= '';
            $bslk			= $satilanlar['paket_baslik'];
            $bslk			.= ($satilanlar['domain'] == '') ? '' : ' ('.$satilanlar['domain'].')';

            $ekle = $db->prepare("INSERT INTO faturalar SET
				uyeid 			= ?,
				baslik 			= ?,
				aciklama 		= ?,
				tutar 			= ?,
				tarih 			= ?,
				bitis_tarih 	= ?,
				durum 			= ?,
				hizmet 			= ?,
				odenen_tarih 	= ?,				
				odeme_yontemi 	= ?");
            $ekle->execute(array(
                $uyeid,
                $bslk,
                $aciklama,
                $tutar,
                $faturatarih,
                $faturatarih,
                "1",
                $satilanlar['id'],
                $faturatarih,
                $odeme_yontemi
            ));
        }

        $sorgu = $db->prepare("UPDATE satilanlar SET
				durum 			= ?,
				domain 			= ?,
				download 		= ?,
				tutar 			= ?,
				baslangic_tarih = ?,
				paket 			= ?,	
				adres 			= ?,	
				odenen_tarih 	= ?,	
				onaylanan_tarih = ?,	
				odeme_yontemi 	= ?
				WHERE id 		= ?");
        $guncelle = $sorgu->execute(array(
            $durum,
            $domain,
            $download,
            $tutar,
            $baslangic_tarih,
            $pakt,
            $adres_bilgisi,
            $odenen_tarih,
            $onaylanan_tarih,
            $odeme_yontemi,
            $id
        ));
        if($guncelle)
        {
            $last_id  = $db->lastInsertId();

            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Müşteri Web Paketi Hesabı Düzenlendi",
                'icon' 		=> "icon-people",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$musteri['ad']." ".$musteri['soyad']."</strong> isimli müşterinin web paketi hesabını düzenledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $sablon 			= $db->query("SELECT * FROM bildirim_sablonu WHERE id = '7'")->fetch(PDO::FETCH_ASSOC);
            $gelendegisken 	= explode(",", $sablon['degiskenler']);
            $musteri_paneli_url	= url."hesabim.html";
            $gidendegisken	= [$musteri['ad']." ".$musteri['soyad'],TvERtXpE3w_tarih($onaylanan_tarih),$paket['adi'],$musteri_paneli_url,$musteri['email'],$musteri['sifre'],$logo,$domain_bilgi];

            if($sablon["sbildirim"] == "1")
            {
                $uyesmssablon2 = $sablon['icerik3'];
                smsgonder($gelendegisken,$gidendegisken,$uyesmssablon2,$musteri['telefon'],$uyesmssablon2);
            }
            if($sablon["ysbildirim"] == "1")
            {
                $adminsmssablon2 = $sablon['icerik4'];
                smsgonder($gelendegisken,$gidendegisken,$adminsmssablon2,sms_kime,$adminsmssablon2);
            }
            if($sablon["ubildirim"] == "1")
            {
                $uyekonu2 	= $sablon['konu'];//TvERtXpE3w_turkce($sablon['konu']);
                $uyesablon2 	= $sablon['icerik'];
                mailgonder($gelendegisken,$gidendegisken,$uyesablon2,$musteri['email']," ".$uyekonu2."",$uyesablon2);
            }
            if($sablon["abildirim"] == "1")
            {
                $adminkonu2 	= $sablon['konu2'];//TvERtXpE3w_turkce($sablon['konu2']);
                $adminsablon2= $sablon['icerik2'];
                mailgonder($gelendegisken,$gidendegisken,$adminsablon2,m_kime," ".$adminkonu2."",$adminsablon2);
            }
            $_SESSION['ms_webpaket_duzenle'] = 'yes';
            header("Location:".$url."");
        }
        else
        {
            $_SESSION['ms_webpaket_duzenle'] = 'no';
            header("Location:".$url."");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/ms-web-paket-duzenle/".$id.".html");
    }
}

##Satılanları Sil##
if(@$_GET['satilanlar'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid = $_GET['uyeid'];
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $musteri_bul= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);
        $Sorgu = $db->prepare("DELETE FROM satilanlar WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                $TopluSorgu = $db->prepare("SELECT * FROM faturalar WHERE hizmet = ?");
                $TopluSorgu->execute(array($_GET['id']));
                $Topluislem = $TopluSorgu->fetchALL(PDO::FETCH_ASSOC);
                foreach ( $Topluislem as $TopluSonuc )
                {
                    $TSorgu = $db->prepare("DELETE FROM faturalar WHERE id = :id");
                    $TSorgu->execute(array('id' => $TopluSonuc['id']));
                }
                $last_id 		= $id;
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 		= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Kayıt Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$musteri_bul['ad']." ".$musteri_bul['soyad']."</strong> müşteriye ait kaydı sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['satilanlar'] = 'yes';
                header("Location:../yonetim/".$_GET['url']."");
            }
            else
            {
                $_SESSION['satilanlar'] = 'no';
                header("Location:../yonetim/".$_GET['url']."");
            }
        }
        else
        {
            exit;
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/".$_GET['url']."");
    }
}

##Satılanları Toplu Sil ##
if(isset($_POST['satilanlar_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $satilan_bul= $db->query("SELECT * FROM satilanlar WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $musteri_bul= $db->query("SELECT * FROM uyeler WHERE id = '{$satilan_bul['uyeid']}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM satilanlar WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    $TTopluSorgu = $db->prepare("SELECT * FROM faturalar WHERE hizmet = ?");
                    $TTopluSorgu->execute(array($i));
                    $TTopluislem = $TTopluSorgu->fetchALL(PDO::FETCH_ASSOC);
                    foreach ( $TTopluislem as $TTopluSonuc )
                    {
                        $TSorgu = $db->prepare("DELETE FROM faturalar WHERE id = :id");
                        $TSorgu->execute(array('id' => $TTopluSonuc['id']));
                    }
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 		= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Kayıt Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$musteri_bul['ad']." ".$musteri_bul['soyad']."</strong> müşteriye ait kaydı sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['satilanlar_tumu'] = 'yes';
                    header("Location:../yonetim/".$_POST['url']."");
                }
                else
                {
                    $_SESSION['satilanlar_tumu'] = 'no';
                    header("Location:../yonetim/".$_POST['url']."");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/".$_POST['url']."");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/".$_POST['url']."");
    }
}

##Ms Hizmet Ekle ##
if(isset($_POST['ms_hizmet_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid 	= $_POST['uyeid'];
    if($_SESSION['rutbe'] == 0)
    {
        $baslik			= $_POST["baslik"];
        $icerik			= $_POST["icerik"];
        $tutar			= $_POST["tutar"];
        $durum			= $_POST["durum"];
        $tarih			= date("Y-m-d H:i:s");

        $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
        $bildirimkt 		= strtotime($kayitt);
        $bildirimt 		= strtotime($btarih);

        $upload = new upload($_FILES['dosya']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/dosyalar");
            if ($upload->processed)
            {
                $Dosya=''.$upload->file_dst_name.'';
            }
        }
        $gitti=$Dosya=''.$upload->file_dst_name.'';

        $musteri 		= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);

        $sorgu = $db->prepare("INSERT INTO musteri_hizmetler SET
				uyeid 			= ?,
				baslik 			= ?,
				icerik 			= ?,
				dosya 			= ?,
				tutar 			= ?,
				durum 			= ?,
				tarih 			= ?");
        $Ekle = $sorgu->execute(array(
            $uyeid,
            $baslik,
            $icerik,
            $Dosya,
            $tutar,
            $durum,
            $tarih
        ));
        if($Ekle)
        {
            $last_id  = $db->lastInsertId();

            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Yeni Hizmet Tanımlandı.",
                'icon' 		=> "icon-people",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$musteri['ad']." ".$musteri['soyad']."</strong> isimli müşteriye hizmet tanımlandı.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));

            $_SESSION['ms_hizmet_ekle'] = 'yes';
            header("Location:../yonetim/musteri-duzenle/".$uyeid.".html?islemler=hizmetler");
        }
        else
        {
            $_SESSION['ms_hizmet_ekle'] = 'no';
            header("Location:../yonetim/ms-hizmet-ekle/".$uyeid.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/ms-hizmet-ekle/".$uyeid.".html");
    }
}

##Ms Hizmet Düzenle ##
if(isset($_POST['ms_hizmet_duzenle']))
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid 	= $_POST['uyeid'];
    $url 	= $_POST['url'];
    $id 		= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $baslik				= $_POST["baslik"];
        $icerik				= $_POST["icerik"];
        $tutar				= $_POST["tutar"];
        $durum 				= $_POST["durum"];

        $btarih				= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $kayitt				= TvERtXpE3w_tr_tarih('Y-m-d');
        $bildirimkt 			= strtotime($kayitt);
        $bildirimt 			= strtotime($btarih);

        $upload = new upload($_FILES['dosya']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/dosyalar");
            if ($upload->processed)
            {
                $Dosya=''.$upload->file_dst_name.'';
            }
        }

        if(isset($Dosya)){
            $dosya_bul= $db->query("SELECT * FROM musteri_hizmetler WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/dosyalar/".$dosya_bul['dosya']);
            $guncelle = $db->prepare("UPDATE musteri_hizmetler SET dosya = ? WHERE id = ?");
            $guncelle->execute([$Dosya,$id]);
            $Dosya=''.$upload->file_dst_name.'';
        }

        $musteri 		= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);

        $sorgu = $db->prepare("UPDATE musteri_hizmetler SET
				baslik 			= ?,
				icerik 			= ?,
				tutar 			= ?,	
				durum 			= ?
				WHERE id 		= ?");
        $guncelle = $sorgu->execute(array(
            $baslik,
            $icerik,
            $tutar,
            $durum,
            $id
        ));
        if($guncelle)
        {
            $last_id  = $db->lastInsertId();

            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Müşterinin alan hizmeti düzenlendi",
                'icon' 		=> "icon-people",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$musteri['ad']." ".$musteri['soyad']."</strong> isimli müşterinin hizmetini düzenledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['ms_hizmet_duzenle'] = 'yes';
            header("Location:".$url."");
        }
        else
        {
            $_SESSION['ms_hizmet_duzenle'] = 'no';
            header("Location:".$url."");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/ms-hizmet-duzenle/".$id.".html");
    }
}

##Ms Hizmet Sil##
if(@$_GET['mshizmetsil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid = $_GET['uyeid'];
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $musteri_bul= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);
        $Sorgu = $db->prepare("DELETE FROM musteri_hizmetler WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                unlink("../".tema."/uploads/dosyalar/".$hizmet_bul['dosya']);
                $last_id 		= $id;
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 		= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Müşteri Hizmeti Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$musteri_bul['ad']." ".$musteri_bul['soyad']."</strong> müşteriye ait hizmeti sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['mshizmetsil'] = 'yes';
                header("Location:../yonetim/".$_GET['url']."");
            }
            else
            {
                $_SESSION['mshizmetsil'] = 'no';
                header("Location:../yonetim/".$_GET['url']."");
            }
        }
        else
        {
            exit;
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/".$_GET['url']."");
    }
}

##Ms Hizmet Toplu Sil ##
if(isset($_POST['ms_hizmet_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $hizmet_bul= $db->query("SELECT * FROM musteri_hizmetler WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $musteri_bul= $db->query("SELECT * FROM uyeler WHERE id = '{$hizmet_bul['uyeid']}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM musteri_hizmetler WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    unlink("../".tema."/uploads/dosyalar/".$hizmet_bul['dosya']);
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 		= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Müşteri Hizmeti Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$musteri_bul['ad']." ".$musteri_bul['soyad']."</strong> müşteriye ait hizmeti sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['ms_hizmet_tumu'] = 'yes';
                    header("Location:../yonetim/".$_POST['url']."");
                }
                else
                {
                    $_SESSION['ms_hizmet_tumu'] = 'no';
                    header("Location:../yonetim/".$_POST['url']."");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/".$_POST['url']."");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/".$_POST['url']."");
    }
}

##Ms Sözleşme Ekle ##
if(isset($_POST['ms_sozlesme_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid 	= $_POST['uyeid'];
    if($_SESSION['rutbe'] == 0)
    {
        $baslik			= $_POST["baslik"];
        $icerik			= $_POST["icerik"];
        $tutar			= $_POST["tutar"];
        $durum			= $_POST["durum"];
        $tarih			= date("Y-m-d H:i:s");

        $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
        $bildirimkt 		= strtotime($kayitt);
        $bildirimt 		= strtotime($btarih);

        $upload = new upload($_FILES['dosya']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/dosyalar");
            if ($upload->processed)
            {
                $Dosya=''.$upload->file_dst_name.'';
            }
        }
        $gitti=$Dosya=''.$upload->file_dst_name.'';

        $musteri 		= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);

        $sorgu = $db->prepare("INSERT INTO musteri_sozlesmeler SET
				uyeid 			= ?,
				baslik 			= ?,
				icerik 			= ?,
				dosya 			= ?,
				tutar 			= ?,
				durum 			= ?,
				tarih 			= ?");
        $Ekle = $sorgu->execute(array(
            $uyeid,
            $baslik,
            $icerik,
            $Dosya,
            $tutar,
            $durum,
            $tarih
        ));
        if($Ekle)
        {
            $last_id  = $db->lastInsertId();

            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Yeni Sözleşme Tanımlandı.",
                'icon' 		=> "icon-people",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$musteri['ad']." ".$musteri['soyad']."</strong> isimli müşteriye sozlesme tanımlandı.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));

            $_SESSION['ms_sozlesme_ekle'] = 'yes';
            header("Location:../yonetim/musteri-duzenle/".$uyeid.".html?islemler=sozlesmeler");
        }
        else
        {
            $_SESSION['ms_sozlesme_ekle'] = 'no';
            header("Location:../yonetim/ms-sozlesme-ekle/".$uyeid.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/ms-sozlesme-ekle/".$uyeid.".html");
    }
}

##Ms Sözleşme Düzenle ##
if(isset($_POST['ms_sozlesme_duzenle']))
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid 	= $_POST['uyeid'];
    $url 	= $_POST['url'];
    $id 		= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $baslik				= $_POST["baslik"];
        $icerik				= $_POST["icerik"];
        $tutar				= $_POST["tutar"];
        $durum 				= $_POST["durum"];

        $btarih				= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $kayitt				= TvERtXpE3w_tr_tarih('Y-m-d');
        $bildirimkt 			= strtotime($kayitt);
        $bildirimt 			= strtotime($btarih);

        $upload = new upload($_FILES['dosya']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/dosyalar");
            if ($upload->processed)
            {
                $Dosya=''.$upload->file_dst_name.'';
            }
        }

        if(isset($Dosya)){
            $dosya_bul= $db->query("SELECT * FROM musteri_sozlesmeler WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/dosyalar/".$dosya_bul['dosya']);
            $guncelle = $db->prepare("UPDATE musteri_sozlesmeler SET dosya = ? WHERE id = ?");
            $guncelle->execute([$Dosya,$id]);
            $Dosya=''.$upload->file_dst_name.'';
        }

        $musteri 		= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);

        $sorgu = $db->prepare("UPDATE musteri_sozlesmeler SET
				baslik 			= ?,
				icerik 			= ?,
				tutar 			= ?,	
				durum 			= ?
				WHERE id 		= ?");
        $guncelle = $sorgu->execute(array(
            $baslik,
            $icerik,
            $tutar,
            $durum,
            $id
        ));
        if($guncelle)
        {
            $last_id  = $db->lastInsertId();

            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Müşterinin alan sozlesmei düzenlendi",
                'icon' 		=> "icon-people",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$musteri['ad']." ".$musteri['soyad']."</strong> isimli müşterinin sozlesmeini düzenledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['ms_sozlesme_duzenle'] = 'yes';
            header("Location:".$url."");
        }
        else
        {
            $_SESSION['ms_sozlesme_duzenle'] = 'no';
            header("Location:".$url."");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/ms-sozlesme-duzenle/".$id.".html");
    }
}

##Ms Sözleşme Sil##
if(@$_GET['mssozlesmesil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid = $_GET['uyeid'];
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $musteri_bul= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);
        $Sorgu = $db->prepare("DELETE FROM musteri_sozlesmeler WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                unlink("../".tema."/uploads/dosyalar/".$sozlesme_bul['dosya']);
                $last_id 		= $id;
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 		= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Müşteri Sözleşmei Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$musteri_bul['ad']." ".$musteri_bul['soyad']."</strong> müşteriye ait sozlesmei sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['mssozlesmesil'] = 'yes';
                header("Location:../yonetim/".$_GET['url']."");
            }
            else
            {
                $_SESSION['mssozlesmesil'] = 'no';
                header("Location:../yonetim/".$_GET['url']."");
            }
        }
        else
        {
            exit;
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/".$_GET['url']."");
    }
}

##Ms Sözleşme Toplu Sil ##
if(isset($_POST['ms_sozlesme_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $sozlesme_bul= $db->query("SELECT * FROM musteri_sozlesmeler WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $musteri_bul= $db->query("SELECT * FROM uyeler WHERE id = '{$sozlesme_bul['uyeid']}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM musteri_sozlesmeler WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    unlink("../".tema."/uploads/dosyalar/".$sozlesme_bul['dosya']);
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 		= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Müşteri Sözleşmei Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$musteri_bul['ad']." ".$musteri_bul['soyad']."</strong> müşteriye ait sozlesmei sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['ms_sozlesme_tumu'] = 'yes';
                    header("Location:../yonetim/".$_POST['url']."");
                }
                else
                {
                    $_SESSION['ms_sozlesme_tumu'] = 'no';
                    header("Location:../yonetim/".$_POST['url']."");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/".$_POST['url']."");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/".$_POST['url']."");
    }
}




##Ms Rapor Ekle ##
if(isset($_POST['ms_rapor_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid 	= $_POST['uyeid'];
    if($_SESSION['rutbe'] == 0)
    {
        $baslik			= $_POST["baslik"];
        $icerik			= $_POST["icerik"];
        $tutar			= $_POST["tutar"];
        $durum			= $_POST["durum"];
        $tarih			= date("Y-m-d H:i:s");

        $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
        $bildirimkt 		= strtotime($kayitt);
        $bildirimt 		= strtotime($btarih);

        $upload = new upload($_FILES['dosya']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/dosyalar");
            if ($upload->processed)
            {
                $Dosya=''.$upload->file_dst_name.'';
            }
        }
        $gitti=$Dosya=''.$upload->file_dst_name.'';

        $musteri 		= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);

        $sorgu = $db->prepare("INSERT INTO musteri_raporlar SET
				uyeid 			= ?,
				baslik 			= ?,
				icerik 			= ?,
				dosya 			= ?,
				tutar 			= ?,
				durum 			= ?,
				tarih 			= ?");
        $Ekle = $sorgu->execute(array(
            $uyeid,
            $baslik,
            $icerik,
            $Dosya,
            $tutar,
            $durum,
            $tarih
        ));
        if($Ekle)
        {
            $last_id  = $db->lastInsertId();

            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Yeni Rapor Tanımlandı.",
                'icon' 		=> "icon-people",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$musteri['ad']." ".$musteri['soyad']."</strong> isimli müşteriye rapor tanımlandı.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));

            $_SESSION['ms_rapor_ekle'] = 'yes';
            header("Location:../yonetim/musteri-duzenle/".$uyeid.".html?islemler=raporlar");
        }
        else
        {
            $_SESSION['ms_rapor_ekle'] = 'no';
            header("Location:../yonetim/ms-rapor-ekle/".$uyeid.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/ms-rapor-ekle/".$uyeid.".html");
    }
}

##Ms Rapor Düzenle ##
if(isset($_POST['ms_rapor_duzenle']))
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid 	= $_POST['uyeid'];
    $url 	= $_POST['url'];
    $id 		= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $baslik				= $_POST["baslik"];
        $icerik				= $_POST["icerik"];
        $tutar				= $_POST["tutar"];
        $durum 				= $_POST["durum"];

        $btarih				= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $kayitt				= TvERtXpE3w_tr_tarih('Y-m-d');
        $bildirimkt 			= strtotime($kayitt);
        $bildirimt 			= strtotime($btarih);

        $upload = new upload($_FILES['dosya']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/dosyalar");
            if ($upload->processed)
            {
                $Dosya=''.$upload->file_dst_name.'';
            }
        }

        if(isset($Dosya)){
            $dosya_bul= $db->query("SELECT * FROM musteri_raporlar WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/dosyalar/".$dosya_bul['dosya']);
            $guncelle = $db->prepare("UPDATE musteri_raporlar SET dosya = ? WHERE id = ?");
            $guncelle->execute([$Dosya,$id]);
            $Dosya=''.$upload->file_dst_name.'';
        }

        $musteri 		= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);

        $sorgu = $db->prepare("UPDATE musteri_raporlar SET
				baslik 			= ?,
				icerik 			= ?,
				tutar 			= ?,	
				durum 			= ?
				WHERE id 		= ?");
        $guncelle = $sorgu->execute(array(
            $baslik,
            $icerik,
            $tutar,
            $durum,
            $id
        ));
        if($guncelle)
        {
            $last_id  = $db->lastInsertId();

            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Müşterinin alan rapori düzenlendi",
                'icon' 		=> "icon-people",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$musteri['ad']." ".$musteri['soyad']."</strong> isimli müşterinin raporini düzenledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['ms_rapor_duzenle'] = 'yes';
            header("Location:".$url."");
        }
        else
        {
            $_SESSION['ms_rapor_duzenle'] = 'no';
            header("Location:".$url."");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/ms-rapor-duzenle/".$id.".html");
    }
}

##Ms Rapor Sil##
if(@$_GET['msraporsil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid = $_GET['uyeid'];
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $musteri_bul= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);
        $Sorgu = $db->prepare("DELETE FROM musteri_raporlar WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                unlink("../".tema."/uploads/dosyalar/".$rapor_bul['dosya']);
                $last_id 		= $id;
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 		= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Müşteri Rapori Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$musteri_bul['ad']." ".$musteri_bul['soyad']."</strong> müşteriye ait rapori sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['msraporsil'] = 'yes';
                header("Location:../yonetim/".$_GET['url']."");
            }
            else
            {
                $_SESSION['msraporsil'] = 'no';
                header("Location:../yonetim/".$_GET['url']."");
            }
        }
        else
        {
            exit;
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/".$_GET['url']."");
    }
}

##Ms Rapor Toplu Sil ##
if(isset($_POST['ms_rapor_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $rapor_bul= $db->query("SELECT * FROM musteri_raporlar WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $musteri_bul= $db->query("SELECT * FROM uyeler WHERE id = '{$rapor_bul['uyeid']}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM musteri_raporlar WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    unlink("../".tema."/uploads/dosyalar/".$rapor_bul['dosya']);
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 		= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Müşteri Rapori Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$musteri_bul['ad']." ".$musteri_bul['soyad']."</strong> müşteriye ait rapori sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['ms_rapor_tumu'] = 'yes';
                    header("Location:../yonetim/".$_POST['url']."");
                }
                else
                {
                    $_SESSION['ms_rapor_tumu'] = 'no';
                    header("Location:../yonetim/".$_POST['url']."");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/".$_POST['url']."");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/".$_POST['url']."");
    }
}



##Ms E-Fatura Ekle ##
if(isset($_POST['ms_efatura_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid 	= $_POST['uyeid'];
    if($_SESSION['rutbe'] == 0)
    {
        $baslik			= $_POST["baslik"];
        $icerik			= $_POST["icerik"];
        $tutar			= $_POST["tutar"];
        $durum			= $_POST["durum"];
        $tarih			= date("Y-m-d H:i:s");

        $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
        $bildirimkt 		= strtotime($kayitt);
        $bildirimt 		= strtotime($btarih);

        $upload = new upload($_FILES['dosya']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/dosyalar");
            if ($upload->processed)
            {
                $Dosya=''.$upload->file_dst_name.'';
            }
        }
        $gitti=$Dosya=''.$upload->file_dst_name.'';

        $musteri 		= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);

        $sorgu = $db->prepare("INSERT INTO musteri_efaturalar SET
				uyeid 			= ?,
				baslik 			= ?,
				icerik 			= ?,
				dosya 			= ?,
				tutar 			= ?,
				durum 			= ?,
				tarih 			= ?");
        $Ekle = $sorgu->execute(array(
            $uyeid,
            $baslik,
            $icerik,
            $Dosya,
            $tutar,
            $durum,
            $tarih
        ));
        if($Ekle)
        {
            $last_id  = $db->lastInsertId();

            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Yeni E-Fatura Tanımlandı.",
                'icon' 		=> "icon-people",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$musteri['ad']." ".$musteri['soyad']."</strong> isimli müşteriye efatura tanımlandı.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));

            $_SESSION['ms_efatura_ekle'] = 'yes';
            header("Location:../yonetim/musteri-duzenle/".$uyeid.".html?islemler=efaturalar");
        }
        else
        {
            $_SESSION['ms_efatura_ekle'] = 'no';
            header("Location:../yonetim/ms-efatura-ekle/".$uyeid.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/ms-efatura-ekle/".$uyeid.".html");
    }
}

##Ms E-Fatura Düzenle ##
if(isset($_POST['ms_efatura_duzenle']))
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid 	= $_POST['uyeid'];
    $url 	= $_POST['url'];
    $id 		= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $baslik				= $_POST["baslik"];
        $icerik				= $_POST["icerik"];
        $tutar				= $_POST["tutar"];
        $durum 				= $_POST["durum"];

        $btarih				= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $kayitt				= TvERtXpE3w_tr_tarih('Y-m-d');
        $bildirimkt 			= strtotime($kayitt);
        $bildirimt 			= strtotime($btarih);

        $upload = new upload($_FILES['dosya']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/dosyalar");
            if ($upload->processed)
            {
                $Dosya=''.$upload->file_dst_name.'';
            }
        }

        if(isset($Dosya)){
            $dosya_bul= $db->query("SELECT * FROM musteri_efaturalar WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/dosyalar/".$dosya_bul['dosya']);
            $guncelle = $db->prepare("UPDATE musteri_efaturalar SET dosya = ? WHERE id = ?");
            $guncelle->execute([$Dosya,$id]);
            $Dosya=''.$upload->file_dst_name.'';
        }

        $musteri 		= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);

        $sorgu = $db->prepare("UPDATE musteri_efaturalar SET
				baslik 			= ?,
				icerik 			= ?,
				tutar 			= ?,	
				durum 			= ?
				WHERE id 		= ?");
        $guncelle = $sorgu->execute(array(
            $baslik,
            $icerik,
            $tutar,
            $durum,
            $id
        ));
        if($guncelle)
        {
            $last_id  = $db->lastInsertId();

            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Müşterinin alan efaturai düzenlendi",
                'icon' 		=> "icon-people",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$musteri['ad']." ".$musteri['soyad']."</strong> isimli müşterinin efaturaini düzenledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['ms_efatura_duzenle'] = 'yes';
            header("Location:".$url."");
        }
        else
        {
            $_SESSION['ms_efatura_duzenle'] = 'no';
            header("Location:".$url."");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/ms-efatura-duzenle/".$id.".html");
    }
}

##Ms E-Fatura Sil##
if(@$_GET['msefaturasil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid = $_GET['uyeid'];
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $musteri_bul= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);
        $Sorgu = $db->prepare("DELETE FROM musteri_efaturalar WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                unlink("../".tema."/uploads/dosyalar/".$efatura_bul['dosya']);
                $last_id 		= $id;
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 		= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Müşteri E-Faturai Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$musteri_bul['ad']." ".$musteri_bul['soyad']."</strong> müşteriye ait efaturai sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['msefaturasil'] = 'yes';
                header("Location:../yonetim/".$_GET['url']."");
            }
            else
            {
                $_SESSION['msefaturasil'] = 'no';
                header("Location:../yonetim/".$_GET['url']."");
            }
        }
        else
        {
            exit;
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/".$_GET['url']."");
    }
}

##Ms E-Fatura Toplu Sil ##
if(isset($_POST['ms_efatura_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $efatura_bul= $db->query("SELECT * FROM musteri_efaturalar WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $musteri_bul= $db->query("SELECT * FROM uyeler WHERE id = '{$efatura_bul['uyeid']}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM musteri_efaturalar WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    unlink("../".tema."/uploads/dosyalar/".$efatura_bul['dosya']);
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 		= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Müşteri E-Faturai Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$musteri_bul['ad']." ".$musteri_bul['soyad']."</strong> müşteriye ait efaturai sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['ms_efatura_tumu'] = 'yes';
                    header("Location:../yonetim/".$_POST['url']."");
                }
                else
                {
                    $_SESSION['ms_efatura_tumu'] = 'no';
                    header("Location:../yonetim/".$_POST['url']."");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/".$_POST['url']."");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/".$_POST['url']."");
    }
}



##Ms Referans Ekle ##
if(isset($_POST['ms_referans_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid 	= $_POST['uyeid'];
    if($_SESSION['rutbe'] == 0)
    {
        $baslik			= $_POST["baslik"];
        $icerik			= $_POST["icerik"];
        $tutar			= $_POST["tutar"];
        $durum			= $_POST["durum"];
        $tarih			= date("Y-m-d H:i:s");

        $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
        $bildirimkt 		= strtotime($kayitt);
        $bildirimt 		= strtotime($btarih);

        $upload = new upload($_FILES['dosya']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/dosyalar");
            if ($upload->processed)
            {
                $Dosya=''.$upload->file_dst_name.'';
            }
        }
        $gitti=$Dosya=''.$upload->file_dst_name.'';

        $musteri 		= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);

        $sorgu = $db->prepare("INSERT INTO musteri_referanslar SET
				uyeid 			= ?,
				baslik 			= ?,
				icerik 			= ?,
				dosya 			= ?,
				tutar 			= ?,
				durum 			= ?,
				tarih 			= ?");
        $Ekle = $sorgu->execute(array(
            $uyeid,
            $baslik,
            $icerik,
            $Dosya,
            $tutar,
            $durum,
            $tarih
        ));
        if($Ekle)
        {
            $last_id  = $db->lastInsertId();

            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Yeni Referans Tanımlandı.",
                'icon' 		=> "icon-people",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$musteri['ad']." ".$musteri['soyad']."</strong> isimli müşteriye referans tanımlandı.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));

            $_SESSION['ms_referans_ekle'] = 'yes';
            header("Location:../yonetim/musteri-duzenle/".$uyeid.".html?islemler=referanslar");
        }
        else
        {
            $_SESSION['ms_referans_ekle'] = 'no';
            header("Location:../yonetim/ms-referans-ekle/".$uyeid.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/ms-referans-ekle/".$uyeid.".html");
    }
}

##Ms Referans Düzenle ##
if(isset($_POST['ms_referans_duzenle']))
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid 	= $_POST['uyeid'];
    $url 	= $_POST['url'];
    $id 		= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $baslik				= $_POST["baslik"];
        $icerik				= $_POST["icerik"];
        $tutar				= $_POST["tutar"];
        $durum 				= $_POST["durum"];

        $btarih				= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $kayitt				= TvERtXpE3w_tr_tarih('Y-m-d');
        $bildirimkt 			= strtotime($kayitt);
        $bildirimt 			= strtotime($btarih);

        $upload = new upload($_FILES['dosya']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/dosyalar");
            if ($upload->processed)
            {
                $Dosya=''.$upload->file_dst_name.'';
            }
        }

        if(isset($Dosya)){
            $dosya_bul= $db->query("SELECT * FROM musteri_referanslar WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/dosyalar/".$dosya_bul['dosya']);
            $guncelle = $db->prepare("UPDATE musteri_referanslar SET dosya = ? WHERE id = ?");
            $guncelle->execute([$Dosya,$id]);
            $Dosya=''.$upload->file_dst_name.'';
        }

        $musteri 		= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);

        $sorgu = $db->prepare("UPDATE musteri_referanslar SET
				baslik 			= ?,
				icerik 			= ?,
				tutar 			= ?,	
				durum 			= ?
				WHERE id 		= ?");
        $guncelle = $sorgu->execute(array(
            $baslik,
            $icerik,
            $tutar,
            $durum,
            $id
        ));
        if($guncelle)
        {
            $last_id  = $db->lastInsertId();

            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Müşterinin alan referansi düzenlendi",
                'icon' 		=> "icon-people",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$musteri['ad']." ".$musteri['soyad']."</strong> isimli müşterinin referansini düzenledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['ms_referans_duzenle'] = 'yes';
            header("Location:".$url."");
        }
        else
        {
            $_SESSION['ms_referans_duzenle'] = 'no';
            header("Location:".$url."");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/ms-referans-duzenle/".$id.".html");
    }
}

##Ms Referans Sil##
if(@$_GET['msreferanssil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid = $_GET['uyeid'];
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $musteri_bul= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);
        $Sorgu = $db->prepare("DELETE FROM musteri_referanslar WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                unlink("../".tema."/uploads/dosyalar/".$referans_bul['dosya']);
                $last_id 		= $id;
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 		= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Müşteri Referansi Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$musteri_bul['ad']." ".$musteri_bul['soyad']."</strong> müşteriye ait referansi sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['msreferanssil'] = 'yes';
                header("Location:../yonetim/".$_GET['url']."");
            }
            else
            {
                $_SESSION['msreferanssil'] = 'no';
                header("Location:../yonetim/".$_GET['url']."");
            }
        }
        else
        {
            exit;
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/".$_GET['url']."");
    }
}

##Ms Referans Toplu Sil ##
if(isset($_POST['ms_referans_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $referans_bul= $db->query("SELECT * FROM musteri_referanslar WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $musteri_bul= $db->query("SELECT * FROM uyeler WHERE id = '{$referans_bul['uyeid']}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM musteri_referanslar WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    unlink("../".tema."/uploads/dosyalar/".$referans_bul['dosya']);
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 		= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Müşteri Referansi Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$musteri_bul['ad']." ".$musteri_bul['soyad']."</strong> müşteriye ait referansi sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['ms_referans_tumu'] = 'yes';
                    header("Location:../yonetim/".$_POST['url']."");
                }
                else
                {
                    $_SESSION['ms_referans_tumu'] = 'no';
                    header("Location:../yonetim/".$_POST['url']."");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/".$_POST['url']."");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/".$_POST['url']."");
    }
}



##Ms Teklif Ekle ##
if(isset($_POST['ms_teklif_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid 	= $_POST['uyeid'];
    if($_SESSION['rutbe'] == 0)
    {
        $baslik			= $_POST["baslik"];
        $icerik			= $_POST["icerik"];
        $tutar			= $_POST["tutar"];
        $durum			= $_POST["durum"];
        $tarih			= date("Y-m-d H:i:s");

        $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
        $bildirimkt 		= strtotime($kayitt);
        $bildirimt 		= strtotime($btarih);

        $upload = new upload($_FILES['dosya']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/dosyalar");
            if ($upload->processed)
            {
                $Dosya=''.$upload->file_dst_name.'';
            }
        }
        $gitti=$Dosya=''.$upload->file_dst_name.'';

        $musteri 		= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);

        $sorgu = $db->prepare("INSERT INTO musteri_teklifler SET
				uyeid 			= ?,
				baslik 			= ?,
				icerik 			= ?,
				dosya 			= ?,
				tutar 			= ?,
				durum 			= ?,
				tarih 			= ?");
        $Ekle = $sorgu->execute(array(
            $uyeid,
            $baslik,
            $icerik,
            $Dosya,
            $tutar,
            $durum,
            $tarih
        ));
        if($Ekle)
        {
            $last_id  = $db->lastInsertId();

            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Yeni Teklif Tanımlandı.",
                'icon' 		=> "icon-people",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$musteri['ad']." ".$musteri['soyad']."</strong> isimli müşteriye teklif tanımlandı.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));

            $_SESSION['ms_teklif_ekle'] = 'yes';
            header("Location:../yonetim/musteri-duzenle/".$uyeid.".html?islemler=teklifler");
        }
        else
        {
            $_SESSION['ms_teklif_ekle'] = 'no';
            header("Location:../yonetim/ms-teklif-ekle/".$uyeid.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/ms-teklif-ekle/".$uyeid.".html");
    }
}

##Ms Teklif Düzenle ##
if(isset($_POST['ms_teklif_duzenle']))
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid 	= $_POST['uyeid'];
    $url 	= $_POST['url'];
    $id 		= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $baslik				= $_POST["baslik"];
        $icerik				= $_POST["icerik"];
        $tutar				= $_POST["tutar"];
        $durum 				= $_POST["durum"];

        $btarih				= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
        $kayitt				= TvERtXpE3w_tr_tarih('Y-m-d');
        $bildirimkt 			= strtotime($kayitt);
        $bildirimt 			= strtotime($btarih);

        $upload = new upload($_FILES['dosya']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/dosyalar");
            if ($upload->processed)
            {
                $Dosya=''.$upload->file_dst_name.'';
            }
        }

        if(isset($Dosya)){
            $dosya_bul= $db->query("SELECT * FROM musteri_teklifler WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/dosyalar/".$dosya_bul['dosya']);
            $guncelle = $db->prepare("UPDATE musteri_teklifler SET dosya = ? WHERE id = ?");
            $guncelle->execute([$Dosya,$id]);
            $Dosya=''.$upload->file_dst_name.'';
        }

        $musteri 		= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);

        $sorgu = $db->prepare("UPDATE musteri_teklifler SET
				baslik 			= ?,
				icerik 			= ?,
				tutar 			= ?,	
				durum 			= ?
				WHERE id 		= ?");
        $guncelle = $sorgu->execute(array(
            $baslik,
            $icerik,
            $tutar,
            $durum,
            $id
        ));
        if($guncelle)
        {
            $last_id  = $db->lastInsertId();

            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Müşterinin alan teklifi düzenlendi",
                'icon' 		=> "icon-people",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$musteri['ad']." ".$musteri['soyad']."</strong> isimli müşterinin teklifini düzenledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['ms_teklif_duzenle'] = 'yes';
            header("Location:".$url."");
        }
        else
        {
            $_SESSION['ms_teklif_duzenle'] = 'no';
            header("Location:".$url."");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/ms-teklif-duzenle/".$id.".html");
    }
}

##Ms Teklif Sil##
if(@$_GET['msteklifsil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid = $_GET['uyeid'];
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $musteri_bul= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);
        $Sorgu = $db->prepare("DELETE FROM musteri_teklifler WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                unlink("../".tema."/uploads/dosyalar/".$teklif_bul['dosya']);
                $last_id 		= $id;
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 		= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Müşteri Teklifi Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$musteri_bul['ad']." ".$musteri_bul['soyad']."</strong> müşteriye ait teklifi sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['msteklifsil'] = 'yes';
                header("Location:../yonetim/".$_GET['url']."");
            }
            else
            {
                $_SESSION['msteklifsil'] = 'no';
                header("Location:../yonetim/".$_GET['url']."");
            }
        }
        else
        {
            exit;
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/".$_GET['url']."");
    }
}

##Ms Teklif Toplu Sil ##
if(isset($_POST['ms_teklif_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $teklif_bul= $db->query("SELECT * FROM musteri_teklifler WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $musteri_bul= $db->query("SELECT * FROM uyeler WHERE id = '{$teklif_bul['uyeid']}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM musteri_teklifler WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    unlink("../".tema."/uploads/dosyalar/".$teklif_bul['dosya']);
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 		= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Müşteri Teklifi Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$musteri_bul['ad']." ".$musteri_bul['soyad']."</strong> müşteriye ait teklifi sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['ms_teklif_tumu'] = 'yes';
                    header("Location:../yonetim/".$_POST['url']."");
                }
                else
                {
                    $_SESSION['ms_teklif_tumu'] = 'no';
                    header("Location:../yonetim/".$_POST['url']."");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/".$_POST['url']."");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/".$_POST['url']."");
    }
}





##Üye Detay Kredi Güncelle##
if(isset($_POST['k_kredi_guncelle']))
{
    TvERtXpE3w_panelislemkontrol();
    $d_id 			= $_POST['krediid'];
    $kredi 			= $_POST['kredi'];
    $tarih			= date('Y-m-d H:i:s');
    $tarih			= TvERtXpE3w_tarih($tarih);
    $ip				= TvERtXpE3w_ip();

    $kredikontrol = $db->prepare("SELECT * FROM krediler WHERE uyeid = ?");
    $kredikontrol->execute(array($d_id));
    if($kredikontrol->rowCount()){
        $KrediSonuc = $kredikontrol->fetch(PDO::FETCH_ASSOC);

        $sorgu = $db->prepare("UPDATE krediler SET
			tutar 		= ?,
			ip 			= ?,
			tarih 		= ?
			WHERE uyeid = ?");
        $guncelle = $sorgu->execute(array(
            $kredi,
            $ip,
            $tarih,
            $KrediSonuc['uyeid']
        ));

        if($guncelle)
        {
            $_SESSION['kredi_yukle'] = 'yes';
            header("Location:../yonetim/musteri-duzenle/".$d_id.".html");
        }
        else
        {
            $_SESSION['kredi_yukle'] = 'no';
            header("Location:../yonetim/musteri-duzenle/".$d_id.".html");
        }
    }
    else
    {
        $sorgu = $db->prepare("INSERT INTO krediler SET
					uyeid 		= ?,
					tutar 		= ?,
					paytronay 	= ?,
					ip			= ?,
					tarih 		= ?");
        $KrediEkle = $sorgu->execute(array(
            $d_id,
            $kredi,
            "1",
            $ip,
            $tarih
        ));
        if($KrediEkle)
        {
            $_SESSION['kredi_yukle'] = 'yes';
            header("Location:../yonetim/musteri-duzenle/".$d_id.".html");
        }
        else
        {
            $_SESSION['kredi_yukle'] = 'no';
            header("Location:../yonetim/musteri-duzenle/".$d_id.".html");
        }
    }
}

##Üye Detay Bakiye Güncelle##
if(isset($_POST['bakiye_guncelle']))
{
    TvERtXpE3w_panelislemkontrol();
    $d_id 			= $_POST['bakiyeid'];
    $bakiye 			= $_POST['bakiye'];

    $sorgu = $db->prepare("UPDATE uyeler SET
		bakiye 		= ?
		WHERE id 	= ?");
    $guncelle = $sorgu->execute(array(
        $bakiye,
        $d_id
    ));

    if($guncelle)
    {
        $_SESSION['bakiye_guncelle'] = 'yes';
        header("Location:../yonetim/musteri-duzenle/".$d_id.".html");
    }
    else
    {
        $_SESSION['bakiye_guncelle'] = 'no';
        header("Location:../yonetim/musteri-duzenle/".$d_id.".html");
    }
}

##Satışları Sil##
if(@$_GET['satissil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    $uyeid = $_GET['uyeid'];
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $musteri_bul= $db->query("SELECT * FROM uyeler WHERE id = '{$uyeid}'")->fetch(PDO::FETCH_ASSOC);
        $Sorgu = $db->prepare("DELETE FROM satilanlar WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                $TopluSorgu = $db->prepare("SELECT * FROM faturalar WHERE hizmet = ?");
                $TopluSorgu->execute(array($_GET['id']));
                $Topluislem = $TopluSorgu->fetchALL(PDO::FETCH_ASSOC);
                foreach ( $Topluislem as $TopluSonuc )
                {
                    $TSorgu = $db->prepare("DELETE FROM faturalar WHERE id = :id");
                    $TSorgu->execute(array('id' => $TopluSonuc['id']));
                }
                $last_id 		= $id;
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 		= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Web Hosting Satışları Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$musteri_bul['ad']." ".$musteri_bul['soyad']."</strong> müşteriye ait web hosting satışını sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['satissil'] = 'yes';
                header("Location:../yonetim/".$_GET['url']."");
            }
            else
            {
                $_SESSION['satissil'] = 'no';
                header("Location:../yonetim/".$_GET['url']."");
            }
        }
        else
        {
            exit;
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/".$_GET['url']."");
    }
}

##Satışları Toplu Sil ##
if(isset($_POST['satis_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $satilan_bul= $db->query("SELECT * FROM satilanlar WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $musteri_bul= $db->query("SELECT * FROM uyeler WHERE id = '{$satilan_bul['uyeid']}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM satilanlar WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    $TTopluSorgu = $db->prepare("SELECT * FROM faturalar WHERE hizmet = ?");
                    $TTopluSorgu->execute(array($i));
                    $TTopluislem = $TTopluSorgu->fetchALL(PDO::FETCH_ASSOC);
                    foreach ( $TTopluislem as $TTopluSonuc )
                    {
                        $TSorgu = $db->prepare("DELETE FROM faturalar WHERE id = :id");
                        $TSorgu->execute(array('id' => $TTopluSonuc['id']));
                    }
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 		= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Web Hosting Satışları Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: crimson;'>".$musteri_bul['ad']." ".$musteri_bul['soyad']."</strong> müşteriye ait web hosting satışını sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['satis_tumu'] = 'yes';
                    header("Location:".$_POST['url']."");
                }
                else
                {
                    $_SESSION['satis_tumu'] = 'no';
                    header("Location:".$_POST['url']."");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:".$_POST['url']."");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:".$_POST['url']."");
    }
}

##Çoklu E-Bülten Sil ##
if(isset($_POST['ebulten_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $mesaj_bul	= $db->query("SELECT * FROM ebulten WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM ebulten WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 		= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "E-Bülten Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: thistle;'>".$mesaj_bul['email']."</strong> isimli e-bülten kaydını sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['ebulten_tumu'] = 'yes';
                    header("Location:../yonetim/ebulten.html");
                }
                else
                {
                    $_SESSION['ebulten_tumu'] = 'no';
                    header("Location:../yonetim/ebulten.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/ebulten.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/ebulten.html");
    }
}

##E-Bülten Sil##
if(@$_GET['ebultensil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $mesaj_bul	= $db->query("SELECT * FROM ebulten WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        $Sorgu = $db->prepare("DELETE FROM ebulten WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                $last_id 		= $i;
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 	= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "E-Bülten Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: thistle;'>".$mesaj_bul['email']."</strong> isimli e-bülten kaydını sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['ebultensil'] = 'yes';
                header("Location:../yonetim/ebulten.html");
            }
            else
            {
                $_SESSION['ebultensil'] = 'no';
                header("Location:../yonetim/ebulten.html");
            }
        }
        else
        {
            exit;
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/ebulten.html");
    }
}

##Ödeme Formu Sil##
if(@$_GET['odemebsil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $mesaj_bul	= $db->query("SELECT * FROM odeme_bildirimleri WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        $Sorgu = $db->prepare("DELETE FROM odeme_bildirimleri WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                $last_id 		= $i;
                $bildirimkt 		= strtotime(TvERtXpE3w_tr_tarih('Y-m-d'));
                $bildirimt 		= strtotime(TvERtXpE3w_tr_tarih('Y-m-d H:i:s'));
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Ödeme Bildirim Formu Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: thistle;'>".$mesaj_bul['isim']."</strong> isimli ödeme formunu sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['odemebsil'] = 'yes';
                header("Location:../yonetim/odemebildirimformu.html");
            }
            else
            {
                $_SESSION['odemebsil'] = 'no';
                header("Location:../yonetim/odemebildirimformu.html");
            }
        }
        else
        {
            echo '<meta http-equiv="refresh" content="0; url=404.html">';
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/odemebildirimformu.html");
    }
}

##Çoklu Ödeme Formu Sil ##
if(isset($_POST['odemeb_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $mesaj_bul	= $db->query("SELECT * FROM odeme_bildirimleri WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM odeme_bildirimleri WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    $last_id 		= $i;
                    $bildirimkt 		= strtotime(TvERtXpE3w_tr_tarih('Y-m-d'));
                    $bildirimt 		= strtotime(TvERtXpE3w_tr_tarih('Y-m-d H:i:s'));
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Ödeme Bildirim Formu Silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: thistle;'>".$mesaj_bul['isim']."</strong> isimli ödeme formunu sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['odemeb_tumu'] = 'yes';
                    header("Location:../yonetim/odemebildirimformu.html");
                }
                else
                {
                    $_SESSION['odemeb_tumu'] = 'no';
                    header("Location:../yonetim/odemebildirimformu.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/odemebildirimformu.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/odemebildirimformu.html");
    }
}

## Banka Kaydet ##
if(isset($_POST['banka_ekle']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $banka 			= $_POST['banka'];
        $hesap 			= $_POST['hesap'];
        $sube 			= $_POST['sube'];
        $hnumara 		= $_POST['hnumara'];
        $iban 			= $_POST['iban'];
        $durum 			= $_POST['durum'];
        $tarih			= TvERtXpE3w_tarih(TvERtXpE3w_tr_tarih('Y-m-d H:i:s'));

        $upload = new upload($_FILES['resim']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/bankalar");
            if ($upload->processed)
            {
                $Resim=''.$upload->file_dst_name.'';
            }
        }
        $gitti=$Resim=''.$upload->file_dst_name.'';

        $sorgu = $db->prepare("INSERT INTO banka_hesaplari SET
				banka 			= ?,
				hesap 			= ?,
				sube 			= ?,
				hnumara 		= ?,
				iban 			= ?,
				durum 			= ?,
				resim 			= ?,
				tarih 			= ?");
        $Ekle = $sorgu->execute(array(
            $banka,
            $hesap,
            $sube,
            $hnumara,
            $iban,
            $durum,
            $Resim,
            $tarih
        ));
        if($Ekle)
        {
            $last_id 		= $db->lastInsertId();
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 		= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Yeni Banka Hesabı Eklendi",
                'icon' 		=> "icon-people",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$banka." </strong> isimli banka hesabı ekledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['banka_ekle'] = 'yes';
            header("Location:../yonetim/banka-hesaplari.html");
        }
        else
        {
            $_SESSION['banka_ekle'] = 'no';
            header("Location:../yonetim/banka-hesap-ekle.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/banka-hesap-ekle.html");
    }
}

## Banka Güncelle ##
if(isset($_POST['banka_guncelle']))
{
    TvERtXpE3w_panelislemkontrol();
    $d_id 	= $_POST['id'];
    if($_SESSION['rutbe'] == 0)
    {
        $banka 			= $_POST['banka'];
        $hesap 			= $_POST['hesap'];
        $sube 			= $_POST['sube'];
        $hnumara 		= $_POST['hnumara'];
        $iban 			= $_POST['iban'];
        $durum 			= $_POST['durum'];

        $upload = new upload($_FILES['resim']);
        if ($upload->uploaded)
        {
            $upload->file_auto_rename = true;
            $upload->process("../".tema."/uploads/bankalar");
            if ($upload->processed)
            {
                $Resim=''.$upload->file_dst_name.'';
            }
        }

        if(isset($Resim)){
            $resim_bul= $db->query("SELECT * FROM banka_hesaplari WHERE id = '{$d_id}'")->fetch(PDO::FETCH_ASSOC);
            unlink("../".tema."/uploads/bankalar/".$resim_bul['resim']);
            $guncelle = $db->prepare("UPDATE banka_hesaplari SET resim = ? WHERE id = ?");
            $guncelle->execute([$Resim,$d_id]);
            $Resim=''.$upload->file_dst_name.'';
        }

        $sorgu = $db->prepare("UPDATE banka_hesaplari SET
			banka 			= ?,
			hesap 			= ?,
			sube 			= ?,
			hnumara 		= ?,
			iban 			= ?,
			durum 			= ?
			WHERE id 		= ?");
        $guncelle = $sorgu->execute(array(
            $banka,
            $hesap,
            $sube,
            $hnumara,
            $iban,
            $durum,
            $d_id
        ));
        if($guncelle)
        {
            $last_id 		= $d_id;
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 	= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Banka Hesabı Güncellendi",
                'icon' 		=> "icon-people",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$banka." </strong> isimli banka hesabını güncelledi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['banka_guncelle'] = 'yes';
            header("Location:../yonetim/banka-duzenle/".$d_id.".html");
        }
        else
        {
            $_SESSION['banka_guncelle'] = 'no';
            header("Location:../yonetim/banka-duzenle/".$d_id.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/banka-duzenle/".$d_id.".html");
    }
}

## Banka Resim Sil##
if(@$_GET['bankaresimsil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    @$resimid 	= $_GET['sid'];
    if($_SESSION['rutbe'] == 0)
    {
        $resim_bul	= $db->query("SELECT * FROM banka_hesaplari WHERE id = '{$resimid}'")->fetch(PDO::FETCH_ASSOC);
        unlink("../".tema."/uploads/bankalar/".$resim_bul['resim']);
        $sorgu = $db->prepare("UPDATE banka_hesaplari SET
					resim	= ?
					WHERE id = ?");
        $guncelle = $sorgu->execute(array(
            "",
            $resimid
        ));
        if($guncelle)
        {
            $last_id 		= $resim_bul["id"];
            $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
            $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
            $bildirimkt 		= strtotime($kayitt);
            $bildirimt 		= strtotime($btarih);
            $BSorgu = $db->prepare("INSERT INTO bildirimler SET
				baslik		= :baslik,
				icon		= :icon,
				bid			= :bid,
				bildirim	= :bildirim,
				ktarih		= :ktarih,
				tarih 		= :tarih");
            $BEkle = $BSorgu->execute(array(
                'baslik' 	=> "Banka Hesabı Logo Silindi",
                'icon' 		=> "icon-people",
                'bid' 		=> $last_id,
                'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$resim_bul['banka']." </strong> isimli banka hesabının logosunu sildi.",
                'ktarih'	=> $bildirimkt,
                'tarih'		=> $bildirimt
            ));
            $_SESSION['bankaresimsil'] = 'yes';
            header("Location:../yonetim/banka-duzenle/".$resimid.".html");
        }
        else
        {
            $_SESSION['bankaresimsil'] = 'no';
            header("Location:../yonetim/banka-duzenle/".$resimid.".html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/banka-duzenle/".$resimid.".html");
    }
}

##Banka Sil##
if(@$_GET['bankasil'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $id = $_GET['id'];
        $url = $_GET['url'];
        $resim_bul= $db->query("SELECT * FROM banka_hesaplari WHERE id = '{$id}'")->fetch(PDO::FETCH_ASSOC);
        unlink("../".tema."/uploads/bankalar/".$resim_bul['resim']);
        $Sorgu = $db->prepare("DELETE FROM banka_hesaplari WHERE id = :id");
        $sil_sorgu = $Sorgu->execute(array('id' => $id));
        if($Sorgu->rowCount())
        {
            if($sil_sorgu)
            {
                $last_id 		= $resim_bul["id"];
                $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                $bildirimkt 		= strtotime($kayitt);
                $bildirimt 		= strtotime($btarih);
                $BSorgu = $db->prepare("INSERT INTO bildirimler SET
					baslik		= :baslik,
					icon		= :icon,
					bid			= :bid,
					bildirim	= :bildirim,
					ktarih		= :ktarih,
					tarih 		= :tarih");
                $BEkle = $BSorgu->execute(array(
                    'baslik' 	=> "Banka Hesabı Silindi",
                    'icon' 		=> "icon-trash",
                    'bid' 		=> $last_id,
                    'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$resim_bul['banka']." </strong> isimli banka hesabını sildi.",
                    'ktarih'	=> $bildirimkt,
                    'tarih'		=> $bildirimt
                ));
                $_SESSION['bankasil'] = 'yes';
                header("Location:../yonetim/banka-hesaplari.html");
            }
            else
            {
                $_SESSION['bankasil'] = 'no';
                header("Location:../yonetim/banka-hesaplari.html");
            }
        }
        else
        {
            echo '<meta http-equiv="refresh" content="0; url=404.html">';
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/banka-hesaplari.html");
    }
}

## Banka Toplu Sil ##
if(isset($_POST['banka_tumu']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $url = $_POST['url'];
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $resim_bul= $db->query("SELECT * FROM banka_hesaplari WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $TopluSorgu = $db->prepare("DELETE FROM banka_hesaplari WHERE id = :id");
                $TopluSil	= $TopluSorgu->execute(array('id' => $i));
                if($TopluSil)
                {
                    $last_id 		= $resim_bul["id"];
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 	= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Banka hesabı silindi",
                        'icon' 		=> "icon-trash",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$resim_bul['banka']." </strong> isimli banka hesabını sildi.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    unlink("../".tema."/uploads/bankalar/".$resim_bul['resim']);
                    $_SESSION['banka_tumu'] = 'yes';
                    header("Location:../yonetim/banka-hesaplari.html");
                }
                else
                {
                    $_SESSION['banka_tumu'] = 'no';
                    header("Location:../yonetim/banka-hesaplari.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/banka-hesaplari.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/banka-hesaplari.html");
    }
}

## Banka Toplu Aktif ##
if(isset($_POST['banka_aktif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $url = $_POST['url'];
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $banka_bul= $db->query("SELECT * FROM banka_hesaplari WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE banka_hesaplari SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "1",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 		= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Banka Hesabı Aktif Edildi",
                        'icon' 		=> "icon-check",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$banka_bul['banka']." </strong> isimli banka hesabını aktif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['banka_aktif'] = 'yes';
                    header("Location:../yonetim/banka-hesaplari.html");
                }
                else
                {
                    $_SESSION['banka_aktif'] = 'no';
                    header("Location:../yonetim/banka-hesaplari.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/banka-hesaplari.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/banka-hesaplari.html");
    }
}

## Banka Toplu Pasif ##
if(isset($_POST['banka_pasif']))
{
    TvERtXpE3w_panelislemkontrol();
    if($_SESSION['rutbe'] == 0)
    {
        $url = $_POST['url'];
        if($_POST['id'])
        {
            foreach($_POST['id'] as $i)
            {
                $banka_bul= $db->query("SELECT * FROM banka_hesaplari WHERE id = '{$i}'")->fetch(PDO::FETCH_ASSOC);
                $sorgu = $db->prepare("UPDATE banka_hesaplari SET
					durum 	= ?
					WHERE id = ?");
                $guncelle = $sorgu->execute(array(
                    "0",
                    $i
                ));
                if($guncelle)
                {
                    $last_id 		= $i;
                    $btarih			= TvERtXpE3w_tr_tarih('Y-m-d H:i:s');
                    $kayitt			= TvERtXpE3w_tr_tarih('Y-m-d');
                    $bildirimkt 		= strtotime($kayitt);
                    $bildirimt 		= strtotime($btarih);
                    $BSorgu = $db->prepare("INSERT INTO bildirimler SET
						baslik		= :baslik,
						icon		= :icon,
						bid			= :bid,
						bildirim	= :bildirim,
						ktarih		= :ktarih,
						tarih 		= :tarih");
                    $BEkle = $BSorgu->execute(array(
                        'baslik' 	=> "Banka Hesabı Pasif Edildi",
                        'icon' 		=> "icon-close",
                        'bid' 		=> $last_id,
                        'bildirim' 	=> "<strong>Yönetici</strong> <strong style='color: orchid;'>".$banka_bul['banka']."</strong> isimli banka hesabını pasif etti.",
                        'ktarih'	=> $bildirimkt,
                        'tarih'		=> $bildirimt
                    ));
                    $_SESSION['banka_pasif'] = 'yes';
                    header("Location:../yonetim/banka-hesaplari.html");
                }
                else
                {
                    $_SESSION['banka_pasif'] = 'no';
                    header("Location:../yonetim/banka-hesaplari.html");
                }
            }
        }
        else
        {
            $_SESSION['secim'] = 'secimyok';
            header("Location:../yonetim/banka-hesaplari.html");
        }
    }
    else
    {
        $_SESSION['demohesap'] = 'no';
        header("Location:../yonetim/banka-hesaplari.html");
    }
}

##Çıkış Yap##
if(@$_GET['cikis'] == "ok")
{
    TvERtXpE3w_panelislemkontrol();
    unset($_SESSION['Yonetim_Id']);
    unset($_SESSION['Yonetim_Kadi']);
    unset($_SESSION['Yonetim_Sifre']);
    unset($_SESSION['rutbe']);
    header("Location:../yonetim/index.html");
}
?>
