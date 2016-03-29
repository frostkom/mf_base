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

			//dom_section.show().siblings('div').hide();
			dom_section.next('table').show().siblings('table').hide();

			$('.wrap form input[name=_wp_http_referer]').val(location.href);
		}
	}

	var arr_tabs = [];

	$('.wrap form > div > a').each(function()
	{
		var dom_obj = $(this),
			dom_id = dom_obj.attr('href').replace('#', ''),
			dom_name = dom_obj.children('h3').text();

		arr_tabs.push({id: dom_id, name: dom_name});
	});

	if(arr_tabs.length > 0)
	{
		arr_tabs.sort(function(a, b)
		{
			return a.name < b.name ? -1 : 1;
		});

		$.each(arr_tabs, function(index, value)
		{
			$('#nav-tab-wrapper').append("<a href='#" + value.id + "' id='tab_" + value.id + "' class='nav-tab'>" + value.name + "</a>");

			$('#' + value.id).hide();
		});
	}

	hash_action();

	$(window).on('hashchange', function()
	{
		hash_action();
	});
});