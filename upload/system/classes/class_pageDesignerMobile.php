<?php
	class pageDesignerMobile extends pageDesigner
	{
		public function __call($method, $args)
		{
			if( !method_exists($this,$method) ){
				return '';
			}
		}
		
		public function getJSData()
		{
			global $C;
			
			$this->system_files = array(
					'jquery',
					//'mobile/jquery.mobile.config',
					//'mobile/jquery.mobile',
					
					'plugins/jquery.ajaxupload',
					
					'mobile/attachments',
					
					'mobile/common',
					'services',
						
					'mobile/htmlarea',
					'mobile/activities',
					'mobile/comments',
					'mobile/users',
					'mobile/groups'
			);
			
			return $this->loadJSData();
		}
		
		public function getCSSData()
		{
			global $C;
				
			$this->system_files = array( 
					//'jquery.mobile.structure', 
					'mobile' 
			);
				
			return $this->loadCSSData();
		}
		
		
		public function usersSettingsMenu( $user_id, $follow, $regime='friendship', $action = '' )
		{

			$html = '';
				
			switch( $regime ){
		
				case 'friendship' :
					$follow ?
					$html = '<a class="action-btn follow" data-action="follow" data-value="'.$user_id.'" data-namespace="users" data-role="services"><span>Follow</span></a>' :
					$html = '<a class="action-btn unfollow" data-action="unfollow" data-value="'.$user_id.'" data-namespace="users" data-role="services"><span>Unfollow</span></a>';
					break;
						
				case 'administration' :
					$html = '<a class="action-btn remove-user" data-action="'.$action.'" data-value="'.$user_id.'" data-namespace="administration" data-role="services"><span>'.ucfirst( str_replace('_', ' ', $action)).'</span></a>';
					break;
			}
				
			return $html;
				
		}
	}