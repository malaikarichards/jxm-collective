<?php

// ==========================================
// 1. API CONSTANTS & CONFIGURATION
// ==========================================
// The API key is saved on Listings > Sync API. Defining SPARK_API_TOKEN in wp-config.php overrides it.
define( 'SPARK_API_ENDPOINT', 'https://replication.sparkapi.com/Version/3/Reso/OData/Property' );

// OData $filter selecting which listings to import. Field names follow the RESO Data Dictionary.
define(
    'SPARK_API_FILTER',
    "StandardStatus eq 'Active' and PropertyType eq 'Residential' and ListPrice ge 400000 and ListPrice le 2000000"
    . " and (CountyOrParish eq 'Palm Beach' or CountyOrParish eq 'Broward')"
);

// Property fields requested from the API. Photos come from $expand=Media.
define( 'SPARK_API_SELECT', 'ListingKey,ListingId,ListPrice,BedroomsTotal,BathroomsTotalDecimal,BathroomsTotalInteger,LivingArea,UnparsedAddress,StandardStatus,PropertySubType,PublicRemarks,ModificationTimestamp,ListOfficeName,ListOfficePhone,ListOfficeEmail' );

// Listings per API page ($top). Replication keys allow up to 1000.
define( 'SPARK_API_PAGE_SIZE', 100 );

// Seconds of work per batch before handing off to the next cron run.
define( 'SPARK_SYNC_BATCH_SECONDS', 25 );

/**
 * Spark API key from wp-config.php, or the one saved on the Sync API page.
 *
 * @return string
 */
function spark_api_token() {
    if ( defined( 'SPARK_API_TOKEN' ) && SPARK_API_TOKEN ) {
        return (string) SPARK_API_TOKEN;
    }

    return (string) get_option( 'spark_api_token', '' );
}

// ==========================================
// 2. THE CORE API SYNC FUNCTION
// ==========================================

/**
 * Start a sync. The feed is processed in batches that follow @odata.nextLink.
 *
 * A full sync ($since = 0) pulls every matching listing and drafts the ones no longer in the feed.
 * An incremental sync only pulls listings modified after $since and never drafts anything.
 *
 * @param bool $force Restart even if a sync is already in progress.
 * @param int  $since Unix timestamp; only pull listings modified after it. 0 for a full sync.
 * @return bool|WP_Error
 */
function sync_spark_listings_to_cpt( $force = false, $since = 0 ) {
    if ( '' === spark_api_token() ) {
        return new WP_Error( 'spark_api_no_token', 'No Spark API key is saved. Add one on Listings > Sync API.' );
    }

    $state   = get_option( 'spark_sync_state' );
    $running = ! empty( $state['next_url'] ) && ( time() - (int) $state['started'] ) < 6 * HOUR_IN_SECONDS;

    // Don't let cron clobber a sync that is still working through the feed. A full sync may replace an incremental one.
    if ( ! $force && $running && ( $since || empty( $state['since'] ) ) ) {
        return true;
    }

    update_option(
        'spark_sync_state',
        array(
            'next_url'  => spark_api_first_page_url( $since ),
            'since'     => (int) $since,
            'processed' => 0,
            'total'     => 0,
            'failures'  => 0,
            'error'     => '',
            'started'   => time(),
        ),
        false
    );
    update_option( 'spark_sync_seen', array(), false );
    delete_transient( 'spark_sync_lock' );

    return spark_sync_run_batch();
}

/**
 * Hourly cron: pull only listings modified since the last completed sync.
 *
 * @return bool|WP_Error
 */
function spark_sync_recent_listings() {
    $last = get_option( 'spark_sync_last' );

    if ( empty( $last['started'] ) ) {
        return sync_spark_listings_to_cpt();
    }

    // Overlap the previous run so edits made while it was paging aren't missed.
    return sync_spark_listings_to_cpt( false, (int) $last['started'] - 10 * MINUTE_IN_SECONDS );
}

/**
 * Build the first Property request: filtered, field-selected, with Media expanded.
 *
 * @param int $since Unix timestamp; only request listings modified after it. 0 for all listings.
 * @return string
 */
function spark_api_first_page_url( $since = 0 ) {
    $filter = SPARK_API_FILTER;

    if ( $since ) {
        $filter .= ' and ModificationTimestamp gt ' . gmdate( 'Y-m-d\TH:i:s\Z', $since );
    }

    return add_query_arg(
        array(
            '$filter' => rawurlencode( $filter ),
            '$select' => rawurlencode( SPARK_API_SELECT ),
            '$expand' => 'Media',
            '$top'    => SPARK_API_PAGE_SIZE,
            '$count'  => 'true',
        ),
        SPARK_API_ENDPOINT
    );
}

