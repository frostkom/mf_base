jQuery(function($)
{
	$(document).on('input', ".form_textfield input[inputmode='numeric']", function()
	{
		let value = $(this).val();

		/* Remove all non-digit characters */
		value = value.replace(/\D/g, '');

		$(this).val(value);
	});
});