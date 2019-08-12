<?php
$request = $_SERVER['SCRIPT_NAME'];
$request = str_replace("/index.php", ".php", $request);
header("Location: ".$request); /* Redirect browser */
exit();
