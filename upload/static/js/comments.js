// Sharetronix Comments namespace
var Comments = function () {

    //var CommentsService = new STXServices.CommonServices();
    var groupID = $('#current-group-guid').val();

    function showLoading(userContext) {
        loadingContainer = $(userContext).parents('.comments-thread-container:first').find('.loading-container');
        $(loadingContainer).show();
        $(loadingContainer).css('left', '0');
    }

    function hideLoading(userContext) {
        loadingContainer = $(userContext).parents('.comments-thread-container:first').find('.loading-container');
        $(loadingContainer).hide();
    }

    function deleteSuccess(response, context) {
    	if (response.status == 'OK') {
    		if ($.browser.msie) {
    			$(context).parents('li:first').animate({ height: 'toggle' }, 'slow');
    		} else {
    			$(context).parents('li:first').animate({ opacity: 'toggle', height: 'toggle' });
    		}
    	} else {
    		STX.showMessage(response.message, "error");
    	}
    }

    function showAllSuccess(response, context) {
        
    }

    function postSuccess(response, context) {

    	$(context).parents('.activity').removeClass('no-comments');
    	
    	commentContent = $(response.html).css('display', 'none');
        

        if ($(context).parents('.comments-thread-container:first').find('.comments-thread').length == 0) {
            olCommentsContainer = $('<ol />').addClass('comments-thread');
            $(context).parents('.comments-thread-container:first').prepend($(olCommentsContainer));
        }
        $(context).parents('.comments-thread-container:first').find('.comments-thread').append(commentContent);

        commentContent.animate({ height: 'show' });
        commentContent.find('.comment').effect('highlight', {}, 3000);
        Comments.activityAddComment(context);
        hideLoading(context);
        //STX.colorboxInit($(cnt));
    }

    function showAllSuccess(response, context) {
        //hideLoading(userContext);
        $('#editor-placeholder').append($('.comments-editor'));
        
        var commentsThread = $(context).parents('.comments-thread-container');
        $('.comments-editor-field', commentsThread).show();
        $('.show-all-comments', commentsThread).remove();
        $('ol', commentsThread).html(response.html);
        //Attachments.updateToken($('.comments-editor.data-content-placeholder'), 'comment');
        //STX.colorboxInit($(resultContainer));
        
    }

   

    function commandFail(response, context) {
    	STX.showMessage(response.message, "error");
        //STX.showMessage(result.get_message(), 'error');
    }

    return {

       

        set: function (el, value, event) {
        	var editor = $(el).parents('.data-content-placeholder').find('.htmlarea textarea')
        	var commentContent = editor.val().trim();
            if (commentContent == editor.data('placeholder') || commentContent == '') { 
            	commentContent = '';
            	el.data('status', 'enabled').removeClass('disabled');
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

        deleteComment: function (el, value, event) {
           
        	Dialogs.confirm(
        			'Are you sure you want to delete this comment?', 
        			function() {
		        		var args = {
		    					//type: 'post',
		    					module: 'comments',
		    					action: 'delete',
		    					data: value
		    				}
		    			Services.invoke(args, deleteSuccess, commandFail, el);
        			}
        	);
        	
        	
        },

       
        showAll: function (el, value, event) {
        	
			var args = {
					//type: 'post',
					module: 'comments',
					action: 'getall',
					data: value
				}
			Services.invoke(args, showAllSuccess, commandFail, el);
        },

        

        activityAddComment: function (el) {
            $('.activity.no-comments .activity-meta-options .comment').show();
            $('.activity:not(.no-comments) .comments-thread-container .comments-editor-field').show();
            $(el).parents('.activity').find('.comments-editor-field').hide();
			
			el.data('status', 'enabled').removeClass('disabled');
			
            commentsEditorContainer = $(el).parents('.activity:first').find('.comments-thread-container:first');

            $(commentsEditorContainer).append($('.comments-editor'));
            $('.comments-editor .htmlarea textarea').val('');
            $('.comments-editor').show();
            $(commentsEditorContainer).show();

            setTimeout(function () {
                Htmlarea.reset($('.comments-editor .htmlarea textarea'), 'comment');
                $('.comments-editor .htmlarea textarea').focus();
            }, 1);

            Attachments.updateToken($('.comments-editor.data-content-placeholder'), 'comment');
        }


    }
} ();