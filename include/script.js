function scroll_to_top()
{
	jQuery("html, body").animate({scrollTop: 0}, 800);
}

function select_option()
{
	jQuery(".mf_form .form_select select[data-value!='']").each(function()
	{
		var dom_obj = jQuery(this),
			dom_value = (dom_obj.attr('data-value') || '');

		if(dom_value != '')
		{
			if(dom_value.match(/,/))
			{
				var arr_values = dom_value.split(',');

				_.each(arr_values, function(value, key)
				{
					dom_obj.children("option[value='" + value + "']").prop('selected', true);
				});
			}

			else
			{
				dom_obj.children("option[value='" + dom_value + "']").prop('selected', true);
			}
		}
	});
}

function render_required()
{
	jQuery(".mf_form :required, .mf_form .required").each(function()
	{
		if(jQuery(this).siblings("label").length > 0)
		{
			jQuery(this).siblings("label").append(" <span class='asterisk'>*</span>");
		}

		else if(jQuery(this).parent("label").length > 0)
		{
			jQuery(this).parent("label").append(" <span class='asterisk'>*</span>");
		}
	});
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
	render_required();

	/* Count maxlength */
	/* ############################ */
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

		dom_counter.text((dom_obj_maxlength - dom_obj_value_length) + " " + script_base.characters_left_text).removeClass('hide');

		if(dom_obj_value_length > dom_obj_maxlength)
		{
			dom_obj.val(dom_obj.val().substring(0, dom_obj_maxlength));
		}
	}

	var dom_obj = $("input[maxlength], textarea[maxlength]");

	/*dom_obj.each(function()
	{
		display_characters_left($(this));
	});*/

	dom_obj.on("keyup", function()
	{
		display_characters_left($(this));
	});

	dom_obj.on("blur", function()
	{
		dom_obj.siblings("label").children(".maxlength_counter").addClass('hide');
	});
	/* ############################ */

	$(".mf_form select[rel='submit_change'], .mf_form input[rel='submit_change']").each(function()
	{
		$(this).removeClass('is_disabled');
	});

	$(document).on('click', "a[rel='confirm'], button[rel='confirm'], .delete > a", function()
	{
		var confirm_text = $(this).attr('confirm_text') || script_base.confirm_question;

		if(!confirm(confirm_text))
		{
			return false;
		}
	});

	$(document).on('change', ".mf_form select[rel='submit_change'], .mf_form input[rel='submit_change']", function()
	{
		this.form.submit();
	});

	$(document).on('keyup', "input[type='url']", function()
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

	$("a[rel='external']").each(function()
	{
		if($(this).children("*").length == 0)
		{
			$(this).append("&nbsp;<i class='fas fa-external-link-alt'></i>");
		}
	});

	$(document).on('click', "a[rel='external']", function(e)
	{
		if(e.which != 3)
		{
			window.open($(this).attr('href'));
			return false;
		}
	});

	$(".overlay_container.modal > div").each(function()
	{
		$(this).append("<i class='fa fa-times'></i>");
	});

	$(document).on('click', ".overlay_container.modal", function(e)
	{
		if(e.target == e.currentTarget)
		{
			$(this).fadeOut();
		}
	});

	$(document).on('click', ".overlay_container.modal .fa-times", function()
	{
		$(this).parents(".overlay_container.modal").fadeOut();
	});
});