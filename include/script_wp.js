jQuery(function($)
{
	/* Search */
	var dom_form = $("form .search-box input[name='s']"),
		plugin_name = dom_form.parents("form").attr('rel');

	if(plugin_name && plugin_name != '')
	{
		dom_form.autocomplete(
		{
			source: function(request, response)
			{
				$.ajax(
				{
					url: script_base_wp.plugins_url + '/' + plugin_name + '/include/api/?type=table_search',
					dataType: "json",
					data: {
						s: request.term
					},
					success: function(data)
					{
						response(data);
					}
				});
			},
			minLength: 3
		});

		dom_form.bind("autocompleteopen", function(e, ui)
		{
			var dom_result = $('.ui-autocomplete'),
				search_width = $(e.target).outerWidth(),
				result_width = dom_result.outerWidth(),
				result_left = parseInt(dom_result.css('left')),
				result_left_new = result_left - (result_width - search_width);

			dom_result.css({'left': result_left_new + 'px'});
		});
	}

	/* Tables */
	$(".tablenav .actions").each(function()
	{
		if($(this).children().length == 0)
		{
			$(this).addClass('hide');
		}
	});

	$(".wp-list-table").removeClass('fixed');

	$(".wp-list-table tr").each(function()
	{
		var self = $(this);

		if(self.find(".set_tr_color").length > 0)
		{
			self.find(".set_tr_color").each(function()
			{
				var add_class = $(this).attr('rel');

				if(add_class != '')
				{
					self.addClass(add_class);
				}
			});
		}
	});

	var dom_obj_toggle = $(".view_data i");

	if(dom_obj_toggle.length > 0)
	{
		function toggle_table(dom_obj)
		{
			dom_obj.toggleClass('fa-eye-slash fa-eye').parents("tr").next("tr").toggleClass('hide');
		}

		$(".wrap > h2:first-child").append("<a href='#' class='add-new-h2 toggle_all_data'>" + script_base_wp.toggle_all_data_text + "</a>");

		$(".toggle_all_data").on('click', function()
		{
			toggle_table(dom_obj_toggle);
		});

		dom_obj_toggle.on('click', function()
		{
			toggle_table($(this).parents("tr").siblings("tr").find(".view_data .fa-eye-slash"));
			toggle_table($(this));
		});
	}

	/* Look for user notifications */
	function send_notification(value)
	{
		if(Notification.permission !== "granted")
		{
			Notification.requestPermission();
		}

		else
		{
			var notification = new Notification(value.title, {
				tag: value.tag,
				icon: value.icon ? value.icon : "",
				body: value.text ? value.text : "",
			});

			if(value.link && value.link != '')
			{
				notification.onclick = function()
				{
					window.open(value.link);
				};
			}
		}
	}

	function check_notifications()
	{
		$.ajax(
		{
			type: "post",
			dataType: "json",
			url: script_base_wp.ajax_url,
			data: {action: "check_notifications"},
			success: function(data)
			{
				if(data.success)
				{
					$.each(data.notifications, function(index, value)
					{
						send_notification(value);
					});
				}

				/*else
				{
					console.log("Error: " , data);
				}*/
			}
		});
	}

	if('Notification' in window)
	{
		setInterval(function()
		{
			check_notifications();
		}, 120000);
	}
});