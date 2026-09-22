<?php

// ==========================================
// 1. API CONSTANTS & CONFIGURATION
// ==========================================
// Replace these with your actual Spark API credentials and endpoints.
define( 'SPARK_API_TOKEN', 'ap45a6gmc46lun3i9kyx3u7u3' );
define( 'SPARK_API_ENDPOINT', 'https://sparkapi.com/v1/listings' ); 

// ==========================================
// 2. THE CORE API SYNC FUNCTION
// ==========================================
function sync_spark_listings_to_cpt() {
    // Setup API Request Headers
    $args = array(
        'headers' => array(
            'Authorization'         => 'Bearer ' . SPARK_API_TOKEN,
            'Accept'                => 'application/json',
            'X-SparkApi-User-Agent' => 'MyWPSyncApp/1.0' // Spark often requires a custom user-agent string
        ),
        'timeout' => 60, // Give the API time to respond
    );

    // Fetch listings with photos expanded so we don't need a second request per listing.
    $listings_url = add_query_arg( array( '_expand' => 'Photos' ), SPARK_API_ENDPOINT );
    $response     = wp_remote_get( $listings_url, $args );
    // Error handling
    if ( is_wp_error( $response ) ) {
        echo 'Spark API Error: ' . $response->get_error_message();
        error_log( 'Spark API Error: ' . $response->get_error_message() );
        return false;
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    // Adjust based on exact Spark API JSON structure (usually under 'D' -> 'Results')
    $listings = $data['D']['Results'] ?? $data['Results'] ?? array(); 

    if ( empty( $listings ) ) {
        return false;
    }

    // Loop through each listing and save/update it
    foreach ( $listings as $listing ) {
        $fields     = is_array( $listing['StandardFields'] ?? null ) ? $listing['StandardFields'] : $listing;
        $listing_id = sanitize_text_field( $listing['Id'] ?? $fields['ListingKey'] ?? '' );
        $mls_id     = sanitize_text_field( $fields['ListingId'] ?? '' );
        $title      = sanitize_text_field( $fields['UnparsedFirstLineAddress'] ?? $fields['UnparsedAddress'] ?? 'Listing ' . $listing_id );

        $post_id = spark_find_listing_post_id( $listing_id, $mls_id, $title );

        if ( $post_id ) {
            // Keep the existing address title; only fix auto-generated duplicates.
            if ( get_the_title( $post_id ) === 'Listing ' . $listing_id && $title !== get_the_title( $post_id ) ) {
                wp_update_post(
                    array(
                        'ID'         => $post_id,
                        'post_title' => $title,
                    )
                );
            }
        } else {
            $post_id = wp_insert_post( array(
                'post_title'  => $title,
                'post_type'   => 'listing',
                'post_status' => 'publish',
            ) );
        }

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            continue;
        }

        update_post_meta( $post_id, 'spark_api_id', $listing_id );

        if ( function_exists( 'update_field' ) ) {
            update_field( 'price', sanitize_text_field( (string) ( $fields['ListPrice'] ?? '' ) ), $post_id );
            update_field( 'bedrooms', sanitize_text_field( (string) ( $fields['BedsTotal'] ?? '' ) ), $post_id );
            update_field( 'bathrooms', sanitize_text_field( (string) ( $fields['BathsTotal'] ?? '' ) ), $post_id );
            update_field( 'mls_id', $mls_id, $post_id );
            update_field( 'address', sanitize_text_field( (string) ( $fields['UnparsedAddress'] ?? '' ) ), $post_id );
            update_field( 'status', sanitize_text_field( (string) ( $fields['MlsStatus'] ?? '' ) ), $post_id );

            $photos = spark_get_listing_photos( $listing, $listing_id, $args );
            spark_save_image_gallery( $post_id, $photos );
        }
    }
    
    return true;
}

/**
 * Find an existing listing post, preferring the original address-titled post over sync duplicates.
 *
 * @param string $listing_id Spark listing Id.
 * @param string $mls_id     MLS ListingId (e.g. 07-917).
 * @param string $title      Address title from Spark.
 * @return int
 */
