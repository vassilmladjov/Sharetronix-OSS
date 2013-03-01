<?php
	
	if( !$this->network->id ) {
		$this->redirect('home');
	}
	if( !$this->user->is_logged ) {
		$this->redirect('signin');
	}
	
	$this->load_langfile('inside/global.php');
	$this->load_langfile('inside/settings.php');
	
	$menu_bdate_d	= array();
	$menu_bdate_m	= array();
	$menu_bdate_y	= array();
	if( $this->user->info->birthdate == '0000-00-00' ) {
		$menu_bdate_d[0]	= '';
		$menu_bdate_m[0]	= '';
		$menu_bdate_y[0]	= '';
	}
	for($i=1; $i<=31; $i++) {
		$menu_bdate_d[$i]	= $i;
	}
	for($i=1; $i<=12; $i++) {
		$menu_bdate_m[$i]	= strftime('%B', mktime(0,0,1,$i,1,2009));
	}
	for($i=intval(date('Y')); $i>=1900; $i--) {
		$menu_bdate_y[$i]	= $i;
	}
	
	$submit	= FALSE;
	$error	= FALSE;
	$errmsg	= '';
	
	$name		= $this->user->info->fullname;
	$location	= $this->user->info->location;
	$gender		= $this->user->info->gender;
	$aboutme		= $this->user->info->about_me;
	$tags		= implode(', ', $this->user->info->tags);
	$bdate_d		= 0;
	$bdate_m		= 0;
	$bdate_y		= 0;
	if( $this->user->info->birthdate != '0000-00-00' ) {
		$bdate_d		= intval(substr($this->user->info->birthdate,8,2));
		$bdate_m		= intval(substr($this->user->info->birthdate,5,2));
		$bdate_y		= intval(substr($this->user->info->birthdate,0,4));
	}
	
	$u	= $this->user->info;
	
	$tmphash	= md5($u->fullname.$u->location.$u->birthdate.$u->gender.$u->about_me.serialize($u->tags));
	
	if( isset($_POST['sbm']) ) {
		$submit	= TRUE;

		$plugins_manager->onUserSettingsSubmit();
		if( !$plugins_manager->isValidEventCall() ){
			$error = TRUE;
			$errmsg = $plugins_manager->getEventCallErrorMessage();
		}
		
		if( !$error ){
			$name		= isset($_POST['profile_name'])? htmlspecialchars( trim($_POST['profile_name']) ) : '';
			$location	= isset($_POST['profile_location'])? htmlspecialchars( trim($_POST['profile_location']) ) : '';
			$gender		= isset($_POST['profile_gender']) ? trim($_POST['profile_gender']) : '';
			$aboutme	= isset($_POST['profile_aboutme'])? htmlspecialchars( trim($_POST['profile_aboutme']) ) : '';
			$tags		= isset($_POST['profile_tags'])? htmlspecialchars( trim($_POST['profile_tags']) ) : '';
			$bdate_d	= isset($_POST['profile_birth_day'])? intval($_POST['profile_birth_day']) : '';
			$bdate_m	= isset($_POST['profile_birth_month'])? intval($_POST['profile_birth_month']) : '';
			$bdate_y	= isset($_POST['profile_birth_year'])? intval($_POST['profile_birth_year']) : '';
			
			if( empty($name) ) {
				$name	= $this->user->info->username;
			}
			
			if( $gender!='m' && $gender!='f' ) {
				$gender	= '';
			}
			if( !isset($menu_bdate_m[$bdate_m]) || !isset($menu_bdate_d[$bdate_d]) || !isset($menu_bdate_y[$bdate_y]) ) {
				$bdate_m	= 0;
				$bdate_d	= 0;
				$bdate_y	= 0;
			}
			if( $bdate_d==0 || $bdate_m==0 || $bdate_y==0 ) {
				$bdate_m	= 0;
				$bdate_d	= 0;
				$bdate_y	= 0;
				$birthdate	= '0000-00-00';
			}
			else {
				$birthdate	= $bdate_y.'-'.str_pad($bdate_m,2,0,STR_PAD_LEFT).'-'.str_pad($bdate_d,2,0,STR_PAD_LEFT);
			}
			$tags	= str_replace(array("\n","\r"), ',', $tags);
			$tags	= preg_replace('/\,+/ius', ',', $tags);
			$tags	= explode(',', $tags);
			foreach($tags as $k=>$v) {
				$v	= trim($v);
				if( FALSE == preg_match('/^[ا-یא-תÀ-ÿ一-龥а-яa-z0-9\-\_\.\s\+]{2,}$/iu', $v) ) {
					unset($tags[$k]);
					continue;
				}
				$tags[$k]	= $v;
			}
			$tags	= implode(', ', $tags);
			
			$db2->query('UPDATE users SET fullname="'.$db2->e($name).'", about_me="'.$db2->e($aboutme).'", tags="'.$db2->e($tags).'", gender="'.$db2->e($gender).'", birthdate="'.$db2->e($birthdate).'", location="'.$db2->e($location).'" WHERE id="'.$this->user->id.'" LIMIT 1');
			
			$this->user->sess['LOGGED_USER']	= $this->network->get_user_by_id($this->user->id, TRUE);
			$this->user->info	= & $this->user->sess['LOGGED_USER'];
			
			$u	= $this->user->info;
			$tmphash2	= md5($u->fullname.$u->location.$u->birthdate.$u->gender.$u->about_me.serialize($u->tags));
			if( $tmphash != $tmphash2 ) {
				$notif = new notifier();
				$notif->set_notification_obj('user', $u->id);
				$notif->onEditProfileInfo();
			}
		}
	}

	$tpl = new template( array('page_title' => $this->lang('settings_profile_pagetitle', array('#SITE_TITLE#'=>$C->SITE_TITLE)), 'header_page_layout'=>'sc') );
	
	$tpl->initRoutine('SettingsLeftMenu', array());
	$tpl->routine->load();
	
	if( $submit && $error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->errorMessage($this->lang('st_email_current_errttl'), $errmsg) );
	}else if( $submit && !$error ){
		$tpl->layout->setVar('main_content_placeholder', $tpl->designer->okMessage($this->lang('st_profile_ok'), $this->lang('st_profile_okmsg') ) );
	}
	
	$tpl->layout->useBlock('empty');
	
	$table = new tableCreator();
	$table->form_title = $this->lang('settings_profile_ttl2');
	
	$rows = array(
			$table->inputField( $this->lang('st_profile_name'), 'profile_name', $name ),
			$table->inputField( $this->lang('st_profile_location'), 'profile_location', $location ),
			$table->selectField( $this->lang('settings_profile_bd_day'), 'profile_birth_day', $menu_bdate_d, $bdate_d ),
			$table->selectField( $this->lang('settings_profile_bd_month'), 'profile_birth_month', $menu_bdate_m, $bdate_m ),
			$table->selectField( $this->lang('settings_profile_bd_year'), 'profile_birth_year', $menu_bdate_y, $bdate_y ),
			$table->radioButton( $this->lang('st_profile_gender'), 'profile_gender', array('m'=>$this->lang('st_profile_gender_m'), 'f'=>$this->lang('st_profile_gender_f')), $gender ),
			$table->textArea( $this->lang('st_profile_aboutme'), 'profile_aboutme', $aboutme ),
			$table->textArea( $this->lang('st_profile_tags'), 'profile_tags', $tags ),
			$table->submitButton( 'sbm', $this->lang('st_profile_savebtn') )
	);
	
	$tpl->layout->block->setVar('empty_block_content', $table->createTableInput( $rows ));

	$tpl->layout->block->save('main_content');
	
	$tpl->display();
?>