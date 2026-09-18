<?php
session_start();
error_reporting(0);
require_once('../../../_class/baglan.php');
require_once('../../../_class/fonksiyon.php');
require_once('../../../language/dil_'.$_SESSION['k_dil'].".php");
$Sorgu = $db->prepare("SELECT * FROM alanadi WHERE id = ?");
$Sorgu->execute(array(1));
$Sonuc = $Sorgu->fetch(PDO::FETCH_ASSOC);
$suzanti 	= json_decode($Sonuc['uzanti']);
$skayit 		= json_decode($Sonuc['kayit']);
$syenileme 	= json_decode($Sonuc['yenileme']);

class kontrol{
	function tum_bosluk_sil($veri)
	{
		$veri = str_replace("/s+/","",$veri);
		$veri = str_replace(" ","",$veri);
		$veri = str_replace(" ","",$veri);
		$veri = str_replace(" ","",$veri);
		$veri = str_replace("/s/g","",$veri);
		$veri = str_replace("/s+/g","",$veri);		
		$veri = trim($veri);
		return $veri; 
	}
	protected $yasakKarakter = array(
			"/","\"","'","&","\\","%","$","#","@","€","[","]","{","}","*","?","=","^","<",">","!","~",",",";","|","´","`"
		);
	protected $hata = array(
		2 => 'Belirttiğiniz alan adında kullanılmayan karakterler var',
		3 => 'Baglanti kurulamiyor',
		4 => 'Herhangi bir uzantı seçmediniz veya istenilen uzantı sorgu listemizde mevcut değil',
		5 => 'Alan adınızı boş bıraktınız veya farklı bir problem var',
	);
	
	protected $servers = array(
		"biz" => "whois.neulevel.biz",
		"com" => "whois.internic.net",
		"ist" => "whois.internic.net",
		"istanbul" => "shop.whois.com",
		"us" => "whois.nic.us",
		"coop" => "whois.nic.coop",
		"info" => "whois.nic.info",
		"name" => "whois.nic.name",
		"net" => "whois.internic.net",
		"gov" => "whois.nic.gov",
		"edu" => "whois.internic.net",
		"mil" => "rs.internic.net",
		"int" => "whois.iana.org",
		"ac" => "whois.nic.ac",
		"ae" => "whois.uaenic.ae",
		"at" => "whois.ripe.net",
		"au" => "whois.aunic.net",
		"be" => "whois.dns.be",
		"bg" => "whois.ripe.net",
		"br" => "whois.registro.br",
		"bz" => "whois.belizenic.bz",
		"ca" => "whois.cira.ca",
		"cc" => "whois.nic.cc",
		"ch" => "whois.nic.ch",
		"cl" => "whois.nic.cl",
		"cn" => "whois.cnnic.net.cn",
		"cz" => "whois.nic.cz",
		"de" => "whois.nic.de",
		"fr" => "whois.nic.fr",
		"hu" => "whois.nic.hu",
		"ie" => "whois.domainregistry.ie",
		"il" => "whois.isoc.org.il",
		"in" => "whois.ncst.ernet.in",
		"ir" => "whois.nic.ir",
		"mc" => "whois.ripe.net",
		"to" => "whois.tonic.to",
		"tv" => "whois.nic.tv",
		"ru" => "whois.ripn.net",
		"org" => "whois.pir.org",
		"aero" => "whois.information.aero",
		"nl" => "whois.domain-registry.nl",
		"com.tr" => "whois.nic.tr",
		"net.tr" => "whois.nic.tr",
		"gen.tr" => "whois.nic.tr",
		"web.tr" => "whois.nic.tr",
		"k12.tr" => "whois.nic.tr",
		"website" => "whois.nic.website",
		"org.tr" => "whois.nic.tr"
	);
	
	protected $domain = domain_url;
	protected $domainBilgi = array();
	
