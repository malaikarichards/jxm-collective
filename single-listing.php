<?php
/**
 * Single listing template.
 *
 * @package JXM
 */

get_header();

while ( have_posts() ) :
	the_post();

	$listing_id = get_the_ID();
	$price      = get_field( 'price', $listing_id );
	$beds       = get_field( 'bedrooms', $listing_id );
	$baths      = get_field( 'bathrooms', $listing_id );
	$sqft       = get_field( 'sqft', $listing_id );
	$address    = get_field( 'address', $listing_id );
	$mls_id     = get_field( 'mls_id', $listing_id );
	$home_type  = get_field( 'home_type', $listing_id );
	$features   = jxm_parse_listing_features( get_field( 'features', $listing_id ) );
	$faqs       = get_field( 'faqs', $listing_id );
	$gallery    = get_field( 'image_gallery', $listing_id );
	$price_display = jxm_format_listing_price( $price );
	$archive_url   = get_post_type_archive_link( 'listing' );

	if ( ! is_array( $gallery ) ) {
		$gallery = array();
	}

	$hero_url = '';
	$hero_alt = get_the_title();

	if ( has_post_thumbnail( $listing_id ) ) {
		$hero_url = get_the_post_thumbnail_url( $listing_id, 'large' );
	} elseif ( ! empty( $gallery[0]['url'] ) ) {
		$hero_url = $gallery[0]['url'];
		$hero_alt = ! empty( $gallery[0]['alt'] ) ? $gallery[0]['alt'] : $hero_alt;
	}

	// Lightbox photos: the featured image first (when set), then the gallery without duplicates.
	$photos = array();

	if ( has_post_thumbnail( $listing_id ) ) {
		$photos[ get_the_post_thumbnail_url( $listing_id, 'full' ) ] = get_the_title();
	}

	foreach ( $gallery as $photo ) {
		if ( ! empty( $photo['url'] ) && ! isset( $photos[ $photo['url'] ] ) ) {
			$photos[ $photo['url'] ] = ! empty( $photo['alt'] ) ? $photo['alt'] : get_the_title();
		}
	}

	$photo_count = count( $photos );
	?>

