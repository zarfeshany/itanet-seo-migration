<?php
require __DIR__ . "/wp-load.php";
if (($_GET["k"] ?? "") !== "dbg") { status_header(403); exit; }
header("Content-Type: application/json; charset=utf-8");
global $wpdb;
$slug = "کانفیگ-و-تنظیم-مودم-فیبر-نوری-هواوی";
$row = $wpdb->get_row($wpdb->prepare("SELECT ID, post_parent, post_name, post_status FROM {$wpdb->posts} WHERE ID=5367"));
$by = $wpdb->get_results($wpdb->prepare("SELECT ID, post_parent, post_name FROM {$wpdb->posts} WHERE post_name=%s", $slug));
$blog = $wpdb->get_row("SELECT ID, post_name FROM {$wpdb->posts} WHERE ID=3393");
echo wp_json_encode(["row"=>$row,"by_slug"=>$by,"blog"=>$blog,"resolver"=>function_exists("itanet_resolve_page_id_from_path")?itanet_resolve_page_id_from_path("blog/".$slug):"nofn"], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);