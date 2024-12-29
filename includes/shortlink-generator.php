<?php

// Generate a short code for a product and save it in the database
function cps_generate_short_code( $product_id ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'product_shortlinks';

    // Check if the product already has a short code
    $row = $wpdb->get_row( $wpdb->prepare( "SELECT short_code FROM $table_name WHERE product_id = %d", $product_id ) );
    if ( $row ) {
        // If a short code already exists, return it
        return $row->short_code;
    }

    // Generate a unique short code
    do {
        $short_code = substr( str_shuffle( 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' ), 0, 6 );
        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE short_code = %s", $short_code ) );
    } while ( $exists );

    // Insert the new short code into the table
    $wpdb->insert(
        $table_name,
        [
            'product_id' => $product_id,
            'short_code' => $short_code,
        ],
        [ '%d', '%s' ]
    );

    return $short_code;
}

// Generate the full short link
function cps_generate_shortlink( $product_id, $custom_domain ) {
    $short_code = cps_generate_short_code( $product_id );
    return trailingslashit( $custom_domain ) . $short_code;
}

function cps_get_products() {
    return get_posts( [
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ] );
}