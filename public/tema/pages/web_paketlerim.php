<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null;?>
<?php if(!isset($_SESSION["site_uyeid"]))
{
	$_SESSION['devam'] = "web_paketlerim";
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
                    <h1 class="heading"><?=@$dil['txt283'];?></h1>
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
            <?php } ?><i><?=@$dil['txt28'];?></i></span>

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
							<h5 class="title">
								<i class="fab fa-chrome"></i>
								<?=@$dil['txt283'];?>
							</h5>
							<div class="pull-right">
								<strong><a href="hesabim.html"><?=@$dil['txt32'];?> </a></strong> /
								<a href="web_paketlerim.html"><?=@$dil['txt283'];?> </a>
							</div>
						</div>

							<table width="100%" id="web_paketlerim" class="table table-bordered table-striped">
								<thead>
								<tr>
									<th class="text-left"><?=@$dil['txt343'];?></th>
									<th scope="col" class="text-left" style="width:100px;"><?=@$dil['txt344'];?></th>
									<th scope="col" class="text-center" style="width:80px;"><?=@$dil['txt36'];?></th>
									<th scope="col" class="text-center" style="width:100px;"><?=@$dil['txt186'];?></th>
									<th scope="col" class="text-center" style="width:100px;"><?=@$dil['txt37'];?></th>
								</tr>
								</thead>
								<tbody>

								</tbody>
							</table>


						
					</div>

				</div>
			</div>


		</div>

    </div>
</div>
<style>
@media only screen and (max-width: 1024px) and (min-width: 320px){
 
    #web_paketlerim table, 
    #web_paketlerim thead, 
    #web_paketlerim tbody, 
    #web_paketlerim th, 
    #web_paketlerim td, 
    #web_paketlerim tr { 
        display: block; 
    }
     
    #web_paketlerim thead tr { 
        position: absolute;
        top: -9999px;
        left: -9999px;
    }
     
    #web_paketlerim tr { margin-bottom:10px;border-bottom: 1px solid #ccc; }

    #web_paketlerim td { 
        border-bottom: none; 
        position: relative;
        padding-left: 40%; 
		overflow: hidden;
		height: auto;
		text-align: left !important;
    }
    #web_paketlerim td:before { 
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
     
    #web_paketlerim td:nth-of-type(1):before { content: "<?=@$dil['txt343'];?>"; }
    #web_paketlerim td:nth-of-type(2):before { content: "<?=@$dil['txt344'];?>"; }
    #web_paketlerim td:nth-of-type(3):before { content: "<?=@$dil['txt36'];?>"; }
    #web_paketlerim td:nth-of-type(4):before { content: "<?=@$dil['txt186'];?>"; }
    #web_paketlerim td:nth-of-type(5):before { content: "<?=@$dil['txt37'];?>"; }

}
</style>
<script type="text/javascript">
$(document).ready(function(){
	var dataTable=$('#web_paketlerim').DataTable({
		"processing": true,
		"serverSide":true,
		"ajax":{
			url:"<?php echo tema;?>/data/web_paketlerim.php",
			type:"post"
		},
		<?php if(strip_tags(isset($_GET['filtrele']))){?>
		"search": {
			"search": "<?php echo $_GET['filtrele'];?>"
		},
		<?php }?>
		"order": [
			[ 0, "desc" ]
		],
		"aLengthMenu": [
			[5, 10, 15, -1],
			[5, 10, 15, "Tümü"]
		],
		"columnDefs": [
			{ "orderable": false, "targets": [4] },
			{  "className": "text-left align-middle", targets: [0] },
			{  "className": "text-center align-middle", targets: [1,2,3,4] }
		],
		"iDisplayLength": 10,
		responsive: true,
		"language": {
			"url":"<?php echo tema;?>/js/lang.json"
		}
	});
});
</script>
