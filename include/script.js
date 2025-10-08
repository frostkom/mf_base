jQuery(function($)
{
	$(document).on('click', "a[rel='confirm'], button[rel='confirm'], .delete > a", function()
	{
		var confirm_text = ($(this).attr('confirm_text') || script_base.confirm_question);

		if(!confirm(confirm_text))
		{
			return false;
		}
	});
});