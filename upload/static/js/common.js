// Sharetronix namespace
var STX = function () {

   
    function hashManager(hash) {
        if (hash) {

            //re-stablish favicon when location hash is changed (Firefox bug)
            var faviconURL = $('link[type="image/x-icon"]').remove().attr("href");
            $('<link href="' + faviconURL + '" rel="shortcut icon" type="image/x-icon" />').appendTo('head');

            hashParams = decodeURIComponent(hash).split(':');
            switch (hashParams[0]) {
                case 'add-comment':
                    if (STX.isElementInViewport($('.comments-editor'))) {
                        $('.comments-editor textarea').focus();
                    } else {
                        $('body').scrollTo($('.comments-editor'), 800, { offset: -(STX.getViewportHeight() - $('.comments-editor').height()) });
                        $('.comments-editor .htmarea').focus();
                    }
                    break;
                case 'comments':
                    $('body').scrollTo($('.comments-thread-container'), 800);
                    break;
                case 'toc':
                    $('body').scrollTo($('.wiki-toc'), 800, { offset: -100 });
                    break;
                case 'goto':
                    if (hashParams[1] && hashParams[1] != '') $('body').scrollTo($('.wiki-chapter-title[name="' + hashParams[1] + '"]'), 800, { offset: -100 });
                    break;

                case 'top':
                    $('body').scrollTo('0px', 800);
                    break;
                case 'comment':
                    if (hashParams[1]) {
                        $('body').scrollTo($('#' + hashParams[1]), 800);
                        $('#' + hashParams[1]).find('.comment').effect('highlight', {}, 3000);
                    }
                    break;
                case 'filter':
                    if (hashParams[1]) Activity.load(hashParams[1]);
                    break;
                case 'show-activity':
                    if (hashParams[1]) Activity.loadSingleActivity(hashParams[1]);
                    break;
                case 'alias':
                    if (hashParams[1]) Activity.alias(hashParams[1]);
                    break;
                default:
                    break;
            }
        }
    }


    function showMessage(message, type) {
        if (STX.unloading) return;

        msgContainerCh = $('<div />').addClass('ch-message-container');
        msgContainerChl = $('<div />').addClass('ch-l');
        msgContainerChr = $('<div />').addClass('ch-r');
        $(msgContainerCh).append($(msgContainerChl)).append($(msgContainerChr));

        msgContainerC1 = $('<div />').addClass('c1-message-container');
        msgContainerC2 = $('<div />').addClass('c2-message-container');
        msgContainerC3 = $('<div />').addClass('c3-message-container');
        msgContainerC4 = $('<div />').addClass('c4-message-container');
        msgContainerCc = $('<div />').addClass('cc-message-container');
        msgContainerCcSpan = $('<span />');
        $(msgContainerCc).append($(msgContainerCcSpan));
        $(msgContainerC4).append($(msgContainerCc));
        $(msgContainerC3).append($(msgContainerC4));
        $(msgContainerC2).append($(msgContainerC3));
        $(msgContainerC1).append($(msgContainerC2));

        msgContainerCf = $('<div />').addClass('cf-message-container');
        msgContainerCfl = $('<div />').addClass('cf-l');
        msgContainerCfr = $('<div />').addClass('cf-r');
        $(msgContainerCf).append($(msgContainerCfl)).append($(msgContainerCfr));

        msgContainer = $('<div />').addClass('message-container').addClass(type);
        $(msgContainer).append($(msgContainerCh)).append($(msgContainerC1)).append($(msgContainerCf));
        $(msgContainer).find('.cc-message-container span').text(message);
        msgContainerBtn = $('<div />').addClass('message-container-close icon empty close');
        $(msgContainer).append($(msgContainerBtn));

        $(msgContainerBtn).click(function () {
            $(this).parents('.message-container').stop();
            if ($.browser.msie) {
                $(this).parents('.message-container').slideUp('fast', function () { $(this).remove(); });
            } else {
                $(this).parents('.message-container').animate({ opacity: 'toggle' }, function () { $(this).remove(); });
            }
        });

        $('body').append($(msgContainer));
        if ($.browser.msie) {
            $(msgContainer).slideDown('fast').animate({ dummy: 1 }, 5000).slideUp('fast', function () { $(this).remove(); });
        } else {
            $(msgContainer).animate({ opacity: 'toggle' }).animate({ dummy: 1 }, 5000).animate({ opacity: 'toggle' }, function () { $(this).remove(); });
        }

    }

    function dropdownMenu() {
	
        $('.menu-btn').live('click', function (e) {
            e.preventDefault();
            menu = $(this).parents('.dropdown').find('.menu-options');
            if ($(menu).css('display') == 'none') {
                $.support.opacity ? $('.menu-options').fadeOut(200) : $('.menu-options').hide();
                $('.menu-btn').removeClass('active');
                $('.menu-btn .dropdown-arrow').remove();

                $.support.opacity ? menu.fadeIn(200) : menu.show();
                $(this).addClass('active');
                arrow = $('<span />').addClass('dropdown-arrow');
                $(this).append($(arrow));
            } else {
                $.support.opacity ? menu.fadeOut(200) : menu.hide();
                $(this).removeClass('active');
                $('.dropdown-arrow', this).remove();
            }
        })

        $('body').click(function (event) {
            caller = event.target;
            if ($(caller).parents('.dropdown').length == 0) {
                $.support.opacity ? $('.menu-options').fadeOut(200) : $('.menu-options').hide();
                $('.menu-btn').removeClass('active');
                $('.menu-btn .dropdown-arrow').remove();
            }
        });
    }

    function scrollTop() {
        $('a.top').live('click', function (e) {
            e.preventDefault();
            $('body').scrollTo('0px', 800);
        });
    }

    function searchBoxInit() {
    	$('#header-search .menu-options a').live('click', function(e) {
    		e.preventDefault();
    		$('#search-lookin').val($(this).data('type'));
    		
    		
    		$('#header-search .menu-btn').text($(this).text());
    		
    		menu = $(this).parents('.dropdown').find('.menu-options');
    		$.support.opacity ? menu.fadeOut(200) : menu.hide();
            $(this).removeClass('active');
            $('.dropdown-arrow', this).remove();
    		
    		
    	});
    	
    	//
    	$('.search-field').focus(function () {
            if ($(this).val() == $(this).data('watermark')) {
                $(this).val('');
            } else {
                $(this).select();
            }
            $(this).addClass('active');
        }).blur(function () {
            if ($(this).val().trim() == '') $(this).val($(this).data('watermark'));
            $(this).removeClass('active');
        });

        $('#searchForm').submit(function (e) {
            searchField = $('.search-field');
            if (searchField.val() == '' || searchField.val() == searchField.data('watermark')) {
                e.preventDefault();
                searchField.select();
            }
        });
    }
    
    
    function searchReplace() {
    	str = $('#searchForm .search-field').val();
    	watermark = $('#searchForm .search-field').data('watermark');
    	str = str.replace(watermark, '');
    	$('#searchForm .search-field').val(str);
    }
    
    
    // --- declare public methods --- //
    return {
    	/*
    	exists: function() {
    		if ((namespaces == null || namespaces[requiredNamespace] == null || namespaces[requiredNamespace] == undefined) && window[namespace] == null) {
    			return false;
    		} else {
    			return true;
    		} 
    			
    	}
    	*/
    	
        getViewportHeight: function () {
            var height = window.innerHeight;
            var mode = document.compatMode;
            if ((mode || !$.support.boxModel)) { height = (mode == 'CSS1Compat') ? document.documentElement.clientHeight : document.body.clientHeight; }
            return height;
        },

        isElementInViewport: function (el) {
            scrolltop = (document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop);
            return (scrolltop > ($(el).offset().top + $(el).height()) || scrolltop + STX.getViewportHeight() < $(el).offset().top + $(el).height()) ? false : true;
        },

        showMessage: function (message, type) { showMessage(message, type); },

        generateToken: function () { return Math.random().toString(16).replace('.', ''); },

        colorboxInit: function (el) {
        	/*
        	console.log(el);
            if (el) {
                $('.attachments', el).each(function () {
                    tmpRel = STX.generateToken();
                    $('a.lightbox-image', $(this)).attr('rel', tmpRel);
                });
                $('a.lightbox-image', el).colorbox({ 'maxWidth': '85%' });
            } else {
            */

                $('.attachments:not(.lightbox-enabled)').each(function () {
                    tmpRel = STX.generateToken();
                    $('a.lightbox-image', $(this)).attr('rel', tmpRel);
                    $(this).addClass('lightbox-enabled');
                });
                $('a.lightbox-image').colorbox({ 'maxWidth': '85%' });
            //}
        },

        searchReplace: searchReplace,
        
        init: function () {
            $.address.change(function (event) { hashManager(event.value); });
            dropdownMenu();
            scrollTop();
            searchBoxInit();
            
            $('input[data-status="focus"]').focus().select();
        }

    }
} ();


// --- declare page load events --- //
$(document).ready(function () {
    STX.init();
});