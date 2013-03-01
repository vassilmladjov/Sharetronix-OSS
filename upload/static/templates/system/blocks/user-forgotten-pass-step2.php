<div class="login-form">
	<h1 class="pagetitle"><?= $this->page->lang('signinforg_form_title'); ?></h1>

	<form action="" method="POST">
	
		<label for="pass1"><?= $this->page->lang('signinforg_form_password'); ?></label>
		<input type="password" value="{%user_forgotten_pass1_value%}" name="pass1" id="pass1"  data-status="focus"/>
		<div class="clear"></div>
		
		<label for="pass2"><?= $this->page->lang('signinforg_form_password2'); ?></label>
		<input type="password" value="{%user_forgotten_pass2_value%}" name="pass2" id="pass2"  data-status="focus"/>
		
		<div class="clear"></div>
		
		<div class="registration-buttons">
			<button type="submit" value="submit"  name="submit" class="btn blue"><span><?= $this->page->lang('signinforg_form_submit2'); ?></span></button>
			<div class="clear"></div>
		</div>
	</form>
</div>