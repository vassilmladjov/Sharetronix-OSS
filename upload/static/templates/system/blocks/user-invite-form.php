<form action="" method="POST">
	
	<div class="invite-users-header">
		<span class="title"><?= $this->page->lang('group_invite_people') ?></span>
		<?php /*
        <input type="text" value="Search members" data-placeholder="Search members" onkeydown="javascript:return (event.keyCode != 13);" onkeyup="javascript:SearchUsers();" class="text" maxlength="50">
        <input type="button" onclick="resetSearch()" class="reset-search-btn" value="X">

         
        <div>	
                <a href="javascript:__doPostBack('ctl00$phMain$invitationControl$lbtnSelected','')" id="ctl00_phMain_invitationControl_lbtnSelected" onclick="javascript:ClearSearchBox();"><span id="ctl00_phMain_invitationControl_lblBtnSelected">Selected</span>
                    (<span class="label-selected-count" id="ctl00_phMain_invitationControl_lblBtnSelectedCount">0</span>)
                </a>
                <a href="javascript:__doPostBack('ctl00$phMain$invitationControl$lbtnAllUsers','')" id="ctl00_phMain_invitationControl_lbtnAllUsers" onclick="javascript:ClearSearchBox();"><span id="ctl00_phMain_invitationControl_lblAllUsers">ALL</span></a>
                <a href="javascript:__doPostBack('ctl00$phMain$invitationControl$lbtnMyFollowers','')" id="ctl00_phMain_invitationControl_lbtnMyFollowers" onclick="javascript:ClearSearchBox();"><span id="ctl00_phMain_invitationControl_lblMyFollowers">My Followers</span></a>
                <a href="javascript:__doPostBack('ctl00$phMain$invitationControl$lbtnIFollow','')" class=" selected" id="ctl00_phMain_invitationControl_lbtnIFollow" onclick="javascript:ClearSearchBox();"><span id="ctl00_phMain_invitationControl_lblIFollow">I Follow</span></a>
            
		</div>
		*/ ?>
        <div class="clear"></div>
    </div>
	
	
	<div class="invite-users">
		<div class="invite-users-list">
			{%users_invite_data%}
			<div class="clear"></div>
		</div>
	</div>
	
	<button type="submit" class="btn blue"><span><?= $this->page->lang('group_invite_people_sbm_btn') ?></span></button>
</form>