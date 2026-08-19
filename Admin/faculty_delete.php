<?php 
session_start();
if (!isset($_SESSION['email']) || !isset($_SESSION['isAdminLogin'])) {
    header("Location: admin_login.php");
    exit();
}

include 'Admin_init.php';
$EMAIL=$_GET[$FACULTY_EMAIL];

$deleteQuery=mysqli_query($con,"DELETE FROM $FACULTY_ADD WHERE $FACULTY_EMAIL='$EMAIL' ");
if($deleteQuery){
    ?>
    <script type="text/javascript">
        alert("Faculty Removed !");
        window.open("admin_panel.php","_self")
        </script>
    <?php
}else{
    echo "error";
}

?>