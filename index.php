<?php
require_once 'php/connection.php';
require_once 'php/functions.php';
printMenu();

$stmt = $connect->prepare("SELECT * FROM ATTILA.FAQ");
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "Nincs adat a táblában.";
    } else {
        foreach ($rows as $row) {
            echo sprintf('<p>Kérdés:%s, Válasz: %s</p>', $row['QUESTION'], $row['ANSWER']);
        }
    }
?>