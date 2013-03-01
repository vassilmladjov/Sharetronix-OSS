<form action="<?=$C->SITE_URL;?>admin/<?= $D->items_type ?>/tab:index/" method="post" class="form-container search-plugins">
	<label for="search_field"><?= $this->page->lang('admin_marketplace_search_item') ?></label><br />
	<input type="text" name="search_string" value="<?= $D->search_term;?>" id="search_field" />
	<button type="submit" id="search_button" class="btn blue"><span><?= $this->page->lang('admin_marketplace_search_item_btn') ?></span></button>
	<div class="clear"><!--  --></div>
</form>

<?php if(empty($D->items)) :?>
	<h3> <?= $this->page->lang('admin_marketplace_no_apps_msg') ?></h3>
<?php else: ?>
	<? $counter = 0; ?>
	<?php foreach($D->items as $p):?>
		<div class="plugin">
			<img src="<?=$C->MARKETPLACE_URL;?><?=str_replace("\\", '/', $p->screenshots[0]->path);?>/<?=$p->screenshots[0]->image;?>"  />
			
			<div class="details">
				<a href="<?=$C->SITE_URL;?>admin/<?= $D->items_type ?>/tab:view/item_id:<?=$p->id;?>" class="title"><?= $p->name;?></a>
				<div class="description"><?= $p->descr;?></div>
				<div class="actions">
					<a href="<?=$C->SITE_URL;?>admin/<?= $D->items_type ?>/tab:view/item_id:<?=$p->id;?>" class="btn">
						<span><?= $this->page->lang('admin_marketplace_app_view_details') ?></span>
					</a>
					<?php if($p->installed == true) :?>
						<a href="<?=$C->SITE_URL;?>admin/<?= $D->items_type ?>/tab:uninstall/item_id:<?=$p->id;?>" class="btn">
							<span><?= $this->page->lang('admin_marketplace_app_uninstall') ?></span>
						</a>
					<?php else: ?>
						<?php if($p->installable == true) :?>
							<a href="<?=$C->SITE_URL;?>admin/<?= $D->items_type ?>/tab:install/item_id:<?=$p->id;?>" class="btn blue">
								<span><?= $this->page->lang('admin_marketplace_app_install') ?></span>
							</a>
						<?php else: ?>
							<a href="<?=$C->MARKETPLACE_URL;?>marketplace/plugin/name/<?=$p->name;?>/id/<?=$p->id;?>" target="_blank" class="btn blue">
								<span><?= $this->page->lang('admin_marketplace_app_buynow') ?></span>
							</a>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			
			</div>
			
		</div>
		<? $counter++;?>
		<? if($counter%2 == 0): ?>
			<div class="clear"><!--  --></div> 
		<? endif; ?>
	<?php endforeach; ?>
<?php endif; ?>
<div class="clear"><!--  --></div>

<?php if(!empty($errors)): ?> 
	<ul>
		<?php foreach($errors as $e): ?>
			<li> <?=$e;?></li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
<div class="clear"><!--  --></div>

<?php if($D->pagination == true):?>
	<div class="pager">
		<span class="pager-item title"><?= $this->page->lang('admin_marketplace_pages') ?></span>
		<?php for($i=0; $i< $D->num_pages; $i++): ?>
			<a href="<?=$C->SITE_URL;?>admin/<?= $D->items_type ?>/tab:index/page:<?=$i;?>" class="pager-item "> <?=(int)($i+1);?></a>
		<?php endfor; ?>
	</div>
<?php endif; ?>
<div class="clear"><!--  --></div>