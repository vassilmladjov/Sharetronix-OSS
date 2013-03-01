<? if(!empty($D->errors) ): ?>
<div class="plugin-information-block">
	<strong><?= $this->page->lang('admin_appsinstall_errors') ?></strong>
	<ul>
	<? foreach($D->errors as $e): ?>
		
		<li> <?=$e;?> </li>
	<? endforeach; ?>
	</ul>
</div>
<? endif; ?>

<? if(!empty($D->messages)):?>
<div class="plugin-information-block">
	<strong><?= $this->page->lang('admin_appsinstall_notices') ?></strong>
	<ul>
	<? foreach($D->messages as $m):?>
		<li><?=$m;?></li>
	<? endforeach; ?>
	</ul>
</div>
<? endif; ?>



<? if($D->install_ok == true): ?>
<div style="text-align: center; margin-top: 20px;">
	<a href="<?=$C->SITE_URL;?>admin/<?= $D->items_type ?>/tab:confirm_install/item_id:<?=$D->item->id;?>/item_path:<?=$D->item_path;?>">
		<button class="btn blue" type="submit">
			<span><?= $this->page->lang('admin_appsinstall_continue') ?></span>
		</button>
	</a>
</div>
<? else: ?>
<div class="plugin-information-block">
	<p><b><?= $this->page->lang('admin_appsinstall_error_msg_conflicts') ?></b> </p>
</div>
<? endif; ?>
