<?php 

if(isset($_SESSION['usertype_id']) && $_SESSION['usertype_id'] == 4){
    header("Location: courier/index.php");
    exit();
}

?>