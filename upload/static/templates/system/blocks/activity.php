<div class="activity {%activity_nocomments%}">
	{%activity_user_avatar%}
	<div class="activity-container">
		<div class="activity-header">
			{%activity_user_username%}
			<div class="meta-info">
				{%activity_user_activity_group%}
				{%activity_top_placeholder%}
			</div>
			<div class="activity-options">{%activity_options%}</div>
		</div>
		<div class="activity-content">{%activity_text%}</div>
		{%activity_attachments%}
		<div class="activity-footer meta-info">{%activity_permlink%} {%activity_footer%}</div>
	</div>
	<div class="clear"></div>
	<div class="comments-thread-container" data-value="{%comments_thread_id%}">
		{%activity_comments_container%}
		<div class="comments-editor-field"><a href="#" data-action="activityAddComment" data-namespace="comments" data-role="services"><?= $this->page->lang('activity_comment_option_comment_write') ?></a></div>
	</div> 
</div>