jQuery(function($)
{
	$(document).on('click', 'a[rel=external]', function(e)
	{
		if(e.which != 3)
		{
			window.open($(this).attr('href'));
			return false;
		}
	});

	$(document).on('click', 'a[rel=confirm], button[rel=confirm], .delete > a', function()
	{
		var confirm_text = $(this).attr('confirm_text') || script_base.confirm_question;

		if(!confirm(confirm_text))
		{
			return false;
		}
	});

	$('.mf_form').on('change', 'select[rel=submit_change]', function()
	{
		this.form.submit();
	});

	$('.mf_form :required, .mf_form .required').each(function()
	{
		$(this).siblings('label').append(" <span class='asterisk'>*</span>");
	});
});