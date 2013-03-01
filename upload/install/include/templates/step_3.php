
	<h1 class="pagetitle">Files and Folders Permissions</h1>
	<div class="wizard-information"><strong>The following files and folders must have read and write permissions (CHMOD <b>0777</b> or <b>0766</b>)</strong></div>
	
	<?php 
	if( $D->error ) {
		if( !$D->errmsg ){
			echo $D->designer->errorMessage('Please set the permissions', 'Set the permissions with your FTP client and hit "Refresh".');
		}else{
			echo $D->designer->errorMessage('Please check your sharetronix package', $D->errmsg);
		}
	}
	?>

	<div class="greygrad" style="margin-top: 5px;">
		<div class="greygrad2">
			<div class="greygrad3" style="padding-top:0px;">
				<table cellpadding="5" style="width:100%;">
				
				<?php foreach($D->files as $fl) { ?>
					<tr>
						<td colspan="2" style="font-size:0; line-height:0; height: 0; padding: 0; border-bottom: 1px solid #efefef;"></td>
					</tr>
					<tr>
						<td><?= $fl ?></td>
					</tr>
				<?php } ?>
				
					<tr>
						<td colspan="2" style="font-size:0; line-height:0; height: 0; padding: 0; border-bottom: 1px solid #efefef;"></td>
					</tr>
				</table>
			</div>
		</div>
	</div>
	
	<?php if( !$D->error ) { ?>
		<div style="margin-top:20px;">
			<a href="?r=<? rand(0,99999); ?>"  class="btn blue"><span>Next Step</span></a>
		</div>
	<?php } ?>
	