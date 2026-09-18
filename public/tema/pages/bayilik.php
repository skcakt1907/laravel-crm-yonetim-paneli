<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null;?>

<div class="top-header overlay" style="background-image: url(<?php echo tema;?>/uploads/arkaplan/paketler/<?php echo $arkaplan['paketler']?>)">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper">
                    <h1 class="heading"><?=@$dil['txt380'];?></h1>
                    <h3 class="subheading"><?=@$dil['txt381'];?></h3>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="ustbanner">
    <div class="container">
        <a href="index.html" class="golink gocheck"> <?=@$dil['txt158'];?> </a> <small class="c-white">&#9679;</small>
        <a href="bayilik.html" class="golink gocheck"> <?=@$dil['txt380'];?> </a>
    </div>
</div>

<?php
$Bayilikler = $db->query("SELECT * FROM bayilikler ORDER BY sira ASC",PDO::FETCH_ASSOC);
?>
<!-- ***** FILTER PLANS ***** -->
<div class="mixcontainer pb-5 pt-4 sec-bg2 " data-ref="container">
    <div class="container ">
        <div class="pricing special">
            <div class="p-0 m-0">
                <?php foreach ( $Bayilikler as $Bayilik ){?>

                    <div class="mix col-md-4" data-size="0">
                        <div class="wrapper text-center">
                            <div class="top-content p-3">
                                <div class="title"><?php echo $Bayilik['paketadi']; ?></div><br/>
                                <div class="price"><?php echo $Bayilik['fiyat']; ?> TL</div>
                                <div class="fromer"><?=@$dil['txt382'];?></div>
                                <div class="fromer"><?=@$dil['txt383'];?></div>
                                <div class="fromer"><?=@$dil['txt384'];?></div>
                                <div class="fromer"><?=@$dil['txt385'];?><?php echo $Bayilik['indirim']?> <?=@$dil['txt386'];?></div>
                                <?php
                                $link = '/bakiyem.html?bid='.$Bayilik['id'];
                                if(defaultpayment==2){
                                    $link = 'https://shopier.com/ShowProductNew/products.php?id='.$Bayilik['shopierid'];
                                }elseif(defaultpayment==3){
                                    $link = $Bayilik['iyzilink'];
                                }
                                ?>
                                <a href="<?=$link;?>" class="btn btn-block btn-primary"><i class="fa fa-shopping-cart"></i> <?=@$dil['txt387'];?></a>
                            </div>
                        </div>
                    </div>
                <?php }?>

                <div class="gap"></div>
                <div class="gap"></div>
                <div class="gap"></div>
            </div>
        </div>

    </div>
</div>
