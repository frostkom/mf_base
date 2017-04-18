jQuery(function($)
{
	function check_condition(dom_obj)
	{
		var dom_value = dom_obj.val(),
			dom_parent = dom_obj.parents('.rwmb-field'),
			condition_type = dom_obj.attr('condition_type'),
			condition_field = dom_obj.attr('condition_field'),
			condition_value = dom_obj.attr('condition_value') || '',
			condition_default = dom_obj.attr('condition_default'),
			field_obj = $((condition_field.substr(0, 1) == '#' ? '' : '#') + condition_field),
			field_parent = field_obj.parents('.rwmb-field');

		switch(condition_type)
		{
			case 'show_if':
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

		if(typeof condition_default != 'undefined')
		{
			field_obj.val(condition_default);
		}
	}

	$('.rwmb-field input[condition_type], .rwmb-field select[condition_type]').each(function()
	{
		check_condition($(this));
	});

	$(document).on('blur change', '.rwmb-field input[condition_type], .rwmb-field select[condition_type]', function()
	{
		check_condition($(this));
	});
});