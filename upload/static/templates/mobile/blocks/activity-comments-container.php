<div class="comments-thread-container" data-value="{%comments_thread_id%}">
		
	{%show_all_activity_comments%}
	<ol class="comments-thread">
		{%activity_comments%}
	</ol>

	<!-- <div class="comments-editor-field"><a href="#" data-action="activityAddComment" data-namespace="comments" data-role="services">write a comment</a></div>  -->
	
	
	

    <div class="comments-editor data-content-placeholder">
    	{%comment_editor_user_avatar%}
		<div class="comments-editor-content">
			<textarea class="htmlarea" id="comment-editor" data-placeholder="Write a comment ..."><?= $this->page->lang('activity_comment_option_comment_write') ?></textarea>
			<div class="buttons">
				<a href="#" data-action="set" data-namespace="comments" data-role="services" class="comment-post post-btn btn"><span><?= $this->page->lang('activity_option_post_btn') ?></span></a>
			</div>
			<div class="clear"></div>

		</div>

	</div>

	
	
</div>