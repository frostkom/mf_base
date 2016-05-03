jQuery(function($)
{
	var dom_form = $("form .search-box input[name='s']"),
		plugin_name = dom_form.parents('form').attr('rel');

	if(plugin_name && plugin_name != '')
	{
		dom_form.autocomplete(
		{
			source: function(request, response)
			{
				$.ajax(
				{
					url: script_base_wp.plugins_url + '/' + plugin_name + '/include/ajax.php?type=table_search',
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

	$('.wp-list-table').removeClass('fixed');

	/* Swipe to edit or delete */
	$('.wp-list-table tr').each(function()
	{
		var self = $(this);

		if(self.find('.row-actions > .edit').length > 0)
		{
			self.append("<div class='swipe_bar from_left'><i class='fa fa-lg fa-wrench'></i></div>").parents('table').addClass('swipe_action');
		}

		if(self.find('.row-actions > .delete, .row-actions > .trash').length > 0)
		{
			self.append("<div class='swipe_bar from_right'><i class='fa fa-lg fa-close'></i></div>").parents('table').addClass('swipe_action');
		}

		if(self.find('.set_tr_color').length > 0)
		{
			self.find('.set_tr_color').each(function()
			{
				var add_class = $(this).attr('rel');

				if(add_class != '')
				{
					self.addClass(add_class);
				}
			});
		}
	});

	var dom_width = $(document).width(),
		threshold = parseInt(dom_width * .3);

	$('.wp-list-table tr').swipe("destroy").swipe(
	{
		swipeStatus: function(event, phase, direction, distance)
		{
			var dom_child = $(this).children('.swipe_bar'),
				progress = 0,
				dom_a;

			//Reset
			dom_child.css({'width': '.3%'});

			if(direction == "left" || direction == "right")
			{
				//if(phase == "start"){}

				if(phase == "move")
				{
					if(direction == "left")
					{
						var from_direction = "from_right",
							bg_addClass = "red";
					}

					else
					{
						var from_direction = "from_left",
							bg_addClass = "green";
					}

					dom_child = $(this).children('div.' + from_direction);
					dom_icon = dom_child.children('i');

					progress = (distance / dom_width * 100);

					if(distance > threshold)
					{
						dom_icon.addClass(bg_addClass);
					}

					else
					{
						dom_icon.removeClass(bg_addClass);
					}
				}

				else if(phase == "end")
				{
					if(direction == "right")
					{
						$(this).find('.row-actions > .edit > a')[0].click();
					}

					else
					{
						$(this).find('.row-actions > .delete > a, .row-actions > .trash > a')[0].click();
					}
				}

				if(progress > 0)
				{
					dom_child.css({'width': progress + '%'}).show();
				}
			}
		},
		threshold: threshold,
		fingers: 1,
		allowPageScroll: 'vertical'
	});
	/* ############### */
});