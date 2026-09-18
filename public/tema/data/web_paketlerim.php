<?php 
if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
{
	ob_start();
	session_start();
	 	
	require_once('../../../_class/baglan.php');
	require_once('../../../_class/fonksiyon.php');
	require_once('../../../language/dil_'.$_SESSION['k_dil'].".php");
	$request=$_REQUEST;
	$col =array(
		0   =>  'id',
		1   =>  'baslangic_tarih',
		2   =>  'tutar',
		3   =>  'durum',
		4   =>  'islem'
	);

	//Search
	$sql ="SELECT * FROM satilanlar WHERE tipi = '2' AND uyeid= '".$_SESSION['site_uyeid']."'";
	if(!empty($request['search']['value'])){
		$sql.=" AND (id LIKE '".$request['search']['value']."%' ";
		$sql.=" OR paket_baslik LIKE '".$request['search']['value']."%' ";
		$sql.=" OR domain LIKE '".$request['search']['value']."%' ";
		$sql.=" OR tutar LIKE '".$request['search']['value']."%' ";
		$sql.=" OR baslangic_tarih LIKE '".$request['search']['value']."%' ";
		$sql.=" OR bitis_tarih LIKE '".$request['search']['value']."%' ";
		$sql.=" OR durum LIKE '".$request['search']['value']."%' )";
	}
    $totalFilter = $db->query($sql)->rowCount();
	$totalData = $db->query($sql)->rowCount();
	//Order
	$sql.=" ORDER BY ".$col[$request['order'][0]['column']]."   ".$request['order'][0]['dir']."  LIMIT ".$request['start']."  ,".$request['length']."  ";
	$query 	= $db->prepare($sql);
	$query->execute();
	$islem 	= $query->fetchALL(PDO::FETCH_ASSOC);
	$data	= array();
	$say 	= $request['start']+1;
	foreach($islem as $row) 
	{
		$paketler	= $db->query("SELECT * FROM yazilimlar WHERE id=".$row['paket'])->fetch(PDO::FETCH_ASSOC);
		if($row['durum'] == 0){
			$durum = '<label class="alert alert-danger alert-sm m-0">'.@$dil['txt297'].'</label>';
			$buton = '<a title="'.@$dil['txt364'].'" style="-webkit-filter:grayscale(100%);filter: grayscale(100%);color: #777;opacity: 0.5;filter: alpha(opacity=50);" class="btn btn-outline-secondary btn-sm"><i class="fa fa-cloud-download" aria-hidden="true"></i> '.@$dil['txt364'].'</a>';
		}
		if($row['durum'] == 1){
			$durum = '<label class="alert alert-success alert-sm m-0">'.@$dil['txt298'].'</label>';
			if($row['download'] == '')
			{
				
				if($paketler['download_file'] != '')
				{
					$buton = '<a target="_blank" href="'.tema.'/uploads/webpaketleri/dosya/'.$paketler['download_file'].'" class="btn btn-outline-secondary btn-sm"><i class="fa fa-cloud-download" aria-hidden="true"></i> '.@$dil['txt364'].'</a>';
				}
				elseif($paketler['download_link'] != '')
				{
					$buton = '<a target="_blank" href="'.$paketler['download_link'].'" class="btn btn-outline-secondary btn-sm"><i class="fa fa-cloud-download" aria-hidden="true"></i> '.@$dil['txt364'].'</a>';
				}
				else
				{
					$buton = '<a title="'.@$dil['txt364'].'" style="-webkit-filter:grayscale(100%);filter: grayscale(100%);color: #777;opacity: 0.5;filter: alpha(opacity=50);" class="btn btn-outline-secondary btn-sm"><i class="fa fa-cloud-download" aria-hidden="true"></i> '.@$dil['txt364'].'</a>';
				}				
			}
			else
			{
				$buton = '<a href="'.$row['download'].'" class="btn btn-outline-secondary btn-sm"><i class="fa fa-cloud-download" aria-hidden="true"></i> '.@$dil['txt364'].'</a>'; 
			}
			 
		}
		$subdata=array();
		$subdata[]="<strong class='link'>".$row['paket_baslik']."</strong><p class='t-detail'>".$row['domain']."</p>";
		$subdata[]=date("d.m.Y",strtotime($row['baslangic_tarih']));		
		$subdata[]=my_number_format($row['tutar']) . ' TL';
		$subdata[]=$durum;
		$subdata[]=''.$buton.'';
		$data[]=$subdata;
	}
	$json_data=array(
		"draw"              		=>  intval($request['draw']),
		"recordsTotal"      	=>  intval($totalData),
		"recordsFiltered"   	=>  intval($totalFilter),
		"data"              		=>  $data
	);
	echo json_encode($json_data);
}
else
{
	die("Erişim engellendi");
}
?>