jQuery(function($)
{
	var dom_select = $("#import_result .form_select select");

	function is_select_value_set(self)
	{
		self.parent(".form_select").removeClass('has_value');

		if(self.val() != '')
		{
			self.parent(".form_select").addClass('has_value');
		}
	}

	dom_select.each(function()
	{
		is_select_value_set($(this));
	});

	dom_select.on('change', function()
	{
		is_select_value_set($(this));
	});
});