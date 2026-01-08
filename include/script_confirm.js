jQuery(function($)
{
	$(document).on('click', "a[rel='confirm'], button[rel='confirm']", function(event)
	{
		var confirm_text = ($(this).attr('confirm_text') || script_base_confirm.confirm_question);

		if(!confirm(confirm_text))
		{
			event.preventDefault();

			return false;
		}
	});
});