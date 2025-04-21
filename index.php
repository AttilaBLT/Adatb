<?php
require_once 'php/connection.php';
require_once 'php/functions.php';
printMenu();

$stmt = $connect->prepare("SELECT * FROM ATTILA.USERS");
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "Nincs adat a táblában.";
    } else {
        foreach ($rows as $row) {
            echo sprintf('<p>%s: %s, jelszó: %s</p>', $row['USERNAME'], $row['EMAIL'], $row['PASSWORD_HASH']);
        }
    }


    var_dump($_SESSION)

?>