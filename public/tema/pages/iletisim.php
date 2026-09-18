<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null;?>
<div class="top-header item7 overlay" style="background-image: url(<?php echo tema;?>/uploads/arkaplan/iletisim/<?php echo $arkaplan['iletisim']?>)">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper">
                    <h1 class="heading"><?=@$dil['txt315'];?></h1>
                    <h3 class="subheading"><?=@$dil['txt316'];?></h3>
                </div>
            </div>
        </div>
    </div> 
</div>
<div class="ustbanner">
	<div class="container">
		<a href="index.html" class="golink gocheck"> <?=@$dil['txt158'];?> </a> <small class="c-white">&#9679;</small>
        <a href="iletisim.html" class="golink gocheck"> <?=@$dil['txt315'];?></a>
	</div>
</div>
<!-- ***** LOCATION ***** -->
<section class="services pt-4 sec-normal">
    <div class="container">
        <div class="randomline">
            <div class="bigline"></div>
            <div class="smallline"></div>
        </div>
        <div class="service-wrap">
            <div class="row">
                <div class="col-sm-12 col-md-6">
                    <div class="service-section pl-4 p-2" style="min-height:180px;">
						<div class="plans badge feat bg-pink"><?=@$dil['txt317'];?></div>
                        <div class="title"><?php echo firma_adi;?></div>
                        <div class="subtitle"><?=@$dil['txt45'];?></br> <?php echo adres;?></div>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6">
                    <div class="service-section pl-4 p-2" style="min-height:180px;">                        
                        <div class="title"><?=@$dil['txt318'];?></div>
                        <div class="subtitle">
						<p>
                            <?=@$dil['txt319'];?>
						</p>
						</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- ***** HELP ***** -->
<section class="services help pt-4 pb-80 cpupath">
    <div class="container">
        <div class="service-wrap">
            <div class="row">
                <div class="col-sm-12 col-md-6 col-lg-4">
                    <div class="help-container">
                        <a href="destek-talebi-olustur.html" class="help-item">
                            <div class="img">
                                <img class="svg ico" src="<?php echo tema;?>/fonts/svg/livechat.svg" height="65" alt="">
                            </div>
                            <div class="inform">
                                <div class="title"><?=@$dil['txt320'];?></div>
                                <div class="description"><?=@$dil['txt321'];?></div>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-4">
                    <div class="help-container">
                        <a href="mailto:<?php echo email;?>" class="help-item gocheck">
                            <div class="img">
                                <img class="svg ico" src="<?php echo tema;?>/fonts/svg/emailopen.svg" height="65" alt="">
                            </div>
                            <div class="inform">
                                <div class="title"><?=@$dil['txt322'];?></div>
                                <div class="description"><?php echo email;?></div>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-4">
                    <div class="help-container">
                        <a href="tel:<?php echo telefon;?>" class="help-item">
                            <div class="img">
                                <img class="svg ico" src="<?php echo tema;?>/fonts/svg/phone.svg" height="65" alt="">
                            </div>
                            <div class="inform">
                                <div class="title"><?=@$dil['txt323'];?></div>
                                <div class="description"><?php echo telefon;?></div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- ***** MAP ***** -->
<section class="services maping sec-normal p-0">
	<div class="service-wrap">
		 <?php echo maps;?>
	</div>
</section>
<!-- ***** CONTACT FORM ***** -->
<section id="ticket" class="pb-80">
    <div class="container">
        <div class="sec-main sec-up mb-0 sec-bg1">
            <div class="randomline">
                <div class="bigline"></div>
                <div class="smallline"></div>
            </div>
            <div class="row">
                <div class="col-md-12 col-lg-12 cd-filter-block mb-0">
                    <div class="form-contact cd-filter-content p-0 sec-bx">
                        <h2 class="section-heading mb-1"><?=@$dil['txt324'];?></h2>
                        <p><?=@$dil['txt325'];?></p>
                        <form action="_class/site_islem.php" method="post" autocomplete="off">
                            <div class="row">
                                <div class="col-md-6">
                                    <label><i class="fas fa-user-tie"></i></label>
                                    <input type="text" name="isim" placeholder="<?=@$dil['txt326'];?>" required="">
                                </div>
                                <div class="col-md-6">
                                    <label><i class="fas fa-envelope"></i></label>
                                    <input type="email" name="email" placeholder="<?=@$dil['txt327'];?>" required="">
                                </div>
                                <div class="col-md-6">
                                    <label><i class="fas fa-phone"></i></label>
                                    <input type="text" name="telefon" placeholder="<?=@$dil['txt328'];?>">
                                </div>
                                <div class="col-md-6">
                                    <label><i class="fas fa-file-alt"></i></label>
                                    <input type="text" name="konu" placeholder="<?=@$dil['txt329'];?>">
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mt-4">
                                        <textarea name="mesaj" class="form-control" rows="5" placeholder="<?=@$dil['txt330'];?>"></textarea>
                                    </div>
                                </div>
								<div class="col-md-6">
                                    <div class="form-group mt-4">
                                       <div class="g-recaptcha" data-sitekey="<?php echo rcaptha; ?>"></div>
                                    </div>
                                </div>
                                <div class="col-md-6 mt-5">
                                    <button type="submit" name="mesajbtn" class="btn btn-default-yellow-fill float-left mr-3"><?=@$dil['txt331'];?></button>
                                    <button type="reset" class="btn btn-default-fill mt-0 mb-3 mr-3"><?=@$dil['txt332'];?></button><br>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php 
if($_SESSION['mesajbtn'] == 'yes')
{
	echo "
	<script>
	swal({
		type: 'success',
		title: '".@$dil['txt11']."',
		text: '".@$dil['txt333']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";
	unset($_SESSION['mesajbtn']);
}		
if($_SESSION['mesajbtn'] == 'no')
{
	echo "
	<script>
	swal({
		type: 'error',
		title: '".@$dil['txt16']."',
		text: '".@$dil['txt17']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";	
	unset($_SESSION['mesajbtn']);
}
if($_SESSION['mesajbtn'] == 'bos')
{
	echo "
	<script>
	swal({
		type: 'warning',
		title: '".@$dil['txt13']."',
		text: '".@$dil['txt14']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";	
	unset($_SESSION['mesajbtn']);
}
?>