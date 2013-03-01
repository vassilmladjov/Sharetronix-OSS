<div class="login-form">
	<h1 class="pagetitle"><?= $this->page->lang('signinforg_form_title'); ?></h1>
	<form action="" method="POST">
		<label for="email"><?= $this->page->lang('signin_form_email'); ?></label>
	
		<input type="text" value="{%user_forgotten_email_value%}" name="email" id="email" data-status="focus"/>
		<div class="clear"></div>
		
		<label for="captcha_word"><?= $this->page->lang('signup_step2_form_captcha') ?></label>
	
		<?php if(!$D->use_google_recaptcha): ?>
		<div class="captcha-fields">
			<input type="text" id="captcha_word" name="captcha_word" {%autofocus%} />
			<input type="hidden" value="{%captcha_key%}" name="captcha_key">
		</div>
		<?php endif; ?>
		
		<div class="captcha-image">{%captcha_image%}</div>
		
		<div class="registration-buttons">
			<button type="submit" value="submit" name="submit" class="btn blue"><span><?= $this->page->lang('signinforg_form_submit'); ?></span></button>
			<div class="clear"></div>
		</div>
	</form>
</div>