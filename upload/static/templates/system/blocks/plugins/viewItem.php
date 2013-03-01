<div class="plugin-details">
	<div class="image-preview">
		<? foreach($D->item->screenshots as $s): ?>
			<img src="<?=$C->MARKETPLACE_URL;?><?=str_replace("\\", '/', $s->path);?><?=$s->image;?>" />
		<? endforeach; ?>
	</div>

    <div class="plugin-description">
    	<?php if( !checkPluginCompatability( $D->item->compatible ) ){
			$designer = pageDesignerFactory::select();
			echo $designer->informationMessage($this->page->lang('admin_apps_warn_ttl'), $this->page->lang('admin_apps_warn_txt'));	
    	} ?>
    	<h1><a href="<?= $C->MARKETPLACE_URL ?>appstore/app/id/<?= $D->item->id ?>" target="_blank"><u><?=$D->item->name;?></u></a></h1>
    	<?=$D->item->short_descr;?>
    	
    	<div class="rating">
    		<span class="star <?= ($D->item->average_rating < 1) ? 'empty' : ''; ?>"></span>
    		<span class="star <?= ($D->item->average_rating < 1.5) ? 'empty' : ''; ?>"></span>
    		<span class="star <?= ($D->item->average_rating < 2.5) ? 'empty' : ''; ?>"></span>
    		<span class="star <?= ($D->item->average_rating < 3.5) ? 'empty' : ''; ?>"></span>
    		<span class="star <?= ($D->item->average_rating < 4.5) ? 'empty' : ''; ?>"></span>
			<div class="rating-meta-info">
				<?= $this->page->lang('admin_marketplace_item_current_rating') ?> <strong><?= $D->item->average_rating; ?></strong>/<?= $this->page->lang('admin_marketplace_item_rating_stars') ?><br>
				<?= $this->page->lang('admin_marketplace_item_num_votes') ?> <strong><?= $D->item->num_votes; ?></strong>
			</div>
			<div class="clear"></div>
		</div>
		
		
		<? if($D->item_installed == true):?>
			<a href="<?=$C->SITE_URL;?>admin/<?= $D->items_type ?>/tab:uninstall/item_id:<?=$D->item->id;?>" class="btn"><span><?= $this->page->lang('admin_marketplace_app_uninstall') ?></span></a>
		<? else: ?>
			<? if($D->item_installable == true): ?>
				<a href="<?=$C->SITE_URL;?>admin/<?= $D->items_type ?>/tab:install/item_id:<?=$D->item->id;?>" class="btn blue"><span><?= $this->page->lang('admin_marketplace_app_install') ?></span></a>
			<? else: ?>
				<a href="<?=$C->MARKETPLACE_URL;?>appstore/plugin/name/<?=$D->item->name;?>/id/<?=$D->item->id;?>" target="_blank" class="btn blue"><span><?= $this->page->lang('admin_marketplace_app_buynow') ?></span></a>
			<? endif;?>
		<? endif; ?>
		<div class="downloads-meta-info">
			<?= $this->page->lang('admin_marketplace_item_price') ?> <strong>$<?= $D->item->price; ?></strong><br />
			<strong><?= $this->page->lang('admin_marketplace_item_num_downloads') ?> <?= $D->item->num_downloads; ?></strong>
		</div>
		<div class="clear"></div>
		
    </div>
    <div class="clear"></div>    
    
	<div class="left-column">	
		<?= $this->page->lang('admin_marketplace_item_release') ?> <strong><?= date("jS M Y, H:i", $D->item->date_created); ?></strong><br>
		<?= $this->page->lang('admin_marketplace_item_lastupdated') ?> <strong><?= date("jS M Y, H:i", $D->item->last_update); ?></strong><br>
		<?= $this->page->lang('admin_marketplace_item_email') ?> <strong><a href="mailto:<?=$D->item->email;?>"><?=$D->item->email;?></a></strong><br>
	</div>
	
	<div class="right-column">
		<h2><?= $this->page->lang('admin_marketplace_item_details') ?></h2>
		<?=$D->item->descr;?>
	</div>
    <div class="clear"></div>
</div>