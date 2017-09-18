function on_load_base()
{
	if(script_base.required_field_text != '')
	{
		jQuery('.mf_form :required, .mf_form .required').each(function()
		{
			jQuery(this).siblings('label').append(" <span class='asterisk'>" + script_base.required_field_text + "</span>");
		});
	}
}

function scroll_to_top()
{
	jQuery('html, body').animate({scrollTop: 0}, 800);
}

jQuery(function($)
{
	on_load_base();

	if(typeof collect_on_load == 'function')
	{
		collect_on_load('on_load_base');
	}

	if(script_base.external_links == 'yes')
	{
		$(document).on('click', 'a[rel=external]', function(e)
		{
			if(e.which != 3)
			{
				window.open($(this).attr('href'));
				return false;
			}
		});
	}

	$(document).on('click', 'a[rel=confirm], button[rel=confirm], .delete > a', function()
	{
		var confirm_text = $(this).attr('confirm_text') || script_base.confirm_question;

		if(!confirm(confirm_text))
		{
			return false;
		}
	});

	$(document).on('change', '.mf_form select[rel=submit_change], .mf_form input[rel=submit_change]', function()
	{
		this.form.submit();
	});

	$(document).on('click', '.toggler', function()
	{
		var toggler_rel = $(this).attr('rel'),
			toggle_obj = $('.toggler[rel=' + toggler_rel + ']'),
			toggle_container = $('.toggle_container[rel=' + toggler_rel + ']');

		if(toggle_container.length > 0)
		{
			toggle_obj.toggleClass('open').find('.fa.fa-caret-right, .fa.fa-caret-down').toggleClass('fa-caret-right fa-caret-down');
			toggle_container.toggleClass('hide');
		}
	});

	$(document).on('keyup', 'input[type=url]', function()
	{
		var dom_obj = $(this),
			dom_val = dom_obj.val();

		if(dom_val.length == 1)
		{
			if(dom_val != 'h')
			{
				dom_obj.val("http://" + dom_val);
			}
		}

		else if(dom_val.length > 4)
		{
			if(dom_val.substring(0, 4) != 'http')
			{
				dom_obj.val("http://" + dom_val);
			}
		}
	});
});