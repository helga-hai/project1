 <?php
$f = fopen("count_file.txt", "a");
fprintf($f, "%s\r\n", $_POST['LastCount']);
fclose($f);

?>