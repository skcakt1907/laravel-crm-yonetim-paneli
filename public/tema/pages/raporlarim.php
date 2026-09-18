<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null;?>
<?php if(!isset($_SESSION["site_uyeid"]))
{
    $_SESSION['devam'] = "raporlarim";
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
                    <h1 class="heading">Raporlarım</h1>
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
                            <h5 class="title">
                                <i class="fas fa-briefcase"></i>
                                Raporlarım
                            </h5>
                            <div class="pull-right">
                                <strong><a href="hesabim.html"><?=@$dil['txt32'];?> </a></strong> /
                                <a href="raporlarim.html">Raporlarım </a>
                            </div>
                        </div>
                        <table width="100%" id="raporlarim" class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th scope="col" class="text-left"><?=@$dil['txt40'];?></th>
                                <th scope="col" class="text-left">Tarih</th>
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

        #raporlarim table,
        #raporlarim thead,
        #raporlarim tbody,
        #raporlarim th,
        #raporlarim td,
        #raporlarim tr {
            display: block;
        }

        #raporlarim thead tr {
            position: absolute;
            top: -9999px;
            left: -9999px;
        }

        #raporlarim tr { margin-bottom:10px;border-bottom: 1px solid #ccc; }

        #raporlarim td {
            border-bottom: none;
            position: relative;
            padding-left: 40%;
            overflow: hidden;
            height: auto;
            text-align: left !important;
        }
        #raporlarim td:before {
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

        #raporlarim td:nth-of-type(1):before { content: "<?=@$dil['txt40'];?>"; }
        #raporlarim td:nth-of-type(2):before { content: "<?=@$dil['txt36'];?>"; }
        #raporlarim td:nth-of-type(3):before { content: "<?=@$dil['txt186'];?>"; }

    }
</style>
<script type="text/javascript">
    $(document).ready(function(){
        var dataTable=$('#raporlarim').DataTable({
            "processing": true,
            "serverSide":true,
            "ajax":{
                url:"<?php echo tema;?>/data/raporlarim.php",
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
                { "orderable": false, "targets": [0] },
                {  "className": "text-left align-middle", targets: [0] },
                {  "className": "text-center align-middle", targets: [0] }
            ],
            "iDisplayLength": 10,
            responsive: true,
            "language": {
                "url":"<?php echo tema;?>/js/lang.json"
            }
        });
    });
</script>
