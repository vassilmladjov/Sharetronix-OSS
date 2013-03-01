<? if($D->error != ""):?>
	<?=$D->error; ?>
<? else: ?>

	<div class="plugin-information-block">
		<p><?= $this->page->lang('admin_appuninstall_msg') ?> <b><?=$D->item->name;?></b> </p>
				
		<div style="margin-top: 10px;">
			<a href="<?=$C->SITE_URL;?>admin/<?= $D->items_type ?>/tab:confirm_uninstall/item_id:<?=$D->item->id;?>/item_path:<?=base64_encode(PLUGINDIR . $D->item->system_name);?>">
				<button class="btn blue" type="submit">
					<span>Yes</span>
				</button>
			</a>		
		
			<a href="<?=$C->SITE_URL;?>admin/<?= $D->items_type ?>/tab:view/item_id:<?=$D->item->id;?>">
				<button class="btn blue" type="submit">
					<span>No</span>
				</button>
			</a>
		</div>
	</div>
<? endif; ?>
			