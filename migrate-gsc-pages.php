<?php
/**
 * One-shot: migrate GSC priority URLs from itanet.ir → tastebox WP pages.
 * DELETE after run. Key: ?k=itanet-gsc-migrate-2026
 */
require __DIR__ . '/wp-load.php';

$key = isset( $_GET['k'] ) ? $_GET['k'] : '';
if ( $key !== 'itanet-gsc-migrate-2026' ) {
	status_header( 403 );
	exit( 'forbidden' );
}

@set_time_limit( 300 );
header( 'Content-Type: application/json; charset=utf-8' );

$blog_parent = 3393; // existing /blog/ page

/**
 * Manifest: source path on itanet → WP page config
 * parent: null = top-level, 'blog' = child of /blog/
 */
$items = array(
	// Landing / static pages
	array( 'path' => '/prices/', 'slug' => 'prices', 'parent' => null ),
	array( 'path' => '/charging/', 'slug' => 'charging', 'parent' => null ),
	array( 'path' => '/contact/', 'slug' => 'contact', 'parent' => null ),
	array( 'path' => '/speed-test/', 'slug' => 'speed-test', 'parent' => null ),
	array( 'path' => '/agentsservice/', 'slug' => 'agentsservice', 'parent' => null ),
	array( 'path' => '/lows/', 'slug' => 'lows', 'parent' => null ),
	array( 'path' => '/kh/', 'slug' => 'kh', 'parent' => null ),
	array( 'path' => '/2ghasemi', 'slug' => '2ghasemi', 'parent' => null ),
	array( 'path' => '/shahin', 'slug' => 'shahin', 'parent' => null ),
	array( 'path' => '/shops/', 'slug' => 'shops', 'parent' => null ),
	// Blog posts (exact Persian slugs)
	array( 'path' => '/blog/کانفیگ-و-تنظیم-مودم-فیبر-نوری-هواوی/', 'slug' => 'کانفیگ-و-تنظیم-مودم-فیبر-نوری-هواوی', 'parent' => 'blog' ),
	array( 'path' => '/blog/راهنمای-خرید-بسته-اینترنت-ایرانسل-در-اپلیکیشن-ایرانسل-من', 'slug' => 'راهنمای-خرید-بسته-اینترنت-ایرانسل-در-اپلیکیشن-ایرانسل-من', 'parent' => 'blog' ),
	array( 'path' => '/blog/adsl', 'slug' => 'adsl', 'parent' => 'blog' ),
	array( 'path' => '/blog/رانژه-کردن-خط-تلفن-چیست', 'slug' => 'رانژه-کردن-خط-تلفن-چیست', 'parent' => 'blog' ),
	array( 'path' => '/blog/wifi/', 'slug' => 'wifi', 'parent' => 'blog' ),
	array( 'path' => '/blog/راهنمای-جامع-شارژ-اینترنت-مخابرات-در-5-دقیقه', 'slug' => 'راهنمای-جامع-شارژ-اینترنت-مخابرات-در-5-دقیقه', 'parent' => 'blog' ),
	array( 'path' => '/blog/راهنمای-خرید-اینترنت-فیبر-نوری-مخابرات-اصفهان', 'slug' => 'راهنمای-خرید-اینترنت-فیبر-نوری-مخابرات-اصفهان', 'parent' => 'blog' ),
	array( 'path' => '/blog/تفاوت-اینترنت-adsl-و-اینترنت-فیبر-نوری؛-کدام-را-انتخاب-کنیم/', 'slug' => 'تفاوت-اینترنت-adsl-و-اینترنت-فیبر-نوری؛-کدام-را-انتخاب-کنیم', 'parent' => 'blog' ),
	array( 'path' => '/blog/انواع-مودم-فیبر-نوری--معرفی-بهترین-مدل-ها/', 'slug' => 'انواع-مودم-فیبر-نوری--معرفی-بهترین-مدل-ها', 'parent' => 'blog' ),
	array( 'path' => '/blog/mac-address', 'slug' => 'mac-address', 'parent' => 'blog' ),
	array( 'path' => '/blog/همه-چیز-درباره-رابطه-اینترنت-فیبر-نوری-و-اینترنت-اشیاء/', 'slug' => 'همه-چیز-درباره-رابطه-اینترنت-فیبر-نوری-و-اینترنت-اشیاء', 'parent' => 'blog' ),
	array( 'path' => '/blog/اینترنت-5g', 'slug' => 'اینترنت-5g', 'parent' => 'blog' ),
	array( 'path' => '/blog/تأثیر-اینترنت-فیبر-نوری-بر-رشد-کسب-و-کارهای-آنلاین-و-اقتصاد-دیجیتال/', 'slug' => 'تأثیر-اینترنت-فیبر-نوری-بر-رشد-کسب-و-کارهای-آنلاین-و-اقتصاد-دیجیتال', 'parent' => 'blog' ),
	array( 'path' => '/blog/vps', 'slug' => 'vps', 'parent' => 'blog' ),
	// Legacy article paths
	array( 'path' => '/10806/article/', 'slug' => 'article', 'parent' => '10806' ),
	array( 'path' => '/moba/article/', 'slug' => 'article', 'parent' => 'moba' ),
);

