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
		2   =>  'bitis_tarih',
		3   =>  'tutar',
		4   =>  'durum'
	);

	//Search
	$sql ="SELECT * FROM satilanlar WHERE uyeid= '".$_SESSION['site_uyeid']."' AND tipi=0";
	if(!empty($request['search']['value'])){
		$sql.=" AND (id LIKE '".$request['search']['value']."%' ";
		$sql.=" OR domain LIKE '".$request['search']['value']."%' ";
		$sql.=" OR bilgiler LIKE '".$request['search']['value']."%' ";
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
		if($row['durum'] == 0){
			$durum = '<label class="alert alert-danger alert-sm mb-0">'.@$dil['txt297'].'</label>';
		}
		if($row['durum'] == 1){
			 $durum = '<label class="alert alert-success alert-sm mb-0">'.@$dil['txt345'].'</label>';
			 if($row['sdurum'] == 0)
			 {
				 $sdurum = '<span style="color:green;font-weight:bold;">'.@$dil['txt298'].'</span>';
			 }
			 if($row['sdurum'] == 1)
			 {
				 $sdurum = '<span style="color:red;">'.@$dil['txt346'].'</span>';
			 }
			  if($row['sdurum'] == 2)
			 {
				 $sdurum = '<span style="color:red;">'.@$dil['txt347'].'</span>';
			 }
			 $degerler = '-'.$sdurum.'<br /><span style="font-size:14px;">DNS Bilgileriniz : '.$row['bilgiler'].'</span>';
		}
		$baslik = '<strong>'.$row['domain'].'</strong> '.$degerler.'';

		$subdata=array();
		$subdata[]=$baslik;	
		$subdata[]=date("d.m.Y",strtotime($row['baslangic_tarih']));	
		$subdata[]=date("d.m.Y",strtotime($row['bitis_tarih']));
        $subdata[] = my_number_format($row['tutar']).' TL';
		$subdata[]=$durum;
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