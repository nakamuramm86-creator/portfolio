<?php
/* ポートフォリオの数え上げ（2026-11-22。英単語帳の count/hit.php と同じ作り）。
 * ページから1回ごとに合図が届き、日ごとの数を1つ増やす。
 * 持つのは「日付・何が起きたか・印」の数だけ。端末を見分ける番号・IPアドレス・名前は持たない。
 *
 * 何が起きたか（e）と、印（k）:
 *   new   … その端末ではじめて開いた。k＝どこから来たか（?from= の印、無ければ前のページのドメイン）
 *   open  … その端末でその日はじめて開いた。k＝同上
 *   click … その日はじめて、そのリンクを押した。k＝どのリンクか（base / mail / note / tango / tsunagari / other）
 */
date_default_timezone_set('Asia/Tokyo');
$dir = __DIR__ . '/data';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
$o = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($o !== '' && parse_url($o, PHP_URL_HOST) !== preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'])) {
    http_response_code(403); exit;
}
if (!is_dir($dir)) { http_response_code(503); exit; }   // setup.php をまだ開いていない

$j = json_decode(file_get_contents('php://input', false, null, 0, 512), true);
if (!is_array($j)) $j = [];
$e = isset($j['e']) ? $j['e'] : '';
if (!in_array($e, ['new', 'open', 'click'], true)) { http_response_code(400); exit; }
$k = isset($j['k']) ? strtolower(substr((string)$j['k'], 0, 32)) : '';
$k = preg_replace('/[^a-z0-9_\-]/', '', $k);
if ($k === '') $k = '-';

$day = date('Y-m-d');
$fp = fopen($dir . '/' . date('Y-m') . '.json', 'c+');
if (!$fp) { http_response_code(500); exit; }
flock($fp, LOCK_EX);
$raw = stream_get_contents($fp);
$a = json_decode($raw === '' ? '{}' : $raw, true);
if (!is_array($a)) $a = [];
// 印の種類が増えすぎないように、1日1種類につき30までにする（いたずらでファイルが太らないように）
if (isset($a[$day][$e]) && !isset($a[$day][$e][$k]) && count($a[$day][$e]) >= 30) $k = 'other';
$a[$day][$e][$k] = (isset($a[$day][$e][$k]) ? $a[$day][$e][$k] : 0) + 1;
ftruncate($fp, 0);
rewind($fp);
fwrite($fp, json_encode($a));
fflush($fp);
flock($fp, LOCK_UN);
fclose($fp);
http_response_code(204);