function itanet_http_get( $url ) {
	$ch = curl_init( $url );
	curl_setopt_array( $ch, array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_TIMEOUT        => 45,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_USERAGENT      => 'ItanetMigrator/1.0',
		CURLOPT_ENCODING       => '',
	) );
	$body = curl_exec( $ch );
	$code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
	$err  = curl_error( $ch );
	curl_close( $ch );
	return array( $code, $body, $err );
}

function itanet_ensure_parent_page( $slug ) {
	$existing = get_posts( array(
		'name'           => $slug,
		'post_type'      => 'page',
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'post_parent'    => 0,
	) );
	if ( $existing ) {
		return (int) $existing[0]->ID;
	}
	$id = wp_insert_post( array(
		'post_title'   => $slug,
		'post_name'    => $slug,
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_content' => '',
		'post_parent'  => 0,
	), true );
	return is_wp_error( $id ) ? 0 : (int) $id;
}

function itanet_extract_title( $html ) {
	if ( preg_match( '/<title[^>]*>(.*?)<\/title>/is', $html, $m ) ) {
		$t = html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES, 'UTF-8' );
		$t = preg_replace( '/\s*\|\s*ایتانت\s*$/u', '', $t );
		$t = trim( $t );
		if ( $t !== '' ) {
			return $t;
		}
	}
	if ( preg_match( '/<h1[^>]*>(.*?)<\/h1>/is', $html, $m ) ) {
		return trim( html_entity_decode( wp_strip_all_tags( $m[1] ), ENT_QUOTES, 'UTF-8' ) );
	}
	return '';
}

function itanet_extract_content( $html ) {
	libxml_use_internal_errors( true );
	$dom = new DOMDocument();
	$loaded = @$dom->loadHTML( '<?xml encoding="UTF-8">' . $html );
	if ( ! $loaded ) {
		return '';
	}
	$xpath = new DOMXPath( $dom );

	$queries = array(
		'//*[contains(concat(" ", normalize-space(@class), " "), " entry-detail ")]',
		'//article',
		'//*[contains(concat(" ", normalize-space(@class), " "), " ContextTextEditor ")]',
		'//*[contains(concat(" ", normalize-space(@class), " "), " col-main ")]',
		'//main',
	);

	$node = null;
	foreach ( $queries as $q ) {
		$list = $xpath->query( $q );
		if ( $list && $list->length ) {
			// pick largest text node among matches
			$best     = null;
			$best_len = 0;
			foreach ( $list as $n ) {
				$text = trim( preg_replace( '/\s+/u', ' ', $n->textContent ) );
				$len  = mb_strlen( $text );
				if ( $len > $best_len ) {
					$best_len = $len;
					$best     = $n;
				}
			}
			if ( $best && $best_len > 80 ) {
				$node = $best;
				break;
			}
		}
	}

	if ( ! $node ) {
		// fallback: page-heading + following siblings area
		$h1 = $xpath->query( '//h1[contains(@class,"page-heading")] | //h1' );
		if ( $h1 && $h1->length ) {
			$node = $h1->item( 0 )->parentNode;
		}
	}

	if ( ! $node ) {
		return '';
	}

	// Remove scripts, styles, forms that are app-only noise (keep text wrappers)
	foreach ( array( 'script', 'style', 'noscript', 'iframe' ) as $tag ) {
		$kill = array();
		foreach ( $node->getElementsByTagName( $tag ) as $el ) {
			$kill[] = $el;
		}
		foreach ( $kill as $el ) {
			if ( $el->parentNode ) {
				$el->parentNode->removeChild( $el );
			}
		}
	}

	$inner = '';
	foreach ( $node->childNodes as $child ) {
		$inner .= $dom->saveHTML( $child );
	}
	if ( trim( wp_strip_all_tags( $inner ) ) === '' ) {
		$inner = $dom->saveHTML( $node );
	}

	// Rewrite relative URLs to absolute itanet.ir
	$inner = preg_replace( '/(src|href)=(["\'])\//', '$1=$2https://itanet.ir/', $inner );
	$inner = preg_replace( '/url\(\//', 'url(https://itanet.ir/', $inner );

	return trim( $inner );
}

