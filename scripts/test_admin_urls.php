<?php
// Simulate the defensive checks implemented in VAPTSECURE_Hook_Driver

function check_null_byte($uri, $query, $body) {
    $decoded_query = urldecode($query);
    if (strpos($query, '%00') !== false) return 'null-byte in query';
    if (strpos($decoded_query, "\0") !== false) return 'null-byte in decoded query';
    if (strpos($uri, '%00') !== false) return 'null-byte in uri';
    if (strpos($body, "\0") !== false) return 'null-byte in body';
    return false;
}

function check_rest_filters($uri, $query, $body) {
    // This mirrors block_rest_api logic: runs only for URIs containing /wp-json
    if (strpos($uri, '/wp-json') === false) return 'not-rest';

    // whitelist patterns
    $whitelist_paths = ['^/wp-json/wp/v2', '^/wp-json/wp/v2/users/me', '^/wp-admin', '^/wp-login.php'];
    foreach ($whitelist_paths as $p) {
        if (preg_match('#' . $p . '#i', $uri)) return 'whitelisted by path';
        // also consider rest_route in query
        if (isset($query) && preg_match('#' . $p . '#i', $query)) return 'whitelisted by route';
    }

    // SQLi heuristic
    $sqli_pattern = '/\b(concat|union\s+select|select\s+\*|insert\s+into|delete\s+from|update\s+\w+)/i';
    if (preg_match($sqli_pattern, $query)) return 'sqli in query';
    if (preg_match($sqli_pattern, $body)) return 'sqli in body';

    return 'rest-allowed';
}

$tests = [
    ['uri' => '/wp-admin/', 'query' => '', 'body' => ''],
    ['uri' => '/wp-admin/admin-ajax.php', 'query' => '', 'body' => 'action=test'],
    ['uri' => '/wp-admin/admin-post.php', 'query' => '', 'body' => 'action=submit'],
    ['uri' => '/wp-login.php', 'query' => '', 'body' => ''],
    ['uri' => '/wp-cron.php', 'query' => '', 'body' => ''],
    ['uri' => '/xmlrpc.php', 'query' => '', 'body' => ''],
    ['uri' => '/wp-json/vaptsecure/v1/features/update', 'query' => '', 'body' => ''],
    ['uri' => '/wp-json/vaptsecure/v1/features/update', 'query' => 'rest_route=/vaptsecure/v1/features/update', 'body' => ''],
    ['uri' => '/wp-json/wp/v2/users/me', 'query' => '', 'body' => ''],
    ['uri' => '/wp-json/wp/v2/posts', 'query' => '', 'body' => '{"title":"hello"}'],
    ['uri' => '/?s=search+term', 'query' => 's=search+term', 'body' => ''],
    ['uri' => '/wp-json/vaptsecure/v1/test?param=1%00', 'query' => 'param=1%00', 'body' => ''],
    ['uri' => '/wp-json/vaptsecure/v1/test', 'query' => 'q=1%20union%20select%20password', 'body' => ''],
    ['uri' => '/wp-admin/admin.php?page=vaptsecure-domain-admin', 'query' => '', 'body' => ''],
];

foreach ($tests as $t) {
    $uri = $t['uri']; $query = $t['query']; $body = $t['body'];
    $null = check_null_byte($uri, $query, $body);
    $rest = check_rest_filters($uri, $query, $body);
    echo "URI: $uri\n";
    echo "  Null check: " . ($null ? $null : 'ok') . "\n";
    echo "  REST filter: $rest\n";
    echo "\n";
}

?>