// Sharetronix Groups namespace
var Administration = function () {

    // --- declare private methods --- //


	function administrationSuccess(response, context) {
		Dialogs.alert(response.html);
		$(context).parents('.people-section ').remove();
	};
	
	function commandFail(response, context) {
        STX.showMessage(response.message, "error");
		//console.log(error);
    };

    
    function _init(){
    	
    	
    	$('input[name=hdr_show_logo]').change(function(){
    		var curr_value = $('input[name=hdr_show_logo]:checked').val();

        	if(curr_value == 1) {
        		$('#custom_logo').parents('tr').hide();
        	} else {
        		$('#custom_logo').parents('tr').show();
        	}		
    	});
    	$('input[name=hdr_show_logo]').trigger("change");
    	

    	$('input[name=hdr_show_favicon]').change(function(){
    		var curr_value = $('input[name=hdr_show_favicon]:checked').val();

        	if(curr_value == 1) {
        		$('#custom_favicon').parents('tr').hide();
        	} else {
        		$('#custom_favicon').parents('tr').show();
        	}		
    	});
    	$('input[name=hdr_show_favicon]').trigger("change");
    	
    };
    
    // --- declare public methods --- //
    return {
 
        remove_moderator: function(el, value, event) {
        	
        	Dialogs.confirm(
        			'Are you sure you want to delete this administrator?', 
        			function() {
		        		var args = {
		        				module: 'administration',
		    					action: 'removemoderator',
		    					data: { users_id: value }
		    				}
		    			Services.invoke(args, administrationSuccess, commandFail, el);
        			}
        	);
        	
        },
    	
    	
        remove_administrator: function(el, value, event) {
        	
        	Dialogs.confirm(
        			'Are you sure you want to delete this administrator?', 
        			function() {
		        		var args = {
		        				module: 'administration',
		    					action: 'removeadmin',
		    					data: { users_id: value }
		    				}
		    			Services.invoke(args, administrationSuccess, commandFail, el);
        			}
        	);
        	
        },
        
        activate_user: function(el, value, event) {
        	var args = {
					//type: 'post',
					module: 'administration',
					action: 'activateuser',
					data: { users_id: value }
				}
			Services.invoke(args, administrationSuccess, commandFail, el);
        },
        _init: _init
    

    }
} ();

$(document).ready(function(){
	Administration._init();
});