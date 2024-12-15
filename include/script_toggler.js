jQuery(function($)
{
	$(document).on('click', ".toggler", function(e)
	{
		var dom_obj = $(this),
			dom_rel = dom_obj.attr('rel'),
			toggle_container = $(".toggle_container[rel='" + dom_rel + "']"),
			is_toggle_container = $(e.target).parents(".toggle_container").length > 0;

		if(toggle_container.length > 0 && is_toggle_container == false)
		{
			if(dom_obj.hasClass('close_siblings') && dom_obj.hasClass('open') == false)
			{
				$(".toggler.close_siblings").removeClass('open').siblings(".toggle_container").addClass('hide');
			}

			dom_obj.toggleClass('open');
			toggle_container.toggleClass('hide');
		}

		/*return false;*/ /* This prevents a tags inside to be clicked */
	});
});