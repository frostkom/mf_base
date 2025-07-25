jQuery(function($)
{
	$(document).on('click', ".toggler", function()
	{
		var dom_obj = $(this);

		if(dom_obj.hasClass('is_open'))
		{
			dom_obj.removeClass('is_open');

			if(dom_obj.siblings(".toggle_container").hasClass('is_open'))
			{
				dom_obj.siblings(".toggle_container").removeClass('is_open');
			}

			if(dom_obj.children(".toggle_container").hasClass('is_open'))
			{
				dom_obj.children(".toggle_container").removeClass('is_open');
			}
		}

		else
		{
			dom_obj.addClass('is_open');
		}
	});
});