/**
 * GET a RESO Web API URL and decode the JSON body.
 *
 * @param string $url Full request URL.
 * @return array|WP_Error
 */
function spark_api_get( $url ) {
    $response = wp_remote_get(
        $url,
        array(
            'headers' => array(
                'Authorization' => 'Bearer ' . spark_api_token(),
                'Accept'        => 'application/json',
            ),
            'timeout' => 60,
        )
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $code = (int) wp_remote_retrieve_response_code( $response );
    $data = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( 200 !== $code || ! is_array( $data ) ) {
        $message = is_array( $data ) && ! empty( $data['error']['message'] ) ? $data['error']['message'] : 'HTTP ' . $code;
        return new WP_Error( 'spark_api_error', $message );
    }

    return $data;
}

/**
 * Process API pages until the time budget runs out, then schedule the next batch.
 *
 * @return bool|WP_Error
 */
function spark_sync_run_batch() {
    $state = get_option( 'spark_sync_state' );

    if ( empty( $state['next_url'] ) ) {
        return false;
    }

    if ( get_transient( 'spark_sync_lock' ) ) {
        return true;
    }

    set_transient( 'spark_sync_lock', 1, 5 * MINUTE_IN_SECONDS );

    if ( function_exists( 'set_time_limit' ) ) {
        set_time_limit( 300 );
    }

    $seen     = get_option( 'spark_sync_seen', array() );
    $deadline = time() + SPARK_SYNC_BATCH_SECONDS;

    while ( $state['next_url'] && time() < $deadline ) {
        $data = spark_api_get( $state['next_url'] );

        if ( is_wp_error( $data ) ) {
            error_log( 'Spark API Error: ' . $data->get_error_message() );

            $state['error'] = $data->get_error_message();
            $state['failures']++;

            // Give up after repeated failures; the next hourly run starts fresh.
            if ( $state['failures'] >= 5 ) {
                $state['next_url'] = '';
            }

            update_option( 'spark_sync_state', $state, false );
            delete_transient( 'spark_sync_lock' );

            if ( $state['next_url'] ) {
                wp_schedule_single_event( time() + 5 * MINUTE_IN_SECONDS, 'spark_sync_batch_event' );
            }

            return $data;
        }

        if ( isset( $data['@odata.count'] ) ) {
            $state['total'] = (int) $data['@odata.count'];
        }

        foreach ( (array) ( $data['value'] ?? array() ) as $listing ) {
            $listing_id = spark_import_listing( $listing );

            if ( $listing_id ) {
                $seen[ $listing_id ] = true;
            }

            $state['processed']++;
        }

        $state['next_url'] = $data['@odata.nextLink'] ?? '';
        $state['failures'] = 0;
        $state['error']    = '';
    }

    if ( empty( $state['next_url'] ) ) {
        // An incremental feed only holds changed listings, so absence from it means nothing.
        $drafted = empty( $state['since'] ) ? spark_draft_missing_listings( array_keys( $seen ) ) : 0;

        update_option(
            'spark_sync_last',
            array(
                'started'   => (int) $state['started'],
                'since'     => (int) ( $state['since'] ?? 0 ),
                'finished'  => time(),
                'processed' => (int) $state['processed'],
                'drafted'   => $drafted,
            ),
            false
        );
        delete_option( 'spark_sync_state' );
        delete_option( 'spark_sync_seen' );
        delete_transient( 'spark_sync_lock' );

        return true;
    }

    update_option( 'spark_sync_state', $state, false );
    update_option( 'spark_sync_seen', $seen, false );
    delete_transient( 'spark_sync_lock' );

    if ( ! wp_next_scheduled( 'spark_sync_batch_event' ) ) {
        wp_schedule_single_event( time() + 5, 'spark_sync_batch_event' );
    }

    return true;
}

/**
 * Create or update a listing post from a RESO Property record.
 *
 * @param array $listing Property record from the RESO Web API.
 * @return string ListingKey on success, empty string when skipped.
 */
function spark_import_listing( $listing ) {
    $listing_id = sanitize_text_field( (string) ( $listing['ListingKey'] ?? '' ) );

    if ( '' === $listing_id ) {
        return '';
    }

    $mls_id   = sanitize_text_field( (string) ( $listing['ListingId'] ?? '' ) );
    $address  = sanitize_text_field( (string) ( $listing['UnparsedAddress'] ?? '' ) );
    $title    = spark_listing_title( $address, $listing_id );
    $modified = (string) ( $listing['ModificationTimestamp'] ?? '' );
    $content  = ! empty( $listing['PublicRemarks'] ) ? wp_kses_post( wpautop( (string) $listing['PublicRemarks'] ) ) : '';

    $post_id = spark_find_listing_post_id( $listing_id, $mls_id, $title );

    if ( $post_id && $modified && get_post_meta( $post_id, 'spark_modified', true ) === $modified ) {
        // Saved even when unchanged so listings imported before office fields were synced get backfilled.
        spark_save_listing_office( $post_id, $listing );

        if ( 'publish' !== get_post_status( $post_id ) ) {
            wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
        }

        return $listing_id;
    }

    if ( $post_id ) {
        $postarr = array(
            'ID'          => $post_id,
            'post_status' => 'publish',
        );

        // Keep the existing address title; only fix auto-generated duplicates.
        if ( get_the_title( $post_id ) === 'Listing ' . $listing_id && $title !== get_the_title( $post_id ) ) {
            $postarr['post_title'] = $title;
        }

        // Only replace remarks that came from Spark, never hand-written content.
        $current = get_post_field( 'post_content', $post_id );
        if ( '' === trim( $current ) || get_post_meta( $post_id, 'spark_content', true ) === $current ) {
            $postarr['post_content'] = $content;
        }

        wp_update_post( $postarr );
    } else {
        $post_id = wp_insert_post( array(
            'post_title'   => $title,
            'post_content' => $content,
            'post_type'    => 'listing',
            'post_status'  => 'publish',
        ) );
    }

    if ( is_wp_error( $post_id ) || ! $post_id ) {
        return '';
    }

    update_post_meta( $post_id, 'spark_api_id', $listing_id );
    update_post_meta( $post_id, 'spark_modified', $modified );
    update_post_meta( $post_id, 'spark_content', $content );
    spark_save_listing_office( $post_id, $listing );

    $baths = $listing['BathroomsTotalDecimal'] ?? $listing['BathroomsTotalInteger'] ?? '';
    $sqft  = $listing['LivingArea'] ?? '';

    if ( function_exists( 'update_field' ) ) {
        update_field( 'price', spark_number( $listing['ListPrice'] ?? '' ), $post_id );
        update_field( 'bedrooms', spark_number( $listing['BedroomsTotal'] ?? '' ), $post_id );
        update_field( 'bathrooms', spark_number( $baths ), $post_id );
        update_field( 'sqft', is_numeric( $sqft ) ? number_format( (float) $sqft ) : '', $post_id );
        update_field( 'mls_id', $mls_id, $post_id );
        update_field( 'address', $address, $post_id );
        update_field( 'home_type', sanitize_text_field( (string) ( $listing['PropertySubType'] ?? '' ) ), $post_id );
        update_field( 'status', sanitize_text_field( (string) ( $listing['StandardStatus'] ?? '' ) ), $post_id );

        $photos = spark_get_listing_photos( $listing, $listing_id );
        spark_save_image_gallery( $post_id, $photos );
    }

    return $listing_id;
}

/**
 * Save the listing firm and its contact details, required for IDX attribution (BeachesMLS rule 20.3.3).
 *
 * @param int   $post_id Listing post ID.
 * @param array $listing Property record from the RESO Web API.
 */
function spark_save_listing_office( $post_id, $listing ) {
    update_post_meta( $post_id, 'list_office_name', sanitize_text_field( (string) ( $listing['ListOfficeName'] ?? '' ) ) );
    update_post_meta( $post_id, 'list_office_phone', sanitize_text_field( (string) ( $listing['ListOfficePhone'] ?? '' ) ) );
    update_post_meta( $post_id, 'list_office_email', sanitize_email( (string) ( $listing['ListOfficeEmail'] ?? '' ) ) );
}

/**
 * Street line of a RESO UnparsedAddress ("2346 N Lylewood Court, Melbourne, FL 32904").
 *
 * @param string $address    UnparsedAddress.
 * @param string $listing_id ListingKey, used when there is no address.
 * @return string
 */
function spark_listing_title( $address, $listing_id ) {
    $street = trim( strtok( $address, ',' ) );

    return '' !== $street ? $street : 'Listing ' . $listing_id;
}

/**
 * Normalize a numeric API value (e.g. 455000.0) for ACF number fields.
 *
 * @param mixed $value Raw value.
 * @return float|int|string
 */
function spark_number( $value ) {
    return is_numeric( $value ) ? 0 + $value : '';
}

/**
 * Draft synced listings that are no longer in the feed (sold, pending, withdrawn, or filtered out).
 *
 * @param string[] $seen_ids ListingKeys returned by the completed sync.
 * @return int Number of posts drafted.
 */
function spark_draft_missing_listings( $seen_ids ) {
    global $wpdb;

    // An empty result is more likely an API problem than every listing selling at once.
    if ( empty( $seen_ids ) ) {
        return 0;
    }

    $seen = array_flip( $seen_ids );
    $rows = $wpdb->get_results(
        "SELECT p.ID, pm.meta_value FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
        WHERE p.post_type = 'listing' AND p.post_status = 'publish'
        AND pm.meta_key IN ( 'spark_api_id', '_spark_id', '_spark_listing_id' )"
    );

    $keep   = array();
    $synced = array();

    foreach ( $rows as $row ) {
        $synced[ (int) $row->ID ] = true;

        if ( isset( $seen[ $row->meta_value ] ) ) {
            $keep[ (int) $row->ID ] = true;
        }
    }

    $drafted = 0;

    foreach ( array_keys( array_diff_key( $synced, $keep ) ) as $post_id ) {
        wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
        $drafted++;
    }

    return $drafted;
}

/**
 * Find an existing listing post, preferring the original address-titled post over sync duplicates.
 *
 * @param string $listing_id RESO ListingKey.
 * @param string $mls_id     MLS ListingId (e.g. R10996003).
 * @param string $title      Address title from Spark.
 * @return int
 */
function spark_find_listing_post_id( $listing_id, $mls_id, $title ) {
    global $wpdb;

    if ( $listing_id ) {
        $exact = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT pm.post_id FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_type = 'listing'
                WHERE pm.meta_key = 'spark_api_id' AND pm.meta_value = %s LIMIT 1",
                $listing_id
            )
        );

        if ( $exact ) {
            return $exact;
        }
    }

    $candidates = array();
    $queries    = array();

    if ( $listing_id ) {
        $queries[] = array( 'meta_key' => '_spark_id', 'meta_value' => $listing_id );
        $queries[] = array( 'meta_key' => '_spark_listing_id', 'meta_value' => $listing_id );
    }

    if ( $mls_id ) {
        $queries[] = array( 'meta_key' => 'mls_id', 'meta_value' => $mls_id );
    }

    foreach ( $queries as $query ) {
        $posts = get_posts(
            array_merge(
                $query,
                array(
                    'post_type'      => 'listing',
                    'post_status'    => 'any',
                    'posts_per_page' => 10,
                    'fields'         => 'ids',
                )
            )
        );

        foreach ( $posts as $id ) {
            $candidates[ (int) $id ] = true;
        }
    }

    if ( $title ) {
        $title_posts = get_posts(
            array(
                'post_type'      => 'listing',
                'post_status'    => 'any',
                'posts_per_page' => 10,
                'title'          => $title,
                'fields'         => 'ids',
            )
        );

        foreach ( $title_posts as $id ) {
            $candidates[ (int) $id ] = true;
        }
    }

    if ( empty( $candidates ) ) {
        return 0;
    }

    $duplicate_title = 'Listing ' . $listing_id;

    foreach ( array_keys( $candidates ) as $id ) {
        if ( get_the_title( $id ) !== $duplicate_title ) {
            return (int) $id;
        }
    }

    return (int) array_key_first( $candidates );
}

