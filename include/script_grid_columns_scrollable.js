document.querySelectorAll('.widget.news.is_scrollable').forEach(function(widget)
{
	const track = widget.querySelector('.grid_columns');
	if (!track) return;

	// Build the arrow buttons with FA icons
	const prev = document.createElement('button');
	prev.className = 'scroll-arrow scroll-arrow--prev';
	prev.setAttribute('aria-label', 'Scroll left');
	prev.innerHTML = '<i class="fa fa-solid fa-chevron-left"></i>';

	const next = document.createElement('button');
	next.className = 'scroll-arrow scroll-arrow--next';
	next.setAttribute('aria-label', 'Scroll right');
	next.innerHTML = '<i class="fa fa-solid fa-chevron-right"></i>';

	widget.appendChild(prev);
	widget.appendChild(next);

	const EPSILON = 6;            // covers padding-induced offset + rounding
	const AUTO_INTERVAL = 4000;   // ms between automatic steps
	let autoTimer = null;

	function updateArrows() {
		const maxScroll = track.scrollWidth - track.clientWidth;
		prev.classList.toggle('is-hidden', track.scrollLeft <= EPSILON);
		next.classList.toggle('is-hidden', track.scrollLeft >= maxScroll - EPSILON);
	}

	function scrollByCard(direction) {
		const card = track.querySelector('li');
		if (!card) return;
		const distance = card.getBoundingClientRect().width + 24; // card width + gap (1.5rem = 24px)
		track.scrollBy({ left: direction * distance, behavior: 'smooth' });
	}

	function stopAutoScroll() {
		if (autoTimer !== null) {
			clearInterval(autoTimer);
			autoTimer = null;
		}
	}

	function startAutoScroll() {
		// Respect users who prefer reduced motion
		if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

		autoTimer = setInterval(function () {
			const maxScroll = track.scrollWidth - track.clientWidth;
			if (track.scrollLeft >= maxScroll - EPSILON) {
				// Reached the end: return to the beginning
				track.scrollTo({ left: 0, behavior: 'smooth' });
				return;
			}
			scrollByCard(1);
		}, AUTO_INTERVAL);
	}

	// Arrow interaction stops auto scroll for good
	prev.addEventListener('click', function () { stopAutoScroll(); scrollByCard(-1); });
	next.addEventListener('click', function () { stopAutoScroll(); scrollByCard(1); });

	track.addEventListener('scroll', updateArrows);
	window.addEventListener('resize', updateArrows);

	// Start from the beginning
	track.scrollLeft = 0;
	updateArrows();
	startAutoScroll();
});