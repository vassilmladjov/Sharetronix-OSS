// Sharetronix Users namespace
var Users = function () {

    // --- declare private methods --- //
	var hideBizcardDelay = 50;
    var showBizcardDelay = 100;
    var hideTimer = null;
    var showTimer = null;
    var currentPosition = { left: '0px', top: '0px' };
    var intShow = null;
    var container = $('<div />').attr('id', 'popup-container');
    var selectedUsersCount = 0;
	
	function followSuccess(response, context) {
		if(response.status!='ERROR'){
			$(context).replaceWith(response.html);
		}else{
			STX.showMessage(response.message, "error");
		}
	}

	function unfollowSuccess(response, context) {
		if(response.status!='ERROR'){
			$(context).replaceWith(response.html);
		}else{
			STX.showMessage(response.message, "error");
		}
	}
	
	function sendMessageSuccess(response, context) {
		context.data('status', 'enabled').removeClass('disabled');
		Dialogs.hidePopup();
		if(response.status == 'ERROR' && response.message=='' ){
			STX.showMessage('Can`t send empty message', "error");
			return;
		}
		STX.showMessage(response.html, "success");
	}
	
	function commandFail(response, context) {
		context.data('status', 'enabled').removeClass('disabled');
        STX.showMessage(response.message, "error");
		//console.log(error);
    };

    function hideBizcard() {
        intShow = null;
        if (hideTimer) clearTimeout(hideTimer);
        hideTimer = setTimeout(function () {
            currentPosition = { left: '0px', top: '0px' };
            $.support.opacity ? container.fadeOut(50) : container.hide();
        }, hideBizcardDelay);
    };
    
    function showBizcard(response) {
        if (showTimer) clearTimeout(showTimer);
        if (intShow && response.status != 'ERROR') {
            container.html($(response.html));
            $.support.opacity ? container.fadeIn(100) : container.show();
        }
    };

    function getbizcardSuccess(response, context) {
        showBizcard(response);
    }

    function userBizcard() {
        $('body').append(container);
        $('.bizcard').live('mouseover', function () {
            if (!$(this).data('hoverIntentAttached')) {
                $(this).data('hoverIntentAttached', true);
                $(this).hoverIntent(
                    function () {
                    	//container.hide();
                    	//hideBizcard();
                        intShow = true;
                        if (hideTimer) clearTimeout(hideTimer);
                        var users_id = $(this).data('userid');
                        if (users_id == '') return;

                        var pos = $(this).offset();
                        var width = $(this).width();
                        var reposition = { left: pos.left + width + 'px', top: pos.top - 5 + 'px' };

                        container.css(reposition);
                        currentPosition = reposition;

                        setTimeout(function () {
        	            	var args = {
        	    					//type: 'post',
        	    					module: 'users',
        	    					action: 'bizcard',
        	    					data: {users_id: users_id}
        	    				}
                        	Services.invoke(args, getbizcardSuccess, commandFail, $(this));
                        }, showBizcardDelay)
                    }, 
                    // hoverIntent mouseOut
                    hideBizcard
                );
                // Fire mouseover so hoverIntent can start doing its magic
                $(this).trigger('mouseover');
            }
        });

        // Allow mouse over of details without hiding details and hide after mouseout
        container.mouseover(function () {
            if (hideTimer) clearTimeout(hideTimer);
        }).mouseout(
        	hideBizcard
        );
        
    }

    function _init() {
    	userBizcard();
    }
    
    
    // --- declare public methods --- //
    return {
 
    	init: _init,
    	
    	follow: function(el, value, event) {
    		var args = {
					//type: 'post',
					module: 'users',
					action: 'follow',
					data: { users_id: value }
				}
			Services.invoke(args, followSuccess, commandFail, el);
        	
        },
        
        unfollow: function(el, value, event) {
        	var args = {
					//type: 'post',
					module: 'users',
					action: 'unfollow',
					data: { users_id: value }
				}
			Services.invoke(args, unfollowSuccess, commandFail, el);
        },
        
        sendMessagePopup: function(el, value, event) {
        	html = '<div class="dialog-title">Send a message to <strong>' + value.users_name + ' (@' + value.users_username +') </strong></div>';
        	html += '<div class="user-status-field htmlarea"><div class="textarea-wrap"><textarea tabindex="1" name="private_message" id="private-message"></textarea><div class="textarea-highlighter"></div></div></div><div class="clear"></div>';
        	html += '<div class="btn-container right"><a class="status-btn post-btn btn" data-action="hidePopup" data-namespace="dialogs" data-role="services"><span>Cancel</span></a>';
        	html += '<a class="status-btn post-btn btn blue" data-action="sendMessage" data-namespace="users" data-role="services" data-value="'+value.users_id+'"><span>Send</span></a></div>';
        	Dialogs.popup(html);
        	el.data('status', 'enabled').removeClass('disabled');
        	$('#private-message').focus();
        },
        
        sendMessage: function(el, value, event) {
        	var args = {
					//type: 'post',
					module: 'users',
					action: 'sendmessage',
					data: { users_id: value, text: $('#private-message').val() }
				}
			Services.invoke(args, sendMessageSuccess, commandFail, el);
        },
        
        
        
        toggleSelectedUsersContainer: function (el, value, event) {
            caller = event.target;
            var input = $('input', el);

            if (!$(caller).is('input')) {


                if (input.is(':checked')) {
                    input.removeAttr('checked');
                } else {
                    input.attr('checked', 'checked');
                }
            }

            if (input.attr('checked')) {
                selectedUsersCount += 1;
                input.parents('.invite-user-container').addClass('selected');
            } else {
                selectedUsersCount -= 1;
                input.parents('.invite-user-container').removeClass('selected');
            }
            //$('.label-selected-count').text(selectedUsersCount);
        }


    }
} ();

$(document).ready(function() {
	Users.init();
});