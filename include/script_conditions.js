jQuery(function($)
{
	function check_selector_condition(dom_obj_selector, condition_type, condition_value, dom_obj_action)
	{
		var dom_value = dom_obj_selector.val(),
			arr_condition_value = JSON.parse(condition_value),
			value_exists = false;

		if($.isArray(dom_value))
		{
			$.each(dom_value, function(key, value)
			{
				if($.inArray(value, arr_condition_value) !== -1)
				{
					value_exists = true;
				}
			});
		}

		else if(dom_value == condition_value || $.inArray(dom_value, arr_condition_value) !== -1)
		{
			value_exists = true;
		}

		switch(condition_type)
		{
			case 'show_this_if':
				if(value_exists == true)
				{
					dom_obj_action.removeClass('hide');
				}

				else
				{
					dom_obj_action.addClass('hide');
				}
			break;

			case 'hide_this_if':
				if(value_exists == true)
				{
					dom_obj_action.addClass('hide');
				}

				else
				{
					dom_obj_action.removeClass('hide');
				}
			break;
		}
	}

	function init_conditions(dom_obj, parent_selector)
	{
		var dom_parent = dom_obj.parents(parent_selector),
			condition_type = dom_obj.attr('condition_type'),
			condition_selector = /*dom_obj.parents(".mf_form") + " " + */"#" + dom_obj.attr('condition_selector'),
			condition_value = dom_obj.attr('condition_value') || '';

		check_selector_condition($(condition_selector), condition_type, condition_value, dom_parent);

		$(document).on('blur change', condition_selector, function()
		{
			check_selector_condition($(this), condition_type, condition_value, dom_parent);
		});
	}

	/*$(".mf_form .form_textfield *[condition_selector]").each(function()
	{
		init_conditions($(this), ".form_textfield");
	});*/

	$(".mf_form .form_select *[condition_selector]").each(function()
	{
		init_conditions($(this), ".form_select");
	});
});