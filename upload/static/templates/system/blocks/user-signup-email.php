<div class="login-form">
	<h1 class="pagetitle"><?= $this->page->lang('signup_subtitle', array('#SITE_TITLE#'=>$C->SITE_TITLE)) ?></h1>
	
	<?php if( $C->USERS_EMAIL_CONFIRMATION && ($this->page->param('using') == 'twitter' || $this->page->param('using') == 'facebook') ){ 
			echo pageDesignerFactory::select()->informationMessage($this->page->lang('signup_before_twitter_facebook_warn'), $this->page->lang('outside_register_with_fb_tw').(($this->page->param('using') == 'facebook')? 'Facebook' : 'Twitter') ); 
	}?>
	
<form action="" method="POST">

	<label for="email"><?= $this->page->lang('signup_step1_form_email') ?></label>
	<input type="text" value="{%user_signup_email_value%}" name="email" id="email" data-status="focus"/>
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
		<button type="submit" value="submit" name="submit" class="btn blue"><span><?= $this->page->lang('signup_step1_form_submit') ?></span></button>
		<div class="clear"></div>
	</div>
</form>

<?php if((!empty($C->FACEBOOK_API_ID) && (!empty($C->FACEBOOK_API_SECRET))) || (!empty($C->TWITTER_CONSUMER_SECRET) && (!empty($C->TWITTER_CONSUMER_KEY))) ): ?>
	<div class="links">
		<?php if(!empty($C->FACEBOOK_API_ID) && (!empty($C->FACEBOOK_API_SECRET))): ?>
		<a href="<?= $C->SITE_URL ?>signup/using:facebook" class="facebook-button"></a>
		<?php endif; ?>
		
		<?php if(!empty($C->TWITTER_CONSUMER_SECRET) && (!empty($C->TWITTER_CONSUMER_KEY))): ?>
		<a href="<?= $C->SITE_URL ?>signup/using:twitter" class="twitter-button login-box"></a>
		<?php endif; ?>
	</div>
	<div class="clear"></div>
	<?php endif; ?>

<div class="clear"></div>

</div>