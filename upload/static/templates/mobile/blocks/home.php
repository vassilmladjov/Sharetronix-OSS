<div id="login-panel" class="panel">
	<div class="panel-container">
		<form action="" method="post">
			<label for="email"><?= $this->page->lang('os_login_email_username') ?></label>
			<input type="text" id="email" name="email" data-status="focus">
			
			<label for="password"><?= $this->page->lang('os_login_pwd') ?></label>
			<input type="password" id="password" name="password">
	
			<button type="submit" class="btn"><span><?= $this->page->lang('os_login_btn_txt') ?></span></button>
			
			<div class="powered_by">
			<!-- "Powered by Sharetronix" backlink -->
				<!--
				You are required to keep the "Powered by Sharetronix" backlink
				as per the Sharetronix License: http://developer.sharetronix.com/license
				-->
				{%stx_footer_link_abc%}
			<!-- "Powered by Sharetronix" backlink END -->
			</div>
			
		</form>
	</div>
</div>

<div class="clear"></div>