jQuery(function($)
{
	function hash_action()
	{
		var hash = location.hash.replace('#', '');

		if(hash == '')
		{
			hash = script_base_settings.default_tab;
		}

		if(hash != '')
		{
			var dom_section = $('#' + hash);

			$('#tab_' + hash).parent('li').addClass('active').siblings('li').removeClass('active');

			if(script_base_settings.settings_page)
			{
				dom_section.siblings('div, table').hide();

				dom_section.show().next('table').show();

				$('.wrap form input[name=_wp_http_referer]').val(location.href);
			}

			else
			{
				dom_section.show().siblings('.nav-target').hide();
			}
		}
	}

	if(script_base_settings.settings_page)
	{
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
				return a.name.toLowerCase() < b.name.toLowerCase() ? -1 : 1;
			});

			$.each(arr_tabs, function(index, value)
			{
				if(value.name.indexOf(' - ') > -1)
				{
					var arr_name = value.name.split(' - ');

					value.name = ' - ' + arr_name[1];
				}

				var tab_label = "<li><a href='#" + value.id + "' id='tab_" + value.id + "'>" + value.name + "</a></li>";

				if(value.id == "settings_base")
				{
					$('.settings-nav ul').prepend(tab_label);
				}

				else
				{
					$('.settings-nav ul').append(tab_label);
				}

				$('#' + value.id).hide();
			});
		}
	}

	else
	{
		$('.nav-target').hide();
	}

	hash_action();

	$(window).on('hashchange', function()
	{
		hash_action();
	});

	$(document).on('click', '.settings-nav ul li a', function(e)
	{
		var dom_li = $(this).parent('li'),
			dom_href = dom_li.find("a").attr("href");

		location.hash = dom_href;

		$("html, body").scrollTop(0);

		return false;
	});
});