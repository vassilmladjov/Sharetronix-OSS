<div class="people-section">

	<div class="avatar">{%single_user_bizcard_avatar%}</div>

	<div class="details">
		<div class="title">
			<a href="<?= $C->SITE_URL ?>{%single_user_bizcard_username%}">{%single_user_bizcard_user_identifier%}</a>
			<span class="job-title">{%single_user_bizcard_jobtitle%}</span>
		</div>
		<div class="statistics">{%single_user_bizcard_activity%}</div>
		<ul class="personal-information">
			<li class="personal-information-email">{%single_user_bizcard_email%}</li>
			{%user_biz_card_more_personal_info%}
		</ul>
		
		
		<div class="options-container">{%single_user_bizcard_follow%}</div>
		
	</div>

	<div class="clear"></div>
</div>