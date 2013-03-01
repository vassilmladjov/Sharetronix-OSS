// Sharetronix namespace
var STX = function () {

	var scrolltop = 0,
		pageTitleText = '',

		pageTitle = $('.page-title'),
		
		body = $('html, body'),
		content = $('#content-container'),
		overlay = $('#menu-overlay'),
		
		homeBtn = $('.btn.home'),
		menuBtn = $('.btn.menu'),
		createPostBtn = $('.btn.new-post'),
		sendMessageBtn = $('.btn.send-message'),
		hidecreatePostBtn = $('<a />').addClass('btn back hide-create-post').html('<span></span>'),
		hideSendMessageBtn = $('<a />').addClass('btn back hide-send-message').html('<span></span>'),
		
		menu = $('#menu'),
		postForm = $('#post-form-container'),
		sendMessage = $('#send-message-container'),
		postFormTextarea = $('#post-form-container textarea'),
		sendMessageTextarea = $('#send-message-container textarea');
	
	
	
	function getScrollTop() {
		return (document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop);
	}
	
	function showMessage(message, type) {
        msgContainer = $('<div />').addClass('message-container').addClass(type).text(message);
        $('body').append(msgContainer);
        msgContainer.animate({ opacity: 'toggle' }).animate({ dummy: 1 }, 5000).animate({ opacity: 'toggle' }, function () { $(this).remove(); });
    }
	
	function menuShow() {		
		menuBtn.addClass('active');
		createPostBtn.hide();
		scrolltop = getScrollTop();

		//overlay.show();
		menu.css({'display':'block'});
		content.hide();

		menu.css({'position':'absolute'});
		body.scrollTop(0);

	}
	
	function menuHide() {
		//console.log(menu);
		menu.css({'display':'none', 'position':'fixed'});
		content.show();
		body.scrollTop(scrolltop);
		//overlay.fadeOut(400);
		menuBtn.removeClass('active');
		createPostBtn.show();
	}
	
	function menuInit() {
		menuBtn.live('click', function(e){
			e.preventDefault();
			($(this).hasClass('active')) ? menuHide() : menuShow();
    	}); 
	}
	

	function createPostShow() {
		pageTitleText = $('.page-title').text();
		pageTitle.text($('#post-form-container textarea').data('value'));
		menuBtn.hide();
		menuBtn.before(hidecreatePostBtn);
		createPostBtn.addClass('active');
		scrolltop = getScrollTop();

		postForm.css({'display':'block'});
		content.hide();

		postForm.css({'position':'absolute'});
		body.scrollTop(0);
		postFormTextarea.val('').focus();
	}

	function createPostHide() {
		
		postForm.css({'display':'none', 'position':'fixed'});
		content.show();
		body.scrollTop(scrolltop);
		//overlay.fadeOut(400);
		createPostBtn.removeClass('active');
		pageTitle.text(pageTitleText);
		menuBtn.show();
		hidecreatePostBtn.remove();
		postForm.find('.uploads .images').html('');
		postForm.find('.uploads .links').html('');
		postForm.find('.uploads .files').html('');
		
	}
	
	function createPostInit() {
		createPostBtn.live('click', function(e){
    		e.preventDefault();
    		($(this).hasClass('active')) ? createPostHide() : createPostShow();
    	}); 
	}
    
	
	
	function sendMessageShow() {
		pageTitleText = $('.page-title').text();
		pageTitle.text('Send message');
		homeBtn.hide();
		homeBtn.before(hideSendMessageBtn);
		sendMessageBtn.addClass('active');
		scrolltop = getScrollTop();

		sendMessage.css({'display':'block'});
		content.hide();

		sendMessage.css({'position':'absolute'});
		body.scrollTop(0);
		sendMessageTextarea.val('').focus();
	}

	function sendMessageHide() {
		
		sendMessage.css({'display':'none', 'position':'fixed'});
		content.show();
		body.scrollTop(scrolltop);
		//overlay.fadeOut(400);
		sendMessageBtn.removeClass('active');
		pageTitle.text(pageTitleText);
		homeBtn.show();
		hideSendMessageBtn.remove();
		
	}
	
	function sendMessageInit() {
		sendMessageBtn.live('click', function(e){
    		e.preventDefault();
    		($(this).hasClass('active')) ? sendMessageHide() : sendMessageShow();
    	}); 
	}
	
	
	
	
	
    // --- declare public methods --- //
    return {

        generateToken: function () { return Math.random().toString(16).replace('.', ''); },
        
        init: function () {

        	$('html, body').scrollTop(0);
        	menuInit()
        	createPostInit();
        	sendMessageInit()
        	
        	$('.btn.back.hide-create-post').live('click', function(e) {
        		e.preventDefault();
        		createPostHide();
        	});
        	
        	$('.btn.back.hide-send-message').live('click', function(e) {
        		e.preventDefault();
        		sendMessageHide();
        	});
        	
        	//createPostHide($('.btn.new-post'));
        	//menuHide($('.btn.menu'));

        },
        
        showMessage: showMessage,
        menuHide: menuHide,
        createPostHide: createPostHide,
        sendMessageHide: sendMessageHide,
        
        generateToken: function () { return Math.random().toString(16).replace('.', ''); }

    }
} ();


// --- declare page load events --- //
//$(document).bind('pageinit', function() {
$(document).ready(function () {
    STX.init();
});