<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null;?>
<section class="sec-normal notfound pt-150">
	<div class="total-grad-pink-blue-intense"></div>
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-9">
				<img class="svg" src="<?php echo tema;?>/patterns/notfound.svg" alt="404">
			</div>
		</div>
		<div class="col-md-12 text-center pt-5">
			<p class=" c-grey"><?=@$dil['txt23'];?> <a href="iletisim.html" class="golink"><?=@$dil['txt24'];?></a></p>
			<a href="index.html" class="btn btn-default-grad-purple-fill mt-3"><?=@$dil['txt25'];?></a>
		</div>
	</div>
</section>