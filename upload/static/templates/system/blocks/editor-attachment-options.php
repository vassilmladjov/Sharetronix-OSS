<div class="attachments-options">

	<a class="attachment-button ac-btn"><?= $this->page->lang('activity_option_user') ?><span class="tooltip"><span><?= $this->page->lang('activity_comment_option_mention') ?></span></span></a>
	<span class="file attachment-button"><?= $this->page->lang('activity_option_file') ?><span class="tooltip"><span><?= $this->page->lang('activity_option_upload_options') ?></span></span></span>
	<div class="attachment-link-container">
		<span class="link attachment-button"><?= $this->page->lang('activity_option_link') ?><span class="tooltip"><span><?= $this->page->lang('activity_option_attach_options') ?></span></span></span>
		<div class="attachment-link-field-container" style="display: none;">
			<span class="attachment-button add-link"><?= $this->page->lang('activity_option_attach') ?></span>
			<input type="text" class="attachment-link-field" value="">
		</div>
	</div>

	<span class="uploading"><?= $this->page->lang('activity_option_upload_txt') ?></span>
	<div class="clear"></div>

</div>