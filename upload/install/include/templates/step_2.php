	<h1 class="pagetitle">Database Settings</h1>
	<div class="wizard-information">Fill in the information about the MySQL database. For new <?= $C->SITE_TITLE ?> installations you must create an <b>empty</b> MySQL database.</div>
						
	<?php 
		if( $D->error ) {
			echo $D->designer->errorMessage('Error', $D->errmsg);
		}
	?>
	
	<form method="post" action="">
		<table class="form-container">
			<tr>
				<td class="field-title">MySQL Host:</td>
				<td>
					<input type="text" class="setinp" name="MYSQL_HOST" value="<?= htmlspecialchars($D->MYSQL_HOST) ?>" />
					<div class="hint">Usually "localhost"</div>														
				</td>
			</tr>
			<tr>
				<td class="field-title">MySQL Username:</td>
				<td><input type="text" autocomplete="off" class="setinp" name="MYSQL_USER" value="<?= htmlspecialchars($D->MYSQL_USER) ?>" /></td>
			</tr>
			<tr>
				<td class="field-title">MySQL Password:</td>
				<td><input type="password" autocomplete="off" class="setinp" name="MYSQL_PASS" value="<?= htmlspecialchars($D->MYSQL_PASS) ?>" /></td>
			</tr>
			<tr>
				<td class="field-title">MySQL Database Name:</td>
				<td>
					<input type="text" class="setinp" name="MYSQL_DBNAME" value="<?= htmlspecialchars($D->MYSQL_DBNAME) ?>" />
					<div class="hint">Create an empty Database and type it's name here.<br /> You can use cPanel or phpMyadmin</div>	
				</td>
			</tr>
			<tr>
				<td class="field-title"></td>
				<td><button type="submit" name="submit" value="Continue" class="btn blue"><span>Next Step</span></button></td>
			</tr>
		</table>
	</form>