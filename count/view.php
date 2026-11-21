<?php
/* ポートフォリオの数を見るページ。setup.php が出した鍵つきURLで開く。直近60日を新しい順に出す。 */
date_default_timezone_set('Asia/Tokyo');
$dir = __DIR__ . '/data';
$kf = $dir . '/viewkey.txt';
$key = isset($_GET['k']) ? (string)$_GET['k'] : '';
if (!is_file($kf) || !hash_equals(trim(file_get_contents($kf)), $key)) { http_response_code(404); exit; }
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

$days = [];
for ($i = 0; $i < 3; $i++) {
    $f = $dir . '/' . date('Y-m', strtotime(date('Y-m-01') . " -$i month")) . '.json';
    if (is_file($f)) {
        $a = json_decode(file_get_contents($f), true);
        if (is_array($a)) $days += $a;
    }
}
krsort($days);
$days = array_slice($days, 0, 60, true);

$E = ['new' => 'はじめて開いた端末', 'open' => 'その日に開いた端末', 'click' => 'リンクを押した'];
$sum = function ($d, $e) { return isset($d[$e]) ? array_sum($d[$e]) : 0; };
$tot = function ($n) use ($days, $E, $sum) {
    $t = array_fill_keys(array_keys($E), 0);
    foreach (array_slice($days, 0, $n, true) as $d) foreach ($E as $e => $_) $t[$e] += $sum($d, $e);
    return $t;
};
$by = function ($e) use ($days) {
    $r = [];
    foreach ($days as $d) foreach (isset($d[$e]) ? $d[$e] : [] as $k => $n) $r[$k] = (isset($r[$k]) ? $r[$k] : 0) + $n;
    arsort($r);
    return $r;
};
$froms = $by('open');
$clicks = $by('click');
$L = ['base' => 'BASE（教材のお店）', 'mail' => 'メールで問い合わせ', 'note' => 'note', 'tango' => '英単語帳のおためし版', 'tsunagari' => 'つながりさがし', 'other' => 'そのほか'];
$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
?><!doctype html>
<html lang="ja"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>ポートフォリオの数</title>
<style>
body{font-family:system-ui,"Hiragino Sans","Yu Gothic",sans-serif;margin:0;padding:16px;background:#f4f5fb;color:#1b2448}
h1{font-size:1.1rem;margin:0 0 4px}p{margin:0 0 12px;color:#586280;font-size:.85rem}
table{border-collapse:collapse;width:100%;background:#fff;margin-bottom:18px;font-size:.9rem}
th,td{border:1px solid #d4d8e4;padding:6px 8px;text-align:right}th:first-child,td:first-child{text-align:left}
th{background:#eceef6;font-weight:600}h2{font-size:.95rem;margin:16px 0 6px}
</style></head><body>
<h1>ポートフォリオの数（/portfolio/）</h1>
<p>端末を見分ける番号・IPアドレス・名前は持っていません。同じ端末のその日2回目は数えないので、数字はおおよその人数です。</p>

<h2>まとめ</h2>
<table><tr><th></th><?php foreach ($E as $l) echo '<th>' . $h($l) . '</th>'; ?></tr>
<?php foreach ([7 => '直近7日', 30 => '直近30日', 60 => '直近60日'] as $n => $l) { $t = $tot($n);
  echo '<tr><td>' . $h($l) . '</td>'; foreach ($E as $e => $_) echo '<td>' . $t[$e] . '</td>'; echo '</tr>'; } ?>
</table>

<h2>どこから来たか（その日に開いた端末・直近60日）</h2>
<table><tr><th>印（?from= か、前のページ）</th><th>端末</th></tr>
<?php if (!$froms) echo '<tr><td colspan="2">まだありません</td></tr>';
foreach ($froms as $f => $n) echo '<tr><td>' . $h($f === '-' ? '印なし（直接・ブックマークなど）' : $f) . '</td><td>' . $n . '</td></tr>'; ?>
</table>

<h2>押されたリンク（直近60日）</h2>
<table><tr><th>リンク</th><th>回数</th></tr>
<?php if (!$clicks) echo '<tr><td colspan="2">まだありません</td></tr>';
foreach ($clicks as $k => $n) echo '<tr><td>' . $h(isset($L[$k]) ? $L[$k] : $k) . '</td><td>' . $n . '</td></tr>'; ?>
</table>

<h2>日ごと</h2>
<table><tr><th>日付</th><?php foreach ($E as $l) echo '<th>' . $h($l) . '</th>'; ?></tr>
<?php if (!$days) echo '<tr><td colspan="4">まだありません</td></tr>';
foreach ($days as $day => $d) { echo '<tr><td>' . $h($day) . '</td>'; foreach ($E as $e => $_) echo '<td>' . $sum($d, $e) . '</td>'; echo '</tr>'; } ?>
</table>
</body></html>
