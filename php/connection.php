<?php

$tns = "
(DESCRIPTION = 
    (ADDRESS = (PROTOCOL = TCP)(HOST = localhost)(PORT = 1521))
    (CONNECT_DATA =
      (SERVER = DEDICATED)
      (SERVICE_NAME = XE)
    )
  )";
$username = "ATTILA";
$password = "Attila";
$db = "oci:dbname=" . $tns;

try {
    $connect = new PDO($db, $username, $password);
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Hiba: " . $e->getMessage();
}
?>




