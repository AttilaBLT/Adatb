<?php
require_once('php/functions.php');
require_once('php/connection.php');

printMenu();
isLoggedIn();

$felhasznaloid = $_SESSION['user']['id'];

$sql = "SELECT USERS.USER_ID, USERS.USERNAME, USERS.EMAIL
        FROM ATTILA.USERS
        WHERE USERS.USER_ID = :felhasznaloid";

$stmt = $connect->prepare($sql);
$stmt->bindParam(':felhasznaloid', $felhasznaloid, PDO::PARAM_INT);
$stmt->execute();

$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    $profilePage = file_get_contents("html/profile.html");
    $tmp = str_replace('::felhasznalonev', $result['USERNAME'], $profilePage);
    $tmp = str_replace('::email', $result['EMAIL'], $tmp);
    $tmp = str_replace('::id', $result['USER_ID'], $tmp);

    if (!empty($result['PICTURE'])) {
        $encodedImg = 'data:image/jpeg;base64,' . base64_encode($result['PICTURE']);
        $tmp = str_replace('::profilkep', $encodedImg, $tmp);
    } else {
        $tmp = str_replace('::profilkep', 'default-profile.png', $tmp); // Alapértelmezett kép
    }

    echo $tmp;
} else {
    echo "Nem tudom megjeleníteni a profilod!";
}

include 'html/footer.html';
?>