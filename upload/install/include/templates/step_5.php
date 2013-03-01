	<script type="text/javascript" src="<?= $_SESSION['INSTALL_DATA']['SITE_URL'] ?>static/js/jquery.js"></script>
	<script type="text/javascript" src="<?= $_SESSION['INSTALL_DATA']['SITE_URL'] ?>static/js/plugins/jquery.colorbox.js"></script>
	<script type="text/javascript">
		var siteurl = "<?= $_SESSION['INSTALL_DATA']['SITE_URL'] ?>install/";
		
		function unshadow_password( pass_type )
		{	
			$.post(siteurl, "pass_type="+encodeURIComponent(pass_type), function(data) {
				pass_type  = (pass_type == 'admin')? 'admin' : 'mysql';
				$('#'+pass_type+'_pass').html(data);
				$('#'+pass_type+'_pass_link').attr('onclick', 'shadow_password(\''+pass_type+'_pass\');').text('hide password');
			});
		}

		function shadow_password( pass_field_id )
		{	
			var pass = $('#'+pass_field_id).text();
			var shadowed_pass = '';

			for( var i=0; i<$('#'+pass_field_id).text().length; i++ ){
				shadowed_pass += '*';
			}

			$('#'+pass_field_id+'_link').attr('onclick', 'unshadow_password(\''+(pass_field_id == 'admin_pass'? 'admin' : 'mysql')+'\');').text('show password')
			$('#'+pass_field_id).text(shadowed_pass); 
		}
		function lightbox(){    
		  $.fn.colorbox({iframe:false, href:"<?= $_SESSION['INSTALL_DATA']['SITE_URL'] ?>static/images/install/network_name.png"});
		}
	</script>
	
	<style>
		.first_table_column{width: 70%; color: #444; font-weight: bold; padding: 5px;}
		.second_table_column{width: 10%; text-align: center;}
		.third_table_column{width: 20%;text-align: center;}
		
		.right_tip_text{color: #444; font-size: 11px; padding: 10px; font-style: italic;}
		.show_pass{font-size: 10px;}
	</style>
	
	<h1 class="pagetitle">Review System Settings</h1>
	<div class="wizard-information">Review and accept your system settings.</div>
	
	<form method="post" action="">
	<table class="form-container">
		<table>
			<tr>
			<td style="width: 70%;">
			<table>
				<tr>
					<td class="first_table_column">Your system compatability results: </td>
					<td class="second_table_column"><span style="color:#008506; font-weight: bold;">OK</span></td>
					<td class="third_table_column"></td>
				</tr>
				
				<tr>
					<td class="first_table_column">Your files and directories permisssions: </td>
					<td class="second_table_column"><span style="color:#008506; font-weight: bold;">OK</span></td>
					<td class="third_table_column"></td>
				</tr>
				
				<tr>
					<td class="first_table_column">MySQL Host: </td>
					<td class="second_table_column"><?= $_SESSION['INSTALL_DATA']['MYSQL_HOST'] ?></td>
					<td class="third_table_column"></td>
				</tr>
				
				<tr>
					<td class="first_table_column">MySQL Database Name: </td>
					<td class="second_table_column"><?= $_SESSION['INSTALL_DATA']['MYSQL_DBNAME'] ?></td>
					<td class="third_table_column"></td>
				</tr>
				
				<tr>
					<td class="first_table_column">MySQL Username: </td>
					<td class="second_table_column"><?= $_SESSION['INSTALL_DATA']['MYSQL_USER'] ?></td>
					<td class="third_table_column"></td>
				</tr>
				
				<tr>
					<td class="first_table_column">MySQL Password: </td>
					<td class="second_table_column"><span id="mysql_pass"><?= shadow_password( $_SESSION['INSTALL_DATA']['MYSQL_PASS'] ) ?></span></td>
					<td class="third_table_column"><?= ( strlen($_SESSION['INSTALL_DATA']['MYSQL_PASS'])>0? '<a class="show_pass" href="javascript: void(0);" onclick="unshadow_password(\'mysql\');"  id="mysql_pass_link">show password</a>' : '') ?></td>
				</tr>
				
				<tr>
					<td class="first_table_column">Admin Username: </td>
					<td class="second_table_column"><?= $_SESSION['INSTALL_DATA']['ADMIN_USER'] ?></td>
					<td class="third_table_column"></td>
				</tr>
				
				<tr>
					<td class="first_table_column">Admin Password: </td>
					<td class="second_table_column"><span id="admin_pass"><?= shadow_password( $_SESSION['INSTALL_DATA']['ADMIN_PASS'] ); ?></span></td>
					<td class="third_table_column"><a class="show_pass" href="javascript: void(0);" onclick="unshadow_password('admin');" id="admin_pass_link">show password</a></td>
				</tr>
				
				<tr>
					<td class="first_table_column">Admin E-mail: </td>
					<td class="second_table_column"><?= $_SESSION['INSTALL_DATA']['ADMIN_EMAIL'] ?></td>
					<td class="third_table_column"></td>
				</tr>
				
				<tr>
					<td class="first_table_column">Your Site URL: </td>
					<td class="second_table_column"><?= $_SESSION['INSTALL_DATA']['SITE_URL'] ?></td>
					<td class="third_table_column"></td>
				</tr>
				
				<tr>
					<td class="first_table_column">Your Domain: </td>
					<td class="second_table_column"><?= $_SESSION['INSTALL_DATA']['DOMAIN'] ?></td>
					<td class="third_table_column"></td>
				</tr>
			</table>
		</td><td style="width: 30px;">
			<h1 class="pagetitle">Tips</h1>
			
			<a href="javascript: void(0);" onClick="lightbox();">
				<img src="<?= $_SESSION['INSTALL_DATA']['SITE_URL'] ?>static/images/install/stx_admin_panel_thumb.png" />
			</a>
			<div class="right_tip_text">You can change your Network Name and Site Intro title in General Settings of your community administration panel. </div>
			<div class="right_tip_text">You can change your Site URL and Domain after installation in the ./system/conf_main.php file</div>
		</td>
		</tr>
		<tr>
			<td colspan="2" style="text-align: center;"><button type="submit" name="submit" value="Continue" class="btn blue"><span>Finish Installation</span></button></td>
		</tr>
		</table>
	</table>
	</form>