<?php
/**
 * Fix truncated Persian slugs + 10806 parent + retry shops.
 * Key: ?k=itanet-fix-slugs-2026
 */
require __DIR__ . '/wp-load.php';
if ( ( isset( $_GET['k'] ) ? $_GET['k'] : '' ) !== 'itanet-fix-slugs-2026' ) {
	status_header( 403 );
	exit( 'forbidden' );
}
header( 'Content-Type: application/json; charset=utf-8' );
global $wpdb;

$fixes = array(
	5367 => 'کانفیگ-و-تنظیم-مودم-فیبر-نوری-هواوی',
	5368 => 'راهنمای-خرید-بسته-اینترنت-ایرانسل-در-اپلیکیشن-ایرانسل-من',
	5370 => 'رانژه-کردن-خط-تلفن-چیست',
	5372 => 'راهنمای-جامع-شارژ-اینترنت-مخابرات-در-5-دقیقه',
	5373 => 'راهنمای-خرید-اینترنت-فیبر-نوری-مخابرات-اصفهان',
	5374 => 'تفاوت-اینترنت-adsl-و-اینترنت-فیبر-نوری؛-کدام-را-انتخاب-کنیم',
	5375 => 'انواع-مودم-فیبر-نوری--معرفی-بهترین-مدل-ها',
	5377 => 'همه-چیز-درباره-رابطه-اینترنت-فیبر-نوری-و-اینترنت-اشیاء',
	5378 => 'اینترنت-5g',
	5379 => 'تأثیر-اینترنت-فیبر-نوری-بر-رشد-کسب-و-کارهای-آنلاین-و-اقتصاد-دیجیتال',
);

$out = array( 'slug_fixes' => array() );

foreach ( $fixes as $id => $slug ) {
	$before = $wpdb->get_var( $wpdb->prepare( "SELECT post_name FROM {$wpdb->posts} WHERE ID=%d", $id ) );
	$wpdb->update( $wpdb->posts, array( 'post_name' => $slug ), array( 'ID' => $id ) );
	clean_post_cache( $id );
	$after = $wpdb->get_var( $wpdb->prepare( "SELECT post_name FROM {$wpdb->posts} WHERE ID=%d", $id ) );
	$out['slug_fixes'][] = array(
		'id'     => $id,
		'before' => $before,
		'after'  => $after,
		'ok'     => ( $after === $slug ),
		'link'   => get_permalink( $id ),
	);
}

// Fix 10806 parent slug (was 10806-2)
$parent = get_page_by_path( '10806-2' );
if ( ! $parent ) {
	// find by child 5382
	$child = get_post( 5382 );
	if ( $child && $child->post_parent ) {
		$parent = get_post( $child->post_parent );
	}
}
if ( $parent ) {
	// If a conflicting page owns slug 10806, rename it
	$conflict = $wpdb->get_var( $wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts} WHERE post_name=%s AND post_type='page' AND ID<>%d AND post_parent=0 LIMIT 1",
		'10806',
		$parent->ID
	) );
	if ( $conflict ) {
		$wpdb->update( $wpdb->posts, array( 'post_name' => '10806-old-' . $conflict ), array( 'ID' => (int) $conflict ) );
		clean_post_cache( (int) $conflict );
	}
	$wpdb->update( $wpdb->posts, array( 'post_name' => '10806' ), array( 'ID' => $parent->ID ) );
	clean_post_cache( $parent->ID );
	$out['10806_parent'] = array(
		'id'   => (int) $parent->ID,
		'link' => get_permalink( 5382 ),
	);
}

// Create/update shops page with timeout-tolerant fetch
function itanet_get( $url, $timeout = 90 ) {
	$ch = curl_init( $url );
	curl_setopt_array( $ch, array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_TIMEOUT        => $timeout,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_USERAGENT      => 'ItanetMigrator/1.1',
		CURLOPT_ENCODING       => '',
	) );
	$body = curl_exec( $ch );
	$code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
	$err  = curl_error( $ch );
	curl_close( $ch );
	return array( $code, $body, $err );
}

list( $code, $html, $err ) = itanet_get( 'https://itanet.ir/shops?categoryId=1726', 90 );
$shops_id = 0;
$existing = get_posts( array( 'name' => 'shops', 'post_type' => 'page', 'post_parent' => 0, 'posts_per_page' => 1, 'post_status' => 'any' ) );
if ( $existing ) {
	$shops_id = (int) $existing[0]->ID;
}

