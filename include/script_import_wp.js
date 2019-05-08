jQuery(function($)
{
	var dom_select = $("#import_result .form_select select");

	function check_select_value(self)
	{
		var dom_value = self.val(),
			dom_parent = self.parent(".form_select");

		dom_parent.removeClass('has_value');

		if(dom_value != '')
		{
			dom_parent.addClass('has_value');

			dom_parent.siblings(".form_select").find("select").each(function()
			{
				if($(this).val() == dom_value)
				{
					$(this).val('');
				}
			});

			dom_parent.siblings(".form_select").find("option[value='" + dom_value + "']").attr('disabled', true);
		}
	}

	dom_select.each(function()
	{
		check_select_value($(this));
	});

	dom_select.on('change', function()
	{
		check_select_value($(this));
	});

	/*$(document).on('click', "table.import_result thead tr[rel!='']", function()
	{
		var dom_rel = $(this).attr('rel');

		$(this).parent("thead").siblings("tbody").children("tr." + dom_rel).removeClass('hide').siblings("tr").addClass('hide');
	});*/

	/*$(document).on('click', "#mf_import button[name='btnImportCheck']", function()
	{
		$.ajax(
		{
			url: script_import_wp.plugin_url + 'api/?type=import/check',
			dataType: 'json',
			data: $(this).parents("form").serialize(),
			success: function(data)
			{
				$("#import_result").html(data.result);
			}
		});
	});*/
});