/**
 * Persist photo URL/alt pairs on the Image Gallery ACF textarea.
 *
 * @param int   $post_id Listing post ID.
 * @param array $photos  Array of [ 'url' => string, 'alt' => string ].
 */
function spark_save_image_gallery( $post_id, $photos ) {
    $encoded = wp_json_encode( array_values( $photos ) );

    if ( function_exists( 'update_field' ) ) {
        update_field( 'field_6aa9cb44290a5', $encoded, $post_id );
    }

    update_post_meta( $post_id, 'image_gallery', $encoded );
    update_post_meta( $post_id, '_image_gallery', 'field_6aa9cb44290a5' );
}

/**
 * Collect public photos from the expanded Media records, in MLS display order.
 *
 * @param array  $listing    Property record from the RESO Web API.
 * @param string $listing_id RESO ListingKey.
 * @return array[] Array of [ 'url' => string, 'alt' => string ].
 */
function spark_get_listing_photos( $listing, $listing_id ) {
    $media = $listing['Media'] ?? null;

    // An empty Media array means no media; only re-request when the expansion is missing.
    if ( ! is_array( $media ) && $listing_id ) {
        $media = spark_fetch_listing_media( $listing_id );
    }

    $media = array_filter(
        (array) $media,
        static function ( $item ) {
            if ( ! is_array( $item ) || 'Photo' !== ( $item['MediaCategory'] ?? '' ) || empty( $item['MediaURL'] ) ) {
                return false;
            }

            $permission = (array) ( $item['Permission'] ?? array() );

            return empty( $permission ) || in_array( 'Public', $permission, true );
        }
    );

    usort(
        $media,
        static function ( $a, $b ) {
            $a_preferred = ! empty( $a['PreferredPhotoYN'] ) ? 0 : 1;
            $b_preferred = ! empty( $b['PreferredPhotoYN'] ) ? 0 : 1;

            return array( $a_preferred, (int) ( $a['Order'] ?? 0 ) ) <=> array( $b_preferred, (int) ( $b['Order'] ?? 0 ) );
        }
    );

    $items = array();
    $seen  = array();

    foreach ( $media as $photo ) {
        $url = esc_url_raw( $photo['MediaURL'] );

        if ( ! $url || isset( $seen[ $url ] ) ) {
            continue;
        }

        $seen[ $url ] = true;
        $items[]      = array(
            'url' => $url,
            'alt' => sanitize_text_field( (string) ( ( $photo['ShortDescription'] ?? '' ) ?: ( $photo['LongDescription'] ?? '' ) ) ),
        );
    }

    return $items;
}