$title = 'فروشگاه ایتانت';
$content = '<h1>فروشگاه</h1><p>صفحه فروشگاه تجهیزات / محصولات ایتانت.</p>';
if ( $code >= 200 && $code < 400 && $html ) {
	if ( preg_match( '/<title[^>]*>(.*?)<\/title>/is', $html, $m ) ) {
		$title = trim( html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES, 'UTF-8' ) );
		$title = preg_replace( '/\s*\|\s*ایتانت\s*$/u', '', $title );
	}
	libxml_use_internal_errors( true );
	$dom = new DOMDocument();
	@$dom->loadHTML( '<?xml encoding="UTF-8">' . $html );
	$xpath = new DOMXPath( $dom );
	$list  = $xpath->query( '//*[contains(@class,"col-main") or contains(@class,"ContextTextEditor") or self::main or self::article]' );
	$best  = null;
	$bestn = 0;
	if ( $list ) {
		foreach ( $list as $n ) {
			$l = mb_strlen( trim( preg_replace( '/\s+/u', ' ', $n->textContent ) ) );
			if ( $l > $bestn ) {
				$bestn = $l;
				$best  = $n;
			}
		}
	}
	if ( $best && $bestn > 40 ) {
		$inner = '';
		foreach ( $best->childNodes as $c ) {
			$inner .= $dom->saveHTML( $c );
		}
		$inner   = preg_replace( '/(src|href)=(["\'])\//', '$1=$2https://itanet.ir/', $inner );
		$content = $inner;
	}
}

$postarr = array(
	'post_title'   => $title,
	'post_name'    => 'shops',
	'post_status'  => 'publish',
	'post_type'    => 'page',
	'post_content' => $content,
	'post_parent'  => 0,
);
if ( $shops_id ) {
	$postarr['ID'] = $shops_id;
	$shops_id      = wp_update_post( $postarr, true );
} else {
	$shops_id = wp_insert_post( $postarr, true );
}
$out['shops'] = array(
	'http'   => $code,
	'error'  => $err,
	'id'     => is_wp_error( $shops_id ) ? 0 : (int) $shops_id,
	'link'   => is_wp_error( $shops_id ) ? '' : get_permalink( $shops_id ),
	'status' => is_wp_error( $shops_id ) ? $shops_id->get_error_message() : 'ok',
);

// Enrich thin pages: prices & 2ghasemi — keep larger HTML chunk
function itanet_enrich( $page_id, $source_url ) {
	list( $code, $html, $err ) = itanet_get( $source_url, 60 );
	if ( $code < 200 || $code >= 400 || ! $html ) {
		return array( 'ok' => false, 'http' => $code, 'error' => $err );
	}
	$title = get_the_title( $page_id );
	if ( preg_match( '/<title[^>]*>(.*?)<\/title>/is', $html, $m ) ) {
		$title = trim( html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES, 'UTF-8' ) );
		$title = preg_replace( '/\s*\|\s*ایتانت\s*$/u', '', $title );
	}
	libxml_use_internal_errors( true );
	$dom = new DOMDocument();
	@$dom->loadHTML( '<?xml encoding="UTF-8">' . $html );
	$xpath = new DOMXPath( $dom );
	// Prefer larger containers including QuickTicket blocks
	$list = $xpath->query( '//body' );
	$body = ( $list && $list->length ) ? $list->item( 0 ) : null;
	$content = '';
	if ( $body ) {
		// remove header/nav/footer/scripts
		foreach ( array( 'script', 'style', 'noscript', 'header', 'footer', 'nav' ) as $tag ) {
			$kill = array();
			foreach ( $body->getElementsByTagName( $tag ) as $el ) {
				$kill[] = $el;
			}
			foreach ( $kill as $el ) {
				if ( $el->parentNode ) {
					$el->parentNode->removeChild( $el );
				}
			}
		}
		foreach ( $body->childNodes as $c ) {
			$content .= $dom->saveHTML( $c );
		}
		$content = preg_replace( '/(src|href)=(["\'])\//', '$1=$2https://itanet.ir/', $content );
		$content = preg_replace( '/url\(\//', 'url(https://itanet.ir/', $content );
	}
	wp_update_post( array(
		'ID'           => $page_id,
		'post_title'   => $title,
		'post_content' => $content,
	) );
	return array(
		'ok'    => true,
		'chars' => mb_strlen( wp_strip_all_tags( $content ) ),
		'title' => $title,
	);
}

$out['enrich'] = array(
	'prices'   => itanet_enrich( 5358, 'https://itanet.ir/prices/' ),
	'2ghasemi' => itanet_enrich( 5365, 'https://itanet.ir/2ghasemi' ),
);

flush_rewrite_rules( false );
echo wp_json_encode( $out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
