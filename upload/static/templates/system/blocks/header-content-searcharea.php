<div id="header-search">
	<form id="searchForm" method="post" action="<?= $C->SITE_URL ?>search">
		<input type="text" name="lookfor" class="search-field" value="<?=isset($D->search_string) ? htmlspecialchars($D->search_string):$this->page->lang('network_header_search_input_txt')?>" x-webkit-speech autocomplete="off" onwebkitspeechchange="STX.searchReplace();" data-watermark="<?= $this->page->lang('network_header_search_input_txt') ?>"  />
		<button type="submit"><span><?= $this->page->lang('hdr_search_submit') ?></span></button>
		<input type="hidden" name="lookin" id="search-lookin" value="<?= $D->hdr_search ?>" />

		<div class="searchselect dropdown">
			<a href="" class="menu-btn"><?= $this->page->lang('hdr_search_'.$D->hdr_search) ?></a>
			<ul class="menu-options">
				<li><a href="" data-type="posts"><?= $this->page->lang('hdr_search_posts') ?></a></li>
				<li><a href="" data-type="users"><?= $this->page->lang('hdr_search_users') ?></a></li>
				<li><a href="" data-type="groups"><?= $this->page->lang('hdr_search_groups') ?></a></li>
			</ul>
		</div>
		
	</form>
</div>