jQuery(function($)
{
	$("form .search-box input[name='s']").autocomplete(
	{
		source: function(request, response)
		{
			$.ajax(
			{
				url: script_base_table.plugins_url + '/' + script_base_table.plugin_name + '/include/ajax.php?type=table_search',
				dataType: "json",
				data: {
					s: request.term
				},
				success: function(data)
				{
					response(data);
				}
			});
		},
		minLength: 3
	});
});