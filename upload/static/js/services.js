// Sharetronix Services namespace
var Services = function () {

	function invoke(args, onsuccess, onfail, context) {
		$.ajax({
			type:		args.type ? args.type : "POST",
			url:		siteurl + 'services/' + args.module + '/' + args.action,
			data:		args.data,
			success:	function(response) {
							if (context) context.data('status', 'enabled').removeClass('disabled');
							onsuccess(response.data, context);
						},
			error:		function(response) {
							if (context) context.data('status', 'enabled').removeClass('disabled');
							onfail(response.data, context) 
						}
		});
	}

	function _init() {
		$('[data-role="services"]').live('click', function (e) {
			if ($(this).is('a')) e.preventDefault();
			if ($(this).data('status') != 'disabled') {
				serviceCallHandler($(this), e);
			}
		});
	}

	function serviceCallHandler(el, event) {
		var requiredNamespace = $(el).data('namespace');
		var namespace = requiredNamespace.charAt(0).toUpperCase() + requiredNamespace.slice(1);
		var action = $(el).data('action');
		var value = $(el).data('value');
		var exec = window[namespace][action];
		el.data('status', 'disabled').addClass('disabled');
		exec(el, value, event);
	}

    return {
    	invoke: invoke,
    	init: _init
    }
} ();

$(document).ready(function () {
	Services.init();
});