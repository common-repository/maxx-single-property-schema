<?php
/*
Plugin Name: MaxX Single Property Schema For Houzez Theme Only
Description: Adds schema markup for single property, including gallery images and aggregate ratings.
Version: 1.0
Author: Muhammad Irfan Ghori
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Function to add schema markup to single property pages
function maxx_psp_add_real_estate_listing_schema() {
    if ( get_option( 'maxx_psp_enable_single_property_schema', 1 ) && is_single() && get_post_type() === 'property' ) {
        global $post;

        // Fetch necessary data
        $prop_id = houzez_get_listing_data( 'property_id' );
        $prop_price = houzez_get_listing_data( 'property_price' );
        $prop_size = houzez_get_listing_data( 'property_size' );
        $land_area = houzez_get_listing_data( 'property_land' );
        $bedrooms = houzez_get_listing_data( 'property_bedrooms' );
        $rooms = houzez_get_listing_data( 'property_rooms' );
        $bathrooms = houzez_get_listing_data( 'property_bathrooms' );
        $year_built = houzez_get_listing_data( 'property_year' );
        $garage = houzez_get_listing_data( 'property_garage' );
        $property_status = houzez_taxonomy_simple( 'property_status' );
        $property_type = houzez_taxonomy_simple( 'property_type' );
        $property_features = wp_get_post_terms( get_the_ID(), 'property_feature', array( 'fields' => 'names' ) );

        // Prepare features array for schema
        $features_array = array();
        foreach ( $property_features as $feature ) {
            $features_array[] = array(
                '@type' => 'LocationFeatureSpecification',
                'name'  => esc_html( $feature ),
            );
        }

        // Get gallery images
        if ( function_exists( 'rwmb_meta' ) ) {
            $properties_images = rwmb_meta( 'fave_property_images', 'type=plupload_image&size=full', $post->ID );
        } else {
            $properties_images = array();
        }

        $image_objects = array();
        // Add each image to the schema
        foreach ( $properties_images as $image ) {
            $image_objects[] = array(
                '@type'      => 'ImageObject',
                'name'       => esc_html( $image['title'] ),
                'contentUrl' => esc_url( $image['url'] ),
            );
        }

        // Prepare aggregateRating data
        $aggregateRatingEnabled = get_option( 'maxx_psp_aggregate_rating_enable', 1 );
        $ratingValue           = get_option( 'maxx_psp_rating_value', '10' );
        $ratingCount           = get_option( 'maxx_psp_rating_count', '32001' );
        $reviewCount           = get_option( 'maxx_psp_review_count', '32001' );
        $worstRating           = get_option( 'maxx_psp_worst_rating', '0' );
        $bestRating            = get_option( 'maxx_psp_best_rating', '10' );

        // Create the schema array
        $schema = array(
            '@context' => 'https://schema.org',
            '@graph'   => array(
                array(
                    '@type'        => 'Product',
                    '@id'          => 'listing',
                    'name'         => esc_html( get_the_title() ),
                    'image'        => $image_objects,
                    'description'  => esc_html( get_the_excerpt() ),
                    'keywords'     => esc_html( get_the_tag_list( '', ', ', '' ) ),
                    'offers'       => array(
                        '@type'             => 'Offer',
                        'priceCurrency'     => 'PKR',
                        'price'             => esc_html( $prop_price ),
                        'url'               => esc_url( get_permalink() ),
                        'availability'      => 'https://schema.org/InStock',
                        'itemCondition'     => 'https://schema.org/NewCondition',
                        'priceValidUntil'   => '2025-01-01',
                    ),
                    'aggregateRating' => $aggregateRatingEnabled ? array(
                        '@type'        => 'AggregateRating',
                        'ratingValue'  => esc_html( $ratingValue ),
                        'ratingCount'  => absint( $ratingCount ),
                        'reviewCount'  => absint( $reviewCount ),
                        'worstRating'  => absint( $worstRating ),
                        'bestRating'   => absint( $bestRating ),
                    ) : null,
                ),
                array(
                    '@type'            => 'SingleFamilyResidence',
                    '@id'              => 'listing',
                    'identifier'       => esc_html( $prop_id ),
                    'name'             => esc_html( get_the_title() ),
                    'address'          => array(
                        '@type'           => 'PostalAddress',
                        'streetAddress'   => esc_html( houzez_get_listing_data( 'property_address' ) ),
                        'addressLocality' => esc_html( houzez_get_listing_data( 'property_city' ) ),
                        'addressRegion'   => esc_html( houzez_get_listing_data( 'property_state' ) ),
                        'postalCode'      => esc_html( houzez_get_listing_data( 'property_zip' ) ),
                        'addressCountry'  => 'PK',
                    ),
                    'numberOfRooms'        => absint( $rooms ),
                    'numberOfBathroomsTotal'=> absint( $bathrooms ),
                    'numberOfBedrooms'     => absint( $bedrooms ),
                    'floorSize'            => array(
                        '@type'    => 'QuantitativeValue',
                        'value'    => esc_html( $prop_size ),
                        'unitCode' => 'FT2',
                    ),
                    'amenityFeature'       => $features_array,
                    'yearBuilt'            => esc_html( $year_built ),
                    'occupancy'            => esc_html( $property_status ),
                ),
            ),
        );

        // Output the schema markup
        echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>';
    }
}
add_action( 'wp_head', 'maxx_psp_add_real_estate_listing_schema' );

// Function to generate breadcrumb schema markup
function maxx_psp_generate_breadcrumb_schema() {
    if ( get_option( 'maxx_psp_breadcrumb_options', 0 ) ) { // Check if breadcrumb options are enabled
        global $post;

        // Initialize the item list for the breadcrumb schema
        $item_list = array();

        // Add the Home link
        $item_list[] = array(
            '@type' => 'ListItem',
            'position' => 1,
            'name' => esc_html__( 'Home', 'maxx-single-property-schema' ),
            'item' => esc_url( home_url() ),
        );

        // Get the current post's property_area terms
        $property_areas = get_the_terms( $post->ID, 'property_area' );
        if ( $property_areas && ! is_wp_error( $property_areas ) ) {
            foreach ( $property_areas as $index => $term ) {
                $item_list[] = array(
                    '@type'    => 'ListItem',
                    'position' => $index + 2,
                    'name'     => esc_html( $term->name ),
                    'item'     => esc_url( get_term_link( $term ) ),
                );
            }
        }

        // Add the current post title to the breadcrumbs
        $item_list[] = array(
            '@type'    => 'ListItem',
            'position' => count( $item_list ) + 1,
            'name'     => esc_html( get_the_title( $post->ID ) ),
            'item'     => esc_url( get_permalink( $post->ID ) ),
        );

        // Generate the breadcrumb schema markup
        $breadcrumb_schema = array(
            '@context'         => 'https://schema.org',
            '@type'            => 'BreadcrumbList',
            'name'             => esc_html( get_the_title( $post->ID ) ),
            'itemListElement'  => $item_list,
        );

        // Output the breadcrumb schema as JSON-LD
        echo '<script type="application/ld+json">' . wp_json_encode( $breadcrumb_schema ) . '</script>';
    }
}
add_action( 'wp_head', 'maxx_psp_generate_breadcrumb_schema' );

// Register settings for the options page
function maxx_psp_register_settings() {
    register_setting( 'maxx_psp_options_group', 'maxx_psp_enable_single_property_schema', 'intval' );
    register_setting( 'maxx_psp_options_group', 'maxx_psp_aggregate_rating_enable', 'intval' );
    register_setting( 'maxx_psp_options_group', 'maxx_psp_rating_value', 'sanitize_text_field' );
    register_setting( 'maxx_psp_options_group', 'maxx_psp_rating_count', 'intval' );
    register_setting( 'maxx_psp_options_group', 'maxx_psp_review_count', 'intval' );
    register_setting( 'maxx_psp_options_group', 'maxx_psp_worst_rating', 'intval' );
    register_setting( 'maxx_psp_options_group', 'maxx_psp_best_rating', 'intval' );
    register_setting( 'maxx_psp_options_group', 'maxx_psp_breadcrumb_options', 'intval' );
}
add_action( 'admin_init', 'maxx_psp_register_settings' );

// Options page HTML
function maxx_psp_options_page_html() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'MaxX Schema Settings', 'maxx-single-property-schema' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'maxx_psp_options_group' );
            do_settings_sections( 'maxx_psp_options_group' );
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Enable Single Property Schema', 'maxx-single-property-schema' ); ?></th>
                    <td>
                        <input type="checkbox" name="maxx_psp_enable_single_property_schema" value="1" <?php checked( 1, get_option( 'maxx_psp_enable_single_property_schema', 1 ), true ); ?> />
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Enable Breadcrumb Schema', 'maxx-single-property-schema' ); ?></th>
                    <td>
                        <input type="checkbox" name="maxx_psp_breadcrumb_options" value="1" <?php checked( 1, get_option( 'maxx_psp_breadcrumb_options', 0 ), true ); ?> />
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Enable Aggregate Rating', 'maxx-single-property-schema' ); ?></th>
                    <td>
                        <input type="checkbox" name="maxx_psp_aggregate_rating_enable" value="1" <?php checked( 1, get_option( 'maxx_psp_aggregate_rating_enable', 1 ), true ); ?> />
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Rating Value', 'maxx-single-property-schema' ); ?></th>
                    <td>
                        <input type="text" name="maxx_psp_rating_value" value="<?php echo esc_attr( get_option( 'maxx_psp_rating_value', '10' ) ); ?>" />
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Rating Count', 'maxx-single-property-schema' ); ?></th>
                    <td>
                        <input type="number" name="maxx_psp_rating_count" value="<?php echo esc_attr( get_option( 'maxx_psp_rating_count', '32001' ) ); ?>" />
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Review Count', 'maxx-single-property-schema' ); ?></th>
                    <td>
                        <input type="number" name="maxx_psp_review_count" value="<?php echo esc_attr( get_option( 'maxx_psp_review_count', '32001' ) ); ?>" />
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Worst Rating', 'maxx-single-property-schema' ); ?></th>
                    <td>
                        <input type="number" name="maxx_psp_worst_rating" value="<?php echo esc_attr( get_option( 'maxx_psp_worst_rating', '0' ) ); ?>" />
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Best Rating', 'maxx-single-property-schema' ); ?></th>
                    <td>
                        <input type="number" name="maxx_psp_best_rating" value="<?php echo esc_attr( get_option( 'maxx_psp_best_rating', '10' ) ); ?>" />
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Add the options page to the admin menu
function maxx_psp_add_admin_menu() {
    add_menu_page(
        'MaxX Schema', 
        'MaxX Schema', 
        'manage_options', 
        'maxx_psp-schema-options', 
        'maxx_psp_options_page_html'
    );
}
add_action( 'admin_menu', 'maxx_psp_add_admin_menu' );

// Add settings link to plugin page
function maxx_psp_plugin_action_links( $links ) {
    $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=maxx_psp-schema-options' ) ) . '">' . esc_html__( 'Settings', 'maxx-single-property-schema' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'maxx_psp_plugin_action_links' );
