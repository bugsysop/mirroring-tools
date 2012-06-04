<?php

$wget    = "/usr/bin/wget";		// Full path to wget
$dowget  = true;				// True: Execute wget, False: Only show which server is most up-to-date
$outpath = "./output";			// Path where everyting should be mirrored to
$verbose = true;				// More chatty output (set to false when adding this script to cron)
$debug   = false;				// Even more chatty output (set to true to see the output of the parsing routines)


// Don't touch anything below unless you know what you're doing

$list = file_get_contents("mirrors.txt");
$mostrecent = 0; $mostrecentserver = "";
foreach (split("\n", $list) as $line) {
  if (strlen($line) > 5) {
    $in = "";
    if (!strpos("http://", $line)) { $line = "http://".$line; }
    if ($s = parse_url($line)) {
      if (@$s['host']) {
        echo $verbose ? ((strlen($s['host']) > 30) ? str_pad(substr($s['host'], 0, 27)."...", 30, " ", STR_PAD_LEFT) : str_pad($s['host'], 30, " ", STR_PAD_LEFT)).": " : "";
        if ($fp = @fsockopen($s['host'], (@$s['port'] ? $s['port'] : 80), $err, $errstr, 5)) {
          $out = "GET ".(@$s['path'] ? $s['path'] : "/")." HTTP/1.1\r\n";
          $out .= "Host: www.wikileaks.org\r\n";
          $out .= "Connection: Close\r\n\r\n";
          fwrite($fp, $out);
          while (!feof($fp)) { $in .= fgets($fp, 128); }
          foreach (split("\n", $in) as $linein) { if (stripos($linein, "modified") != false) { $foundmtime = $linein; }}
          if (@$foundmtime) {
            $foundmtime = preg_match("/: (.*)/", $foundmtime, $rawmtime);
            $mtime = @strtotime($rawmtime[1]);
            echo $verbose ? date("d.m.Y H:i:s", $mtime)."\n" : "";
            if ($mtime > $mostrecent) {
              $mostrecent = $mtime;
              $mostrecentserver = $s;
            }
          } else {
            echo $verbose ? "Server does not send Last-Modified header, ignoring!\n" : "";
          }
          fclose($fp);
        } else {
          echo $verbose ? "Server is unreachable\n" : "";
        }
        unset($foundmtime);
      } else {
        echo $verbose ? "\n----- ERROR -----\nMalformed host: ".$line."\n\n" : "";
      }
    } else {
      echo $verbose ? "\n----- ERROR -----\nMalformed line: ".$line."\n\n" : "";
    }
  }
}

echo $verbose ? "\nDONE. The most up-to-date server is: ".$mostrecentserver['host']." (".date("d.m.Y H:i:s", $mostrecent).")\n\n" : "";

if ($dowget) {
  $cmd = $wget." -nH --convert-links --no-parent ".($verbose ? "" : "-a sync.log")." -P ".$outpath." -mirror ".$mostrecentserver['scheme']."://".$mostrecentserver['host'].@$mostrecentserver['path'];
  if ($verbose) {
    echo "Will start mirroring ".$mostrecentserver['host']." to ".$outpath." in 5 seconds using command:\n";
    echo $cmd."\n\n";
    echo "Last chance to press CTRL+C...\n\n";
    sleep(5);
  }
  echo $verbose ? "Executing: ".$cmd."\n\n" : "";
  passthru($cmd);
}

?>
