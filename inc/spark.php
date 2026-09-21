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

    // Fetch data from Spark API
    $response = wp_remote_get( SPARK_API_ENDPOINT, $args );
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
        $listing_id = sanitize_text_field( $listing['Id'] ?? $listing['ListingKey'] ); 
        $title      = sanitize_text_field( $listing['StandardAddress'] ?? 'Listing ' . $listing_id );

        // 1. Check if this listing already exists to prevent duplicates
        $existing_posts = get_posts( array(
            'post_type'      => 'listing', // Change to your exact CPT slug
            'meta_key'       => 'spark_api_id',
            'meta_value'     => $listing_id,
            'posts_per_page' => 1,
            'post_status'    => 'any'
        ) );

        if ( ! empty( $existing_posts ) ) {
            $post_id = $existing_posts[0]->ID;
            // (Optional) Update post title/content if needed
        } else {
            // 2. Create a new post
            $post_id = wp_insert_post( array(
                'post_title'  => $title,
                'post_type'   => 'listing',
                'post_status' => 'publish',
            ) );
            
            // Save the unique API ID as standard post meta for future duplicate checks
            update_post_meta( $post_id, 'spark_api_id', $listing_id ); 
        }

        // 3. Map API data to ACF fields
        if ( ! is_wp_error( $post_id ) && function_exists('update_field') ) {
            // Syntax: update_field( 'acf_field_name', $api_value, $post_id );
            update_field( 'price', sanitize_text_field( $listing['ListPrice'] ), $post_id );
            update_field( 'bedrooms', sanitize_text_field( $listing['BedsTotal'] ), $post_id );
            update_field( 'bathrooms', sanitize_text_field( $listing['BathsTotal'] ), $post_id );
            update_field( 'status', sanitize_text_field( $listing['MlsStatus'] ), $post_id );
            
            // Add as many ACF fields as you need here...
        }
    }
    
    return true;
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