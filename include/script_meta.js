jQuery(function($)
{
	function check_condition(dom_obj)
	{
		var dom_value = dom_obj.val(),
			condition_type = dom_obj.attr('condition_type'),
			condition_field = dom_obj.attr('condition_field'),
			condition_value = dom_obj.attr('condition_value') || '',
			condition_default = dom_obj.attr('condition_default'),
			dom_field = $('#' + condition_field),
			dom_parent = dom_field.parents('.rwmb-field'),
			statement_is = dom_value == condition_value;

		switch(condition_type)
		{
			case 'show_if':
				if(statement_is == true)
				{
					dom_parent.removeClass('hide');
				}

				else
				{
					dom_parent.addClass('hide');
				}

				if(typeof condition_default != 'undefined')
				{
					dom_field.val(condition_default);
				}
			break;

			case 'hide_if':
				if(statement_is == true)
				{
					dom_parent.addClass('hide');
				}

				else
				{
					dom_parent.removeClass('hide');
				}

				if(typeof condition_default != 'undefined')
				{
					dom_field.val(condition_default);
				}
			break;
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