	protected function domainSorgula($domain,$uzanti){
		
		$karakter = strlen($domain);
		for($i = 0; $i < $karakter; $i++){
			if(@in_array($domain[$i], $this->yasakKarakter)){
				$this->domainBilgi[$uzanti]['hata'] = 2;
				return false;
			}
		}

		if(empty($this->servers[$uzanti])){
			$this->domainBilgi[$uzanti]['hata'] = 4;
			return false;
		}
		
		$baglan = $this->servers[$uzanti];
		
		$output = '';
		try{
			if ($conn = fsockopen ($baglan, 43)) {
				fputs($conn, $domain.'.'.$uzanti."\r\n");
				while(!feof($conn)) {
					$output .= fgets($conn,128);
				}
				fclose($conn);
			}
			else {
				$this->domainBilgi[$uzanti]['hata'] = 3;
			}
			
		}catch(exception $e){
			$this->domainBilgi[$uzanti]['hata'] = 3;
		}
		
		$this->domainBilgi[$uzanti]['whois'] = $output;
		if(stristr($output,"No match") || stristr($output,"No entries" ) || stristr($output, "NOT FOUND" ) ){
			$this->domainBilgi[$uzanti]['durum'] = 0;
		}
		else {
			$this->domainBilgi[$uzanti]['durum'] = 1;
		}

	}

	public function dk($alanadi = NULL,$getUzanti = NULL)
	{

		$domain = strip_tags($this->tum_bosluk_sil($_POST['alanadi']));
		$this->domain = $this->domainPakle($domain);
		/******/
		$ip = $_SERVER['REMOTE_ADDR'];
		$pagina = $_SERVER['REQUEST_URI'];
		$datum = date("d-m-y / H:i:s");
		//$invoegen = $datum . " - " . $ip . " - " . $pagina ."-". $domain. "<br />";
		$invoegen = $datum . " - " . $ip . " - " ." &nbsp;<b>(". $domain."</b>)". "<br />";
		$fopen = fopen("kontrol.html", "a");
		fwrite($fopen, $invoegen);
		fclose($fopen);  
		/*******/
		if(!is_null($getUzanti)){
			$uzantilar = array_map('strip_tags',[$getUzanti]);
		}else{
			return false;
		}

		foreach($uzantilar as $uzanti){
			$uzanti = strtolower(trim($uzanti));
			$this->domainSorgula($this->domain, $uzanti);	
		}


		foreach ($this->domainBilgi as $key => $value) {
			if(!empty($value['hata'])){
				return false;
			}elseif($value['durum']){
				return false;
			}else{
				return true;
			}
		}
		return false;
	}

