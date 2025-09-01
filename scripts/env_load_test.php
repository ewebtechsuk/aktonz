#!/usr/bin/env php
<?php
// Simple harness to test the environment variable load / override logic
// implemented at the top of wp-config.php without fully bootstrapping WordPress.
// It re-implements only the .env / .env.deploy reading snippet so we can
// validate precedence, unreadable files, and parse errors.

error_reporting(E_ALL);

function load_env_pair(string $baseDir): array {
    $log = [];
    $env_primary = $baseDir.'/.env';
    if (file_exists($env_primary)) {
        if (is_readable($env_primary)) {
            $parsed = @parse_ini_file($env_primary);
            if ($parsed !== false) {
                foreach ($parsed as $key => $value) {
                    if (getenv($key) === false) {
                        putenv("$key=$value");
                        $_ENV[$key] = $value;
                        $log[] = "SET (base) $key=$value";
                    } else {
                        $log[] = "SKIP existing (base) $key";
                    }
                }
            } else {
                $log[] = 'ERROR parse .env';
            }
        } else {
            $log[] = '.env exists but unreadable';
        }
    } else {
        $log[] = '.env missing';
    }
    $env_deploy = $baseDir.'/.env.deploy';
    if (file_exists($env_deploy)) {
        if (is_readable($env_deploy)) {
            $parsedDeploy = @parse_ini_file($env_deploy);
            if ($parsedDeploy !== false) {
                foreach ($parsedDeploy as $key => $value) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $log[] = "OVERRIDE (deploy) $key=$value";
                }
            } else {
                $log[] = 'ERROR parse .env.deploy';
            }
        } else {
            $log[] = '.env.deploy exists but unreadable';
        }
    } else {
        $log[] = '.env.deploy missing';
    }
    return $log;
}

function print_section(string $title) {
    echo "\n==== $title ====\n";
}

$baseDir = getcwd();
$backup = [];
foreach (['.env', '.env.deploy'] as $f) {
    if (file_exists($f)) {
        $tmp = $f.'.bak_'.date('YmdHis');
        if (!rename($f, $tmp)) {
            fwrite(STDERR, "Failed to backup existing $f; aborting to avoid data loss.\n");
            exit(1);
        }
        $backup[$f] = $tmp;
    }
}

// Helper to reset env variables touched between scenarios.
function clear_vars(array $names) {
    foreach ($names as $n) {
        putenv($n); // unset by setting empty? Actually putenv without value removes if just NAME ; safer use putenv("$n=");
        putenv("$n");
        unset($_ENV[$n]);
    }
}

$varsUsed = ['DB_NAME','CUSTOM','NEWVAR','PERSIST','PARSE_ERR','OVERRIDE_CHECK'];

// Scenario A: Only .env
file_put_contents('.env', "DB_NAME=from_env\nCUSTOM=one\nPERSIST=keep\nOVERRIDE_CHECK=base\n");
print_section('Scenario A: only .env present');
clear_vars($varsUsed);
foreach (load_env_pair($baseDir) as $line) echo $line, "\n";
echo 'DB_NAME='.getenv('DB_NAME')."\n";
echo 'CUSTOM='.getenv('CUSTOM')."\n";
echo 'NEWVAR='.var_export(getenv('NEWVAR'), true)."\n";

// Scenario B: .env + .env.deploy override
file_put_contents('.env.deploy', "DB_NAME=from_deploy\nNEWVAR=two\nOVERRIDE_CHECK=deploy\n");
print_section('Scenario B: .env + .env.deploy (override)');
clear_vars($varsUsed);
// Pre-set PERSIST to show base respects existing
putenv('PERSIST=preexisting');
foreach (load_env_pair($baseDir) as $line) echo $line, "\n";
echo 'DB_NAME='.getenv('DB_NAME')."\n"; // expect from_deploy
echo 'CUSTOM='.getenv('CUSTOM')."\n";   // expect one
echo 'NEWVAR='.getenv('NEWVAR')."\n";   // expect two
echo 'PERSIST='.getenv('PERSIST')."\n"; // expected preexisting (not overwritten by base) unless deploy sets
echo 'OVERRIDE_CHECK='.getenv('OVERRIDE_CHECK')."\n"; // expect deploy

// Scenario C: unreadable .env.deploy
chmod('.env.deploy', 0000);
print_section('Scenario C: unreadable .env.deploy');
clear_vars($varsUsed);
foreach (load_env_pair($baseDir) as $line) echo $line, "\n";
echo 'DB_NAME='.getenv('DB_NAME')."\n"; // expect from_env
chmod('.env.deploy', 0644);

// Scenario D: parse error in .env.deploy
file_put_contents('.env.deploy', "BADLINE WITHOUT EQUALS\nDB_NAME=good_after_bad\n");
print_section('Scenario D: parse error in .env.deploy');
clear_vars($varsUsed);
foreach (load_env_pair($baseDir) as $line) echo $line, "\n";
echo 'DB_NAME='.getenv('DB_NAME')."\n"; // expect from_env (no override due parse fail)

// Cleanup test artifacts
@unlink('.env');
@unlink('.env.deploy');
foreach ($backup as $orig => $bak) {
    // restore originals
    rename($bak, $orig);
}

print_section('Done');
exit(0);
