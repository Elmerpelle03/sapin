<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/logout.php");
    exit();
}
if (!isset($_SESSION['usertype_id']) || $_SESSION['usertype_id'] != 5) {
    header("Location: ../index.php");
    exit();
}
?>
