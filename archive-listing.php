<?php
/**
 * Listing archive template with search and filters.
 *
 * @package JXM
 */

get_header();

$filters       = jxm_get_listing_filters();
$archive_url   = get_post_type_archive_link( 'listing' );
$has_filters   = $filters['keyword'] || $filters['beds'] || $filters['baths'];
$result_count  = (int) $GLOBALS['wp_query']->found_posts;
$bed_options   = range( 1, 5 );
$bath_options  = array( 1, 1.5, 2, 2.5, 3, 3.5, 4 );
?>

<main class="bg-jxm-cream/40 flex-grow">
	<section class="border-b border-jxm-navy/10 bg-white">
		<div class="container mx-auto px-5 py-12 lg:py-16">
			<header class="mb-8 lg:mb-10">
				<p class="text-accent text-xs font-semibold uppercase tracking-widest mb-3"><?php esc_html_e( 'Properties', 'jxm' ); ?></p>
				<h1 class="font-serif text-4xl lg:text-5xl text-jxm-navy mt-0 mb-3"><?php post_type_archive_title(); ?></h1>
				<p class="text-jxm-navy/70 max-w-2xl mb-0">
					<?php esc_html_e( 'Browse available homes. Filter by keyword, bedrooms, or bathrooms to narrow your search.', 'jxm' ); ?>
				</p>
			</header>

			<form
				method="get"
				action="<?php echo esc_url( $archive_url ); ?>"
				class="grid gap-4 md:grid-cols-2 lg:grid-cols-12 lg:items-end"
				role="search"
			>
				<div class="lg:col-span-5">
					<label for="listing-keyword" class="block text-sm font-medium text-jxm-navy mb-2">
						<?php esc_html_e( 'Keyword', 'jxm' ); ?>
					</label>
					<input
						type="search"
						id="listing-keyword"
						name="keyword"
						value="<?php echo esc_attr( $filters['keyword'] ); ?>"
						placeholder="<?php esc_attr_e( 'Address, MLS #, or keyword…', 'jxm' ); ?>"
						class="w-full border border-jxm-navy/15 bg-white px-4 py-3 text-jxm-navy placeholder:text-jxm-navy/40 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
					>
				</div>

				<div class="lg:col-span-2">
					<label for="listing-beds" class="block text-sm font-medium text-jxm-navy mb-2">
						<?php esc_html_e( 'Beds', 'jxm' ); ?>
					</label>
					<select
						id="listing-beds"
						name="beds"
						class="w-full border border-jxm-navy/15 bg-white px-4 py-3 text-jxm-navy focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
					>
						<option value=""><?php esc_html_e( 'Any', 'jxm' ); ?></option>
						<?php foreach ( $bed_options as $beds ) : ?>
							<option value="<?php echo esc_attr( (string) $beds ); ?>" <?php selected( $filters['beds'], $beds ); ?>>
								<?php
								printf(
									/* translators: %d: minimum bedroom count */
									esc_html__( '%d+', 'jxm' ),
									(int) $beds
								);
								?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="lg:col-span-2">
					<label for="listing-baths" class="block text-sm font-medium text-jxm-navy mb-2">
						<?php esc_html_e( 'Baths', 'jxm' ); ?>
					</label>
					<select
						id="listing-baths"
						name="baths"
						class="w-full border border-jxm-navy/15 bg-white px-4 py-3 text-jxm-navy focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent"
					>
						<option value=""><?php esc_html_e( 'Any', 'jxm' ); ?></option>
						<?php foreach ( $bath_options as $baths ) : ?>
							<option value="<?php echo esc_attr( (string) $baths ); ?>" <?php selected( (string) $filters['baths'], (string) $baths ); ?>>
								<?php
								printf(
									/* translators: %s: minimum bathroom count */
									esc_html__( '%s+', 'jxm' ),
									esc_html( (string) $baths )
								);
								?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="flex flex-wrap gap-3 lg:col-span-3">
					<button type="submit" class="btn-primary bg-accent text-white font-semibold py-3 px-6 border-0 cursor-pointer">
						<?php esc_html_e( 'Search', 'jxm' ); ?>
					</button>
					<?php if ( $has_filters ) : ?>
						<a href="<?php echo esc_url( $archive_url ); ?>" class="inline-flex items-center text-sm font-medium text-jxm-navy/70 hover:text-jxm-navy no-underline">
							<?php esc_html_e( 'Clear filters', 'jxm' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</form>
		</div>
	</section>

	<section class="container mx-auto px-5 py-12 lg:py-16">
		<p class="text-sm text-jxm-navy/60 mb-8">
			<?php
			printf(
				/* translators: %d: number of listings found */
				esc_html( _n( '%d listing found', '%d listings found', $result_count, 'jxm' ) ),
				$result_count
			);
			?>
		</p>

		<?php if ( have_posts() ) : ?>
			<div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content', 'listing-card' );
				endwhile;
				?>
			</div>

			<nav class="mt-12 listing-pagination" aria-label="<?php esc_attr_e( 'Listings pagination', 'jxm' ); ?>">
				<?php
				$pagination_args = array_filter(
					array(
						'keyword' => $filters['keyword'],
						'beds'    => $filters['beds'] ? (string) $filters['beds'] : '',
						'baths'   => $filters['baths'] ? (string) $filters['baths'] : '',
					)
				);

				the_posts_pagination(
					array(
						'mid_size'  => 2,
						'prev_text' => __( 'Previous', 'jxm' ),
						'next_text' => __( 'Next', 'jxm' ),
						'add_args'  => $pagination_args,
					)
				);
				?>
			</nav>
		<?php else : ?>
			<div class="max-w-xl py-8">
				<h2 class="font-serif text-2xl text-jxm-navy mb-3"><?php esc_html_e( 'No listings match your search', 'jxm' ); ?></h2>
				<p class="text-jxm-navy/70 mb-6">
					<?php esc_html_e( 'Try adjusting your keyword or bedroom and bathroom filters.', 'jxm' ); ?>
				</p>
				<?php if ( $has_filters ) : ?>
					<a href="<?php echo esc_url( $archive_url ); ?>" class="btn-primary inline-block bg-accent text-white font-semibold py-3 px-6 no-underline">
						<?php esc_html_e( 'View all listings', 'jxm' ); ?>
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</section>
</main>

<?php
get_footer();
