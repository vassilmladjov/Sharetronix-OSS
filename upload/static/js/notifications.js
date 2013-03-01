// Sharetronix Groups namespace
var Notifications = function () {

    // --- declare private methods --- //


	function notificationsSuccess(response, context) {
		Dialogs.alert(response.html);
		//$(context).parents('.people-section ').remove();
	}
	
	function commandFail(response, context) {
        //STX.showMessage(response.message, "error");
		//console.log(error);
    };

    
    // --- declare public methods --- //
    return {
 
    	showNotificationDetails: function(el, value, event) {
        	var args = {
					//type: 'post',
					module: 'notifications',
					action: 'showdetails',
					data: { users_id: value }
				}
			Services.invoke(args, notificationsSuccess, commandFail, el);
        },

    }
} ();