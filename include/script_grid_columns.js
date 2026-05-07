function handleError(e)
{
	const img = e.currentTarget;

	if(img.dataset.fallbackApplied)
	{
		return;
	}

	img.dataset.fallbackApplied = '1';
	img.onerror = null;
	img.src = script_base_grid_columns.src;
	img.alt = (img.alt || script_base_grid_columns.alt);
	img.classList.add(script_base_grid_columns.class);
}

document.querySelectorAll(".grid_image img").forEach(img => {
	if(img.complete && img.naturalWidth === 0)
	{
		handleError({ currentTarget: img });
	}

	else
	{
		img.addEventListener('error', handleError);
	}
});