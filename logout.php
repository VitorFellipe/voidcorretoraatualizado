<?php
session_start();
session_unset();
session_destroy();
header("Location: teladelogin.html");
exit();
?>