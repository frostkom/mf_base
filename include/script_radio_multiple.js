jQuery(function($)
{
	var dom_radio_multiple = $(".mf_form .form_radio_multiple + .form_radio_multiple");

	if(dom_radio_multiple.length > 0)
	{
		var i = 0,
			selected = 0;

		dom_radio_multiple.each(function()
		{
			if($(this).find(".form_radio:selected").length > 0)
			{
				selected++;
			}

			else
			{
				$(this).addClass('inactive');
			}

			i++;
		});

		if(i > 0 && selected > 0)
		{
			$(".mf_form .form_radio_multiple:first-of-type").addClass('inactive');
		}

		$(document).on('click', ".mf_form .form_radio_multiple input", function(e)
		{
			var dom_obj_next = $(e.currentTarget).parents(".form_radio_multiple").next(".form_radio_multiple");

			if(dom_obj_next.length > 0)
			{
				dom_obj_next.removeClass('inactive').siblings(".form_radio_multiple").addClass('inactive');

				$("html, body").animate({scrollTop: dom_obj_next.offset().top}, 800);
			}
		});
	}
});