<?php
require_once("php/connection.php");
require_once("php/functions.php");

$profilId = $_SESSION['user']['id'];

$sql = "DELETE FROM ATTILA.USERS WHERE USER_ID = :profilId";
$stmt = $connect->prepare($sql);
$stmt->bindParam(':profilId', $profilId, PDO::PARAM_INT);
$stmt->execute();

session_destroy();
header("Location: index.php");
exit;