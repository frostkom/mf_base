jQuery(function($)
{
	function check_condition(dom_obj, parent_selector)
	{
		var dom_value = dom_obj.val(),
			dom_parent = dom_obj.parents(parent_selector),
			condition_type = dom_obj.attr('condition_type'),
			condition_field = dom_obj.attr('condition_field'),
			condition_value = dom_obj.attr('condition_value') || '',
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

	$(".rwmb-field input[condition_field], .rwmb-field select[condition_field]").each(function()
	{
		check_condition($(this), ".rwmb-field");
	});

	$(document).on('blur change', ".rwmb-field input[condition_field], .rwmb-field select[condition_field]", function()
	{
		check_condition($(this), ".rwmb-field");
	});

	/*$(".form_textfield input[condition_field]").each(function()
	{
		check_condition($(this), ".form_textfield");
	});

	$(document).on('blur', ".form_textfield input[condition_field]", function()
	{
		check_condition($(this), ".form_textfield");
	});

	$(".form_select select[condition_field]").each(function()
	{
		check_condition($(this), ".form_select");
	});

	$(document).on('change', ".form_select select[condition_field]", function()
	{
		check_condition($(this), ".form_select");
	});*/

	function check_selector_condition(dom_obj_selector, condition_type, condition_value, dom_obj_action)
	{
		var dom_value = dom_obj_selector.val();

		switch(condition_type)
		{
			case 'show_this_if':
				if(dom_value == condition_value)
				{
					dom_obj_action.removeClass('hide');
				}

				else
				{
					dom_obj_action.addClass('hide');
				}
			break;

			case 'hide_this_if':
				if(dom_value == condition_value)
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
			condition_selector = dom_obj.attr('condition_selector'),
			condition_value = dom_obj.attr('condition_value') || '';

		$("#" + condition_selector).each(function()
		{
			check_selector_condition($(this), condition_type, condition_value, dom_parent);
		});

		$(document).on('blur change', "#" + condition_selector, function()
		{
			check_selector_condition($(this), condition_type, condition_value, dom_parent);
		});
	}

	$(".rwmb-field *[condition_selector]").each(function()
	{
		init_conditions($(this), ".rwmb-field");
	});

	/*$(".form_texfield *[condition_selector]").each(function()
	{
		init_conditions($(this), ".form_texfield");
	});

	$(".form_select *[condition_selector]").each(function()
	{
		init_conditions($(this), ".form_select");
	});*/

	$(".rwmb-custom_html-wrapper .rwmb-input:empty").each(function()
	{
		$(this).parents(".postbox").remove();
	});
});