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
			var window_width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth,
				x_axis = parseInt(item.datapoint[0].toFixed(0)),
				y_axis = item.datapoint[1].toFixed(2).replace('.00', '');

			if(x_axis > 10000000)
			{
				var date = new Date(x_axis),
					year = date.getFullYear(),
					month = date.getMonth() + 1,
					day = date.getDate();

				x_axis = year + '-' + (month >= 10 ? month : '0' + month);

				if(day > 1)
				{
					x_axis += '-' + (day >= 10 ? day : '0' + day);
				}
			}

			$("#tooltip").css({'top': (item.pageY + 5)}).html("<strong>" + item.series.label + "</strong><br><span>" + x_axis + ": " + parseInt(y_axis).toLocaleString() + "</span>").show();

			if(item.pageX > (window_width / 2))
			{
				$("#tooltip").css({'left': 'auto', 'right': (window_width - item.pageX - 5)});
			}

			else
			{
				$("#tooltip").css({'left': (item.pageX + 5), 'right': 'auto'});
			}
		}
	});

	$(".flot_graph").on('mouseout', function()
	{
		$("#tooltip").hide();
	});
});