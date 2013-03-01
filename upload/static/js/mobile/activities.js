// Sharetronix Activities namespace
var Activities = function () {

    // --- declare private methods --- //

	var checknewTimeoutId = null,
		checknewInterval = 15000,
		pageTitle = '',
		
		
		body = $('html, body'),
		showMoreContainer = $('.show-more-container'),
		activityFeedList = $('.activity-feed-list'),
		notificationsStatus = $('.btn.menu .notifications'); 
	

    function postSuccess(response, context) {

        var editor = $(context).parents('.data-content-placeholder').find('textarea');
        var activityContent = $(response.html).css('display', 'none');
        var counter = $(editor).parents('.status-editor-container').find('.characters-counter');
        counter.text(counter.data('value'));
        
        if ($('.noposts').length > 0) $('.noposts').fadeOut();
        
        activityFeedList.prepend(activityContent);

        if ($('.new-activities-count').length > 0) {
            $('.new-activities-count').after(activityContent);
        }
        
        STX.createPostHide();
        activityContent.animate({ height: 'show' });

        //Htmlarea.reset(editor, 'activity');
        Attachments.updateToken($(context).parents('.data-content-placeholder'), 'status');
        $('#last_activity').val(response.inserted_activities_id)
        resetChecknewTimer();
    }
    
    function commandFail(response) {
        STX.showMessage(response.message, "error");
		//console.log(error);
    };
    
    function bookmarkSuccess(response, context) {
    	context.hasClass('empty') ?
    		context.removeClass('empty'):
    		context.addClass('empty');
    }
    
    function getallSuccess(response, context) {
    	var result = $(response.html).html();
    	activityFeedList.append(result);
    	var value = $(context).data('value');
    	if (response.last_activities_id != 0) {
    		value.activities_id = response.last_activities_id;
    		$(context).data('value', value);
    		showMoreContainer.removeClass('active');
    	} else {
    		showMoreContainer.remove();
    	}

    }


    function clearChecknewTimer() {
        if (checknewTimeoutId != null) {
            clearTimeout(checknewTimeoutId);
            checknewTimeoutId = null;
        }
    }
    
    function setChecknewTimer() { checknewTimeoutId = setTimeout(checknew, checknewInterval); }
    
    function resetChecknewTimer() {
    	clearChecknewTimer();
    	setChecknewTimer();
    }
    
    function checknew() {
        if (pageTitle == '') pageTitle = $(document).attr('title');
        
    	var data = {
			activities_type: $('#activities_type').val(),
			last_activity: $('#last_activity').val(),
			activities_tab: $('#activities_tab').val(),
			activities_group: $('#activities_group').val()
		}
		
		var args = {
				//type: 'post',
				module: 'activities',
				action: 'checknew',
				data: data
		}

		Services.invoke(args, checknewSuccess, commandFail);
        
        //clearChecknewTimer();
        
    }

    function getnewSuccess(response, context) {
    	//console.log(context);
    	$(context).remove();
    	var result = $(response.html).html();
    	
    	htmlContent = $('<div />').html(result).css('display', 'none');
    	activityFeedList.prepend(htmlContent);
    	body.scrollTop(0);
    	$(htmlContent).animate({ height: 'show' });
        
        $(document).attr('title', pageTitle);
        
        $('#last_activity').val(response.first_activities_id)
        
    	/*
    	htmlContent = $('<div />').html(newActivitiesHtml).css('display', 'none');
        $('.activity-feed-list').prepend($(htmlContent));
        $(htmlContent).animate({ height: 'show' });
        $(htmlContent).find('.activity').effect('highlight', {}, 3000);
        $(this).remove();

        newActivitiesHtml = '';
        newActivitiesCount = 0;
        $(document).attr('title', pageTitle);
        */
        

    }
    
    
    
    function checknewSuccess(response, context) {
		//console.log(response);
    	var newActivitiesCount = response.new_activities_dashboard;
    	
    	var new_activities_tab_at = response.new_activities_tab_at;
    	var new_activities_tab_commented = response.new_activities_tab_commented;
    	var new_messages = response.new_messages;
    	
    	var new_items_count = $('<span />').addClass('new-items-count');
    	var new_items_count_content = $('<span />');
    	
    	if (newActivitiesCount != 0) {
    	
	        $(document).attr('title', '(' + newActivitiesCount + ') ' + pageTitle);
	        if ($('.new-activities-count').length > 0) {
	            $('.new-activities-count').html(response.html);
	        } else {
	            statusContainer = $('<a />').addClass('new-activities-count').html(response.html).css('display', 'none');
	            
	            statusContainer.click(function (e) {
	            	e.preventDefault();
	            	
	            	var data = {
	        				activities_type: $('#activities_type').val(),
	        				last_activity: $('#last_activity').val(),
	        				activities_tab: $('#activities_tab').val(),
	        				activities_group: $('#activities_group').val()
	        		}
	            	
	            	var args = {
	    					//type: 'post',
	    					module: 'activities',
	    					action: 'getnew',
	    					data: data
	    				}
	
	    			Services.invoke(args, getnewSuccess, commandFail, $(this));
	            	
	            	
	            });
	            
	            activityFeedList.prepend(statusContainer);
	            statusContainer.animate({ opacity: 'toggle' });
	        }
    	    
    	}
    	
    	if (new_activities_tab_at != 0) {
    		notificationsStatus.css('display','block');
    		if ($('.feed-navigation .at .new-items-count').length > 0) {
    			$('.feed-navigation .at .new-items-count span').text(new_activities_tab_at)
    		} else {
    			new_items_count_content.text(new_activities_tab_at);
    			new_items_count.append(new_items_count_content);
    			$('.feed-navigation .at').append(new_items_count)
    			
    		}
    	}
    	
    	if (new_activities_tab_commented != 0) {
    		notificationsStatus.css('display','block');
    		if ($('.feed-navigation .comments .new-items-count').length > 0) {
    			$('.feed-navigation .comments .new-items-count span').text(new_activities_tab_commented)
    		} else {
    			
    			new_items_count_content.text(new_activities_tab_commented);
    			new_items_count.append(new_items_count_content);
    			$('.feed-navigation .comments').append(new_items_count)
    			
    		}
    		
    	}
    	
    	
    	if (new_messages != 0) {
    		notificationsStatus.css('display','block');
    		if ($('.feed-navigation .messages .new-items-count').length > 0) {
    			$('.feed-navigation .messages .new-items-count span').text(new_messages)
    		} else {
    			
    			new_items_count_content.text(new_messages);
    			new_items_count.append(new_items_count_content);
    			$('.feed-navigation .messages').append(new_items_count)
    			
    		}
    		
    	}
    	
    	
    	setChecknewTimer();
	}
	
	function _init() {
		if ($('#activities_type').length > 0 && $('#last_activity').length > 0) {
			setChecknewTimer();
		}
	}
    
    // --- declare public methods --- //
    return {
 
    	init: _init,
    	
        set: function(el, value, event) {
        	var editor = $(el).parents('.data-content-placeholder').find('textarea');
        	var token = $(el).parents('.data-content-placeholder').attr('data-token');
        	//var token = STX.generateToken();
        	var activityContent = editor.val().trim();
        	//console.log(value);
        	
            if (activityContent == editor.data('placeholder') || activityContent == '') { 
            	activityContent = '';
            	editor.focus();
            } else {
    			var data = { 
    					activities_text: activityContent,
    					activities_type: value.activities_type,
    					activities_group: value.activities_group,
    					token: token
    				}
    			var args = {
    					//type: 'post',
    					module: 'activities',
    					action: 'set',
    					data: data
    				}
    			Services.invoke(args, postSuccess, commandFail, el);
            }
        },

        bookmark: function(el, value, event) {
			var args = {
					//type: 'post',
					module: 'activities',
					action: 'bookmark',
					data: value
				}
			Services.invoke(args, bookmarkSuccess, commandFail, el);
        },
        
        getMore: function(el, value, event) {
        	//console.log('loading');
        	//Dialogs.alert('Loading ...');
        	
        	showMoreContainer.addClass('active');
        	
        	var args = {
					//type: 'post',
					module: 'activities',
					action: 'getall',
					data: value
				}
			Services.invoke(args, getallSuccess, commandFail, el);
        	
        }
    
    

    }
} ();

//--- declare page load events --- //
$(document).ready(function () {
    Activities.init();
});