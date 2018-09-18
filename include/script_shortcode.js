jQuery(function($)
{
	$(document).on('change', ".mf_shortcode_wrapper select", function()
	{
		var dom_parent = $(this).parent(".form_select"),
			dom_siblings = dom_parent.siblings(".form_select");

		dom_siblings.children("select").val('');

		if($(this).val() != '')
		{
			dom_siblings.addClass('hide').prev("h3").addClass('hide');
		}

		else
		{
			dom_siblings.removeClass('hide').prev("h3").removeClass('hide');
		}
	});

	$(document).on('click', ".mf_shortcode_wrapper .button-primary", function()
	{
		$(".mf_shortcode_wrapper select").each(function()
		{
			var value = $(this).val(),
				type = $(this).attr('rel');

			if(value != '')
			{
				var type_id = '';

				if(parseInt(value) == value)
				{
					type_id = ' id=' + value;
				}

				window.send_to_editor('[' + type + type_id + ']');
			}
		});
	});

	$(document).on('click', ".mf_shortcode_wrapper .button-secondary", function()
	{
		tb_remove();
	});
});