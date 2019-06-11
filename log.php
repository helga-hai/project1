<?php
$f = fopen("log.txt", "a");
fprintf($f, "%s\r\n", $_POST['log']);
fclose($f);

?>