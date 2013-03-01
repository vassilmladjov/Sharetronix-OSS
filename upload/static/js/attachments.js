// Sharetronix Attachments namespace
var Attachments = function () {
    var statusUploader;
    var commentsUploader;

    var attachedLinks = function () { };
    attachedLinks.activity = new Array();
    attachedLinks.comment = new Array();

    // --- declare private methods --- //
    function showLoading(el) {
        var loadingContainer = el.find('.uploading');
        $(loadingContainer).show();
        $(el).closest('.comments-editor-content').find('.disable-btn').show();
    }

    function hideLoading(el) {
        var loadingContainer = el.find('.uploading');
        $(loadingContainer).hide();
        $(el).closest('.comments-editor-content').find('.disable-btn').hide();
    }

    function changeImageUrl(container, index) {
        var lc = $(index).parents('.container');
        $(lc).attr('data-image', $('img', index).attr('src'));
    }

    function deleteAttachment(response, el) {
    	var data = { 
    			attachment_id: response.att_id,
    			attachment_type: response.att_type,
    			token: response.token
			}
		var args = {
				//type: 'post',
				module: 'attachments',
				action: 'delete',
				data: data
			}
		Services.invoke(args, deleteAttachmentSuccess, commandFail, el);
    }
    
    function deleteAttachmentSuccess(response, context) {
    	var parent = $(context).parents('.container');
        var uploadContainer = $(parent).parents('.uploads');
        var url = $(parent).data('url');
        removeAttachedLink(url, parent);
        $(parent).remove();
        attachmentsPlaceholder(uploadContainer);
    }
    
    function attachLinkSuccess(result, userContext) {
        var linkCnt = $(userContext).parents('.data-content-placeholder');
        hideLoading(linkCnt);
        btn = $(userContext).parents('.data-content-placeholder').find('.post-btn');
        $(btn).data('status', 'enabled');
        $(btn).removeClass('disabled');

        $('.attachment-link-field-container', linkCnt).hide();
        $('.uploads', linkCnt).show();

        if (result.type == 'page') {
            var attributes = [];
            if (result.url) { attributes['data-url'] = result.url; }
            if (result.type) { attributes['data-type'] = result.type; }
            if (result.description) { attributes['data-description'] = result.description; }
            if (result.title) { attributes['data-title'] = result.title; }
            var pageContainer = $('<div />').addClass('container').attr(attributes);
            if (result.Images != null && result.Images.length > 0) {
                $(pageContainer).addClass('container').attr({ 'data-image': result.Images[0] });
            }
            var clearEl = $('<div />').addClass('clear');
            var pageContent = $('<div />').addClass('content');
            if (result.Images == null || result.Images.length == 0) $(pageContent).addClass('text-info')
            var pageContentLink = $('<a />').attr({ 'href': result.url, 'target': '_blank' }).addClass('link-title').text(result.title);
            var pageContentText = $('<span />');
            if (result.description != '') {
                $(pageContentText).text(result.description);
            }
            var imgContainerDelete = $('<a />').addClass('delete').click(function () {
            	deleteAttachment(result, $(this));
            });
            if (result.Images != null && result.Images.length > 0) {
                var imagesList = $('<ul />');
                for (var i = 0; i < result.Images.length; i++) {
                    if (result.Images[i] != '') {
                        var liImage = $('<li />');
                        var tmpImage = $('<img />').attr('src', result.Images[i]);
                        $(liImage).append($(tmpImage));
                        $(imagesList).append($(liImage));
                    }
                }
                $(pageContainer).append($(imagesList));
                if (result.Images.length > 1) {
                    $(imagesList).jcarousel({ scroll: 1, itemVisibleInCallback: changeImageUrl });
                } else {
                    $(imagesList).addClass('single-thumb');
                }
            } else {
            }

            $(pageContent).append($(pageContentLink));
            $(pageContent).append($(pageContentText));
            $(pageContent).append($(imgContainerDelete));

            $(pageContainer).append($(pageContent));
            $(pageContainer).append($(clearEl));
            $('.attachments .links', linkCnt).append($(pageContainer));

        } else if (result.type == 'file') {
            Attachments.uploadComplete(result.FileLocation, result.ThumbLocation, result.Token, result.FileName, result.FileType, result.CssClass)
        } else if (result.type == 'videoembed') {
        	
        	
        	
        	
        	
        	
        	var attributes = [];
            if (result.url) { attributes['data-url'] = result.url; }
            if (result.description) { attributes['data-description'] = result.description; }
            if (result.title) { attributes['data-title'] = result.title; }
            var pageContainer = $('<div />').addClass('container').attr(attributes);

            $(pageContainer).addClass('container').attr({ 'data-image': result.video_image });

            
            
            var imagesList = $('<ul />');
            var liImage = $('<li />');
            var tmpImage = $('<img />').attr('src', result.video_image);

            var playIcon = $('<span />').addClass('play-icon');
            $(liImage).append($(playIcon));
            $(liImage).append($(tmpImage));
            $(imagesList).append($(liImage));
            $(pageContainer).append($(imagesList));
            $(imagesList).addClass('single-thumb');
            
            
            
            
            
            var clearEl = $('<div />').addClass('clear');
            var pageContent = $('<div />').addClass('content');

            var pageContentLink = $('<a />').attr({ 'href': result.url, 'target': '_blank' }).addClass('link-title').text(result.title);
            var pageContentText = $('<span />');

            $(pageContentText).text(result.description);
            
            
            var imgContainerDelete = $('<a />').addClass('delete').click(function () {
            	deleteAttachment(result, $(this));
            });

      
            $(pageContent).append($(pageContentLink));
            $(pageContent).append($(pageContentText));
            $(pageContent).append($(imgContainerDelete));

            $(pageContainer).append($(pageContent));
            $(pageContainer).append($(clearEl));
            $('.attachments .links', linkCnt).append($(pageContainer));
        	
        	
        	
        	
        	
        	
        	
        }
        $('.attachment-link-container .attachment-link-field', linkCnt).val('');
    }

    function attachLinkFail(result, userContext) {
        STX.showMessage(result._message, 'error');
        var linkCnt = $(userContext).parents('.data-content-placeholder');
        hideLoading(linkCnt);

        btn = $(userContext).parents('.data-content-placeholder').find('.post-btn');
        $(btn).data('status', 'enabled');
        $(btn).removeClass('disabled');


        $('.attachment-link-field-container', linkCnt).hide();
        $('.attachment-link-container .attachment-link-field', linkCnt).val('');
    }

    function attachedLink(url, el) {
        url = url.replace(/(\b(https?|ftp|file):\/\/)/gi, '').replace(/www./gi, '').replace(/\//gi, '');
        if (linkType(el) == 'activity') {
            if (attachedLinks.activity == null || attachedLinks.activity[url] == null) {
                attachedLinks.activity[url] = url;
                return false;
            } else {
                return true;
            }
        } else if (linkType(el) == 'comment') {
            if (attachedLinks.comment == null || attachedLinks.comment[url] == null) {
                attachedLinks.comment[url] = url;
                return false;
            } else {
                return true;
            }

        } else {
            return false;
        }
    }

    function removeAttachedLink(url, el) {
    	if (url) {
	        url = url.replace(/(\b(https?|ftp|file):\/\/)/gi, '').replace(/www./gi, '').replace(/\//gi, '');
	        if (linkType(el) == 'activity') {
	            delete attachedLinks.activity[url];
	        } else if (linkType(el) == 'comment') {
	            delete attachedLinks.comment[url];
	        }
    	}

    }

    function linkType(el) {
        var type = '';
        if ($(el).parents('.status-editor').length > 0) {
            type = 'activity';
        } else if ($(el).parents('.comments-editor-content').length > 0) {
            type = 'comment';
        }
        return type;
    }

    function containerType(el) {
        var type = '';
        if ($(el).parents('.status-editor').length > 0) {
            type = 'status';
        } else if ($(el).parents('.comments-editor-content').length > 0) {
            type = 'comment';
        }
        return type;
    }


    function uploadClick(el) {
        attachmentsContainer = $(el).parents('.data-content-placeholder').find('.attachments');
        url = $(el).parents('.attachment-link-field-container').find('.attachment-link-field').val();
        if (url != '') {
            if (attachedLink(url, el)) {
                STX.showMessage('This URL is already attached!');
            } else {
                showLoading($(el).parents('.data-content-placeholder'));
                token = $(el).parents('.data-content-placeholder').attr('data-token');
                showLoading(attachmentsContainer);

                btn = $(el).parents('.data-content-placeholder').find('.post-btn');
                $(btn).addClass('disabled');
                $(btn).data('status', 'disabled');
                container = containerType(el);
                
                var args = {
    					//type: 'post',
    					module: 'attachments',
    					action: 'seturl',
    					data: { 
    						url: url, 
    						token: token, 
    						container: container	
    					}
    				}
    			Services.invoke(args, attachLinkSuccess, attachLinkFail, el);
                
                
            }
        } else {
            $(el).parents('.attachment-link-field-container').find('.attachment-link-field').focus();
        }
    }

    function attachLink(el, url) {
        attachmentsContainer = $(el).parents('.data-content-placeholder').find('.attachments');
        if (url != '') {
            if (attachedLink(url, el)) {
                //STX.showMessage('This URL is already attached!');
            } else {
                showLoading($(el).parents('.data-content-placeholder'));
                token = $(el).parents('.data-content-placeholder').attr('data-token');
                showLoading(attachmentsContainer);

                btn = $(el).parents('.data-content-placeholder').find('.post-btn');
                $(btn).addClass('disabled');
                $(btn).data('status', 'disabled');
                container = containerType(el);
                
                
                var args = {
    					//type: 'post',
    					module: 'attachments',
    					action: 'seturl',
    					data: { 
    						url: url, 
    						token: token, 
    						container: container	
    					}
    				}
    			Services.invoke(args, attachLinkSuccess, attachLinkFail, el);
            }
        }
    }

    function swapLinkContainer() {
        $('body').click(function (event) {
            caller = event.target;
            if ($(caller).parents('.attachment-link-container').length == 0 && !$(caller).hasClass('attachment-button')) {
                $('.attachment-link-field-container').hide();
            }
        });

        $('.attachment-button.link').live('click', function () {
            parentContainer = $(this).parent('.attachment-link-container');
            var cnt = $(this).parent('.attachment-link-container').find('.attachment-link-field-container');
            if ($(cnt).css('display') == 'none') {
                $(cnt).show();
                $(cnt).find('input').focus();
                $(cnt).find('input').val($(cnt).find('input').val());
            } else {
                $(cnt).hide();
            }
        });
    }

    function collectAttachments(el) {
        var attachmentsCollection = new Array();
        $('[data-type="video"]', el).each(function () {
            var attachmentsEl = new Object();
            attachmentsEl.Type = $(this).attr('data-type');
            attachmentsEl.Url = $(this).attr('data-url');
            attachmentsEl.Description = $(this).attr('data-description');
            attachmentsEl.Text = $(this).attr('data-text');
            attachmentsEl.Title = $(this).attr('data-title');
            attachmentsEl.EmbedUrl = $(this).attr('data-embed');

            attachmentsEl.Images = new Array();
            attachmentsEl.Images[0] = $(this).attr('data-image');

            attachmentsCollection[attachmentsCollection.length] = attachmentsEl;
        });
        $('[data-type="page"]', el).each(function () {
            var attachmentsEl = new Object();
            attachmentsEl.Type = $(this).attr('data-type');
            attachmentsEl.Url = $(this).attr('data-url');
            attachmentsEl.Description = $(this).attr('data-description');
            attachmentsEl.Text = $(this).attr('data-text');
            attachmentsEl.Title = $(this).attr('data-title');
            attachmentsEl.Images = new Array();
            attachmentsEl.Images[0] = $(this).attr('data-image');

            attachmentsCollection[attachmentsCollection.length] = attachmentsEl;
        });
        return attachmentsCollection;
    }

    function hasAttachments(el) {
        at = 0;
        at = collectAttachments(el).length;
        attachedImages = $('.attachments .images .container', el).length;
        attachedFiles = $('.attachments .files .container', el).length;
        allAttachments = at + attachedImages + attachedFiles;
        if (allAttachments > 0) {
            return true;
        } else {
            return false;
        }

    }

    function attachmentsPlaceholder(uploadContainer) {
        var attachmentsCount = 0;

        attachmentsCount += $('.images', uploadContainer).children().length;
        attachmentsCount += $('.links', uploadContainer).children().length;
        attachmentsCount += $('.files', uploadContainer).children().length;
        if (attachmentsCount == 0) $(uploadContainer).hide();


        //console.log(attachmentsCount);

    }

    function generateHandler(token, params) {
        return siteurl + 'services/attachments/setfile' + '/token:' + token + params;
    }

    function _initUploader(el) {
        var container = $(el).parents('.data-content-placeholder');
        
        return new AjaxUpload(el, {
            action: generateHandler(''),
            onSubmit: function (file, extension) { showLoading(container); },
            onComplete: function (file, response) {
            	var result = eval('(' + $(response).text() + ')');
                var response = result.data;
                //console.log($(response).text());
                //console.log(fileUploadOutput);
                //var fileType = Attachments.getFileType(fileUploadOutput.file_type);
                Attachments.uploadComplete(response);
                hideLoading(container);
            }
        });
        
        
        /*
        newToken = STX.generateToken();
        var uploader = new qq.FileUploader({
            // pass the dom node (ex. $(selector)[0] for jQuery users)
            element: $('.attachments-options')[0],
            // path to server-side upload script
            action: generateHandler(newToken, '/container:status')
        });
        */
    }

    function linkFinder() {
        setTimeout(testFn, 5000);
    }

    function commandFail(response, context) {
    	STX.showMessage(response.message, "error");
        //STX.showMessage(result.get_message(), 'error');
    }
    
    // --- declare public methods --- //
    return {
        initUpload: function () {
            if ($('.attachment-button.file', '.status-editor').length > 0) {
                statusUploader = _initUploader($('.attachment-button.file', '.status-editor'));
                this.updateToken($('.data-content-placeholder', '.status-editor'), 'status');
            }
            if ($('.attachment-button.file', '.comments-editor-content').length > 0) {
                commentsUploader = _initUploader($('.attachment-button.file', '.comments-editor-content'));
                this.updateToken($('.attachment-button.file', '.comments-editor-content'), 'comment');
            }
        },

        getFileType: function (type) {
            if (type == 0) return "image";
            if (type == 2) return "videoimage";
            return "file";
        },

        uploadComplete: function (response) {
        	
        	//response.url, response.token, response.file_name, response.file_type
            $('#' + response.token).show();
            if (response.file_type == "image" || response.file_type == "picture") {
                cnt = $('<span />').addClass('image-thumb container');
                $(cnt).append('<img src="' + response.url + '" alt="' + response.file_name + '" title="' + response.file_name + '" />');
                
                var imgContainerDelete = $('<a />').addClass('delete').click(function () {
                	deleteAttachment(response, $(this));
                });

                
                $(cnt).append(imgContainerDelete);  
                $('#' + response.token).find('.images').append($(cnt));

           } else {
                cnt = $('<div />').addClass('container');
                $(cnt).append('<a href="' + response.url + '" title="' + response.file_name + '" class="icon file ' + response.file_type + '" target= "_blank" >' + response.file_name + '</a>');
                var imgContainerDelete = $('<a />').addClass('delete').click(function () {
                	deleteAttachment(response, $(this));
                });
                $(cnt).append(imgContainerDelete);  
                
                $('#' + response.token).find('.files').append($(cnt));
                $('#' + response.token).show();
            }
        },

        collectAttachments: function (el) { return collectAttachments(el); },

        hasAttachments: function (el) { return hasAttachments(el); },

        updateToken: function (el, type) {
            newToken = STX.generateToken();
            $(el).attr('data-token', newToken);
            $('.attachments', el).attr('id', newToken);

            switch (type) {
                case 'status':
                    if (statusUploader) {
                        statusUploader._settings.action = generateHandler(newToken, '/container:status');
                    }
                    break;
                case 'comment':
                    if (commentsUploader) {
                        commentsUploader._settings.action = generateHandler(newToken, '/container:comment');
                    }
                    break;
                default:
                    break;
            }
        },

        attachLink: function (el, url) {
            attachLink(el, url)
        },

        attachEvents: function () {
            swapLinkContainer();

            $('.attachment-button.add-link').live('click', function () { uploadClick($(this)) });
            $('.video-youtube').live('click', function () {
                videoPlaceholder = $(this).parents('.youtube-container').find('.video-placeholder');

                if ($(this).attr('data-type') == 'soundcloud') {
                    $(videoPlaceholder).css('min-height', '90px');
                }
                if ($(videoPlaceholder).html() == '') {
                    $(videoPlaceholder).show();
                    $(videoPlaceholder).html($(this).attr('data-embed'))
                } else {
                    $(videoPlaceholder).hide();
                    $(videoPlaceholder).html('');
                }
            })

            var player = new Array();
            $('.lightbox-video').live('click', function (e) {
                e.preventDefault();
                function stopVideoPlayers(el) {
                    $('.uploaded-video', el).each(function () {
                        player[$(this).attr('id')].stop();
                        $(this).hide();
                    });
                }
                if ($(this).attr('data-tmpid') == '') { $(this).attr('data-tmpid', STX.generateToken()) }
                tmpId = $(this).attr('data-tmpid');
                videoPlaceholder = $(this).parents('.images').find('.video-placeholder');
                if ($('#' + tmpId, videoPlaceholder).length > 0) {
                    if ($('#' + tmpId, videoPlaceholder).css('display') == 'none') {
                        stopVideoPlayers(videoPlaceholder);
                        player[tmpId] = flowplayer(tmpId, rootURL + "swf/flowplayer-3.2.1.swf", { clip: { 'scaling': 'fit'} });
                        $('#' + tmpId, videoPlaceholder).show();
                        $(videoPlaceholder).show();
                    } else {
                        stopVideoPlayers(videoPlaceholder);
                        $('#' + tmpId, videoPlaceholder).hide();
                        $(videoPlaceholder).hide();
                    }
                } else {
                    stopVideoPlayers(videoPlaceholder);
                    videoPlayer = $('<a />').attr({ 'id': tmpId, 'href': $(this).attr('href') }).addClass('uploaded-video');
                    $(videoPlaceholder).append($(videoPlayer));
                    $(videoPlaceholder).show();
                    player[tmpId] = flowplayer(tmpId, rootURL + "swf/flowplayer-3.2.1.swf", { clip: { 'scaling': 'fit'} });
                }
            })

            $('.attachment-link-field').keydown(function (e) {
                if (e.which == 13) {
                    e.preventDefault();
                    uploadClick($(this).parents('.attachment-link-field-container').find('.attachment-button.add-link'));
                }
                if (e.which == 27) {
                    e.preventDefault();
                    var linkCnt = $(this).parents('.data-content-placeholder');
                    hideLoading(linkCnt);
                    $('.attachment-link-field-container', linkCnt).hide();
                    $(this).val('');
                }
            });

            Attachments.updateToken($('.status-editor .data-content-placeholder'), 'status');
            Attachments.updateToken($('.comments-editor.data-content-placeholder'), 'comment');

        },

        reset: function (type) {
            if (type == 'activity') {
                attachedLinks.activity = new Array();
            } else if (type == 'comment') {
                attachedLinks.comment = new Array();
            }
        }
    }
} ();

$(document).ready(function() {
    Attachments.initUpload();
    Attachments.attachEvents();
});