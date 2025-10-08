jQuery(function($)
{
	$(".mf_form select[rel='submit_change']").each(function()
	{
		$(this).removeClass('is_disabled');
	});

	$(document).on('change', ".mf_form select[rel='submit_change'], .mf_form input[rel='submit_change']", function()
	{
		this.form.submit();
	});
});