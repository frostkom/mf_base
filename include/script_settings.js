jQuery(function($)
{
	function hash_action()
	{
		var hash = location.hash.replace('#', '');

		if(hash == '')
		{
			hash = "settings_base";
		}

		if(hash != '')
		{
			var dom_section = $('.wrap > form > div#' + hash);

			$('#nav-tab-wrapper').children('a#tab_' + hash).addClass('nav-tab-active').siblings('a').removeClass('nav-tab-active');

			dom_section.show().siblings('div').hide();
			dom_section.next('table').show().siblings('table').hide();

			$('.wrap form input[name=_wp_http_referer]').val(location.href);
		}
	}

	$('.wrap form > div > a').each(function()
	{
		var dom_obj = $(this),
			dom_id = $(this).attr('href').replace('#', ''),
			dom_name = $(this).children('h3').text();

		$('#nav-tab-wrapper').append("<a href='#" + dom_id + "' id='tab_" + dom_id + "' class='nav-tab'>" + dom_name + "</a>");

		dom_obj.hide();
	});

	hash_action();

	$(window).on('hashchange', function()
	{
		hash_action();
	});
});