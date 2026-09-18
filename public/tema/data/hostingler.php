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
        4   =>  'sdurum',
        5   =>  'islem'
    );

    //Search
    $sql ="SELECT * FROM satilanlar WHERE tipi = '1' AND uyeid= '".$_SESSION['site_uyeid']."'";
    if(!empty($request['search']['value'])){
        $sql.=" AND (id LIKE '".$request['search']['value']."%' ";
        $sql.=" OR hosting_baslik LIKE '".$request['search']['value']."%' ";
        $sql.=" OR domain LIKE '".$request['search']['value']."%' ";
        $sql.=" OR tutar LIKE '".$request['search']['value']."%' ";
        $sql.=" OR baslangic_tarih LIKE '".$request['search']['value']."%' ";
        $sql.=" OR bitis_tarih LIKE '".$request['search']['value']."%' ";
        $sql.=" OR sdurum LIKE '".$request['search']['value']."%' )";
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
        if($row['sdurum'] == 1){
		$durum = '<label class="alert alert-warning alert-sm m-0">Askıya Alındı</label>';
		$buton = '<a title="'.@$dil['txt350'].'" style="-webkit-filter:grayscale(100%);filter: grayscale(100%);color: #777;opacity: 0.5;filter: alpha(opacity=50);" class="btn btn-outline-secondary btn-sm"><i class="fa fa-cog" aria-hidden="true"></i> '.@$dil['txt350'].'</a>';
	}
	if($row['sdurum'] == 0){
		 $durum = '<label class="alert alert-success alert-sm m-0">Aktif</label>';
		 $buton = '<a href="" data-toggle="modal" data-target="#hosting-'.$row['id'].'" data-backdrop="static" data-keyboard="false" class="btn btn-outline-secondary btn-sm"><i class="fa fa-cog" aria-hidden="true"></i> '.@$dil['txt350'].'</a>';
	}
	if($row['sdurum'] == 2){
		$durum = '<label class="alert alert-danger alert-sm m-0">İptal Edildi</label>';
		$buton = '<a title="'.@$dil['txt350'].'" style="-webkit-filter:grayscale(100%);filter: grayscale(100%);color: #777;opacity: 0.5;filter: alpha(opacity=50);" class="btn btn-outline-secondary btn-sm"><i class="fa fa-cog" aria-hidden="true"></i> '.@$dil['txt350'].'</a>';
	}

        if($row['zmnt'] == 0)
        {
            $zmnt	= "(".@$dil['txt81'].")";
        }
        if($row['zmnt'] == 1)
        {
            $zmnt	= "(".@$dil['txt83'].")";
        }
        $subdata=array();
        $subdata[]='<strong class="class="link"">'.$row['hosting_baslik'].'</strong><p class="t-detail">'.$row['domain'].'</p>';
        $subdata[]=date("d.m.Y",strtotime($row['baslangic_tarih']));
        $subdata[]=date("d.m.Y",strtotime($row['bitis_tarih']));
        $subdata[]=my_number_format($row['tutar']). ' TL ' .$zmnt;
        $subdata[]=$durum;
        $subdata[]=''.$buton.'
					<div class="modal fade" id="hosting-'.$row['id'].'" role="dialog">
						<div class="modal-dialog modal-lg">
							<div class="modal-content text-left">
								<div class="modal-header pb-2 pt-2" style="background:#38647A;">				
									<h5 class="modal-title d-inline-block text-white">'.$row['domain'].' '.@$dil['txt352'].'</h5>
									<button type="button" class="close p-0 m-1" data-dismiss="modal"><i class="far fa-times-circle"></i></button>
								</div>
								<div class="modal-body">                
									<div class="modal-text">
										<div class="alert alert-warning">
											'.@$dil['txt351'].'
										</div>
										'.nl2br($row['bilgiler']).'  
									</div>											
								</div>
							</div>
						</div>
					</div>';
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
