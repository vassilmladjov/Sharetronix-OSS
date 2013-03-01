<?php
	class staticHTML
	{
		protected $layout;
		
		public function __construct( & $layout )
		{
			$this->layout = $layout;
		}
		
		public function doReplace( $html, $placeholder )
		{
			$placeholder = '{%'.$placeholder.'%}';
			
			$this->layout->html = str_replace( $placeholder, $html.$placeholder, $this->layout->html );
		}
		
		public function useActivityContainer( $placeholder = 'main_content' )
		{
			$html = '	
						<div class="activity-feed-list">
							{%activity-container-list%}
						</div>
						{%activity_container_show_more%}	
						';
			
			$this->doReplace($html, $placeholder);
		}
		
		public function useActivityCommentContainer( $placeholder = 'main_content' )
		{
			$html = '	
						{%show_all_activity_comments%}
						<ol class="comments-thread">
							<li>{%activity_comments%}</li>
						</ol>
					';
				
			$this->doReplace($html, $placeholder);
		}
		
		public function useEmptyBlock( $placeholder = 'main_content' )
		{
			$html = '	
						{%empty_block_content%}
					';
		
			$this->doReplace($html, $placeholder);
		}
		
		public function useShowmoreButton( $placeholder = 'main_content' )
		{
			global $page; 
			
			$html = '
						<div class="show-more-container">
							<div class="loading-container"><div class="loading-indicatior"></div></div>
							<a href="" data-action="getMore" data-namespace="activities" data-role="services" data-value="{%activities_pager_value%}" class="show-more"><span>Show more</span></a>
						</div>
					';
		
			$this->doReplace($html, $placeholder);
		}
	}