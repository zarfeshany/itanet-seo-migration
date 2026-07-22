<?php
/**
 * Plugin Name: Itanet Exact Path Resolver
 * Description: Resolves UTF-8 hierarchical page paths via direct DB lookup.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function itanet_resolve_page_id_from_path( $path ) {
	global $wpdb;
	$path = trim( rawurldecode( (string) $path ), '/' );
	if ( $path === '' ) {
		return 0;
	}
	$segments = array_values( array_filter( explode( '/', $path ), 'strlen' ) );
	$parent   = 0;
	$id       = 0;
	foreach ( $segments as $seg ) {
		$id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts}
				 WHERE post_name = %s AND post_parent = %d
				   AND post_type = 'page' AND post_status = 'publish'
				 LIMIT 1",
				$seg,
				$parent
			)
		);
		if ( ! $id ) {
			$id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts}
					 WHERE post_name = %s AND post_type = 'page' AND post_status = 'publish'
					 LIMIT 1",
					$seg
				)
			);
			if ( ! $id ) {
				return 0;
			}
		}
		$parent = $id;
	}
	return $id;
}

function itanet_current_request_path() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
	$path = parse_url( $uri, PHP_URL_PATH );
	return is_string( $path ) ? $path : '';
}

add_action( 'parse_request', function ( $wp ) {
	$path    = itanet_current_request_path();
	$page_id = itanet_resolve_page_id_from_path( $path );
	if ( $page_id > 0 ) {
		$wp->query_vars = array( 'page_id' => $page_id );
		if ( ! headers_sent() ) {
			header( 'X-Itanet-Resolved: ' . $page_id );
		}
	}
}, 1 );

add_filter( 'pre_handle_404', function ( $preempt, $wp_query ) {
	if ( $preempt || is_admin() ) {
		return $preempt;
	}
	if ( $wp_query instanceof WP_Query && $wp_query->get( 'page_id' ) ) {
		return $preempt;
	}
	$path    = itanet_current_request_path();
	$page_id = itanet_resolve_page_id_from_path( $path );
	if ( $page_id > 0 ) {
		$wp_query->queried_object    = get_post( $page_id );
		$wp_query->queried_object_id = $page_id;
		$wp_query->is_page           = true;
		$wp_query->is_singular       = true;
		$wp_query->is_404            = false;
		$wp_query->post_count        = 1;
		$wp_query->posts             = array( get_post( $page_id ) );
		$wp_query->post              = $wp_query->posts[0];
		status_header( 200 );
		return true;
	}
	return $preempt;
}, 10, 2 );

add_action( 'template_redirect', function () {
	if ( ! is_404() ) {
		return;
	}
	$page_id = itanet_resolve_page_id_from_path( itanet_current_request_path() );
	if ( $page_id > 0 ) {
		wp_safe_redirect( home_url( '/?page_id=' . $page_id ), 301 );
		exit;
	}
}, 0 );
