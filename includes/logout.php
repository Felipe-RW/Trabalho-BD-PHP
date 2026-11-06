<?php

session_start();

$_SESSION = [];

session_destroy();

header('Location: /Trabalho-BD-PHP/index.html');
exit();

?>