<div class="login-form">

	<h1 class="pagetitle"><?= $this->page->lang('signin_form_caption') ?></h1>

	<form action="" method="post">
	
	
		<label for="email"><?= $this->page->lang('signin_email_or_username') ?></label>
		<input type="text" class="text" id="email" name="email" data-status="focus">
		<div class="clear"></div>
		
		<label for="password"><?= $this->page->lang('signin_form_password') ?></label>
		<input type="password" class="text" id="password" name="password">
		
		
	
		<div class="registration-buttons">
			<button type="submit" class="login btn blue"><span><?= $this->page->lang('signin_login_box_ttl') ?></span></button>
			<span class="remember-me">
				<input type="checkbox" name="rememberme" id="rememberme" value="1" ><label for="rememberme"><?= $this->page->lang('signin_form_rem') ?></label>
			</span>
			<div class="clear"></div>
		</div>
	
		<div class="links">
			<a href="<?= $C->SITE_URL ?>signin/forgotten"><?= $this->page->lang('signin_form_forgotten'); ?></a>
			<a href="<?= $C->SITE_URL ?>signup"><?= $this->page->lang('signin_reg_title') . $this->page->lang('signin_reg_button'); ?></a>
		</div>
		
		<?php if((!empty($C->FACEBOOK_API_ID) && (!empty($C->FACEBOOK_API_SECRET))) || (!empty($C->TWITTER_CONSUMER_SECRET) && (!empty($C->TWITTER_CONSUMER_KEY))) ): ?>
		<div class="links">
			<?php if(!empty($C->FACEBOOK_API_ID) && (!empty($C->FACEBOOK_API_SECRET))): ?>
			<a href="<?= $C->SITE_URL ?>signup/using:facebook" class="facebook-button"></a>
			<?php endif; ?>
			
			<?php if(!empty($C->TWITTER_CONSUMER_SECRET) && (!empty($C->TWITTER_CONSUMER_KEY))): ?>
			<a href="<?= $C->SITE_URL ?>signup/using:twitter" class="twitter-button login-box"></a>
			<?php endif; ?>
		</div>
		<?php endif; ?>
		
		<div class="clear"></div>

	</form>
</div>
