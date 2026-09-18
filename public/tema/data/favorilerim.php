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
		2   =>  'islem'
	);

	//Search
	$sql ="SELECT * FROM favoriler WHERE ilanid= '".$_SESSION['site_uyeid']."'";
	if(!empty($request['search']['value'])){
		$sql.=" AND (id LIKE '".$request['search']['value']."%' ";
		$sql.=" OR ilanid LIKE '".$request['search']['value']."%' ";
		$sql.=" OR tarih LIKE '".$request['search']['value']."%' )";
	}
    $totalFilter = $db->query($sql)->rowCount();
	$totalData = $db->query($sql)->rowCount();
	
	//Order
	$sql.=" ORDER BY ".$col[$request['order'][0]['column']]."   ".$request['order'][0]['dir']."  LIMIT ".$request['start']."  ,".$request['length']."  ";
	$query 	= $db->prepare($sql);
	$query->execute();
	$islem 	= $query->fetchALL(PDO::FETCH_ASSOC);
	$data	= array();
	foreach($islem as $row) 
	{
		$ilanbul 	= $db->query("SELECT * FROM yazilimlar WHERE id = '{$row['icerikid']}'")->fetch(PDO::FETCH_ASSOC);
		$detay		= '<a href="detay/'.$ilanbul['seo'].'.html">'.$ilanbul['adi'].'</a>';

		$subdata=array();
		$subdata[]=$detay;
		$subdata[]=$row['tarih'];
		$subdata[]='<a href="_class/site_islem.php?favoricikar=ok&id='.$ilanbul["id"].'&link=favorilerim.html" class="btn btn-outline-primary btn-sm" title="'.@$dil['txt348'].'"><i class="fa fa-trash-o" title="'.@$dil['txt348'].'"></i> '.@$dil['txt348'].'</a>';
		$data[]=$subdata;
	}
	$json_data=array(
		"draw"             		=>  intval($request['draw']),
		"recordsTotal"      	=>  intval($totalData),
		"recordsFiltered"   	=>  intval($totalFilter),
		"data"             		=>  $data
	);
	echo json_encode($json_data);
}
else
{
	die("Erişim engellendi");
}
?>