<main class="bg-jxm-cream/40 flex-grow">
	<article id="post-<?php the_ID(); ?>" <?php post_class( '' ); ?>>
		<?php if ( $hero_url ) : ?>
			<div class="relative w-full overflow-hidden bg-jxm-navy">
				<img
					src="<?php echo esc_url( $hero_url ); ?>"
					alt="<?php echo esc_attr( $hero_alt ); ?>"
					class="w-full max-h-[70vh] object-cover"
				>

				<?php if ( $photo_count > 1 ) : ?>
					<button
						type="button"
						class="absolute bottom-4 right-4 lg:bottom-6 lg:right-6 inline-flex items-center gap-2 bg-white text-jxm-navy text-sm font-semibold px-5 py-3 border-0 shadow-soft cursor-pointer hover:bg-jxm-cream transition-colors duration-300"
						data-gallery-open="0"
						aria-haspopup="dialog"
						aria-controls="listing-gallery"
					>
						<i class="fa-solid fa-images" aria-hidden="true"></i>
						<?php
						printf(
							/* translators: %d: number of photos */
							esc_html__( 'View gallery (%d)', 'jxm' ),
							(int) $photo_count
						);
						?>
					</button>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="container mx-auto px-5 py-10 lg:py-16">
			<p class="mb-6">
				<a href="<?php echo esc_url( $archive_url ); ?>" class="text-sm font-medium text-jxm-navy/70 hover:text-accent no-underline transition-colors duration-300">
					&larr; <?php esc_html_e( 'Back to listings', 'jxm' ); ?>
				</a>
			</p>

			<div class="grid gap-10 lg:grid-cols-12 lg:gap-14">
				<div class="lg:col-span-8">
					<header class="mb-8">
						<?php if ( $address || $home_type ) : ?>
							<p class="text-accent text-xs font-semibold uppercase tracking-widest mb-3">
								<?php echo esc_html( $home_type ? $home_type : $address ); ?>
							</p>
						<?php endif; ?>

						<?php the_title( '<h1 class="font-serif text-4xl lg:text-5xl text-jxm-navy mt-0 mb-4">', '</h1>' ); ?>

						<?php if ( $address && $address !== get_the_title() ) : ?>
							<p class="text-jxm-navy/70 mb-0"><?php echo esc_html( $address ); ?></p>
						<?php endif; ?>
					</header>

					<?php if ( get_the_content() ) : ?>
						<div class="entry-content mb-10">
							<?php the_content(); ?>
						</div>
					<?php endif; ?>

					<?php if ( $features ) : ?>
						<section class="mb-10">
							<h2 class="font-serif text-2xl text-jxm-navy mt-0 mb-5"><?php esc_html_e( 'Features', 'jxm' ); ?></h2>
							<ul class="grid gap-2 sm:grid-cols-2 text-jxm-navy/80 mb-0 pl-5 list-disc">
								<?php foreach ( $features as $feature ) : ?>
									<li><?php echo esc_html( $feature ); ?></li>
								<?php endforeach; ?>
							</ul>
						</section>
					<?php endif; ?>

					<?php if ( $faqs ) : ?>
						<section class="mb-10">
							<h2 class="font-serif text-2xl text-jxm-navy mt-0 mb-5"><?php esc_html_e( 'FAQs', 'jxm' ); ?></h2>
							<div class="text-jxm-navy/80 leading-relaxed whitespace-pre-line">
								<?php echo esc_html( $faqs ); ?>
							</div>
						</section>
					<?php endif; ?>

					<?php if ( $address ) : ?>
						<section class="mb-10">
							<h2 class="font-serif text-2xl text-jxm-navy mt-0 mb-5"><?php esc_html_e( 'Location', 'jxm' ); ?></h2>
							<div class="bg-secondary shadow-soft">
								<iframe
									src="<?php echo esc_url( 'https://maps.google.com/maps?' . http_build_query( array( 'q' => $address, 'z' => 15, 'output' => 'embed' ) ) ); ?>"
									title="<?php echo esc_attr( sprintf( /* translators: %s: listing address */ __( 'Map of %s', 'jxm' ), $address ) ); ?>"
									class="block w-full aspect-video border-0"
									loading="lazy"
									referrerpolicy="no-referrer-when-downgrade"
									allowfullscreen
								></iframe>
							</div>
							<p class="mt-3 mb-0 text-sm">
								<a
									href="<?php echo esc_url( 'https://www.google.com/maps/search/?' . http_build_query( array( 'api' => 1, 'query' => $address ) ) ); ?>"
									class="text-jxm-navy/70 hover:text-accent no-underline transition-colors duration-300"
									target="_blank"
									rel="noopener noreferrer"
								>
									<?php esc_html_e( 'Open in Google Maps', 'jxm' ); ?> <i class="fa-solid fa-arrow-up-right-from-square text-xs" aria-hidden="true"></i>
								</a>
							</p>
						</section>
					<?php endif; ?>

					<?php
					get_template_part(
						'template-parts/listing-attribution',
						null,
						array(
							'listing_id' => $listing_id,
							'class'      => 'text-jxm-navy/80 mb-6',
						)
					);

					if ( jxm_is_mls_listing( $listing_id ) ) {
						get_template_part( 'template-parts/listing-disclaimer', null, array( 'class' => 'mb-10' ) );
					}
					?>
				</div>

				<aside class="lg:col-span-4">
					<div class="bg-white shadow-soft p-6 lg:p-8 lg:sticky lg:top-24">
						
					<h2 class="font-serif text-2xl text-jxm-navy mt-0 mb-5"><?php esc_html_e( 'Property Stats', 'jxm' ); ?></h2>
						<?php if ( $price_display ) : ?>
							<p class="text-sm uppercase tracking-widest text-jxm-navy/50 mb-2"><?php esc_html_e( 'Price', 'jxm' ); ?></p>
							<p class="font-serif text-3xl text-jxm-navy mt-0 mb-6"><?php echo esc_html( $price_display ); ?></p>
						<?php endif; ?>

						<ul class="space-y-3 text-jxm-navy/80 mb-8 list-none p-0">
							<?php if ( $beds ) : ?>
								<li class="flex justify-between border-b border-jxm-navy/10 pb-3">
									<span><?php esc_html_e( 'Bedrooms', 'jxm' ); ?></span>
									<strong class="text-jxm-navy font-semibold"><?php echo esc_html( $beds ); ?></strong>
								</li>
							<?php endif; ?>
							<?php if ( $baths ) : ?>
								<li class="flex justify-between border-b border-jxm-navy/10 pb-3">
									<span><?php esc_html_e( 'Bathrooms', 'jxm' ); ?></span>
									<strong class="text-jxm-navy font-semibold"><?php echo esc_html( $baths ); ?></strong>
								</li>
							<?php endif; ?>
							<?php if ( $sqft ) : ?>
								<li class="flex justify-between border-b border-jxm-navy/10 pb-3">
									<span><?php esc_html_e( 'Square Feet', 'jxm' ); ?></span>
									<strong class="text-jxm-navy font-semibold"><?php echo esc_html( $sqft ); ?></strong>
								</li>
							<?php endif; ?>
							<?php if ( $home_type ) : ?>
								<li class="flex justify-between border-b border-jxm-navy/10 pb-3">
									<span><?php esc_html_e( 'Home Type', 'jxm' ); ?></span>
									<strong class="text-jxm-navy font-semibold"><?php echo esc_html( $home_type ); ?></strong>
								</li>
							<?php endif; ?>
							<?php if ( $mls_id ) : ?>
								<li class="flex justify-between pb-1">
									<span><?php esc_html_e( 'MLS #', 'jxm' ); ?></span>
									<strong class="text-jxm-navy font-semibold"><?php echo esc_html( $mls_id ); ?></strong>
								</li>
							<?php endif; ?>
						</ul>
						<h2 class="font-serif text-2xl text-jxm-navy mt-0 mb-5"><?php esc_html_e( 'Inquire About This Listing', 'jxm' ); ?></h2>
						<div id="eb-listing-form"></div>
