<?php
// music_playlist.php — يعيد JSON بقائمة ملفات /music
header('Content-Type: application/json; charset=utf-8');

$dir  = __DIR__ . '/music';
$base = 'music'; // URL نسبي من الجذر
$allowed = ['mp3','m4a','ogg','wav','flac'];

$out = [];
if (is_dir($dir)) {
  foreach (scandir($dir) as $f) {
    if ($f === '.' || $f === '..') continue;
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    if (in_array($ext, $allowed, true)) {
      $title = preg_replace('/[_-]+/',' ', pathinfo($f, PATHINFO_FILENAME));
      $out[] = [
        'src'   => rtrim($base,'/') . '/' . rawurlencode($f),
        'title' => $title,
        'file'  => $f,
      ];
    }
  }
}
usort($out, fn($a,$b)=> strnatcasecmp($a['file'],$b['file']));
echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