/**
 * GET Property('{ListingKey}')/Media when the Property payload did not include the expansion.
 *
 * @param string $listing_id RESO ListingKey.
 * @return array
 */
function spark_fetch_listing_media( $listing_id ) {
    $data = spark_api_get( SPARK_API_ENDPOINT . "('" . rawurlencode( $listing_id ) . "')/Media?\$top=1000" );

    if ( is_wp_error( $data ) ) {
        error_log( 'Spark Media API Error: ' . $data->get_error_message() );
        return array();
    }

    return $data['value'] ?? array();
}

/**
 * Return image_gallery as an array of [ 'url' => string, 'alt' => string ].
 */
add_filter( 'acf/format_value/name=image_gallery', 'spark_format_image_gallery_field', 10, 3 );
function spark_format_image_gallery_field( $value, $post_id, $field ) {
    // Keep the raw JSON string in the WP admin textarea.
    if ( is_admin() && ! wp_doing_ajax() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        return $value;
    }

    if ( is_string( $value ) && '' !== $value ) {
        $decoded = json_decode( $value, true );
        $value   = is_array( $decoded ) ? $decoded : array();
    }

    if ( ! is_array( $value ) ) {
        return array();
    }

    $items = array();

    foreach ( $value as $item ) {
        if ( is_string( $item ) && $item ) {
            $items[] = array(
                'url' => $item,
                'alt' => '',
            );
            continue;
        }

        if ( ! is_array( $item ) ) {
            continue;
        }

        $url = $item['url'] ?? '';
        $alt = $item['alt'] ?? '';

        if ( $url ) {
            $items[] = array(
                'url' => $url,
                'alt' => $alt,
            );
        }
    }

    return $items;
}

