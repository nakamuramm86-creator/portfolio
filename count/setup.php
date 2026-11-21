<?php
/* 最初に1回だけブラウザで開く。数をためる場所を作り、外から読めないようにして、
 * 数を見るページ（view.php）の鍵つきURLを出す。何度開いてもよい（鍵は作り直さない）。 */
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
$dir = __DIR__ . '/data';

echo "ポートフォリオの数え上げの準備\n\n";
echo "○ PHP " . PHP_VERSION . "\n";

if (!is_dir($dir)) @mkdir($dir, 0700);
$ht = $dir . '/.htaccess';
if (!is_file($ht)) {
    @file_put_contents($ht, "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n");
}
$ok = is_dir($dir) && is_writable($dir) && is_file($ht);
echo ($ok ? "○ " : "× ") . "data フォルダ" . ($ok ? "（外から読めない設定つき）" : " を作れない。count フォルダの書き込み権限を確かめる") . "\n";
if (!$ok) exit;

$kf = $dir . '/viewkey.txt';
if (!is_file($kf)) file_put_contents($kf, bin2hex(random_bytes(8)), LOCK_EX);
$key = trim(file_get_contents($kf));
echo "○ 数を見るページの鍵\n\n";

$host = preg_replace('/[^A-Za-z0-9.\-]/', '', $_SERVER['HTTP_HOST']);
$path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
echo "準備できました。数を見るページ（このURLは人に渡さない）：\n";
echo "https://" . $host . $path . "/view.php?k=" . $key . "\n";
