jQuery(function($)
{
	function display_characters_left(dom_obj, init)
	{
		var dom_obj_maxlength = dom_obj.attr('maxlength'),
			dom_obj_value_length = dom_obj.val().length,
			dom_counter = dom_obj.siblings("label").children(".maxlength_counter");

		if(dom_counter.length == 0)
		{
			dom_obj.siblings("label").append("<span class='maxlength_counter'></span>");

			dom_counter = dom_obj.siblings("label").children(".maxlength_counter");
		}

		dom_counter.text((dom_obj_maxlength - dom_obj_value_length) + " " + script_base_maxlength.characters_left_text).removeClass('hide');

		if(dom_obj_value_length > dom_obj_maxlength)
		{
			dom_obj.val(dom_obj.val().substring(0, dom_obj_maxlength));
		}
	}

	var dom_obj = $("input[maxlength], textarea[maxlength]");

	dom_obj.on("input", function()
	{
		display_characters_left($(this));
	});

	dom_obj.on("blur", function()
	{
		dom_obj.siblings("label").children(".maxlength_counter").addClass('hide');
	});
});