/**
 * Stop an in-progress sync and clear its queued batches.
 */
function spark_cancel_sync() {
    delete_option( 'spark_sync_state' );
    delete_option( 'spark_sync_seen' );
    delete_transient( 'spark_sync_lock' );
    wp_clear_scheduled_hook( 'spark_sync_batch_event' );
}

/**
 * Number of listing posts in any status, including drafts and trash.
 *
 * @param string $status Only count this post status. Empty for all statuses.
 * @return int
 */
function spark_count_listings( $status = '' ) {
    $counts = (array) wp_count_posts( 'listing' );

    return $status ? (int) ( $counts[ $status ] ?? 0 ) : (int) array_sum( $counts );
}

/**
 * Permanently delete listing posts until the batch time budget runs out.
 *
 * @param string $status Only delete this post status. Empty for all statuses.
 * @return int Listings still remaining.
 */
function spark_delete_listings_batch( $status = '' ) {
    if ( function_exists( 'set_time_limit' ) ) {
        set_time_limit( 300 );
    }

    $deadline = time() + SPARK_SYNC_BATCH_SECONDS;

    do {
        $ids = get_posts(
            array(
                'post_type'        => 'listing',
                'post_status'      => $status ? $status : array_keys( get_post_stati() ),
                'posts_per_page'   => 100,
                'fields'           => 'ids',
                'no_found_rows'    => true,
                'suppress_filters' => true,
            )
        );

        foreach ( $ids as $id ) {
            wp_delete_post( $id, true );

            if ( time() >= $deadline ) {
                break 2;
            }
        }
    } while ( $ids && time() < $deadline );

    return spark_count_listings( $status );
}

