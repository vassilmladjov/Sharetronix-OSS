// Sharetronix Dialogs namespace
var Dialogs = function () {

    function layout(msg, callback, data, type, titleText, style) {
        var overlay = $('<div />').addClass('overlay');
        $('body').append(overlay);
        overlay.fadeTo('slow', 0.8);
        var container = $('<div />').addClass('dialog-container');
        //var title = (titleText == '' | titleText == null) ? $('<h1 />').addClass('dialog-title').text('Message') : $('<h1 />').addClass('dialog-title').text(titleText);
        //container.append(title);
        var content = $('<div />').addClass('dialog-content').html(msg);
        if (style) content.addClass(style);
        container.append(content);
        //container.draggable({ handle: title });
        $('body').append(container);
        buttons(container, overlay, callback, data, type);
    }

    function buttons(container, overlay, callback, data, type) {
        var btncontent = $('<div />').addClass('btn-container');

        var btnOK = $('<a />').addClass('btn');
        var btnOKInner = $('<span />').text('OK');
        btnOK.append(btnOKInner);
        btnOK.click(function (e) {
            e.preventDefault();
            container.remove();
            overlay.fadeTo('slow', 0, function () { overlay.remove(); });
            if ($.isFunction(callback)) { callback(data); }
        });

        var btnYES = $('<a />').addClass('btn')
        var btnYESInner = $('<span />').text('Yes');
        btnYES.append(btnYESInner);
        btnYES.click(function (e) {
            e.preventDefault();
            container.remove();
            overlay.fadeTo('slow', 0, function () { overlay.remove(); });
            if ($.isFunction(callback)) { callback(data); }
        });

        var btnCancel = $('<a />').addClass('btn')
        var btnCancelInner = $('<span />').text('Cancel');
        btnCancel.append(btnCancelInner);
        btnCancel.click(function (e) {
            e.preventDefault();
            container.remove();
            overlay.fadeTo('slow', 0, function () { overlay.remove(); });
        });

        var btnNO = $('<a />').addClass('btn')
        var btnNOInner = $('<span />').text('No');
        btnNO.append(btnNOInner);
        btnNO.click(function (e) {
            e.preventDefault();
            container.remove();
            overlay.fadeTo('slow', 0, function () { overlay.remove(); });
        });

        switch (type) {
            case 'alert':
                btncontent.append(btnOK);
                break;
            case 'confirm':
                btncontent.append(btnOK);
                btncontent.append(btnCancel);
                break;

            case 'confirm-yes-no':
                btncontent.append(btnYES);
                btncontent.append(btnNO);
                break;

            case 'confirm-yes-no-cancel':
                btncontent.append(btnYES);
                btncontent.append(btnNO);
                btncontent.append(btnCancel);
                break;


            default:
                btncontent.append(btnOK);
        }

        container.append(btncontent);
    }



    function showPageFlow(html, callback) {
        var ow = $(document).width();
        $('body').css({ 'overflow-x': 'hidden', 'overflow-y': 'hidden' });

        var offset = 260;
        var totalPadding = 140;
        var w = $(document).width();
        var h = $('#page-container').height();
        var animateInterval = 500;

        //userbar to stay in current position when body scroll is hidden
        $('#userbar').css({ 'margin-right': w - ow });



        overlay = $('<div />').addClass('overlay page-flow-overlay');
        $('body').append(overlay);
        $(overlay).fadeTo(animateInterval, 0.8);
        $('.page-flow-overlay').live('click', hidePageFlow);


        page = $('<div />').addClass('page-flow').css({ 'width': w - offset, 'left': w + 10 });
        pageScroll = $('<div />').addClass('page-flow-scroll').css({ 'height': $('body').height() - totalPadding });
        pageContent = $('<div />').addClass('page-flow-content').html(html);

        $(pageScroll).append(pageContent);
        $(page).append(pageScroll);

        closeBtn = $('<div />').addClass('close close-page-flow');
        $('.page-flow .close').live('click', hidePageFlow)
        $(page).append(closeBtn);


        $('body').append(page);
        $(page).animate({ 'left': offset }, animateInterval, 'easeOutCubic', function () {
            $('body').css('overflow-x', 'auto');
            $('.close-page-flow').css('position', 'fixed');
            $('input:first', page).focus();
            if ($.isFunction(callback)) { callback(); }
        });
    }


    function hidePageFlow() {
        var offset = 260;
        var w = $(document).width();
        var animateInterval = 200;

        $('.overlay').fadeTo(animateInterval, 0, function () { $('.overlay').remove(); });
        $('.close-page-flow').css('position', 'absolute');

        $('body').css({ 'overflow-x': 'hidden', 'overflow-y': 'hidden' });
        $('.page-flow').animate({ 'left': w + 10 }, animateInterval, 'easeInCubic', function () {
            $('.page-flow').remove();
            $('body').css({ 'overflow-x': 'auto', 'overflow-y': 'auto' });

            //userbar to stay in current position when body scroll is visible
            $('#userbar').css({ 'margin-right': 0 });
        });
    }


    function showPopup(html) {
        var overlay = $('<div />').addClass('overlay');
        $('body').append(overlay);
        overlay.fadeTo('slow', 0.8);
        var container = $('<div />').addClass('dialog-container popup');
        //var title = (titleText == '' | titleText == null) ? $('<h1 />').addClass('dialog-title').text('Message') : $('<h1 />').addClass('dialog-title').text(titleText);
        //container.append(title);
        var content = $('<div />').addClass('dialog-content').html(html);
        container.append(content);
        //container.draggable({ handle: title });
        $('body').append(container);
        //buttons(container, overlay, callback, data, type);
    }
    
    function hidePopup(el, value, event) {
    	if (el) {
    		event.preventDefault();
    		$(el).parents('.popup').remove();
    	} else {
    		$('.popup').remove();
    	}
        $('.overlay').fadeTo('slow', 0, function () { $('.overlay').remove(); });
    }


    return {
        alert: function (msg, callback, data, title, style) {
            layout(msg, callback, data, 'alert', title, style);
        },

        confirm: function (msg, callback, data, title, style) {
            layout(msg, callback, data, 'confirm', title, style);
        },

        confirmYesNo: function (msg, callback, data, title, style) {
            layout(msg, callback, data, 'confirm-yes-no', title, style);
        },

        confirmYesNoCancel: function (msg, callback, data, title, style) {
            layout(msg, callback, data, 'confirm-yes-no-cancel', title, style);
        },

        popup: showPopup,
        
        hidePopup: hidePopup,
        
        pageFlow: function (html, callback) {
            showPageFlow(html, callback);
        },

        hidePageFlow: function () {
            hidePageFlow()
        }

    }

} ();