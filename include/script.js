function on_load_base()
{
	jQuery('.mf_form :required, .mf_form .required').each(function()
	{
		jQuery(this).siblings('label').append(" <span class='asterisk'>*</span>");
	});
}

jQuery(function($)
{
	on_load_base();

	if(typeof collect_on_load == 'function')
	{
		collect_on_load('on_load_base');
	}

	$.fn.nextElementInDom = function(selector, options)
	{
		var defaults = { stopAt : 'body' };
		options = $.extend(defaults, options);

		var parent = $(this).parent(),
			found = parent.next(selector); //find -> next

		switch(true)
		{
			case (found.length > 0):
			return found;

			case (parent.length === 0 || parent.is(options.stopAt)):
			return $([]);

			default:
			return parent.nextElementInDom(selector);
		}
    };

	$(document).on('click', 'a[rel=external]', function(e)
	{
		if(e.which != 3)
		{
			window.open($(this).attr('href'));
			return false;
		}
	});

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
		$(this).children('.fa').toggleClass('fa-caret-right fa-caret-down');

		var toggle_container = $(this).next('.toggle_container');

		if(toggle_container.length == 0)
		{
			toggle_container = $(this).nextElementInDom('.toggle_container')
		}

		if(toggle_container.length == 0)
		{
			console.log("Could not find .toggle_container");
		}

		else
		{
			$(this).toggleClass('open');
			toggle_container.toggleClass('hide');
		}
	});
});