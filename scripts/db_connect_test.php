#!/usr/bin/env php
<?php
// Quick database connectivity test independent of full WordPress bootstrap.
// Usage (inside wordpress container):
//   php scripts/db_connect_test.php
// Or from host:
//   docker compose exec wordpress php scripts/db_connect_test.php
// Optional env overrides: DB_HOST, DB_USER, DB_PASSWORD, DB_NAME

function env_or($name, $fallback) {
    $v = getenv($name);
    return ($v === false || $v === '') ? $fallback : $v;
}

$host = env_or('DB_HOST', getenv('WORDPRESS_DB_HOST') ?: 'localhost');
// If running in docker and host still localhost, prefer service name db:3306
if (in_array($host, ['localhost','127.0.0.1','localhost:3306'], true) && file_exists('/.dockerenv') && getenv('WORDPRESS_DB_HOST') === false) {
    $host = 'db:3306';
}
$user = env_or('DB_USER', getenv('WORDPRESS_DB_USER') ?: 'wpuser');
$pass = env_or('DB_PASSWORD', getenv('WORDPRESS_DB_PASSWORD') ?: 'wpsecret');
$name = env_or('DB_NAME', getenv('WORDPRESS_DB_NAME') ?: 'wpdb');

[$hostOnly, $port] = (function($h){
    if (str_contains($h, ':')) {
        $parts = explode(':', $h, 2);
        return [$parts[0], (int)$parts[1]];
    }
    return [$h, 3306];
})($host);

echo "Attempting mysqli connect to $hostOnly:$port database '$name' as '$user'...\n";

$mysqli = @mysqli_init();
if (!$mysqli) {
    fwrite(STDERR, "Failed to init mysqli\n");
    exit(2);
}
@mysqli_options($mysqli, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
$t0 = microtime(true);
$ok = @$mysqli->real_connect($hostOnly, $user, $pass, $name, $port);
$dt = sprintf('%.3f', microtime(true)-$t0);
if (!$ok) {
    fwrite(STDERR, "FAILED ($dt s): ".mysqli_connect_errno()." ".mysqli_connect_error()."\n");
    // Suggest remedies
    $code = mysqli_connect_errno();
    if ($code === 2002) {
        fwrite(STDERR, "Hint: Host unreachable. If inside Docker, ensure service name 'db' resolves and container is healthy. If running on host, use 127.0.0.1:3307 per docker-compose port mapping.\n");
    } elseif ($code === 1045) {
        fwrite(STDERR, "Hint: Authentication error—check user/password.\n");
    } elseif ($code === 1049) {
        fwrite(STDERR, "Hint: Unknown database—ensure DB initialization ran.\n");
    }
    exit(1);
}
echo "SUCCESS ($dt s): Server version: ".$mysqli->server_info."\n";
$res = $mysqli->query('SELECT NOW() AS now');
if ($res) {
    $row = $res->fetch_assoc();
    echo "DB time: ".$row['now']."\n";
}
$mysqli->close();
exit(0);
