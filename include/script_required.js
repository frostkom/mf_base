function render_required()
{
	jQuery(".mf_form [required]").each(function()
	{
		var dom_label;

		if(jQuery(this).is("label"))
		{
			dom_label = jQuery(this);
		}

		else
		{
			if(jQuery(this).siblings("label").length > 0)
			{
				dom_label = jQuery(this).siblings("label");
			}

			else if(jQuery(this).parent("label").length > 0)
			{
				dom_label = jQuery(this).parent("label");
			}
		}

		if(typeof dom_label !== 'null')
		{
			if(dom_label.children(".asterisk").length == 0)
			{
				dom_label.append("<span class='asterisk'>*</span>");
			}
		}
	});

	jQuery(document).on('submit', "form", function(e)
	{
		jQuery(e.currentTarget).find(".form_checkbox_multiple.required, .form_radio_multiple.required").each(function()
		{
			var isValid = false;

			jQuery(this).find("input").each(function()
			{
				if(jQuery(this).is(":checked"))
				{
					isValid = true;
				}
			});

			if(isValid == true)
			{
				jQuery(this).find("label").css("color", "");
			}

			else
			{
				jQuery(this).find("label").css("color", "red");

				jQuery("html, body").animate({scrollTop: (jQuery(this).offset().top - 40)}, 800);

				e.preventDefault();
			}
		});
	});
}

jQuery(function()
{
	render_required();
});