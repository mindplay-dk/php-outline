<?php

preg_match(
	'/(\$[\w\d_]*)\s+from\s+(.*)\sto\s(.*(?=\sby\s)|.*)(\sby\s(.+))?/',
	"\$i from date('Y', time()) to date('Y')-60 + 100",
	$exp
);

echo "<pre>"; var_dump($exp);

$var = $from = $to = null; $by = 1;

$c = count($exp);

if ($c==4 || $c==6) list($var, $from, $to) = array($exp[1], $exp[2], $exp[3]);
if ($c==6) $by = $exp[5];

var_dump($var,$from,$to,$by);

?>