(function ()
{
	var perfBar = function(budget)
	{
		window.onload = function()
		{
			window.performance = window.performance || window.mozPerformance || window.msPerformance || window.webkitPerformance || {};

			var timing = window.performance.timing,
				now = new Date().getTime(),
				output, loadTime;

			if(!timing)
			{
				//fail silently
				return;
			}

			budget = budget ? budget : 1000;

			var start = timing.navigationStart,
				results = document.createElement('div');

			//results.setAttribute('id', 'results');
			loadTime = now - start;
			results.innerHTML = (now - start) + "ms";

			/*if(loadTime > budget)
			{
				results.className += (results.className != '' ? ' ' : '') + 'overBudget';
			}

			else
			{
				results.className += (results.className != '' ? ' ' : '') + 'underBudget';
			}*/

			document.body.appendChild(results);
		}
	};

	window.perfBar = perfBar;

	perfBar();
}());