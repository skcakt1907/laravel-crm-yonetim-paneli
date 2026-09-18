<?php echo !defined("GUVENLIK") ? die("Erisim Engellendi!.") : null; ?>
<?php
$Sorgu = $db->prepare("SELECT * FROM alanadi WHERE id = ?");
$Sorgu->execute(array(1));
if ($Sorgu->rowCount()) {
	$Sonuc = $Sorgu->fetch(PDO::FETCH_ASSOC);
}
?>
<?php
$uzanti 		= json_decode($Sonuc['uzanti']);
$kayit 		= json_decode($Sonuc['kayit']);
$yenileme 	= json_decode($Sonuc['yenileme']);
?>
<?php if (isset($_GET['durum'])) : ?>
	<script>
		swal({
		type: 'success',
		title: 'Başarılı',
		text: 'Alanadı Sepetinize Eklendi',
		confirmButtonText: 'Tamam',
		timer: 5000
	})
	</script>
<?php endif; ?>
<div class="top-header overlay" style="background-image: url(<?php echo tema; ?>/uploads/arkaplan/alanadi/<?php echo $arkaplan['alanadi'] ?>)">
	<div class="container">
		<div class="row">
			<div class="col-sm-12 col-md-12">
				<div class="wrapper">
					<h1 class="heading"><?= @$dil['txt238']; ?></h1>
					<h3 class="subheading"><?= @$dil['txt239']; ?></h3>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="ustbanner">
	<div class="container">
		<a href="index.html" class="golink gocheck"> <?= @$dil['txt158']; ?> </a> <small class="c-white">&#9679;</small>
		<a href="<?php echo $sayfalink; ?>" class="golink gocheck"> <?= @$dil['txt238']; ?> </a>
	</div>
</div>
<!-- ***** CONTROLS ***** -->
<script type="text/javascript" language="javascript">
	$(document).ready(
		function() {
			$(".secimYap").click(function() {
				var kontrol = $(this).is(":checked");
				if (kontrol == true) {
					$(this).parent("label").removeClass("noselect").addClass("select");
				} else {
					$(this).parent("label").removeClass("select").addClass("noselect");
				}
			});

			$("#domainSorgula").click(
				function() {
					var veri = $("#domainForm").serialize();
					if ($("#alanadi").val() == '') {
						alert("<?= @$dil['txt68']; ?>");
						return;
					}
					$("#domainBilgileri").html('<div class="ortala"><img src="<?php echo tema; ?>/img/loading.gif" alt="<?= @$dil["txt1"]; ?>" /></div>');
					$.ajax({
						type: 'POST',
						url: '<?php echo tema; ?>/data/kontrol.php',
						data: veri,
						success: function(gelen) {
							$("#domainBilgileri").html(gelen);
							$(".link").unbind('click');
							$(".link").bind('click', function() {
								$(this).parent("td").find(".popDiv").fadeIn("normal");
							});
							$(".close").unbind('click');
							$(".close").bind('click', function() {
								$(".popDiv").fadeOut("normal");
							});
						}
					});

				}
			);
			$(".close").click(function() {
				$(".popDiv").fadeOut("normal");
			});
		}
	);
</script>
<section class="search-domain section-padding p-5">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-lg-9 col-md-12 col-sm-12 col-xs-12 centered wow fadeInUp" data-wow-delay="0.3s">
				<h2 class="mb-4 cl-black text-center"><?= @$dil['txt69']; ?></h2>
				<div class="search-domain-content">
					<form action="" id="domainForm">
						<input type="text" name="alanadi" placeholder="<?= @$dil['txt70']; ?>" required>
						<select name="uzanti[]">
							<?php if (is_array($uzanti)) :
								foreach ($uzanti as $k => $v) : ?>
									<?php $degisken = explode(".", $uzanti[$k]); ?>
									<option value="<?= $degisken[1]; ?><?php echo ($degisken[2] == true ? '.' : ''); ?><?= $degisken[2]; ?>"><?= @$uzanti[$k]; ?></option>
							<?php endforeach;
							endif; ?>
						</select>
						<input type="hidden" name="formVeriAL" value="<?= $_SESSION['formVeriAL']; ?>" />
						<button class="bttn btn-fill" type="button" id="domainSorgula"><?= @$dil['txt71']; ?></button>
					</form>
				</div>
				<div class="domain-type">
					<?php if (is_array($uzanti)) :
						foreach ($uzanti as $k => $v) : if ($k > 4) continue; ?>
							<div class="single-domain-type">
								<h3><?= @$uzanti[$k]; ?>/ <span><?= @$kayit[$k]; ?> <?= @$dil['txt72']; ?></span></h3>
							</div>
					<?php endforeach;
					endif; ?>
				</div>
				<div class="domainBilgileri" id="domainBilgileri"></div>
			</div>
		</div>
	</div>
</section>
<!-- ***** FILTER PLANS ***** -->
<div class="mixcontainer sec-normal sec-bg2 " data-ref="container">
	<div class="container ">
		<div class="pricing special">
			<div class="p-0 m-0">
				<div class="best-plans pricing">
					<div class="sec-main sec-bg1">
						<div class="row">
							<div class="col-sm-12">
								<div class="table-responsive-lg">
									<table class="table compare">
										<thead>
											<tr>
												<td class="bb-pink pt-0 title "><?= @$dil['txt240']; ?></td>
												<td class="bb-pink pt-0 title"><?= @$dil['txt241']; ?></td>
												<td class="bb-pink pt-0 title"><?= @$dil['txt242']; ?></td>
											</tr>
										</thead>
										<tbody>
											<?php if (is_array($uzanti)) :
												foreach ($uzanti as $k => $v) : ?>
													<tr class="mix ext gtld">
														<td>
															<div class="badge bg-grey mr-1"><?= @$uzanti[$k]; ?></div>
														</td>
														<td><?= @$kayit[$k]; ?> TL</td>
														<td><?= @$yenileme[$k]; ?> TL</td>
													</tr>
											<?php endforeach;
											endif; ?>
										</tbody>
									</table>
								</div>
							</div>
						</div>
						<div class="cd-fail-message">" No items could be found matching the criteria "</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- ***** FEATURES ***** -->
<section id="scroll" class="history-section feat01 sec-normal">
	<div class="container">
		<div class="randomline">
			<div class="bigline"></div>
			<div class="smallline"></div>
		</div>
		<div class="sec-main sec-bg1">
			<div class="row">
				<div class="col-md-12 col-lg-12">
					<div class="info-content">
						<h4><?= @$dil['txt243']; ?></h4>
						<p><?= @$dil['txt244']; ?></p>
					</div>
					<div class="info-content">
						<h4><?= @$dil['txt245']; ?></h4>
						<p><?= @$dil['txt246']; ?></p>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>