function itanet_find_page( $slug, $parent_id ) {
	$args = array(
		'name'           => $slug,
		'post_type'      => 'page',
		'post_status'    => 'any',
		'posts_per_page' => 1,
		'post_parent'    => (int) $parent_id,
	);
	$found = get_posts( $args );
	return $found ? (int) $found[0]->ID : 0;
}

$results = array();
$ok      = 0;
$fail    = 0;

foreach ( $items as $item ) {
	$path   = $item['path'];
	$slug   = $item['slug'];
	$parent = $item['parent'];
	$url    = 'https://itanet.ir' . $path;

	$parent_id = 0;
	if ( $parent === 'blog' ) {
		$parent_id = $blog_parent;
	} elseif ( is_string( $parent ) && $parent !== '' ) {
		$parent_id = itanet_ensure_parent_page( $parent );
	}

	list( $code, $html, $err ) = itanet_http_get( $url );
	$row = array(
		'path'   => $path,
		'slug'   => $slug,
		'source' => $url,
		'http'   => $code,
	);

	if ( $code < 200 || $code >= 400 || ! $html ) {
		$row['status'] = 'fetch_failed';
		$row['error']  = $err ? $err : ( 'HTTP ' . $code );
		$fail++;
		$results[] = $row;
		continue;
	}

	$title   = itanet_extract_title( $html );
	$content = itanet_extract_content( $html );

	if ( $title === '' ) {
		$title = $slug;
	}
	if ( mb_strlen( trim( wp_strip_all_tags( $content ) ) ) < 40 ) {
		// Keep a minimal body so the URL still exists with title
		$content = '<h1>' . esc_html( $title ) . '</h1><p>محتوای این صفحه از نسخه اصلی ایتانت منتقل شده است. بخش‌های تعاملی سرویس ممکن است به سامانه CRM ارجاع شوند.</p>' . $content;
		$row['note'] = 'thin_content';
	}

	$existing_id = itanet_find_page( $slug, $parent_id );
	$postarr     = array(
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_content' => $content,
		'post_parent'  => $parent_id,
	);

	if ( $existing_id ) {
		$postarr['ID'] = $existing_id;
		$pid           = wp_update_post( $postarr, true );
		$row['action'] = 'updated';
	} else {
		$pid           = wp_insert_post( $postarr, true );
		$row['action'] = 'created';
	}

	if ( is_wp_error( $pid ) ) {
		$row['status'] = 'wp_error';
		$row['error']  = $pid->get_error_message();
		$fail++;
	} else {
		$row['status'] = 'ok';
		$row['id']     = (int) $pid;
		$row['link']   = get_permalink( $pid );
		$row['chars']  = mb_strlen( wp_strip_all_tags( $content ) );
		$ok++;
	}
	$results[] = $row;
}

// Flush rewrite rules so new hierarchical pages resolve
flush_rewrite_rules( false );

echo wp_json_encode( array(
	'ok'      => $ok,
	'fail'    => $fail,
	'total'   => count( $items ),
	'skipped' => array(
		'home'        => '/',
		'ftth_exists' => '/ftth/',
		'app'         => '/app/profiles',
		'fragments'   => '#blog*',
	),
	'results' => $results,
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
