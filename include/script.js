function render_required()
{
	jQuery(".mf_form :required, .mf_form .required").each(function()
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

	$(document).on('click', "a[rel='confirm'], button[rel='confirm'], .delete > a", function()
	{
		var confirm_text = ($(this).attr('confirm_text') || script_base.confirm_question);

		if(!confirm(confirm_text))
		{
			return false;
		}
	});

	$(".mf_form select[rel='submit_change']").each(function()
	{
		$(this).removeClass('is_disabled');
	});

	$(document).on('change', ".mf_form select[rel='submit_change'], .mf_form input[rel='submit_change']", function()
	{
		this.form.submit();
	});

	$(document).on('keyup', "input[type='url']", function()
	{
		var dom_obj = $(this),
			dom_val = dom_obj.val();

		if(dom_val.length > 7)
		{
			if(dom_val.substring(0, 4) != 'http' && dom_val.substring(0, 2) != '//' && dom_val.substring(0, 4) != 'tel:' && dom_val.substring(0, 7) != 'mailto:' && dom_val.substring(0, 1) != '#')
			{
				dom_obj.val("https://" + dom_val);
			}
		}
	});

	$(document).on('input', ".form_textfield input[type='tel']", function()
	{
		$(this).val($(this).val().replace(/[^0-9]/g, ''));
	});
});