# Extract FTTH content and generate migration PHP
$ErrorActionPreference = 'Stop'
$baseDir = 'C:\Users\Itanet\Desktop\Itanet'
$html = Get-Content -Path "$baseDir\ftth-source.html" -Raw -Encoding UTF8

if ($html -match '<title>([^<]+)</title>') {
    $fullTitle = $Matches[1].Trim()
    $postTitle = ($fullTitle -split '\s*\|\s*')[0].Trim()
} else {
    throw 'Title not found'
}

function Get-MetaContent([string]$Html, [string]$Attr, [string]$Value) {
    if ($Html -match "<meta\s+$Attr=`"$([regex]::Escape($Value))`"\s+content=`"([^`"]*)`"") {
        return $Matches[1]
    }
    return ''
}

$metaDescription = Get-MetaContent $html 'name' 'description'
$ogTitle = Get-MetaContent $html 'property' 'og:title'
$ogDescription = Get-MetaContent $html 'property' 'og:description'
if (-not $ogDescription) { $ogDescription = Get-MetaContent $html 'name' 'og:description' }

if ($html -notmatch '(?s)<article\s+class="entry-detail">\s*<div\s+class="content-text\s+clearfix">(.*?)</div>\s*</article>') {
    throw 'Article content not found'
}
$postContent = $Matches[1].Trim()

# SEO description fallback from first paragraph text
$seoDescription = $metaDescription
if (-not $seoDescription) {
    $paragraphs = [regex]::Matches($postContent, '(?s)<p[^>]*>(.*?)</p>')
    foreach ($pm in $paragraphs) {
        $plain = [regex]::Replace($pm.Groups[1].Value, '<[^>]+>', '')
        $plain = [System.Net.WebUtility]::HtmlDecode($plain).Trim()
        if ($plain -and $plain -ne [char]0xA0 -and $plain.Length -gt 20) {
            if ($plain.Length -gt 160) { $plain = $plain.Substring(0, 157) + '...' }
            $seoDescription = $plain
            break
        }
    }
}

$data = [ordered]@{
    post_title = $postTitle
    meta_description = $seoDescription
    og_title = $ogTitle
    post_content = $postContent
}
$data | ConvertTo-Json -Depth 5 | Set-Content -Path "$baseDir\ftth-data.json" -Encoding UTF8

# Build PHP with heredoc-safe content embedding via base64
$contentB64 = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($postContent))
$titleEsc = $postTitle -replace "\\", "\\\\" -replace "'", "\\'"
$descEsc = $seoDescription -replace "\\", "\\\\" -replace "'", "\\'"

$php = @"
<?php
/**
 * One-shot FTTH page migration - DELETE AFTER USE
 */
header('Content-Type: application/json; charset=utf-8');

`$key = isset(`$_GET['k']) ? `$_GET['k'] : '';
if (`$key !== 'itanet-ftth-migrate-2026') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

require_once __DIR__ . '/wp-load.php';

`$post_title = '$titleEsc';
`$meta_description = '$descEsc';
`$post_content = base64_decode('$contentB64');

`$existing = get_page_by_path('ftth', OBJECT, 'page');
`$page_data = [
    'post_title'   => `$post_title,
    'post_name'    => 'ftth',
    'post_content' => `$post_content,
    'post_status'  => 'publish',
    'post_type'    => 'page',
    'post_author'  => 1,
];

if (`$existing) {
    `$page_data['ID'] = `$existing->ID;
    `$page_id = wp_update_post(`$page_data, true);
    `$action = 'updated';
} else {
    `$page_id = wp_insert_post(`$page_data, true);
    `$action = 'created';
}

if (is_wp_error(`$page_id)) {
    http_response_code(500);
    echo json_encode(['error' => `$page_id->get_error_message()]);
    exit;
}

`$seo = ['plugins' => []];

// Yoast SEO
if (defined('WPSEO_VERSION') || class_exists('WPSEO_Meta')) {
    update_post_meta(`$page_id, '_yoast_wpseo_title', `$post_title);
    update_post_meta(`$page_id, '_yoast_wpseo_metadesc', `$meta_description);
    update_post_meta(`$page_id, '_yoast_wpseo_meta-robots-noindex', '0');
    `$seo['plugins'][] = 'yoast';
}

// Rank Math
if (defined('RANK_MATH_VERSION') || class_exists('RankMath')) {
    update_post_meta(`$page_id, 'rank_math_title', `$post_title);
    update_post_meta(`$page_id, 'rank_math_description', `$meta_description);
    `$seo['plugins'][] = 'rankmath';
}

// Generic fallback
update_post_meta(`$page_id, '_wp_page_template', 'default');

clean_post_cache(`$page_id);
wp_cache_flush();

`$permalink = get_permalink(`$page_id);

echo json_encode([
    'success' => true,
    'action' => `$action,
    'page_id' => `$page_id,
    'permalink' => `$permalink,
    'title' => `$post_title,
    'seo' => `$seo,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

"@

Set-Content -Path "$baseDir\itanet-ftth-migrate.php" -Value $php -Encoding UTF8 -NoNewline
Write-Output "Title: $postTitle"
Write-Output "Content length: $($postContent.Length)"
Write-Output "SEO description: $($seoDescription.Substring(0, [Math]::Min(80, $seoDescription.Length)))..."
Write-Output "PHP written to itanet-ftth-migrate.php"
