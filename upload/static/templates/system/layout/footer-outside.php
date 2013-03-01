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
		
		{%footer_js_data%}
	</body>
</html>