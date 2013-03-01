// Sharetronix Users namespace
var Users = function () {

    // --- declare private methods --- //	
	function followSuccess(response, context) {
		$(context).replaceWith(response.html);
	}

	function unfollowSuccess(response, context) {
		$(context).replaceWith(response.html);
	}
	
	function commandFail(response, context) {
        STX.showMessage(response.message, "error");
		//console.log(error);
    };
    
	function sendMessageSuccess(response, context) {
		//Dialogs.hidePopup();
		STX.sendMessageHide();
		STX.showMessage(response.html);
	}


    // --- declare public methods --- //
    return {

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
        
        sendMessage: function(el, value, event) {
        	var args = {
					//type: 'post',
					module: 'users',
					action: 'sendmessage',
					data: { users_id: $('#user-id').val(), text: $('#send-message-container textarea').val() }
				}
			Services.invoke(args, sendMessageSuccess, commandFail, el);
        }


    }
} ();