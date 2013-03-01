<div class="links">
	<div class="youtube-container container">
		<a data-type="youtube" data-embed="{%activity_videoembed_html%}" class="thumb video-youtube">
			<span class="play-icon"></span>
			<img src="{%activity_videoembed_img_link%}" alt="<?= $this->page->lang('activity_attachment_video_thumb') ?>">
		</a>
		<div class="content">
			<a target="_blank" href="{%activity_videoembed_link_href%}" class="link-title">{%activity_videoembed_link_title%}</a>
			<span>{%activity_videoembed_link_description%}</span>
		</div>
		<div class="clear"></div>
		<div class="video-placeholder" style="display: none;"></div>
	</div>
</div>