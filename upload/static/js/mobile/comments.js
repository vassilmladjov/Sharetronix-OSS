// Sharetronix Comments namespace
var Comments = function () {

    //var CommentsService = new STXServices.CommonServices();
    var groupID = $('#current-group-guid').val(),
    	commentsTextarea = $('.comments-editor textarea');

    function deleteSuccess(response, context) {
    	if (response.status == 'OK') {
    		$(context).parents('li:first').animate({ opacity: 'toggle', height: 'toggle' });
    	} else {
    		STX.showMessage(response.message, "error");
    	}
    }


    function postSuccess(response, context) {
    	commentContent = $(response.html).css('display', 'none');

        
        $(context).parents('.comments-thread-container').find('.comments-thread').append(commentContent);

        commentContent.animate({ height: 'show' });
        commentsTextarea.val(commentsTextarea.data('placeholder')).blur();
        
        commentsCount = context.parents('.comments-thread-container').find('.comments-thread li').length;
        context.parents('.activity-container').find('.activity .activity-footer .add-comment.action').text(commentsCount);
        
    }

    function showAllSuccess(response, context) {
    	$(context).addClass('active');
    	$(context).parents('.activity-container').find('.comments-thread').html(response.html);
    	$(context).parents('.activity-container').find('.comments-thread-container').animate({ opacity: 'toggle', height: 'toggle' }, function() {
    		$(context).parents('.activity-container').removeClass('no-comments');
    	});
    }

    function commandFail(response, context) {
    	STX.showMessage(response.message, "error");
        //STX.showMessage(result.get_message(), 'error');
    }

    function _init() {
        commentsTextarea.live('focus', function(e) {
        	if ($(this).data('placeholder') == $(this).val()) $(this).val('');
        	$(this).addClass('active');
        }).live('blur', function(e) {
        	if ($(this).val() == '') $(this).val($(this).data('placeholder'));
        	$(this).removeClass('active');
        });
    }
    
    return {

        set: function (el, value, event) {
        	var editor = $(el).parents('.data-content-placeholder').find('textarea');
        	var commentContent = editor.val().trim();
            if (commentContent == editor.data('placeholder') || commentContent == '') { 
            	commentContent = '';
            	editor.focus();
            } else {
            	var val = el.parents('.comments-thread-container').data('value');
    			var data = { 
    					comments_text: commentContent,
    					activities_type: val.activities_type,
    					activities_id: val.activities_id
    				}
    			var args = {
    					//type: 'post',
    					module: 'comments',
    					action: 'set',
    					data: data
    				}
    			Services.invoke(args, postSuccess, commandFail, el);
            }
        },

        showAll: function (el, value, event) {
        	
        	if (el.parents('.activity-container').hasClass('no-comments')) {
				var args = {
						//type: 'post',
						module: 'comments',
						action: 'getall',
						data: value
					}
				Services.invoke(args, showAllSuccess, commandFail, el);
        	} else {
        		$(el).parents('.activity-container').find('.comments-thread-container').animate({ opacity: 'toggle', height: 'toggle' });
        		$(el).toggleClass('active');
        	}
        },

        activityAddComment: function (el, value, event) {
            commentsTextarea.focus();
            
        },
        
        init: _init

    }
} ();


$(document).ready(function () {
	Comments.init(); 
});
