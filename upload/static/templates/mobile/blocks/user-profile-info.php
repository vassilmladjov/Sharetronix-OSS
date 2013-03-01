<div id="profile">
    <div class="avatar" style="background-image:url(<?= $C->STORAGE_URL.'avatars/' ?>{%user_profile_avatarimg%});">
    	<?php /* <img src="<?= $C->STORAGE_URL.'avatars/' ?>{%user_profile_avatarimg%}"> */ ?>
    	<h1><strong>{%user_profile_username%}</strong><span class="job-title">{%user_profile_jobtitle%}</span></h1>
    	
    	<div class="options-container">{%user_header_follow_button%}</div>
    </div>

    {%user_profile_navigation%}

	{%user_profile_details%}
	
	<input type="hidden" id="user-id" value="{%user_id%}" />
</div>