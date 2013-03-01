<?php
	
	$PAGE_TITLE	= 'Installation';
		
	$installed	= FALSE;
	//if( isset($OLDC->INSTALLED, $OLDC->VERSION) && $OLDC->INSTALLED == 1 && $OLDC->VERSION>=VERSION ) {
	//	$installed	= TRUE;
	//}
	
	if( $installed ) {
		$_SESSION['INSTALL_STEP']	= 0;
		
		$html	.= 	'<h1 class="pagetitle">Welcome to '.SITE_TITLE.' Installation Wizard</h1>
					'.errorbox('Oops', SITE_TITLE.' is already installed on your system. Please remove the "install/" folder.', FALSE, 'margin-top:5px;');
	}
	else {
		$_SESSION['INSTALL_STEP']	= 0;
		
		$error	= FALSE;
		if( isset($_POST['submit']) ) {
			$a	= isset($_POST['accept1']) && $_POST['accept1']=="1";
			//$b	= isset($_POST['accept2']) && $_POST['accept2']=="1";
			if( ! $a /*|| ! $b*/) {
				$error	= TRUE;
			}
			if( ! $error ) {
				$_SESSION['INSTALL_STEP']	= 1;
				header('Location: ?next');
				exit;
			}
		}
		
		$html	.= '
			<h1 class="pagetitle">Welcome to '.SITE_TITLE.' Installation Wizard</h1>
			<p>This wizard will help you install '.SITE_TITLE.' '.VERSION.' on your webserver.</p>';
		
		if( $error ) {
			$html	.= errorbox('Sorry', 'You must accept the '.SITE_TITLE.' License terms and limitations to proceed with installation.');
		}
		$html	.= '
			<form method="post" action="">
				<br />
				<div class="registration-buttons">
					<button type="submit" name="submit" value="Install" class="btn blue"><span>Install</span></button>
					<span class="remember-me">
						<label>
							<input type="checkbox" name="accept1" value="1" />I accept the '.SITE_TITLE.' <a href="license.txt" target="_blank"> License&nbsp;terms&nbsp;and&nbsp;limitations</a>.
						</label>
					</span>
				</div>
			</form>
		';
	}
	
?>