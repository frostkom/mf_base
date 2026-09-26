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

	const EPSILON = 6; // covers padding-induced offset + rounding

	function updateArrows() {
		const maxScroll = track.scrollWidth - track.clientWidth;

		if (track.scrollLeft <= EPSILON) {
			prev.classList.add('is-hidden');
		} else {
			prev.classList.remove('is-hidden');
		}

		if (track.scrollLeft >= maxScroll - EPSILON) {
			next.classList.add('is-hidden');
		} else {
			next.classList.remove('is-hidden');
		}
	}

	function scrollByCard(direction) {
		const card = track.querySelector('li');
		if (!card) return;
		const distance = card.getBoundingClientRect().width + 24; // card width + gap (1.5rem = 24px)
		track.scrollBy({ left: direction * distance, behavior: 'smooth' });
	}

	prev.addEventListener('click', () => scrollByCard(-1));
	next.addEventListener('click', () => scrollByCard(1));

	track.addEventListener('scroll', updateArrows);
	window.addEventListener('resize', updateArrows);

	updateArrows(); // set correct initial state
});