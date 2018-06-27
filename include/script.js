function scroll_to_top()
{
	jQuery("html, body").animate({scrollTop: 0}, 800);
}

function preload(url)
{
	var img = new Image();
	img.src = url;
}

jQuery.fn.shorten = function(options)
{
	var settings = jQuery.extend(
	{
		'ellipsis': "&hellip;",
		'showChars': 255,
		'moreText': script_base.read_more
	}, options);

	return this.each(function()
	{
		var self = jQuery(this),
			text_start = self.text().slice(0, settings.showChars),
			text_end = self.text().slice(settings.showChars);

		if(text_end.length > 0)
		{
			self.addClass('shorten-shortened').html(text_start + "<span class='shorten-clipped hide'>" + text_end + "</span><span class='shorten-ellipsis form_button'>" + settings.ellipsis + "<br><a href='#' class='shorten-more-link'>" + settings.moreText + settings.ellipsis + "</a></span>");
		}
	});
};

jQuery(function($)
{
	if(script_base.required_field_text != '')
	{
		$(".mf_form :required, .mf_form .required").each(function()
		{
			if($(this).siblings("label").length > 0)
			{
				$(this).siblings("label").append(" <span class='asterisk'>" + script_base.required_field_text + "</span>");
			}

			else if($(this).parent("label").length > 0)
			{
				$(this).parent("label").append(" <span class='asterisk'>" + script_base.required_field_text + "</span>");
			}
		});
	}

	$(".mf_form select[rel=submit_change], .mf_form input[rel=submit_change]").each(function()
	{
		$(this).removeAttr('disabled');
	});

	$(document).on('click', "a[rel=confirm], button[rel=confirm], .delete > a", function()
	{
		var confirm_text = $(this).attr('confirm_text') || script_base.confirm_question;

		if(!confirm(confirm_text))
		{
			return false;
		}
	});

	$(document).on('change', ".mf_form select[rel=submit_change], .mf_form input[rel=submit_change]", function()
	{
		this.form.submit();
	});

	/* This is only to make the arrow inside selects turn down again when it closes */
	/*$(document).on('change', ".mf_form select", function()
	{
		$(this).blur();
	});*/

	$(document).on('click', '.toggler', function(e)
	{
		var toggler_rel = $(this).attr('rel'),
			toggle_obj = $(".toggler[rel=" + toggler_rel + "]"),
			toggle_container = $(".toggle_container[rel=" + toggler_rel + "]"),
			is_toggle_container = $(e.target).parents(".toggle_container").length > 0;

		if(toggle_container.length > 0 && is_toggle_container == false)
		{
			toggle_obj.toggleClass('open');
			toggle_container.toggleClass('hide');
		}
	});

	$(document).on('keyup', "input[type=url]", function()
	{
		var dom_obj = $(this),
			dom_val = dom_obj.val();

		if(dom_val.length == 1)
		{
			if(dom_val != 'h' && dom_val != '/')
			{
				dom_obj.val("http://" + dom_val);
			}
		}

		else if(dom_val.length > 4)
		{
			if(dom_val.substring(0, 4) != 'http' && dom_val.substring(0, 2) != '//')
			{
				dom_obj.val("http://" + dom_val);
			}
		}
	});
});