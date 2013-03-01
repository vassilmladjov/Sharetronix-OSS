<!DOCTYPE html>
<html>
	<head>
		<title><?= $C->SITE_TITLE ?> - <?php if(!$_SESSION['INSTALL_DATA']['IS_UPDATE']){ ?>Installation (Step <?= $_SESSION['INSTALL_STEP'].'/'.$C->MAX_STEPS ?>) <?php }else{ ?> Update to  <?php echo $C->VERSION; } ?>
		
		</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<link href="../static/css/framework.css" type="text/css" rel="stylesheet" />
	</head>
	<body class="fixed-header layout-c">
		<div id="layout-container">
			
				<div id="header">
					<div class="header-container">
						<img src="../static/images/logo.png" alt="Sharetronix" class="system-logo" />
						<div class="clear"></div>
					</div>
				</div>
				
				<div id="page-container">
				
				<div id="subheader"> </div>
				
				<?php if( !isset($_SESSION['INSTALL_DATA']['IS_UPDATE']) || $_SESSION['INSTALL_DATA']['IS_UPDATE'] === FALSE ): ?>
				
				<ul class="progress-bar">
				    <li class="<?= ($_SESSION['INSTALL_STEP'] == 1)? 'current-step' : ((1 < $_SESSION['INSTALL_STEP'])? 'finished prev-step' : '') ?> first">
				        <span class="left-bkg"><span class="right-bkg">
				            <strong>Step 1</strong>
				            System compatability
				        </span></span>
				    </li>
				    <li class="<?= ($_SESSION['INSTALL_STEP'] == 2)? 'current-step' : ((2 < $_SESSION['INSTALL_STEP'])? 'finished prev-step' : '') ?>">
				        <span class="left-bkg"><span class="right-bkg">
				            <strong>Step 2</strong>
				            Database credentials setup
				        </span></span>
				    </li>
				    <li class="<?= ($_SESSION['INSTALL_STEP'] == 3)? 'current-step' : ((3 < $_SESSION['INSTALL_STEP'])? 'finished prev-step' : '') ?>">
				        <span class="left-bkg"><span class="right-bkg">
				            <strong>Step 3</strong>
				            Set files and directories permissions
				        </span></span>
				    </li>
				    <li class="<?= ($_SESSION['INSTALL_STEP'] == 4)? 'current-step' : ((4 < $_SESSION['INSTALL_STEP'])? 'finished prev-step' : '') ?>">
				        <span class="left-bkg"><span class="right-bkg">
				            <strong>Step 4</strong>
				            Administration setup
				        </span></span>
				    </li>
				    <li class="<?= ($_SESSION['INSTALL_STEP'] == 5)? 'current-step' : '' ?> last">
				        <span class="left-bkg"><span class="right-bkg">
				            <strong>Step 5</strong>
				            Review your settings and finish installation
				        </span></span>
				    </li>
				</ul>

				<?php endif; ?>
				
				<div class="login-form">
				
				