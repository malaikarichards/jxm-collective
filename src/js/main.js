jQuery(document).ready(function ($) {
	$('.menu-toggle').on('click', function () {
		const expanded = $(this).attr('aria-expanded') === 'true';
		$(this).attr('aria-expanded', expanded ? 'false' : 'true');
		$('#primary-menu').toggleClass('hidden');
		$(this).find('.icon-open').toggleClass('hidden');
		$(this).find('.icon-close').toggleClass('hidden');
	});

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
