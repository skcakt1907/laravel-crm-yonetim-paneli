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
        0   =>  'baslik',
        1   =>  'id'
    );

    //Search
    $sql ="SELECT * FROM musteri_efaturalar WHERE uyeid= '".$_SESSION['site_uyeid']."'";
    if(!empty($request['search']['value'])){
        $sql.=" AND (id LIKE '".$request['search']['value']."%' ";
        $sql.=" OR baslik LIKE '".$request['search']['value']."%' ";
        $sql.=" OR tutar LIKE '".$request['search']['value']."%' ";
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
        $durumlar	= explode("\n",$row['durum']);
        $i		= 0;
        $kc		= count($durumlar);
        $durum 	= "";
        foreach($durumlar as $durumyaz)
        {
            $i		= $i+1;
            $durum    .= '<span style="font-size:14px;color:'.($i == $kc ? 'green' : 'grey').';"><strong>'.$durumyaz.'</strong></span><br>';
        }
        if($row['dosya'] != '')
        {
            $dosya = '<strong><a target="_blank" href="'.tema.'/uploads/dosyalar/'.$row['dosya'].'"><i class="fa fa-file-word-o"></i> E-Fatura İndir</a></strong>';
        }
        else
        {
            $dosya = '';
        }
        $baslik = '<strong>'.$row['baslik'].'</strong><br>'.nl2br($row['icerik']).'<br />'.$dosya.'';

        $subdata=array();
        $subdata[]=$baslik;
        $subdata[]=$row['tarih'];
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