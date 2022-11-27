jQuery(function($)
{
	$(document).on('click', ".form_textfield .clear_color", function()
	{
		$(this).addClass('is_disabled').parent(".description").siblings("input[type='color']").val('#435355');

		return false;
	});
});