<?php
require_once('php/functions.php');
require_once('php/connection.php');

printMenu();
isLoggedIn();

$felhasznaloid = $_SESSION['user']['id'];

$sql = "SELECT USER_ID, USERNAME, EMAIL, PASSWORD_HASH
        FROM ATTILA.USERS 
        WHERE USER_ID = :felhasznaloid";

$stmt = $connect->prepare($sql);
$stmt->bindParam(':felhasznaloid', $felhasznaloid, PDO::PARAM_INT);
$stmt->execute();

$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($result) {
    foreach ($result as $row) {
        $profilePage = file_get_contents("html/profile_editor.html");
        $tmp = str_replace('::felhasznalonev', $row['USERNAME'], $profilePage);
        $tmp = str_replace('::email', $row['EMAIL'], $tmp);
        $tmp = str_replace('::id', $row['USER_ID'], $tmp);

        echo $tmp;
    }
} else {
    echo "Nem tudom megjeleníteni a profilod!";
}