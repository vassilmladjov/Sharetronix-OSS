<?php
	class notification
	{
		private $notifications;
		private $tpl;
		private $db2;
		private $user;
		
		public function __construct( & $tpl )
		{//edit this 
			$this->notifications = array();
			$this->tpl = $tpl;
			$this->user = & $GLOBALS['user'];
			$this->db2 = & $GLOBALS['db2'];
			
			$this->db2->query( 'SELECT notif_type, in_group_id, from_user_id, notif_object_type, notif_object_id, date FROM notifications WHERE to_user_id="'.intval($this->user->id).'" ORDER BY id DESC LIMIT 50' );
			while( $n = $this->db2->fetch_object() ){
			
				//this could be removed
				if( $n->in_group_id > 0 ){
					$g = $this->network->get_group_by_id( $n->in_group_id );
					if( !$g ){
						continue;
					}
				}
				//end this could be removed
			
				if( !isset($this->notifications[ $n->notif_type ]) ){
					$this->notifications[ $n->notif_type ] = array();
				}
			
				$ndx = ( $n->notif_object_id > 0 )? $n->notif_object_type.'_'.$n->notif_object_id : 'none_0';
			
				if( !isset($this->notifications[ $n->notif_type ][ $ndx ]) ){
					$notifications[ $n->notif_type ][ $ndx ] = array();
				}
				$this->notifications[ $n->notif_type ][$ndx][$n->from_user_id.'-'.$n->in_group_id] = array( 'gid' => $n->in_group_id, 'from_uid' => $n->from_user_id, 'date' => $n->date, 'nobj' => array( 'type' => $n->notif_object_type, 'id' => $n->notif_object_id ));
			}
		}
		
		public function load()
		{
			$html = '';
			
			if( !count($this->notifications) ){
				$this->tpl->layout->setVar('main_content', createNoPostBox('No Posts', 'No Posts Found'));
				return FALSE;
			}
			
			/* Copy paste from the constroller, should be edited with the new design
			ob_start();
			
			$hide_notification = FALSE;
			$ntf_group = '';
			
	
			
				foreach( $notifications as $notif_type => $notif_array ){
						
					$notification_group_count = count($notif_array);
					$ntf_group = $notif_type;
					echo '<div id="dv_'.$ntf_group.'">';
					if($notification_group_count>1){
						echo '<hr class="hidden_ntf_group_hr" id="hr_'.$ntf_group.'" >';
					}
					foreach( $notif_array as $nobj_name => $notif_objects ){
						$numb = count($notif_objects);
						$notif_objects = reset($notif_objects);
						$date = $notif_objects['date'];
						$u = $this->network->get_user_by_id( $notif_objects['from_uid'] );
						if( !$u ){
							continue;
						}
						$is_in_group = $notif_objects['gid'];
			
						//the info for the langiage file
						$usr_avatar = '<img src="'.$C->IMG_URL.'avatars/thumbs3/'.$u->avatar.'" alt="'.htmlspecialchars($u->fullname).'" title="'.htmlspecialchars($u->fullname).'" />';
						$usr = '<a href="'.$C->SITE_URL.$u->username.'">'.$u->username.'</a>';
						$a1 = '<a href="javascript: void(0);" onClick="show_notif_users(\''.trim($notif_type).'\' ,\''.trim($nobj_name).'\', \''.$is_in_group.'\' );" >';
						$a2 = '</a>';
						$a3 = '';
						$notifobj = '';
						$error_continue = FALSE;
						//end
			
						$about = explode('_', $nobj_name);
						if( $about[0] != '0' || $about[1] != '0' ){
							switch( $about[0] ){
								case 'post':
									$a3 = '<a href="'.$C->SITE_URL.'view/post:'.intval($about[1]).'">';
									break;
								case 'network':
									$notifobj = '<a href="'.$C->SITE_URL.'">'.mb_substr($C->SITE_TITLE, 0, 20).'</a>';
									break;
								case 'group':
									$g1 = $this->network->get_group_by_id( $about[1] );
									$g1 = $g1? $g1->groupname : '';
									$notifobj = '<a href="'.$C->SITE_URL.$g1.'">'.$g1.'</a>';
									if( empty($g1) ){
										$error_continue = TRUE;
									}
									break;
								case 'user':
									if( !$is_in_group ){
										$u1 = $this->network->get_user_by_id( $about[1] );
										$u1 = $u1? $u1->username : '';
										$notifobj = '<a href="'.$C->SITE_URL.$u1.'">'.$u1.'</a>';
			
										if( empty($u1) ){
											$error_continue = TRUE;
										}
									}else{
										$g1 = $this->network->get_group_by_id( $is_in_group );
										$g1 = $g1? $g1->groupname : '';
										$notifobj = '<a href="'.$C->SITE_URL.$g1.'">'.$g1.'</a>';
			
										if( empty($g1) ){
											$error_continue = TRUE;
										}
									}
									break;
							}
						}
			
						if( $error_continue ){
							continue;
						}
			
						$error = FALSE;
						$post_show_slow = FALSE;
						$post_date = post::parse_date($date);
						$notif_text = '';
			
						if( $numb == 1 ){
							$notif_text = $this->lang('single_msg_'.$notif_type, array('#USER#'=>$usr, '#A3#'=>$a3, '#A2#'=>$a2, '#NOTIFOBJ#'=>$notifobj));
						}elseif( $numb > 1 ){
							$notif_text = $this->lang('grouped_msg_'.$notif_type, array('#USER#'=>$usr, '#NUM#'=>($numb-1), '#A1#'=>$a1, '#A2#'=>$a2, '#A3#'=>$a3, '#NOTIFOBJ#'=>$notifobj));
						}
			
						$this->load_template('single_notification.php');
						if( $notification_group_count != 1 ){
							$hide_notification = TRUE;
						}
					}
					echo '</div>';
						
					if($notification_group_count>1){
						echo '<a id="a_'.$ntf_group.'_show" href="javascript: void(0);" onClick="show_notification_group(\''.$ntf_group.'\');" class="hidden_ntf_group_link" style="display: block;"> '.( $notification_group_count-1).' '.$this->lang('dbrd_more_notifications_link').' </a>';
						echo '<hr class="hidden_ntf_group_hr" id="hr_'.$ntf_group.'" >';
					}
			
					$hide_notification = FALSE;
						
				}
			
			$posts_html	= ob_get_contents();
			ob_end_clean();
			*/
		}
	}