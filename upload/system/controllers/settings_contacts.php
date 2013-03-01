<?php
	
	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if( !$this->user->is_logged ) {
		$this->redirect('signin');
	}
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/settings.php');
	
	require_once($C->INCPATH.'helpers/func_externalprofiles.php');
	
	$submit	= FALSE;
	$error = FALSE;
	$errmsg	= '';
	
	global $plugins_manager;
	
	$website = '';
	$work_phone = '';
	$personal_phone = '';
	
	$db2->query('SELECT website, work_phone, personal_phone FROM users_details WHERE user_id="'.$this->user->id.'" LIMIT 1');
	if($obj = $db->fetch_object()) {
		$website = $obj->website;
		$work_phone = $obj->work_phone;
		$personal_phone = $obj->personal_phone;
	}
	
	if( isset($_POST['sbm']) ) {
		$submit	= TRUE;
		
		$plugins_manager->onUserSettingsSubmit();
		if( !$plugins_manager->isValidEventCall() ){
			$error = TRUE;
			$errmsg = $plugins_manager->getEventCallErrorMessage();
		}
		
		if( !$error ){
			$website_new	= isset($_POST['profile_website'])? htmlspecialchars( trim($_POST['profile_website']) ) : '';
			$work_phone_new	= isset($_POST['profile_wphone'])? htmlspecialchars( trim($_POST['profile_wphone']) ) : '';
			$personal_phone_new		= isset($_POST['profile_pphone']) ? htmlspecialchars(  trim($_POST['profile_pphone']) ) : '';
			
			$update_fields	= array('website'=>'', 'personal_phone'=>'', 'work_phone'=>'');
			
			if( $website_new == 'http://' ) {
				$website	= '';
			}
			if( !empty($website_new) && !preg_match('/^((http|ftp|https):\/\/)?([a-z0-9.-]+\.)+[a-z]{2,4}(\/([a-z0-9-_\/]+)?)?$/iu', $website_new) ) {
				$errmsg	= $this->lang('st_cnt_error_website');
			}
			elseif(!empty($website_new) && $website_new != 'http://') {
				if( ! preg_match('/^(http|ftp|https):\/\//iu', $website_new) ) {
					$website_new	= 'http://'.$website_new;
				}
				$update_fields['website']	= $website_new;
			}
			
			if(!empty($personal_phone_new)){
				$update_fields['personal_phone']	= htmlspecialchars($personal_phone_new);
			}
			if(!empty($work_phone_new)){
				$update_fields['work_phone']	= htmlspecialchars($work_phone_new);
			}
			
			if( $website!==$website_new || $personal_phone!==$personal_phone_new || $work_phone!==$work_phone_new ) {
	
				$db2->query('INSERT INTO `users_details` (user_id, website, work_phone, personal_phone)
						       VALUES ('. $this->user->id .',"'. $db2->e($update_fields['website']) .'", "'. $db2->e($update_fields['work_phone']) .'", "'. $db2->e($update_fields['personal_phone']) .'")
						       ON DUPLICATE KEY UPDATE website="'.$db2->e($update_fields['website']).'", work_phone="'.$db2->e($update_fields['work_phone']).'", personal_phone="'.$db2->e($update_fields['personal_phone']).'"');
				
				$this->user->sess['cdetails']	= $this->db2->fetch('SELECT * FROM users_details WHERE user_id="'.$this->user->id.'" LIMIT 1');
				
				//if( $tmphash != md5(serialize($i)) ) {	
					$notif = new notifier();
					$notif->onEditProfileInfo();
				//}
			}
		}
		
		$this->network->get_user_details_by_id( $this->user->id, TRUE );
	}
	
	$tpl = new template( array('page_title' => $this->lang('settings_contacts_pagetitle', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'sc') );
	
	$tpl->initRoutine('SettingsLeftMenu', array());
	$tpl->routine->load();
	
	if( $submit && $error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('st_cnt_error'), $errmsg) );
	}else if( $submit && !$error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('st_cnt_ok'), $this->lang('st_cnt_okmsg') ) );
	}
	
	$table = new tableCreator();
	$table->form_title = $this->lang('st_contacts_section1');
	
	$rows = array(
			$table->inputField( $this->lang('st_cnt_s1_website'), 'profile_website', isset($website_new)? $website_new : $website ),
			$table->inputField( $this->lang('st_cnt_s1_pphone'), 'profile_pphone', isset($personal_phone_new)? $personal_phone_new : $personal_phone ),
			$table->inputField( $this->lang('st_cnt_s1_wphone'), 'profile_wphone', isset($work_phone_new)? $work_phone_new : $work_phone ),
			$table->submitButton( 'sbm', $this->lang('st_cnt_btn') )
	);

	$tpl->layout->setVar('main_content', $table->createTableInput( $rows ));
	
	$tpl->display();
?>