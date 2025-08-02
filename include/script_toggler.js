jQuery(function($)
{
	var togglers_open = [];

	function set_open(dom_obj, is_open)
	{
		var index = togglers_open.indexOf(dom_obj.attr('id'));

		if(is_open)
		{
			dom_obj.addClass('is_open');

			if(dom_obj.attr('id') !== undefined)
			{
				$("." + dom_obj.attr('id')).removeClass('hide');
			}

			if(index === -1)
			{
				togglers_open.push(dom_obj.attr('id'));
			}
		}

		else
		{
			dom_obj.removeClass('is_open');

			if(dom_obj.attr('id') !== undefined)
			{
				$("." + dom_obj.attr('id')).addClass('hide');
			}

			togglers_open.splice(index, 1);
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

	$(document).on('click', ".toggler", function()
	{
		var dom_obj = $(this);

		if(dom_obj.hasClass('is_open'))
		{
			set_open(dom_obj, false);

			if(dom_obj.siblings(".toggle_container").hasClass('is_open'))
			{
				set_open(dom_obj.siblings(".toggle_container"), false);
			}

			if(dom_obj.children(".toggle_container").hasClass('is_open'))
			{
				set_open(dom_obj.children(".toggle_container"), false);
			}
		}

		else
		{
			set_open(dom_obj, true);
		}

		save_open();
	});
});