function spark_find_listing_post_id( $listing_id, $mls_id, $title ) {
    $candidates = array();
    $queries    = array();

    if ( $listing_id ) {
        $queries[] = array( 'meta_key' => 'spark_api_id', 'meta_value' => $listing_id );
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
 * Collect listing photos from the Spark payload, falling back to a dedicated photos request.
 *
 * @param array  $listing    Listing payload from Spark.
 * @param string $listing_id Spark listing ID.
 * @param array  $args       wp_remote_get args (headers, timeout).
 * @return array[] Array of [ 'url' => string, 'alt' => string ].
 */
function spark_get_listing_photos( $listing, $listing_id, $args ) {
    $photos = $listing['StandardFields']['Photos'] ?? $listing['Photos'] ?? array();

    if ( empty( $photos ) && $listing_id ) {
        $photos = spark_fetch_listing_photos( $listing_id, $args );
    }

    $items = array();
    $seen  = array();

    foreach ( (array) $photos as $photo ) {
        if ( ! is_array( $photo ) ) {
            continue;
        }

        $url = $photo['UriLarge']
            ?? $photo['Uri2048']
            ?? $photo['Uri1600']
            ?? $photo['Uri1280']
            ?? $photo['Uri1024']
            ?? $photo['Uri800']
            ?? $photo['Uri640']
            ?? $photo['Uri300']
            ?? '';

        $url = $url ? esc_url_raw( $url ) : '';

        if ( ! $url || isset( $seen[ $url ] ) ) {
            continue;
        }

        $seen[ $url ] = true;
        $items[]      = array(
            'url' => $url,
            'alt' => sanitize_text_field( (string) ( $photo['Name'] ?? '' ) ),
        );
    }

    return $items;
}

/**
 * GET /v1/listings/{id}/photos when the listings payload did not include photos.
 *
 * @param string $listing_id Spark listing ID.
 * @param array  $args       wp_remote_get args (headers, timeout).
 * @return array
 */
function spark_fetch_listing_photos( $listing_id, $args ) {
    $photos_url = trailingslashit( SPARK_API_ENDPOINT ) . rawurlencode( $listing_id ) . '/photos';
    $response   = wp_remote_get( $photos_url, $args );

    if ( is_wp_error( $response ) ) {
        error_log( 'Spark Photos API Error: ' . $response->get_error_message() );
        return array();
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    return $body['D']['Results'] ?? $body['Results'] ?? array();
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

// ==========================================
// 3. DAILY CRON JOB AUTOMATION
// ==========================================
add_action( 'wp', 'setup_spark_api_daily_cron' );
function setup_spark_api_daily_cron() {
    // Schedule the event if it doesn't already exist
    if ( ! wp_next_scheduled( 'spark_daily_sync_event' ) ) {
        wp_schedule_event( time(), 'daily', 'spark_daily_sync_event' );
    }
}

// Hook our sync function to the scheduled cron event
add_action( 'spark_daily_sync_event', 'sync_spark_listings_to_cpt' );


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
    // Listen for the button press and verify security nonce
    if ( isset( $_POST['manual_spark_sync'] ) && check_admin_referer( 'spark_sync_action', 'spark_sync_nonce' ) ) {
        
        $result = sync_spark_listings_to_cpt(); // Run the core function
        
        if ( $result ) {
            echo '<div class="notice notice-success is-dismissible"><p>Listings successfully synced from Spark API!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Sync failed or no listings were found. Check your error logs.</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1>Spark API Listing Synchronization</h1>
        <p>The system automatically syncs listings once a day. If you need to pull data immediately, click the button below.</p>
        
        <form method="post" action="">
            <?php wp_nonce_field( 'spark_sync_action', 'spark_sync_nonce' ); ?>
            <input type="hidden" name="manual_spark_sync" value="1">
            <?php submit_button( 'Force Sync Now', 'primary' ); ?>
        </form>
    </div>
    <?php
}