function convert_php_array_to_block_js(array, reverse = true)
{
	var arr_options = [];

	jQuery.each(array, function(index, value)
	{
		if(index == "")
		{
			index = 0;
		}

		arr_options.push({value: index, label: value});
	});

	if(reverse == true)
	{
		arr_options.reverse();
	}

	return arr_options;
}