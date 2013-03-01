<div id="editor-placeholder">
    <div class="comments-editor data-content-placeholder">
	
	    <div class="loading-container">
	        <div class="loading-indicatior"></div>
	    </div>
    
    	{%comment_editor_user_avatar%}
		<div class="comments-editor-content">
			{%editor_textarea%}

			<div class="attachments-options">
				<a class="attachment-button ac-btn">User<span class="tooltip"><span><?= $this->page->lang('activity_comment_option_mention') ?></span></span></a>
			</div>

			<div class="buttons">
				<a data-action="set" data-namespace="comments" data-role="services" class="comment-post post-btn btn small blue"><span><?= $this->page->lang('activity_comment_option_post_btn') ?></span></a>
				<a data-value="0" data-action="add" data-namespace="comments" data-role="ajax-click" class="comment-cancel post-btn btn small"><span><?= $this->page->lang('activity_comment_option_cancel') ?></span></a>
			</div>
			<div class="clear"></div>

		</div>

	</div>
</div>