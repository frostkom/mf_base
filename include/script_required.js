function render_required()
{
	jQuery(".mf_form [required]").each(function()
	{
		if(jQuery(this).is("label"))
		{
			jQuery(this).append("<span class='asterisk'>*</span>");
		}

		else
		{
			if(jQuery(this).siblings("label").length > 0)
			{
				jQuery(this).siblings("label").append("<span class='asterisk'>*</span>");
			}

			else if(jQuery(this).parent("label").length > 0)
			{
				jQuery(this).parent("label").append("<span class='asterisk'>*</span>");
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