// ==========================================
// 3. CRON JOB AUTOMATION
// ==========================================
add_action( 'wp', 'setup_spark_api_cron' );
function setup_spark_api_cron() {
    // Hourly: pull listings modified since the last sync.
    if ( ! wp_next_scheduled( 'spark_hourly_sync_event' ) ) {
        wp_schedule_event( time(), 'hourly', 'spark_hourly_sync_event' );
    }

    // Daily at 3am: full sync, which also drafts listings that left the feed (sold, pending, expired).
    if ( ! wp_next_scheduled( 'spark_daily_sync_event' ) ) {
        $tomorrow = new DateTime( 'tomorrow 03:00', wp_timezone() );
        wp_schedule_event( $tomorrow->getTimestamp(), 'daily', 'spark_daily_sync_event' );
    }
}

add_action( 'spark_hourly_sync_event', 'spark_sync_recent_listings' );
add_action( 'spark_daily_sync_event', 'sync_spark_listings_to_cpt' );

// Each batch schedules the next one until the feed is exhausted.
add_action( 'spark_sync_batch_event', 'spark_sync_run_batch' );


// ==========================================
// 4. MANUAL TRIGGER BUTTON IN WP ADMIN
// ==========================================
add_action( 'admin_menu', 'register_spark_sync_admin_page' );
function register_spark_sync_admin_page() {
    // Adds a submenu item under your Custom Post Type
    add_submenu_page(
        'edit.php?post_type=listing', // Attach to your CPT menu (change slug if needed)
        'Force API Sync',
        'Sync API',
        'manage_options',
        'spark-api-sync',
        'render_spark_sync_admin_page'
    );
}

