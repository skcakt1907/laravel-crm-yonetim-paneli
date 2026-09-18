<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null;?>
<?php if(!isset($_SESSION["site_uyeid"]))
{
	$_SESSION['devam'] = "destek-taleplerim";
	header("Location:".$url."/giris.html");
}
else
{
	unset($_SESSION['devam']);
}
?>
<div class="top-header overlay" style="background-image: url(<?php echo tema;?>/uploads/arkaplan/uyelik/<?php echo $arkaplan['uyelik']?>)">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper">
                    <h1 class="heading"><?=@$dil['txt182'];?></h1>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="ustbanner">
	<div class="container">
		<span><?=@$dil['txt27'];?> <strong><?php echo $Bilgilerim['ad'];?> <?php echo $Bilgilerim['soyad'];?></strong>.
            <?php if($Bilgilerim['bayi']!=0){
                $Bayi = $db->query("SELECT * FROM bayilikler WHERE id='{$Bilgilerim['bayi']}'")->fetch(PDO::FETCH_ASSOC);
                ?>
                (<?=$Bayi['paketadi'];?>)
            <?php } ?> <i><?=@$dil['txt28'];?></i></span>

		<div class="ustsil"></div>

		<span class="ustson"><?=@$dil['txt29'];?> <strong> <?php echo TvERtXpE3w_tarih($Bilgilerim['son_giris']);?></strong> <?=@$dil['txt30'];?> <div class="ustsil"></div>
			<?=@$dil['txt31'];?> <strong><?php echo $Bilgilerim['ip'];?></strong></span>
	</div>
</div>

<!-- ***** FILTER PLANS ***** -->
<div class="mixcontainer">
    <div class="container">

		<div id="wrapper" class="mt-4">
		
			<div class="row">
				<?php require_once("sitebar.php");?>
				<div class="col-md-9">
					<div class="col-md-12 border-left-3 main-content">
						<div class="title-area mb-4">
							<h5 class="title"><i class="fa fa-life-ring"></i> <?=@$dil['txt182'];?></h5>
							<a href="destek-talebi-olustur.html" class="btn btn-sm btn-outline-primary pull-right"> + <?=@$dil['txt222'];?></a>
						</div>
						<table width="100%" id="datatable" class="table table-bordered table-striped">
							<thead>
							<tr>								
								<th scope="col" class="text-center" style="width:60px;"><?=@$dil['txt223'];?></th>
								<th scope="col" class="text-left"><?=@$dil['txt224'];?></th>
								<th scope="col" class="text-left" style="width:160px;"><?=@$dil['txt225'];?></th>
								<th scope="col" class="text-center" style="width:140px;"><?=@$dil['txt186'];?></th>
								<th scope="col" class="text-center" style="width:150px;"><?=@$dil['txt226'];?></th>
							</tr>
							</thead>
							<tbody></tbody>
						</table>
					</div>
				</div>
			</div>

		</div>

    </div>
</div>
<style>
@media 
only screen and (max-width: 760px),
(min-device-width: 768px) and (max-device-width: 1024px)  {
 
    #datatable table, 
    #datatable thead, 
    #datatable tbody, 
    #datatable th, 
    #datatable td, 
    #datatable tr { 
        display: block; 
    }
     
    #datatable thead tr { 
        position: absolute;
        top: -9999px;
        left: -9999px;
    }
     
    #datatable tr { margin-bottom:10px;border-bottom: 1px solid #ccc; }

    #datatable td { 
        border-bottom: none; 
        position: relative;
        padding-left: 40%; 
		overflow: hidden;
		height: auto;
		text-align: left !important;
    }
    #datatable td:before { 
        position: absolute;
        top: 0px;
        left: 0px;
        width: 35%; 
        padding: 12px 10px 0 5px;
        height: 100%;
        white-space: nowrap;
        background-color:#333;
        color:#fff;
		text-align: left;
    }
     
    #datatable td:nth-of-type(1):before { content: "<?=@$dil['txt223'];?>"; }
    #datatable td:nth-of-type(2):before { content: "<?=@$dil['txt224'];?>"; }
    #datatable td:nth-of-type(3):before { content: "<?=@$dil['txt225'];?>"; }
    #datatable td:nth-of-type(4):before { content: "<?=@$dil['txt186'];?>"; }
    #datatable td:nth-of-type(5):before { content: "<?=@$dil['txt226'];?>"; }

}
</style>
<script type="text/javascript">
$(document).ready(function(){
	var dataTable=$('#datatable').DataTable({
		"processing": true,
		"serverSide":true,
		"ajax":{
			url:"<?php echo tema;?>/data/destek.php",
			type:"post"
		},
		"order": [
			[ 0, "desc" ]
		],
		"aLengthMenu": [
			[5, 10, 15, -1],
			[5, 10, 15, "Tümü"]
		],
		"columnDefs": [
			{ "orderable": false, "targets": [0, 4] },
			{  "className": "text-left align-middle", targets: [1,2] },
			{  "className": "text-center align-middle", targets: [0,3,4] }
		],
		"iDisplayLength": 10,
		responsive: true,
		"language": {
			"url":"<?php echo tema;?>/js/lang.json"
		}
	});
});
</script>
