<?php

// ==========================================
// 1. API CONSTANTS & CONFIGURATION
// ==========================================
// The API key is saved on Listings > Sync API. Defining SPARK_API_TOKEN in wp-config.php overrides it.
define( 'SPARK_API_ENDPOINT', 'https://replication.sparkapi.com/Version/3/Reso/OData/Property' );

// OData $filter selecting which listings to import. Field names follow the RESO Data Dictionary.
define( 'SPARK_API_FILTER', "StandardStatus eq 'Active' and PropertyType eq 'Residential' and ListPrice ge 1000000" );

// Property fields requested from the API. Photos come from $expand=Media.
define( 'SPARK_API_SELECT', 'ListingKey,ListingId,ListPrice,BedroomsTotal,BathroomsTotalDecimal,BathroomsTotalInteger,LivingArea,UnparsedAddress,StandardStatus,PropertySubType,PublicRemarks,ModificationTimestamp' );

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
 * Start a full sync. The feed is processed in batches that follow @odata.nextLink.
 *
 * @param bool $force Restart even if a sync is already in progress.
 * @return bool|WP_Error
 */
function sync_spark_listings_to_cpt( $force = false ) {
    if ( '' === spark_api_token() ) {
        return new WP_Error( 'spark_api_no_token', 'No Spark API key is saved. Add one on Listings > Sync API.' );
    }

    $state = get_option( 'spark_sync_state' );

    // Don't let the daily cron clobber a sync that is still working through the feed.
    if ( ! $force && ! empty( $state['next_url'] ) && ( time() - (int) $state['started'] ) < 6 * HOUR_IN_SECONDS ) {
        return true;
    }

    update_option(
        'spark_sync_state',
        array(
            'next_url'  => spark_api_first_page_url(),
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
 * Build the first Property request: filtered, field-selected, with Media expanded.
 *
 * @return string
 */
function spark_api_first_page_url() {
    return add_query_arg(
        array(
            '$filter' => rawurlencode( SPARK_API_FILTER ),
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

            // Give up after repeated failures; the next daily run starts fresh.
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
        $drafted = spark_draft_missing_listings( array_keys( $seen ) );

        update_option(
            'spark_sync_last',
            array(
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

    $state = get_option( 'spark_sync_state' );
    $last  = get_option( 'spark_sync_last' );
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
            </table>
            <?php if ( ! $token_in_config ) : ?>
                <?php submit_button( 'Save API Key', 'secondary' ); ?>
            <?php endif; ?>
        </form>

        <hr>

        <p>The system automatically syncs listings once a day. If you need to pull data immediately, click the button below.</p>
        <p><strong>Filter:</strong> <code><?php echo esc_html( SPARK_API_FILTER ); ?></code></p>

        <?php if ( ! empty( $state['next_url'] ) ) : ?>
            <?php // Reloading keeps WP-Cron firing so batches continue while this page is open. ?>
            <meta http-equiv="refresh" content="15">
            <p>
                <strong>Sync in progress:</strong>
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
                &mdash; <?php echo esc_html( number_format( (int) $last['processed'] ) ); ?> listings,
                <?php echo esc_html( number_format( (int) $last['drafted'] ) ); ?> no longer in the feed moved to draft.
            </p>
        <?php endif; ?>

        <form method="post" action="">
            <?php wp_nonce_field( 'spark_sync_action', 'spark_sync_nonce' ); ?>
            <input type="hidden" name="manual_spark_sync" value="start">
            <?php submit_button( 'Force Sync Now', 'primary' ); ?>
        </form>
    </div>
    <?php
}
