<?php
if(isset($_SESSION['user_id'])){
    $stmt = $pdo->prepare("SELECT userdetails_id FROM userdetails WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    if(!$stmt->fetch()){
        $_SESSION['warning_message'] = "Please set your user details first.";
        header('Location: edit_profile.php');
        exit();
    }
}
?>