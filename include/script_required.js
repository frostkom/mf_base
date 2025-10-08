function render_required()
{
	jQuery(".mf_form .required").each(function()
	{
		if(jQuery(this).siblings("label").length > 0)
		{
			jQuery(this).siblings("label").append("<span class='asterisk'>*</span>");
		}

		else if(jQuery(this).parent("label").length > 0)
		{
			jQuery(this).parent("label").append("<span class='asterisk'>*</span>");
		}
	});
}

jQuery(function($)
{
	render_required();
});