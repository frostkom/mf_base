jQuery(function($)
{
	var togglers_open = [];

	function set_open(dom_obj, is_open)
	{
		var dom_obj_id = dom_obj.attr('id');

		if(dom_obj_id !== undefined)
		{
			var index = togglers_open.indexOf(dom_obj_id);
		}

		if(is_open)
		{
			dom_obj.addClass('is_open');

			if(dom_obj_id !== undefined && dom_obj.hasClass('has_memory'))
			{
				$("." + dom_obj_id).removeClass('hide');

				if(index === -1)
				{
					togglers_open.push(dom_obj_id);
				}
			}
		}

		else
		{
			dom_obj.removeClass('is_open');

			if(dom_obj_id !== undefined)
			{
				$("." + dom_obj_id).addClass('hide');

				if(index !== -1)
				{
					togglers_open.splice(index, 1);
				}
			}
		}
	}

	function save_open()
	{
		if(typeof $.Storage != 'undefined')
		{
			var togglers_open_unique = [...new Set(togglers_open)];

			$.Storage.set('togglers_open', JSON.stringify(togglers_open_unique));
		}
	}

	if(typeof $.Storage != 'undefined')
	{
		if(typeof $.Storage.get('togglers_open') != 'undefined')
		{
			togglers_open = JSON.parse($.Storage.get('togglers_open'));

			var count_temp = togglers_open.length;

			for(var i = 0; i < count_temp; i++)
			{
				var dom_obj = $("#" + togglers_open[i]);

				set_open(dom_obj, true);
			}
		}
	}

	function open_toggle(dom_obj)
	{
		set_open(dom_obj, true);

		return true;
	}

	function close_toggle(dom_obj)
	{
		set_open(dom_obj, false);

		dom_obj.siblings(".toggle_container").each(function()
		{
			if($(this).hasClass('is_open'))
			{
				set_open($(this), false);
			}
		});

		dom_obj.children(".toggle_container").each(function()
		{
			if($(this).hasClass('is_open'))
			{
				set_open($(this), false);
			}
		});

		return false;
	}

	function do_toggle(dom_obj)
	{
		var is_open = false;

		if(dom_obj.hasClass('is_open'))
		{
			is_open = close_toggle(dom_obj);
		}

		else
		{
			is_open = open_toggle(dom_obj);
		}

		save_open();

		return is_open;
	}

	$(document).on('click', ".toggle_all", function()
	{
		var toggler_is_open = 0;

		$(".toggler.is_toggleable").each(function()
		{
			if($(this).hasClass('is_open'))
			{
				toggler_is_open++;
			}
		});

		$(".toggler.is_toggleable").each(function()
		{
			var dom_obj = $(this);

			if(toggler_is_open > 0)
			{
				if(dom_obj.hasClass('is_open'))
				{
					close_toggle(dom_obj);
				}
			}

			else
			{
				open_toggle(dom_obj);
			}
		});
	});

	$(document).on('click', ".toggler.is_toggleable", function()
	{
		do_toggle($(this));
	});
});