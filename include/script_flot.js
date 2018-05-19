jQuery(function($)
{
	if($("#tooltip").length == 0)
	{
		$("body").append("<div id='tooltip' class='tooltip_box'></div>");
	}

	if(typeof arr_flot_functions !== 'undefined')
	{
		$.each(arr_flot_functions, function(key, value)
		{
			eval(value + "();");
		});
	}

	$(".flot_graph").on('plothover', function(event, pos, item)
	{
		if(item)
		{
			var x_axis = parseInt(item.datapoint[0].toFixed(0)),
				y_axis = item.datapoint[1].toFixed(2).replace('.00', '');

			if(x_axis > 10000000)
			{
				var date = new Date(x_axis);
				x_axis = date.getDate() + '/' + (date.getMonth() + 1) + ' ' + date.getFullYear();
			}

			$("#tooltip").css({top: item.pageY + 5, left: item.pageX + 5}).html("<strong>" + item.series.label + "</strong><br><span>" + x_axis + ": " + parseInt(y_axis).toLocaleString() + "</span>").show();
		}
	});

	$(".flot_graph").on('mouseout', function()
	{
		$("#tooltip").hide();
	});
});