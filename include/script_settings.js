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
			var dom_section = $("#" + hash);

			$("#tab_" + hash).parent("li").addClass('active').siblings("li").removeClass('active');

			if(script_base_settings.settings_page)
			{
				dom_section.siblings("div, table").addClass('hide');

				dom_section.removeClass('hide').show().next("table").removeClass('hide');

				$(".settings-tabs input[name='_wp_http_referer']").val(location.href);
			}

			else
			{
				dom_section.removeClass('hide').siblings(".nav-target").addClass('hide');
			}
		}
	}

	if(script_base_settings.settings_page)
	{
		/* Prevent refresh */
		var ctrlKeyDown = false;

		$(document).on("keydown", function(e)
		{
			if((e.which || e.keyCode) == 116 || ((e.which || e.keyCode) == 82 && ctrlKeyDown))
			{
				/* Pressing F5 or Ctrl+R */
				e.preventDefault();
			}

			else if((e.which || e.keyCode) == 17)
			{
				/* Pressing only Ctrl */
				ctrlKeyDown = true;
			}
		});

		$(document).on("keyup", function(e)
		{
			/* Key up Ctrl */
			if((e.which || e.keyCode) == 17)
			{
				ctrlKeyDown = false;
			}
		});

		/* Add tabs */
		var dom_nav = $(".settings-nav ul");

		if(dom_nav.children("li").length == 0)
		{
			var arr_tabs = [];

			$(".settings-tabs > div > a").each(function()
			{
				var dom_obj = $(this),
					dom_id = dom_obj.attr('href').replace('#', ''),
					dom_name = dom_obj.children("h3").text();

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
						dom_nav.prepend(tab_label);
					}

					else
					{
						dom_nav.append(tab_label);
					}

					$("#" + value.id).hide();
				});
			}

			$(".settings-wrap .display_warning").each(function()
			{
				var self = $(this),
					tab_id = self.parents(".form-table").prev("div").attr('id');

				if($("#tab_" + tab_id + " .fa-exclamation-triangle").length == 0)
				{
					$("#tab_" + tab_id).append(" <i class='fa fa-exclamation-triangle yellow'></i>");
				}
			});
		}

		$(".api_base_info").each(function()
		{
			var self = $(this);

			$.ajax(
			{
				url: script_base_settings.ajax_url,
				type: 'post',
				dataType: 'json',
				data:
				{
					action: 'api_base_info',
					user_id: script_base_settings.user_id
				},
				success: function(data)
				{
					self.html(data.html);
				}
			});
		});

		function api_base_cron(self)
		{
			$.ajax(
			{
				url: script_base_settings.ajax_url,
				type: 'post',
				dataType: 'json',
				data:
				{
					action: 'api_base_cron'
				},
				success: function(data)
				{
					self.html(data.html);
				}
			});
		}

		$(".api_base_cron").each(function()
		{
			var self = $(this);

			api_base_cron(self);

			setInterval(function()
			{
				api_base_cron(self);
			}, 60000);
		});

		$(document).on('click', "button[name='btnBaseOptimize']:not(.is_disabled)", function(e)
		{
			var dom_obj = $(e.currentTarget),
				dom_container = $(".api_base_optimize");

			dom_obj.addClass('is_disabled');
			dom_container.html("<i class='fa fa-spinner fa-spin fa-2x'></i>");

			$.ajax(
			{
				url: script_base_settings.ajax_url,
				type: 'post',
				dataType: 'json',
				data: {
					action: 'api_base_optimize'
				},
				success: function(data)
				{
					dom_obj.removeClass('is_disabled');
					dom_container.html(data.html);
				}
			});

			return false;
		});
	}

	hash_action();

	$(window).on('hashchange', function()
	{
		hash_action();
	});

	$(".settings-wrap.loading").removeClass('loading');

	$(document).on('click', ".settings-nav ul li a", function(e)
	{
		var dom_li = $(this).parent("li"),
			dom_href = dom_li.find("a").attr("href");

		location.hash = dom_href;

		$("html, body").scrollTop(0);

		return false;
	});
});