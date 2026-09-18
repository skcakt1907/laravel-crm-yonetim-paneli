<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null; ?>
<?php if (!isset($_SESSION["site_uyeid"])) {
    $_SESSION['devam'] = "hesabim";
    header("Location:" . $url . "/giris.html");
} else {
    unset($_SESSION['devam']);
    $toplam_destek         = $db->query("SELECT * FROM destek WHERE uyeid = '{$Bilgilerim['id']}' AND ustid = '0'")->rowCount();
    $odenmemis_fatura     = $db->query("SELECT * FROM faturalar WHERE uyeid = '{$Bilgilerim['id']}' AND durum = '0'")->rowCount();
    $alanadlari             = $db->query("SELECT * FROM satilanlar WHERE uyeid = '{$Bilgilerim['id']}' AND tipi = '0'")->rowCount();
    $yazilimlar             = $db->query("SELECT * FROM satilanlar WHERE uyeid = '{$Bilgilerim['id']}' AND tipi = '2'")->rowCount();
    $faturatutar         = $db->query("SELECT sum(tutar) AS toplagel FROM  faturalar WHERE uyeid = '{$Bilgilerim['id']}' AND durum = '0'")->fetch();
}
$files = $db->prepare("SELECT * FROM dosyalarim WHERE user_id = ?");
$files->execute(array($_SESSION['site_uyeid']));
$satirSayisi = $files->rowCount();
?>
<div class="top-header overlay" style="background-image: url(<?php echo tema; ?>/uploads/arkaplan/uyelik/<?php echo $arkaplan['uyelik'] ?>)">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper">
                    <h1 class="heading"><?= @$dil['txt398']; ?></h1>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="ustbanner">
    <div class="container">
        <span><?= @$dil['txt27']; ?> <strong><?php echo $Bilgilerim['ad']; ?> <?php echo $Bilgilerim['soyad']; ?></strong>.

            <?php if ($Bilgilerim['bayi'] != 0) {
                $Bayi = $db->query("SELECT * FROM bayilikler WHERE id='{$Bilgilerim['bayi']}'")->fetch(PDO::FETCH_ASSOC);
            ?>
                (<?= $Bayi['paketadi']; ?>)
            <?php } ?> <i><?= @$dil['txt28']; ?></i></span>

        <div class="ustsil"></div>

        <span class="ustson"><?= @$dil['txt29']; ?> <strong> <?php echo TvERtXpE3w_tarih($Bilgilerim['son_giris']); ?></strong> <?= @$dil['txt30']; ?> <div class="ustsil"></div>
            <?= @$dil['txt31']; ?> <strong><?php echo $Bilgilerim['ip']; ?></strong></span>
    </div>
</div>

