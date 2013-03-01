// Sharetronix Groups namespace
var Groups = function () {

    // --- declare private methods --- //

	function joinSuccess(response, context) {
		$(context).parents('.options-container').html(response.html);
	}
	
	function leaveSuccess(response, context) {
		if(response.status!='ERROR'){
			$(context).parents('.options-container').html(response.html);
		}else{
			STX.showMessage(response.message, "error");
		}
		
	}
	
	function commandFail(response, context) {
        STX.showMessage(response.message, "error");
		//console.log(error);
    };

    
    // --- declare public methods --- //
    return {
 
        join: function(el, value, event) {
        	var args = {
					//type: 'post',
					module: 'groups',
					action: 'join',
					data: { groups_id: value }
				}
			Services.invoke(args, joinSuccess, commandFail, el);
        },
        
        leave: function(el, value, event) {
        	var args = {
					//type: 'post',
					module: 'groups',
					action: 'leave',
					data: { groups_id: value }
				}
			Services.invoke(args, leaveSuccess, commandFail, el);
        	
        },
    

    }
} ();