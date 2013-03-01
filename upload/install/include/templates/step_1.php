	<h1 class="pagetitle">Welcome to <?= $C->SITE_TITLE ?> Installation Wizard</h1>
	<div class="wizard-information">This wizard will help you install <strong> <?= $C->SITE_TITLE ?> <?= $C->VERSION ?> </strong> on your webserver.</div>

	<?php if( $D->error && $D->errtype == 'license' ) { 
		echo $D->designer->errorMessage('Sorry', 'You must accept the '.$C->SITE_TITLE.' License terms and limitations to proceed with installation.');
	}elseif($D->error){
		echo $D->designer->errorMessage('Not Compatible', 'Please correct the highlighted settings and hit "Refresh".'); 
	}
	?>
	
	<?php if( !$D->error || $D->errtype != 'license' ) : ?>
	
		<div class="greygrad" style="margin-top: 30px;">
			<div class="greygrad2">
				<div class="greygrad3" style="padding-top:0px;">
					<table style="width:100%;">
						<tr>
							<td >
							<strong>Your system compatability results:</strong>
							</td>
							<td style="text-align:right; font-weight:bold;"><?= ( $D->error && $D->errtype == 'compatability' )? '<span style="color:red;">FAIL</span>' : '<span style="color:#008506;">OK</span>'; ?></td>
						</tr>
						<tr><td colspan="2" style="height: 20px;"></td></tr>
						<?php foreach($D->check as $k=>$v):
							$txt	= $D->texts[$k]; ?>

							<tr>
								<td colspan="2" style="font-size:0; line-height:0; height: 0; padding: 0; border-bottom: 1px solid #efefef;"></td>
							</tr>
							<tr>
								<td style="<?= $v?'':'color:red;font-weight:bold;' ?>"><?= $txt ?></td>
								<td style="text-align:right; font-weight:bold;"><?= $v?'<span style="color:#008506;">OK</span>':'<span style="color:red;">FAIL</span>' ?></td>
								</tr>
						<?php endforeach; ?>
							<tr>
								<td colspan="2" style="font-size:0; line-height:0; height: 0; padding: 0; border-bottom: 1px solid #efefef;"></td>
						</tr>
					</table>
				</div>
			</div>
		</div>	
		
	<?php endif; ?>
		
	<?php if(!$D->error || $D->errtype == 'license'): ?>
	
		<form method="post" action="">
			<br />
			<div class="registration-buttons">
				<button type="submit" name="submit" value="Install" class="btn blue"><span>Start <?= $_SESSION['INSTALL_DATA']['IS_UPDATE']? 'Update' : 'Installation' ?></span></button>
				<span class="remember-me">
					<label>
						<input type="checkbox" name="accept1" value="1" />I accept the <?= $C->SITE_TITLE ?> <a href="license.txt" target="_blank"> License&nbsp;terms&nbsp;and&nbsp;limitations</a>.
					</label>
				</span>
			</div>
		</form>
		
	<?php endif; ?>	