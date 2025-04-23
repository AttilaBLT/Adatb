<?php
require_once 'php/connection.php';
require_once 'php/functions.php';
printMenu();

$stmt = $connect->prepare("SELECT * FROM ATTILA.FAQ");
    $stmt->execute();

    echo '<h2>GYIK</h2>';

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "Nincs adat a táblában.";
    } else {
        foreach ($rows as $row) {
            echo sprintf('<p><strong>Kérdés: </strong>%s, <strong>Válasz: </strong>%s</p>', $row['QUESTION'], $row['ANSWER']);

        }
    }
?>