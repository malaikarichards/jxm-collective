jQuery(document).ready(function ($) {
	$('.menu-toggle').on('click', function () {
		const expanded = $(this).attr('aria-expanded') === 'true';
		$(this).attr('aria-expanded', expanded ? 'false' : 'true');
		$('#primary-menu').toggleClass('hidden');
		$(this).find('.icon-open').toggleClass('hidden');
		$(this).find('.icon-close').toggleClass('hidden');
	});

	const gallery = document.getElementById('listing-gallery');

	if (gallery && typeof gallery.showModal === 'function') {
		const image = gallery.querySelector('[data-gallery-image]');
		const counter = gallery.querySelector('[data-gallery-counter]');
		const stage = gallery.querySelector('[data-gallery-stage]');
		const thumbs = Array.from(gallery.querySelectorAll('[data-gallery-index]'));
		let current = 0;
		let touchStartX = null;

		const show = function (index) {
			current = (index + thumbs.length) % thumbs.length;

			const thumb = thumbs[current];
			image.src = thumb.dataset.src;
			image.alt = thumb.dataset.alt;
			counter.textContent = current + 1 + ' / ' + thumbs.length;

			thumbs.forEach(function (item, i) {
				if (i === current) {
					item.setAttribute('aria-current', 'true');
				} else {
					item.removeAttribute('aria-current');
				}
			});
			thumb.scrollIntoView({ block: 'nearest', inline: 'center' });

			new Image().src = thumbs[(current + 1) % thumbs.length].dataset.src;
		};

		$('[data-gallery-open]').on('click', function () {
			gallery.showModal();
			document.documentElement.classList.add('overflow-hidden');
			show(parseInt(this.dataset.galleryOpen, 10) || 0);
		});

		gallery.addEventListener('close', function () {
			document.documentElement.classList.remove('overflow-hidden');
		});

		gallery.querySelector('[data-gallery-close]').addEventListener('click', function () {
			gallery.close();
		});
		gallery.querySelector('[data-gallery-prev]').addEventListener('click', function () {
			show(current - 1);
		});
		gallery.querySelector('[data-gallery-next]').addEventListener('click', function () {
			show(current + 1);
		});
		thumbs.forEach(function (thumb, i) {
			thumb.addEventListener('click', function () {
				show(i);
			});
		});

		gallery.addEventListener('keydown', function (event) {
			if (event.key === 'ArrowLeft') {
				show(current - 1);
			} else if (event.key === 'ArrowRight') {
				show(current + 1);
			} else if (event.key === 'Escape') {
				event.preventDefault();
				gallery.close();
			}
		});

		// Clicking the empty area around the photo closes the gallery.
		stage.addEventListener('click', function (event) {
			if (event.target === stage) {
				gallery.close();
			}
		});

		stage.addEventListener('touchstart', function (event) {
			touchStartX = event.changedTouches[0].clientX;
		}, { passive: true });

		stage.addEventListener('touchend', function (event) {
			if (touchStartX === null) {
				return;
			}

			const deltaX = event.changedTouches[0].clientX - touchStartX;
			touchStartX = null;

			if (Math.abs(deltaX) > 50) {
				show(deltaX < 0 ? current + 1 : current - 1);
			}
		});
	}

	$('a[href^="#"]').on('click', function (event) {
		const target = $(this.getAttribute('href'));
		if (target.length) {
			event.preventDefault();
			$('html, body').stop().animate(
				{
					scrollTop: target.offset().top - 100,
				},
				800
			);
		}
	});
});
