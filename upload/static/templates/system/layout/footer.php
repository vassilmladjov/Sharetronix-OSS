				<div class="clear"></div>
			</div>
			<div id="footer-spacer"></div>
		</div>

		<div class="footer-container">			
			<div id="footer">
				<div class="left">
					<?= htmlspecialchars($C->OUTSIDE_SITE_TITLE) ?>
					{%footer_placeholder%}
				</div>
					
				<div class="right">
					<span>
						
						<!-- "Powered by Sharetronix" backlink -->
							<!--
							You are required to keep the "Powered by Sharetronix" backlink
							as per the Sharetronix License: http://developer.sharetronix.com/license
							-->
							{%stx_footer_link_abc%}
						<!-- "Powered by Sharetronix" backlink END -->
							
					</span> 
				</div>
			</div>
		</div>
		
		<?php
			// 
			// Please read the file ./system/cronjobs/readme.txt
			// 	
			
			/*
			if( !isset($C->CRONJOB_IS_INSTALLED) || !$C->CRONJOB_IS_INSTALLED ) {
				$lastrun	= $GLOBALS['cache']->get('cron_last_run');
				if( ! $lastrun || $lastrun < time()-60 ) {
					echo '
						<script type="text/javascript">
							var tmpreq = ajax_init(false);
							if( tmpreq ) {
								tmpreq.onreadystatechange	= function() {  };
								tmpreq.open("HEAD", siteurl+"cron/r:"+Math.round(Math.random()*1000), true);
								tmpreq.setRequestHeader("connection", "close");
								tmpreq.send("");
								setTimeout( function() { tmpreq.abort(); }, 3000 );
							}			
						</script>';
				}
			}
			
			*/
		?>
	{%footer_js_data%}
	
	
	
	{%comment_editor%}
	
	
	</body>
</html>