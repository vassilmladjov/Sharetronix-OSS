
<? if( $D->error === FALSE ): ?>
		
		<table class="languages">
			<tr>
				<th width="40%"> <?= $this->page->lang('admin_languages_langs_col') ?> </th>
				<th width="10%"> <?= $this->page->lang('admin_languages_abrv_col') ?> </th>
				<th width="20%"> <?= $this->page->lang('admin_languages_lastver_col') ?> </th>
				<th width="30%"> <?= $this->page->lang('admin_languages_act_col') ?> </th>
			</tr>
		
			<?php foreach($D->languages as $lang): ?>
			<tr>
				<td> <?=$lang['lang_name'];?> </td>
				<td style="text-transform: uppercase;"> <?=$lang['langkey'];?> </td>
				<td> 
					<? if($lang['version'] == 0): ?>
						<? $lang['version'] = 1349049600; ?>
					<? endif; ?>
					
					<?=date("d M Y", $lang['version']); ?>
					
				</td>
				<td>
					<? if($lang['installed'] == false):?>
						<a href="<?=$C->SITE_URL;?>admin/languages/tab:installLangpack/langkey:<?=$lang['langkey'];?>" class="btn">
							<span>Install</span>
						</a>
					<? else: ?>
						<? if ( $lang['upgradable'] == true): ?>
							<a href="<?=$C->SITE_URL;?>admin/languages/tab:updateLangpack/langkey:<?=$lang['langkey'];?>" class="btn blue">
								<span>Upgrade</span>
							</a>
						<? endif; ?>
						<a href="<?=$C->SITE_URL;?>admin/languages/tab:uninstallLangpack/langkey:<?=$lang['langkey'];?>" class="btn">
							<span>Uninstall</span>
						</a>
					<? endif; ?>
				</td>
			</tr>
			<? endforeach; ?>
			
			</table>
<?php endif; ?>

<?php if($D->error !== FALSE){ echo $D->designer->errorMessage($this->page->lang('admgnrl_error'), $D->error); } ?>