	protected function domainkontrol(){
		GLOBAL $skayit;
		GLOBAL $suzanti;
		GLOBAL $dil;

		
		$domain = strip_tags($this->tum_bosluk_sil($_POST['alanadi']));
		$this->domain = $this->domainPakle($domain);
		/******/
		$ip = $_SERVER['REMOTE_ADDR'];
		$pagina = $_SERVER['REQUEST_URI'];
		$datum = date("d-m-y / H:i:s");
		//$invoegen = $datum . " - " . $ip . " - " . $pagina ."-". $domain. "<br />";
		$invoegen = $datum . " - " . $ip . " - " ." &nbsp;<b>(". $domain."</b>)". "<br />";
		$fopen = fopen("kontrol.html", "a");
		fwrite($fopen, $invoegen);
		fclose($fopen);  

		/*******/
		if(@is_array($_POST['uzanti'])){
			$uzantilar = array_map('strip_tags',$_POST['uzanti']);
		}else{
			echo '<div class="notice">Hiçbir uzantı seçmediniz.</div>';
			return false;
		}
		
		foreach($uzantilar as $uzanti){
			$uzanti = strtolower(trim($uzanti));
			$this->domainSorgula($this->domain, $uzanti);	
		}
		
		echo '
		<table width="100%" border="0" class="domainler" align="center" style="display:inline-table;margin-top:50px;">
		<thead>';
		foreach ($this->domainBilgi as $key=>$value) {
			if(!empty($value['hata'])){
				$text1 = $this->hata[$value['hata']];
				$text2 = '';
				$class= "hata";
			}elseif($value['durum']){
				$domainAd = '<a target="_blank" href="http://'.$this->domain.'.'.$key.'">'.$this->domain.'.'.$key.'</a>';
				$text1 = '<span class="kirmizi">'.@$dil['txt354'].'</span>';
				$text2 = '<span class="link">Detay</span><div class="popDiv none"><div class="title">'.$this->domain.'.'.$key.' - Adlı Veriye Göre Domain Sorgulama. <span class="close">x</span></div><div class="whoScrol"><pre>'.$value['whois'].'</pre></div></div>';
				$class= "Alınmış";
				$alanlar= '<tr>
							<td colspan="4" align="center">
								<h4> <strong style="color:red;">(<a href="http://'.$this->domain.'.'.$key.'" target="_blank">'.$domainAd.'</a>) '.@$dil['txt353'].'</strong></h4>
							</td>
						</tr>';
				
			}else{
				$domainAd = $this->domain.'.'.$key;
				$text1 = '<span class="yesil">'.@$dil['txt355'].'</span>';
				$suzantiID = array_search(".".$key, $suzanti);
				$fiyat= 1;
				if($suzantiID !== FALSE) $fiyat = (int) @$skayit[$suzantiID];
				if(isset($_SESSION['site_uyeid']))
				{
					$text2 = '<span class="link"><strong><a href="javascript:;" data-key="'.$key.'" data-alanadi="'.$this->domain.'" target="_parent" id="sl"><i class="fa fa-shopping-cart"></i> '.@$dil['txt356'].'</a></strong></span>';
				}
				else
				{
					$text2 = '<span class="link"><strong><a href="javascript:;" data-key="'.$key.'" data-alanadi="'.$this->domain.'" target="_parent" id="sl"><i class="fa fa-shopping-cart"></i> '.@$dil['txt356'].'</a></strong></span>';
					$class = "bos";
				}
				echo '<tr><td colspan="4" align="center"><h3 style="color:green;">'.@$dil['txt357'].' <strong>'.$domainAd.'</strong> '.@$dil['txt358'].'</h3></td></tr>';
				$alanlar = '<tr class="'.$class.'">
							<td width="30%"><a href="http://'.$this->domain.'.'.$key.'" target="_blank">'.$domainAd.'</a></td>
							<td width="15%" align="center">
								<strong style="color:green;">'.$text1.'</strong>
							</td>
							<td width="25%" align="center">
								<select id="tesclsure">
									<option value="1"> '.number_format($fiyat,2).' ₺. (1 '.@$dil['txt359'].')</option>
									<option value="2"> '.(number_format($fiyat*2,2)).' ₺. (2 '.@$dil['txt359'].')</option>
									<option value="3"> '.(number_format($fiyat*3,2)).' ₺. (3 '.@$dil['txt359'].')</option>
									<option value="4"> '.(number_format($fiyat*4,2)).' ₺. (4 '.@$dil['txt359'].')</option>
									<option value="5"> '.(number_format($fiyat*5,2)).' ₺. (5 '.@$dil['txt359'].')</option>
								</select>

							</td>
							<td width="30%" align="center">
								'.$text2.'
							</td>
						</tr>';
			}
			
			echo '
				<tr>
					<td width="25%" bgcolor="#F0F0F0"><strong>'.@$dil['txt360'].'</strong></td>
					<td width="15%" align="center" bgcolor="#F0F0F0"><strong>'.@$dil['txt361'].'</strong></td>
					<td width="30%" align="center" bgcolor="#F0F0F0"><strong>'.@$dil['txt362'].'</strong></td>
					<td width="30%" align="center" bgcolor="#F0F0F0"><strong>'.@$dil['txt363'].'</strong></td>
				</tr>				
			</thead>
			<tbody>
			'.$alanlar.'';
		}	
		echo '</tbody></table>';
		
	}

	protected function domainPakle($domain){
		$domain = strtolower(trim($this->tum_bosluk_sil($_POST['alanadi'])));
		$domain = str_replace(array('www.','http://'), array('',''), $domain);
		$slac = explode('/', $domain);
		$nokta = explode('.',$slac[0]);
		return $nokta[0];
	}
	
	public function __construct(){		
		if($_POST['formVeriAL'] == $_SESSION['formVeriAL']){
			$this->domainkontrol();
		}

	}
	
}


	$kontrol = new kontrol();

?>

<script type="text/javascript">
	$("#tesclsure").each(function(index, el) {
		var sl = $("#sl");
		if(sl.lenght == 0) return;
		var url = "alanadi-satinal/"+$(sl).data('alanadi')+"-"+$(sl).data('key')+"-"+$(this).val()+".html";
		$(sl).attr('href',url);
	}).change(function(event) {
		var sl = $("#sl");
		if(sl.lenght == 0) return;
		var url = "alanadi-satinal/"+$(sl).data('alanadi')+"-"+$(sl).data('key')+"-"+$(this).val()+".html";
		$(sl).attr('href',url);
	});
	
</script>