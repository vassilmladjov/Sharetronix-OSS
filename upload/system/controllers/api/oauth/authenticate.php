<?php
	global $user;
	$snow_form = true;
	
	$this->load_template('header_oauth.php');
	
	if(isset($_GET['oauth_token']) && $_GET['oauth_token'] != ''){
		require_once( $C->INCPATH.'classes/class_oauth.php' );
		$oauth_client = new OAuth($_GET['oauth_token']);
	}else{
		echo 'Missing request token.';
		$snow_form = false;
	}
		
	if(isset($_POST['submit']))
	{	
		$oauth_client->set_variable('consumer_key', $oauth_client->get_field_in_table('oauth_request_token', 'consumer_key','request_token', $_GET['oauth_token']));
		
		$err = true;
		if($_POST['submit'] == 'Deny'){
			$app_name = $oauth_client->get_field_in_table('applications', 'name', 'app_id', $oauth_client->get_value_in_consumer_key('app_id'));
?>
			
			<p style='margin-bottom: 50px; width: auto; text-align: center;'>
				You've denied <b><?=  $app_name; ?></b> access to interact with your account!
			</p>
			
<?php
		}
		elseif($_POST['submit'] == 'Allow'){
			$err = false;
			$user_id = $user->id;
		}elseif($_POST['submit'] == 'Submit'){
			$user->logout();
			$user->login($_POST['email'], md5($_POST['password'])); 
			if( !$user->is_logged ) $err = true;
			else{
				$user_id = $user->id;
				$err = false;
			}
		}
		
		if(!$err){
			$oauth_client->set_variable('user_id', $user_id);
			
			if(!$verifier = $oauth_client->get_verifier_request()){
				echo $oauth_client->get_variable('error_msg');
			}
			else{
				if(!$oauth_client->update_field_in_table('oauth_request_token', 'user_id', $oauth_client->get_variable('user_id'), 'request_token', $_GET['oauth_token']) || !$oauth_client->update_field_in_table('oauth_request_token', 'time_stamp', time(), 'request_token', $_GET['oauth_token'])){
					echo $oauth_client->get_variable('error_msg');
				}else{						
					$callback = $oauth_client->get_field_in_table('applications', 'callback_url', 'app_id', $oauth_client->get_value_in_consumer_key('app_id'));
													
					$oauth_client->log();
					
					if($callback){
						$this->redirect($callback.'?oauth_token='.$_GET['oauth_token'].'&oauth_verifier='.$verifier);
						exit();
					}else{
						echo '<p style="width: auto; text-align: center;">Your verifier is: <b>'.$verifier.'</b>.You should enter it manually at your service provider.</p><div class="klear"></div>';
						$snow_form = false;
					}
				}
			}
		}
	}
	if(isset($_GET['oauth_token'])  && $snow_form && $oauth_client->is_valid_request_token(true) && !$oauth_client->there_is_error())
	{	
		$oauth_client->set_variable('consumer_key', $oauth_client->get_field_in_table('oauth_request_token', 'consumer_key', 'request_token', $_GET['oauth_token']));
		$app_name = $oauth_client->get_field_in_table('applications', 'name', 'app_id', $oauth_client->get_value_in_consumer_key('app_id'));
?>
		<div id="poblicpage_login">
		<form method="post" action="<?= $C->SITE_URL.'oauth/authenticate?oauth_token='.$_GET['oauth_token'] ?>">
		
		<?php if( !$user->is_logged ) { ?>
		
			<table id="regform" cellspacing="5">
				<tr>
					<td></td>
					<td>
						<b>Sign In</b>
						<a id="forgotpass" href="<?= $C->SITE_URL ?>signin/forgotten">Forgot your password?</a>
					</td>
				</tr>
				<tr>
					<td class="regparam">E-mail:</td>
					<td><input type="text" name="email" value="" tabindex="1" maxlength="100" class="reginp" /></td>
				</tr>
				<tr>
					<td class="regparam">Password: </td>
					<td><input type="password" name="password" value="" tabindex="2" maxlength="100" class="reginp" /></td>
				</tr>
				<tr>
					<td></td>
					<td valign="middle">
						<input type="submit" name='submit' value="Submit" tabindex="4" style="float:left; padding:4px; font-weight:bold;" />
					</td>
				</tr>
			</table>
		<?php }else{ ?>
			<table id="regform" cellspacing="5" align='center'>
				<tr>
					<td valign="middle" colspan='2'> Do you allow <b><?= $app_name ?></b> to get access to your user credentials. </td>
				</tr>
				<tr>
					<td align="right">
						<input type="submit" name='submit' value="Allow" tabindex="4" style="padding:4px; font-weight:bold;" />
					</td>
					<td align="left">
						<input type="submit" name='submit' value="Deny" tabindex="4" style="padding:4px; font-weight:bold;" />
					</td>
				</tr>
			</table>
		<?php } ?>
		</form>
		</div>
		<div id="poblicpage_info">
			<h2>What do you share?</h2>
			<strong><?= $C->SITE_TITLE; ?></strong> takes your privacy very seriously.Please ensure that you trust this application 
			before proceeding! You may revoke access to this application at any time by visiting your Settings page.
		</div>
		<div class="klear"></div>
			
	<?php	
	}elseif($snow_form) echo 'Invalid oauth token provided';
	
	$this->load_template('footer.php');
	?>
	