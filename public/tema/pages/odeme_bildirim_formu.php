<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null;?>
<div class="top-header item7 overlay" style="background-image: url(<?php echo tema;?>/uploads/arkaplan/iletisim/<?php echo $arkaplan['iletisim']?>)">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper">
                    <h1 class="heading"><?=@$dil['txt369'];?></h1>
                    <h3 class="subheading"><?=@$dil['txt370'];?></h3>
                </div>
            </div>
        </div>
    </div>
</div> 
<div class="ustbanner">
	<div class="container">
		<a href="index.html" class="golink gocheck"> <?=@$dil['txt158'];?> </a> <small class="c-white">&#9679;</small>
        <a href="odeme-bildirim-formu.html" class="golink gocheck"> <?=@$dil['txt369'];?></a>
	</div>
</div>

<!-- ***** CONTACT FORM ***** -->
<section id="ticket" class=" pb-80 pt-35">
    <div class="container">
        <div class="sec-main mb-0 sec-bg1">
            <div class="randomline">
                <div class="bigline"></div>
                <div class="smallline"></div>
            </div>
            <div class="row">
                <div class="col-md-12 col-lg-12 cd-filter-block mb-0">
                    <div class="form-contact cd-filter-content p-0 sec-bx">
                        <form action="_class/site_islem.php" method="post" autocomplete="off">
                            <div class="row">
                                <div class="col-md-6">
                                    <label><i class="fas fa-user-tie"></i></label>
                                    <input type="text" name="isim" placeholder="<?=@$dil['txt326'];?>" required="">
                                </div>
                                <div class="col-md-6">
                                    <label><i class="far fa-money-bill-alt"></i></label>
                                    <input type="text" name="tutar" placeholder="<?=@$dil['txt371'];?>" required="">
                                </div>
                                <div class="col-md-6">
                                    <label><i class="fas fa-calendar-alt"></i></label>
                                    <input type="text" name="tarih" class="date" placeholder="<?=@$dil['txt372'];?>" required="">
                                </div>
								<div class="col-md-6">
									<label for="banka"><i class="fas fa-university"></i></label>
									<select id="banka" name="banka" class="select-filter">
										<option value=""><?=@$dil['txt373'];?></option>
										<?php $BankaSorgu = $db->prepare("SELECT * FROM banka_hesaplari WHERE durum = ? ORDER BY id ASC");
										$BankaSorgu->execute(array("1"));
										$Bankaislem = $BankaSorgu->fetchALL(PDO::FETCH_ASSOC);?>
										<?php foreach ( $Bankaislem as $BankaSonuc ){?>
										<option value="<?php echo $BankaSonuc['banka']; ?> , <?php echo $BankaSonuc['sube']; ?> , <?php echo $BankaSonuc['hesap']; ?>"><?php echo $BankaSonuc['banka']; ?> , <?php echo $BankaSonuc['sube']; ?> , <?php echo $BankaSonuc['hesap']; ?></option>
										<?php }?>
									</select>
								</div>
                                <div class="col-md-6">
                                    <div class="form-group mt-4">
                                        <textarea name="notunuz" class="form-control" rows="5" placeholder="<?=@$dil['txt310'];?>"></textarea>
                                    </div>
                                </div>
								<div class="form-group mt-4">
											<div class="g-recaptcha" data-sitekey="<?php echo rcaptha; ?>"></div>
											</div>
                                <div class="col-md-6 mt-5">
                                    <button type="submit" name="odemebildirimbtn" class="btn btn-default-yellow-fill float-left mr-3"><?=@$dil['txt174'];?></button>
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
if($_SESSION['odemebildirimbtn'] == 'yes')
{
	echo "
	<script>
	swal({
		type: 'success',
		title: '".@$dil['txt11']."',
		text: '".@$dil['txt368']."',
		confirmButtonText: '".@$dil['txt15']."',
		timer: 5000
	})
	</script>";
	unset($_SESSION['odemebildirimbtn']);
}		
if($_SESSION['odemebildirimbtn'] == 'no')
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
	unset($_SESSION['odemebildirimbtn']);
}
if($_SESSION['odemebildirimbtn'] == 'bos')
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
	unset($_SESSION['odemebildirimbtn']);
}
?>