<!-- ***** FILTER PLANS ***** -->
<div class="mixcontainer">
    <div class="container" id="muspanel">

        <div id="wrapper">

            <div class="row mt-5">
                <?php require_once("sitebar.php"); ?>

                <div class="col-md-9">
                    <div class="col-md-12 border-left-3 main-content">
                        <div class="title-area mb-4">
                            <h5 class="title">
                                <i class="fas fa-globe"></i>
                                <?= @$dil['txt398']; ?>
                            </h5>
                            <div class="pull-right">
                                <button data-toggle="modal" data-target="#dosya_yukle" data-backdrop="static" data-keyboard="false" class="ms-5 btn btn-primary btn-sm"><i class="fas fa-plus"></i> Dosya Ekle</button>
                            </div>
                        </div>
                        <div class="modal fade" id="dosya_yukle" role="dialog">
                            <div class="modal-dialog modal-lg">

                                <!-- Modal content-->
                                <div class="modal-content">
                                    <div class="modal-header pb-2 pt-2" style="background:#38647A;">
                                        <h5 class="modal-title d-inline-block text-white"><?= @$dil['txt400']; ?></h5>
                                        <button type="button" class="close p-0 m-1" data-dismiss="modal"><i class="far fa-times-circle"></i></button>
                                    </div>
                                    <form action="_class/site_islem.php" method="post" autocomplete="off" enctype="multipart/form-data">
                                        <div class="modal-body">
                                            <input name="file" type="file" class="dropify" />
                                        </div>
                                        <div class="modal-footer">
                                            <button name="dosya_yukle" class="button btn btn-primary p-2"><i class="fas fa-plus-circle"></i> <?= @$dil['txt399']; ?></button>
                                            <button type="button" class="button btn btn-default p-2" data-dismiss="modal"><i class="far fa-times-circle"></i> <?= @$dil['txt60']; ?></button>
                                        </div>
                                    </form>
                                </div>

                            </div>
                        </div>

                        <table width="100%" id="datatable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th scope="col" class="text-left">Dosya Adı</th>
                                    <th scope="col" class="text-center">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($satirSayisi === 0){ ?>
                                    <tr class="odd" style="text-align: center;"><td style="text-align: center;" valign="top" colspan="5" class="dataTables_empty">Tabloda herhangi bir veri mevcut değil</td></tr>
                                <?php }else{ ?>
                                <?php foreach ($files as $item) : ?>
                                    <tr>
                                        <td><?php echo $item['file_name'] ?></td>
                                        <td style="margin:auto;text-align:center">
                                            <a download="" type="button" href="tema/webajans/uploads/dosyalar2/<?php echo $item['file_name']; ?>" class="btn btn-outline-secondary btn-sm">İndir <i class="fa fa-cloud-download" aria-hidden="true"></i> </a>
                                            <button class="btn btn-outline-danger btn-sm" data-toggle="popover-x" data-target="#myPopover10a<?php echo $item['id']; ?>">Sil <i class="fa fa-trash" aria-hidden="true"></i> </button>
                                            <div id="myPopover10a<?php echo $item['id']; ?>" class="popover popover-x popover-default">
                                                <div class="arrow"></div>
                                                <h3 class="popover-header popover-title"><span class="close pull-right" data-dismiss="popover-x">&times;</span>Dikkat!!!</h3>
                                                <div class="popover-body popover-content">
                                                    <?= $item['file_name']; ?> Silmek İstediğinizden Eminmisiniz?
                                                </div>
                                                <form action="_class/site_islem.php" method="post">
                                                    <input type="text" name="id" required readonly hidden value="<?php echo $item['id']; ?>" id="">
                                                    <input type="text" name="url" required readonly hidden value="dosyalarim.html" id="">
                                                    <div class="popover-footer">
                                                        <button name="file_delete" type="submit" class="btn btn-sm btn-primary">Sil</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach;} ?>
                            </tbody>
                        </table>
                        <div class="alert alert-warning alert-dismissible fade show mt-4" role="alert">
                            <button type="button" class="close mt-0" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <?= @$dil['txt38']; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>
<?php if (isset($_GET['durum'])) : ?>
    <script>
        swal({
            type: 'success',
            title: 'Başarılı',
            text: 'Dosya Yükleme Başarılı',
            confirmButtonText: 'Tamam',
            timer: 5000
        })
    </script>
<?php endif; ?>
<?php if (isset($_GET['delete'])) : ?>
    <script>
        swal({
            type: 'success',
            title: 'Başarılı',
            text: 'Dosya Silme Başarılı',
            confirmButtonText: 'Tamam',
            timer: 5000
        })
    </script>
<?php endif; ?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script type="text/javascript" src="https://jeremyfagis.github.io/dropify/dist/js/dropify.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://jeremyfagis.github.io/dropify/dist/css/dropify.min.css">
<script>
    $('.dropify').dropify();
</script>
<style>
    @media only screen and (max-width: 1024px) and (min-width: 320px) {

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

        #datatable tr {
            margin-bottom: 10px;
            border-bottom: 1px solid #ccc;
        }

        #datatable td {
            border-bottom: none;
            position: relative;
            padding-left: 40%;
            overflow: hidden;
            height: auto;
        }

        #datatable td:before {
            position: absolute;
            top: 0px;
            left: 0px;
            width: 30%;
            padding: 12px 10px 0 5px;
            height: 100%;
            white-space: nowrap;
            background-color: #333;
            color: #fff;
            text-align: left;
        }

        #datatable td:nth-of-type(1):before {
            content: "<?= @$dil['txt40']; ?>";
        }

        #datatable td:nth-of-type(2):before {
            content: "<?= @$dil['txt36']; ?>";
        }

        #datatable td:nth-of-type(3):before {
            content: "<?= @$dil['txt186']; ?>";
        }

        #datatable td:nth-of-type(4):before {
            content: "<?= @$dil['txt37']; ?>";
        }
    }
</style>
<script type="text/javascript">
    $(document).ready(function() {
        var dataTable = $('#datatable').DataTable({
            "serverSide": true,
            "aLengthMenu": [
                [5, 10, 15, -1],
                [5, 10, 15, "Tümü"]
            ],
            "columnDefs": [{
                    "orderable": false,
                    "targets": [3]
                },
                {
                    "className": "text-left align-middle",
                    targets: [0]
                },
                {
                    "className": "text-center align-middle",
                    targets: [1, 2, 3]
                }
            ],
            "iDisplayLength": 10,
            responsive: true,
            "language": {
                "url": "<?php echo tema; ?>/js/lang.json"
            }
        });
    });
</script>