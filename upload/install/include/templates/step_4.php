	<h1 class="pagetitle">Administrative Account</h1>
	<div class="wizard-information">Create an Administrative account for accessing the Administration center.</div>
	
	<?php if( $D->error ) {
		echo $D->designer->errorMessage('Error', $D->errmsg);
	} ?>

	<form method="post" action="">
	<table class="form-container">
		<tr>
			<td class="field-title">Admin Username:</td>
			<td><input type="text" autocomplete="off" class="setinp" name="ADMIN_USER" value="<?= htmlspecialchars($D->ADMIN_USER) ?>" /></td>
		</tr>
		<tr>
			<td class="field-title">Admin Password:</td>
			<td><input type="password" autocomplete="off" class="setinp" name="ADMIN_PASS" value="<?= htmlspecialchars($D->ADMIN_PASS) ?>" /></td>
		</tr>
		<tr>
			<td class="field-title">Password Again:</td>
			<td><input type="password" autocomplete="off" class="setinp" name="ADMIN_PASS2" value="<?= htmlspecialchars($D->ADMIN_PASS2) ?>" /></td>
		</tr>
		<tr>
			<td class="field-title">E-Mail Address:</td>
			<td><input type="text" class="setinp" name="ADMIN_EMAIL" value="<?= htmlspecialchars($D->ADMIN_EMAIL) ?>" /></td>
		</tr>
		<tr>
			<td class="field-title"></td>
			<td><button type="submit" name="submit" value="Continue" class="btn blue"><span>Next Step</span></button></td>
		</tr>
	</table>
	</form>