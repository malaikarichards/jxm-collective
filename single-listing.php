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

					<?php if ( count( $gallery ) > 1 ) : ?>
						<section class="mb-10" aria-label="<?php esc_attr_e( 'Listing gallery', 'jxm' ); ?>">
							<h2 class="font-serif text-2xl text-jxm-navy mt-0 mb-5"><?php esc_html_e( 'Gallery', 'jxm' ); ?></h2>
							<div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
								<?php foreach ( $gallery as $index => $photo ) : ?>
									<?php
									if ( empty( $photo['url'] ) || ( 0 === $index && $hero_url === $photo['url'] && ! has_post_thumbnail( $listing_id ) ) ) {
										continue;
									}
									$alt = ! empty( $photo['alt'] ) ? $photo['alt'] : get_the_title();
									?>
									<figure class="overflow-hidden bg-secondary m-0">
										<img
											src="<?php echo esc_url( $photo['url'] ); ?>"
											alt="<?php echo esc_attr( $alt ); ?>"
											class="w-full h-48 object-cover"
											loading="lazy"
										>
									</figure>
								<?php endforeach; ?>
							</div>
						</section>
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
				</div>

				<aside class="lg:col-span-4">
					<div class="bg-white shadow-soft p-6 lg:p-8 lg:sticky lg:top-24">
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
						<div id="eb-listing-form"></div>
<script>
(window.EhDynamicRef ||= []).push(() => {
    EhForms.create({
      "formId": "4516006341640192", // Required: The unique ID of your form
      "target": "#eb-listtng-form", // Optional: Use a selector like ".class" or "#id"
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
</main>

	<?php
endwhile;

get_footer();
