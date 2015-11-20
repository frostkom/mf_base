var dom_urls,
	dom_list,
	arr_attachments = [];

function render_attachment_list()
{
	var output_list = '',
		output_urls = '';

	jQuery.each(arr_attachments, function(index, value)
	{
		arr_value = value.split("|");

		var file_name = arr_value[0],
			file_url = arr_value[1];

		if(file_name != '')
		{
			output_list += "<tr>"
				+ "<td>" + file_name + "</td>"
				+ "<td><a href='#' rel='" + index + "'>" + script_media_button.delete + "</a></td>"
			+ "</tr>";

			output_urls += (output_urls != '' ? "," : "") + value;
		}
	});

	dom_list.html(output_list);
	dom_urls.val(output_urls);
}

jQuery(function($)
{
	$('.mf_media_button').each(function()
	{
		var dom_obj = $(this);

		dom_list = dom_obj.children('.mf_media_list');
		dom_urls = dom_obj.children('.mf_media_urls');
		arr_attachments = dom_urls.val().split(",");

		render_attachment_list();
	});

	$('.mf_media_button').on('click', function()
	{
		var restore_send_to_editor = window.send_to_editor,
			dom_obj = $(this);

		window.send_to_editor = function(html)
		{
			var dom_raw = dom_obj.children('.mf_media_raw');

			dom_list = dom_obj.children('.mf_media_list');
			dom_urls = dom_obj.children('.mf_media_urls');
			arr_attachments = dom_urls.val().split(",");

			dom_raw.html(html);

			dom_raw.children('a').each(function()
			{
				var dom_a = $(this),
					file_name = '',
					file_url = '';

				if(dom_a.children('img').length > 0)
				{
					file_name = dom_a.children('img').attr('alt');
					file_url = dom_a.children('img').attr('src');
				}

				else
				{
					file_name = dom_a.text();
					file_url = dom_a.attr('href');
				}

				arr_attachments.push(file_name + "|" + file_url);
			});

			dom_raw.empty();

			render_attachment_list();

			tb_remove();

			window.send_to_editor = restore_send_to_editor;
		}
	});

	$('.mf_media_button .mf_media_list').on('click', 'a', function()
	{
		var dom_obj = $(this),
			dom_urls = dom_obj.parents('.mf_media_list').siblings('.mf_media_urls'),
			dom_rel = parseInt(dom_obj.attr('rel'));

		arr_attachments = dom_urls.val().split(",");

		arr_attachments.splice(dom_rel, 1);

		render_attachment_list();

		return false;
	});
});