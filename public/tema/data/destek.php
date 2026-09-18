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
		1   =>  'tarih',
		2   =>  'baslik',
		3   =>  'durum',
		4   =>  'islem'
	);

	//Search
	$sql ="SELECT * FROM destek WHERE ustid = '0' AND uyeid= '".$_SESSION['site_uyeid']."'";
	if(!empty($request['search']['value'])){
		$sql.=" AND (id LIKE '".$request['search']['value']."%' ";
		$sql.=" OR baslik LIKE '".$request['search']['value']."%' ";
		$sql.=" OR hizmet LIKE '".$request['search']['value']."%' )";
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
		if($row['durum'] == 0){
			$durum = '<label class="alert alert-danger alert-sm m-0 text-center">'.@$dil['txt187'].'</label>';
		}
		if($row['durum'] == 1){
			$durum = '<label class="alert alert-success alert-sm m-0 text-center">'.@$dil['txt188'].'</label>';
		}
		if($row['durum'] == 2){
			$durum = '<label class="alert alert-info alert-sm m-0 text-center">'.@$dil['txt189'].'</label>';
		}

		$subdata=array();
		$subdata[]=$row['id'];
		$subdata[]='<a class="link" href="destek/'.$row['id'].'.html">'.$row['baslik'].'</a><p class="t-detail">'.$row['hizmet'].'</p>';
		$subdata[]=TvERtXpE3w_tarihcevir($row['tarih']);		
		$subdata[]=$durum;
		$subdata[]='<a href="destek/'.$row['id'].'.html" class="btn btn-outline-primary btn-sm"><i class="fa fa-search" aria-hidden="true"></i> '.@$dil['txt291_1'].'</a>';
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