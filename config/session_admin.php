<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/logout.php");
    exit();
}



if (!isset($_SESSION['usertype_id']) || !in_array($_SESSION['usertype_id'], [1, 5])) {
    header("Location: ../index.php");
    exit();
}
?>
