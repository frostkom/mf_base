jQuery(function($)
{
	/* condition_field */
	/* ######################## */
	function check_condition(dom_obj, parent_selector)
	{
		var dom_value = dom_obj.val(),
			dom_parent = dom_obj.parents(parent_selector),
			condition_type = dom_obj.attr('condition_type'),
			condition_field = dom_obj.attr('condition_field'),
			condition_value = (dom_obj.attr('condition_value') || ''),
			condition_default = dom_obj.attr('condition_default'),
			field_obj = $((condition_field.substr(0, 1) == '#' ? '' : '#') + condition_field),
			field_parent = field_obj.parents(parent_selector);

		switch(condition_type)
		{
			case 'show_if':
			case 'show_if_empty':
				if(dom_value == condition_value)
				{
					field_parent.removeClass('hide');
				}

				else
				{
					field_parent.addClass('hide');
				}
			break;

			case 'hide_if':
			case 'hide_if_empty':
				if(dom_value == condition_value)
				{
					field_parent.addClass('hide');
				}

				else
				{
					field_parent.removeClass('hide');
				}
			break;
		}

		if(typeof condition_default != 'undefined' && field_obj.val() == '')
		{
			field_obj.val(condition_default);
		}
	}

	$(".rwmb-field input[condition_field], .rwmb-field select[condition_field], .rwmb-field textarea[condition_field]").each(function()
	{
		check_condition($(this), ".rwmb-field");
	});

	$(document).on('blur change', ".rwmb-field input[condition_field], .rwmb-field select[condition_field], .rwmb-field textarea[condition_field]", function()
	{
		check_condition($(this), ".rwmb-field");
	});
	/* ######################## */

	/* condition_selector */
	/* ######################## */
	function check_required(dom_obj, display)
	{
		var dom_child = dom_obj.find("input, select, textarea"),
			attr_required = dom_child.attr('required'),
			attr_required_hidden = dom_child.attr('required_hidden');

		if(display == false && typeof attr_required !== 'undefined')
		{
			dom_child.attr({'required_hidden': true}).removeAttr('required');
		}

		if(display == true && typeof attr_required_hidden !== 'undefined')
		{
			dom_child.attr({'required': true}).removeAttr('required_hidden');
		}
	}

	function check_selector_condition(dom_obj_selector, condition_type, condition_value, dom_obj_action)
	{
		var dom_value = dom_obj_selector.val(),
			arr_condition_value = (condition_value.substr(0, 1) == '[' ? JSON.parse(condition_value) : []),
			value_exists = false;

		if($.isArray(dom_value))
		{
			$.each(dom_value, function(key, value)
			{
				if(value == condition_value || condition_value.match('\"' + value + '\"') || $.inArray(value, arr_condition_value) !== -1)
				{
					value_exists = true;
				}
			});
		}

		else if(dom_value == condition_value || condition_value.match('\"' + dom_value + '\"') || $.inArray(dom_value, arr_condition_value) !== -1)
		{
			value_exists = true;
		}

		switch(condition_type)
		{
			case 'show_this_if':
				if(value_exists == true)
				{
					dom_obj_action.removeClass('hide');

					check_required(dom_obj_action, true);
				}

				else
				{
					dom_obj_action.addClass('hide');

					check_required(dom_obj_action, false);
				}
			break;

			case 'hide_this_if':
				if(value_exists == true)
				{
					dom_obj_action.addClass('hide');

					check_required(dom_obj_action, false);
				}

				else
				{
					dom_obj_action.removeClass('hide');

					check_required(dom_obj_action, true);
				}
			break;
		}

		/* Hide parent postbox if no fields are visible */
		var dom_postbox = dom_obj_action.parent(".rwmb-meta-box").parent(".inside").parent(".postbox");

		if(dom_obj_action.siblings(".rwmb-field:not(.hide)").length > 0)
		{
			dom_postbox.removeClass('hide');
		}

		else
		{
			dom_postbox.addClass('hide');
		}
	}

	function init_conditions(dom_obj, parent_selector)
	{
		var dom_parent = dom_obj.parents(parent_selector),
			condition_type = dom_obj.attr('condition_type'),
			condition_selector = "#" + dom_obj.attr('condition_selector') + ":not(div)",
			condition_value = (dom_obj.attr('condition_value') || '');

		check_selector_condition($(condition_selector), condition_type, condition_value, dom_parent);

		$(document).on('blur change input', condition_selector, function()
		{
			check_selector_condition($(this), condition_type, condition_value, dom_parent);
		});
	}

	$(".rwmb-field *[condition_selector]").each(function()
	{
		init_conditions($(this), ".rwmb-field");
	});
	/* ######################## */

	$(".rwmb-custom_html-wrapper .rwmb-input:empty").each(function()
	{
		$(this).parents(".postbox").remove();
	});
});