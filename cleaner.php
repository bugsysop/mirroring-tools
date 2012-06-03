<?php

$infile  = file_get_contents("input.txt");
$mirrors = file_get_contents("mirrors.txt");

preg_match_all("/http:\/\/(\S*)(\"|'|\s|\r|\n)/U", $infile, $urls);
$IUrls = $urls[1];

preg_match_all("/(.*)(\s|\r|\n)/", $mirrors, $urls);
$MUrls = $urls[1];
$MUrls = $MUrls;

$diff = array_unique(array_diff($IUrls, $MUrls));
sort($diff);

$fp = fopen("mirrors.txt", "a");
foreach ($diff as $url) {
  $url = trim($url);
  echo "Adding: ".$url."\n";
  fwrite($fp, $url."\n");
}
fclose($fp);

?>