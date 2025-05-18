<?php
require_once 'php/connection.php';
require_once 'php/functions.php';
printMenu();

var_dump($_SESSION);

$stmt = $connect->prepare(" SELECT n.MESSAGE, n.CREATED_AT, s.END_DATE, (SELECT COUNT(*) 
    FROM ATTILA.NOTIFICATIONS 
    WHERE USER_ID = n.USER_ID) AS total_notifications
    FROM ATTILA.NOTIFICATIONS n
    LEFT JOIN ATTILA.SUBSCRIPTION s ON n.SUBSCRIPTION_ID = s.ID
    WHERE n.USER_ID = :user_id
    ORDER BY n.CREATED_AT DESC
");
$stmt->bindParam(':user_id', $_SESSION['user']['id'], PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<h2>Értesítések</h2>';
if (!empty($notifications)) {
    foreach ($notifications as $notification) {
        echo sprintf(
            '<p><strong>%s</strong> - %s',
            htmlspecialchars($notification['MESSAGE']),
            htmlspecialchars($notification['CREATED_AT'])
        );
        if (!empty($notification['END_DATE'])) {
            echo sprintf(' (Lejárat: %s)', htmlspecialchars($notification['END_DATE']));
        }
        echo '</p>';
    }
    
    echo sprintf('<p>Összes értesítés: %d</p>', htmlspecialchars($notifications[0]['TOTAL_NOTIFICATIONS']));
} else {
    echo '<p>Nincsenek új értesítések.</p>';
}

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

include 'html/footer.html';
?>