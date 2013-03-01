{%header_content_searcharea%}
{%main_navigation%}


<?php if( $this->user->is_logged ) { ?>
	<div id="user-navigation">	
		<div class="user-options dropdown">
			<a href="<?= $C->SITE_URL ?><?= $this->user->info->username ?>" class="arrow menu-btn"><span class="plain-avatar"><img src="<?= $C->STORAGE_URL ?>avatars/thumbs3/<?= $this->user->info->avatar ?>" alt="" /></span></a>
		
			<ul class="menu-options">
				<li><a href="<?= $C->SITE_URL ?>settings"><span><?= $this->page->lang('hdr_nav_settings') ?></span></a></li>
				<?php if( $this->user->is_logged && $this->user->info->is_network_admin == 1 ) { ?>
					<li><a href="<?= $C->SITE_URL ?>admin" class="item-btn <?= $this->page->request[0]=='admin'?'active':'' ?>"><span><?= $this->page->lang('hdr_nav_admin') ?></span></a></li>
				<?php } ?>	
				<li><a href="<?= $C->SITE_URL ?>signout"><span><?= $this->page->lang('hdr_nav_signout') ?></span></a></li>
			</ul>
		</div>
		<a href="<?= $C->SITE_URL ?><?= $this->user->info->username ?>" class="username"><?= $this->user->info->username ?></a>
		<div class="clear"></div>
	</div>		
<?php } else { ?>
	<ul class="signup-navigation">
		<li><a href="<?= $C->SITE_URL ?>signin"><?= $this->page->lang('hdr_nav_signin') ?></a></li>
		<li><a href="<?= $C->SITE_URL ?>signup"><?= $this->page->lang('hdr_nav_signup') ?></a></li>
	</ul>
<?php } ?>