<script>
(window.EhDynamicRef ||= []).push(() => {
    EhForms.create({
      "formId": "4516006341640192", // Required: The unique ID of your form
      "target": "#eb-listing-form", // Optional: Use a selector like ".class" or "#id"
      "onFormReady": function(el, setValue) { // Optional: Callback function triggered when the form is fully loaded
        // Example: Automatically fill the email field
        // setValue("email", "hello@example.com");
		setValue("property_of_interest", "<?php echo esc_attr( $mls_id ); ?>");

        // Write your custom code below
const iframe = document.getElementById('eh_form_ifrm_4516006341640192');
								const doc = (el && el.ownerDocument && el.ownerDocument !== document)
									? el.ownerDocument
									: (iframe && iframe.contentDocument);
								if (!doc) {
									return;
								}

								if (iframe) {
									iframe.style.setProperty('width', '100%', 'important');
									iframe.style.setProperty('min-width', '0', 'important');
								}

								if (!doc.getElementById('jxm-nl-btn-style')) {
									const style = doc.createElement('style');
									style.id = 'jxm-nl-btn-style';
									style.textContent = [
									
									'.eb-field-label{text-transform:uppercase!important}',

									].join('');
									(doc.head || doc.documentElement).appendChild(style);
								}
      }
   });
});
</script>
<!-- 
						<a href="/contact/" class="btn-primary block text-center bg-accent text-white font-semibold py-3 px-6 no-underline">
							<?php //esc_html_e( 'Schedule a Consultation', 'jxm' ); ?>
						</a> -->
					</div>
				</aside>
			</div>
		</div>
	</article>

	<?php if ( $photo_count > 1 ) : ?>
		<dialog id="listing-gallery" class="listing-gallery" aria-label="<?php esc_attr_e( 'Listing gallery', 'jxm' ); ?>">
			<div class="flex h-full flex-col">
				<div class="flex items-center justify-between px-4 py-3 sm:px-6">
					<p class="mb-0 text-sm text-white/80" data-gallery-counter aria-live="polite"></p>
					<button type="button" class="listing-gallery__control" data-gallery-close aria-label="<?php esc_attr_e( 'Close gallery', 'jxm' ); ?>">
						<i class="fa-solid fa-xmark" aria-hidden="true"></i>
					</button>
				</div>

				<div class="relative flex min-h-0 flex-1 items-center justify-center px-4 sm:px-20" data-gallery-stage>
					<button type="button" class="listing-gallery__control absolute left-2 top-1/2 -translate-y-1/2 sm:left-6" data-gallery-prev aria-label="<?php esc_attr_e( 'Previous photo', 'jxm' ); ?>">
						<i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
					</button>
					<img src="" alt="" class="max-h-full max-w-full object-contain" data-gallery-image>
					<button type="button" class="listing-gallery__control absolute right-2 top-1/2 -translate-y-1/2 sm:right-6" data-gallery-next aria-label="<?php esc_attr_e( 'Next photo', 'jxm' ); ?>">
						<i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
					</button>
				</div>

				<ul class="m-0 flex list-none gap-2 overflow-x-auto p-4 sm:px-6">
					<?php
					$photo_index = 0;
					foreach ( $photos as $photo_url => $photo_alt ) :
						/* translators: 1: photo number, 2: total photos */
						$thumb_label = sprintf( __( 'Photo %1$d of %2$d', 'jxm' ), $photo_index + 1, $photo_count );
						?>
						<li class="shrink-0">
							<button
								type="button"
								class="listing-gallery__thumb"
								data-gallery-index="<?php echo esc_attr( (string) $photo_index ); ?>"
								data-src="<?php echo esc_url( $photo_url ); ?>"
								data-alt="<?php echo esc_attr( $photo_alt ); ?>"
								aria-label="<?php echo esc_attr( $thumb_label ); ?>"
							>
								<img src="<?php echo esc_url( $photo_url ); ?>" alt="" class="block h-16 w-24 object-cover" loading="lazy" decoding="async">
							</button>
						</li>
						<?php
						++$photo_index;
					endforeach;
					?>
				</ul>
			</div>
		</dialog>
	<?php endif; ?>
</main>

	<?php
endwhile;

get_footer();