function render_spark_sync_admin_page() {
    $token_in_config = defined( 'SPARK_API_TOKEN' ) && SPARK_API_TOKEN;

    if ( isset( $_POST['spark_api_token'] ) && ! $token_in_config && check_admin_referer( 'spark_api_key_action', 'spark_api_key_nonce' ) ) {
        $token = trim( sanitize_text_field( wp_unslash( $_POST['spark_api_token'] ) ) );

        if ( '' === $token ) {
            delete_option( 'spark_api_token' );
            echo '<div class="notice notice-warning is-dismissible"><p>API key removed. Syncing is paused until a key is saved.</p></div>';
        } else {
            update_option( 'spark_api_token', $token, false );
            echo '<div class="notice notice-success is-dismissible"><p>API key saved.</p></div>';
        }
    }

    // Listen for the button press and verify security nonce
    if ( isset( $_POST['manual_spark_sync'] ) && check_admin_referer( 'spark_sync_action', 'spark_sync_nonce' ) ) {

        $result = 'continue' === $_POST['manual_spark_sync'] ? spark_sync_run_batch() : sync_spark_listings_to_cpt( true );

        if ( is_wp_error( $result ) ) {
            echo '<div class="notice notice-error is-dismissible"><p>Spark API error: ' . esc_html( $result->get_error_message() ) . '</p></div>';
        } elseif ( $result ) {
            echo '<div class="notice notice-success is-dismissible"><p>Sync started. Listings are imported in batches in the background.</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Sync failed or no listings were found. Check your error logs.</p></div>';
        }
    }

    $deleting = false;

    if ( isset( $_POST['spark_delete_listings'] ) && check_admin_referer( 'spark_delete_action', 'spark_delete_nonce' ) ) {
        if ( 'DELETE' !== trim( wp_unslash( $_POST['spark_delete_confirm'] ?? '' ) ) ) {
            echo '<div class="notice notice-error is-dismissible"><p>Type DELETE in the confirmation box to delete all listings.</p></div>';
        } else {
            spark_cancel_sync();
            update_option( 'spark_delete_in_progress', 1, false );
            $deleting = true;
        }
    } elseif (
        isset( $_GET['spark_delete_continue'] )
        && get_option( 'spark_delete_in_progress' )
        && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['spark_delete_continue'] ) ), 'spark_delete_continue' )
    ) {
        $deleting = true;
    }

    if ( $deleting ) {
        $remaining = spark_delete_listings_batch();

        if ( $remaining ) {
            $continue_url = add_query_arg(
                'spark_delete_continue',
                wp_create_nonce( 'spark_delete_continue' ),
                admin_url( 'edit.php?post_type=listing&page=spark-api-sync' )
            );

            // Each reload deletes another batch so no single request times out.
            echo '<meta http-equiv="refresh" content="1;url=' . esc_url( $continue_url ) . '">';
            echo '<div class="notice notice-info"><p>Deleting listings&hellip; ' . esc_html( number_format( $remaining ) ) . ' remaining. Keep this page open until it finishes.</p></div>';
        } else {
            delete_option( 'spark_delete_in_progress' );
            echo '<div class="notice notice-success is-dismissible"><p>All listings have been deleted.</p></div>';
        }
    }

    $deleting_drafts = false;

    if ( ! $deleting && isset( $_POST['spark_delete_drafts'] ) && check_admin_referer( 'spark_delete_drafts_action', 'spark_delete_drafts_nonce' ) ) {
        update_option( 'spark_delete_drafts_in_progress', 1, false );
        $deleting_drafts = true;
    } elseif (
        ! $deleting
        && isset( $_GET['spark_delete_drafts_continue'] )
        && get_option( 'spark_delete_drafts_in_progress' )
        && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['spark_delete_drafts_continue'] ) ), 'spark_delete_drafts_continue' )
    ) {
        $deleting_drafts = true;
    }

    if ( $deleting_drafts ) {
        $remaining = spark_delete_listings_batch( 'draft' );

        if ( $remaining ) {
            $continue_url = add_query_arg(
                'spark_delete_drafts_continue',
                wp_create_nonce( 'spark_delete_drafts_continue' ),
                admin_url( 'edit.php?post_type=listing&page=spark-api-sync' )
            );

            echo '<meta http-equiv="refresh" content="1;url=' . esc_url( $continue_url ) . '">';
            echo '<div class="notice notice-info"><p>Deleting draft listings&hellip; ' . esc_html( number_format( $remaining ) ) . ' remaining. Keep this page open until it finishes.</p></div>';
        } else {
            delete_option( 'spark_delete_drafts_in_progress' );
            echo '<div class="notice notice-success is-dismissible"><p>All draft listings have been deleted.</p></div>';
        }
    }

    $state         = get_option( 'spark_sync_state' );
    $last          = get_option( 'spark_sync_last' );
    $listing_count = spark_count_listings();
    $draft_count   = spark_count_listings( 'draft' );
    ?>
    <div class="wrap">
        <h1>Spark API Listing Synchronization</h1>

        <form method="post" action="">
            <?php wp_nonce_field( 'spark_api_key_action', 'spark_api_key_nonce' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="spark_api_token">API Key</label></th>
                    <td>
                        <?php if ( $token_in_config ) : ?>
                            <p class="description">The API key is set by <code>SPARK_API_TOKEN</code> in <code>wp-config.php</code>.</p>
                        <?php else : ?>
                            <input type="password" id="spark_api_token" name="spark_api_token" class="regular-text" value="<?php echo esc_attr( get_option( 'spark_api_token', '' ) ); ?>" autocomplete="off" spellcheck="false">
                            <label><input type="checkbox" onclick="document.getElementById('spark_api_token').type = this.checked ? 'text' : 'password';"> Show</label>
                            <p class="description">Your Spark / RESO Web API access token, sent as a Bearer token.</p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="spark_last_sync">Last Sync</label></th>
                    <td>
                        <?php if ( ! empty( $last['finished'] ) ) : ?>
                            <input type="text" id="spark_last_sync" class="regular-text" readonly value="<?php echo esc_attr( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $last['finished'] ) ); ?>">
                            <p class="description">
                                <?php echo empty( $last['since'] ) ? 'Full sync' : 'Hourly update'; ?>
                                finished <?php echo esc_html( human_time_diff( (int) $last['finished'] ) ); ?> ago.
                            </p>
                        <?php else : ?>
                            <input type="text" id="spark_last_sync" class="regular-text" readonly value="Never">
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <?php if ( ! $token_in_config ) : ?>
                <?php submit_button( 'Save API Key', 'secondary' ); ?>
            <?php endif; ?>
        </form>

        <hr>

        <p>
            Every hour the system pulls listings that changed since the last sync. Once a day (3am) it runs a full sync,
            which also moves listings that left the feed to draft. Force Sync Now runs a full sync immediately.
        </p>
        <p><strong>Filter:</strong> <code><?php echo esc_html( SPARK_API_FILTER ); ?></code></p>

        <?php if ( ! empty( $state['next_url'] ) ) : ?>
            <?php // Reloading keeps WP-Cron firing so batches continue while this page is open. ?>
            <meta http-equiv="refresh" content="15">
            <p>
                <strong><?php echo empty( $state['since'] ) ? 'Full sync' : 'Hourly update'; ?> in progress:</strong>
                <?php echo esc_html( number_format( (int) $state['processed'] ) ); ?>
                <?php if ( ! empty( $state['total'] ) ) : ?>
                    of <?php echo esc_html( number_format( (int) $state['total'] ) ); ?>
                <?php endif; ?>
                listings processed.
            </p>
            <?php if ( ! empty( $state['error'] ) ) : ?>
                <p><strong>Last error:</strong> <?php echo esc_html( $state['error'] ); ?> (retrying)</p>
            <?php endif; ?>
            <form method="post" action="">
                <?php wp_nonce_field( 'spark_sync_action', 'spark_sync_nonce' ); ?>
                <input type="hidden" name="manual_spark_sync" value="continue">
                <?php submit_button( 'Run Next Batch Now', 'secondary' ); ?>
            </form>
        <?php elseif ( ! empty( $last['finished'] ) ) : ?>
            <p>
                <strong>Last sync:</strong>
                <?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $last['finished'] ) ); ?>
                <?php if ( empty( $last['since'] ) ) : ?>
                    (full) &mdash; <?php echo esc_html( number_format( (int) $last['processed'] ) ); ?> listings,
                    <?php echo esc_html( number_format( (int) $last['drafted'] ) ); ?> no longer in the feed moved to draft.
                <?php else : ?>
                    (hourly update) &mdash; <?php echo esc_html( number_format( (int) $last['processed'] ) ); ?> changed listings.
                <?php endif; ?>
            </p>
        <?php endif; ?>

        <form method="post" action="">
            <?php wp_nonce_field( 'spark_sync_action', 'spark_sync_nonce' ); ?>
            <input type="hidden" name="manual_spark_sync" value="start">
            <?php submit_button( 'Force Sync Now', 'primary' ); ?>
        </form>

        <hr>

        <h2>Delete All Drafts</h2>
        <p>
            Permanently deletes all <?php echo esc_html( number_format( $draft_count ) ); ?> draft listings, including ones drafted by hand.
            Published listings are not affected, and redirects for removed listings stay in place.
            If a deleted listing comes back on the market, the next sync imports it as a new post.
        </p>
        <?php if ( ! $deleting_drafts && get_option( 'spark_delete_drafts_in_progress' ) && $draft_count ) : ?>
            <p><strong>A previous draft delete was interrupted.</strong> Submit again to delete the remaining drafts.</p>
        <?php endif; ?>
        <form method="post" action="" onsubmit="return confirm('Permanently delete all draft listings? This cannot be undone.');">
            <?php wp_nonce_field( 'spark_delete_drafts_action', 'spark_delete_drafts_nonce' ); ?>
            <input type="hidden" name="spark_delete_drafts" value="1">
            <?php submit_button( 'Delete All Drafts', 'delete', 'submit', true, $draft_count ? array() : array( 'disabled' => 'disabled' ) ); ?>
        </form>

        <hr>

        <h2>Delete All Listings</h2>
        <p>
            Permanently deletes all <?php echo esc_html( number_format( $listing_count ) ); ?> listing posts (published, draft, and trashed) and their details.
            This cannot be undone. Any sync in progress is stopped; the next sync will import listings from Spark again.
        </p>
        <?php if ( ! $deleting && get_option( 'spark_delete_in_progress' ) && $listing_count ) : ?>
            <p><strong>A previous delete was interrupted.</strong> Submit again to delete the remaining listings.</p>
        <?php endif; ?>
        <form method="post" action="" onsubmit="return confirm('Permanently delete all listings? This cannot be undone.');">
            <?php wp_nonce_field( 'spark_delete_action', 'spark_delete_nonce' ); ?>
            <input type="hidden" name="spark_delete_listings" value="1">
            <p>
                <label for="spark_delete_confirm">Type <code>DELETE</code> to confirm:</label>
                <input type="text" id="spark_delete_confirm" name="spark_delete_confirm" class="regular-text" autocomplete="off" spellcheck="false">
            </p>
            <?php submit_button( 'Delete All Listings', 'delete', 'submit', true, $listing_count ? array() : array( 'disabled' => 'disabled' ) ); ?>
        </form>
    </div>
    <?php
}
