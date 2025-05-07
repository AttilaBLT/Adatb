<?php
require_once 'php/connection.php';
require_once 'php/functions.php';

function createSubscription($user_id, $service_id, $start_date, $end_date, $status) {
    global $connect;
    try {
        $sql = "
            DECLARE
                v_id NUMBER;
            BEGIN
                INSERT INTO ATTILA.Subscription (user_id, service_id, start_date, end_date, status)
                VALUES (:user_id, :service_id, TO_DATE(:start_date, 'YYYY-MM-DD'), 
                        CASE WHEN :end_date IS NULL OR :end_date = '' THEN NULL ELSE TO_DATE(:end_date, 'YYYY-MM-DD') END, :status)
                RETURNING id INTO v_id;
            END;
        ";
        $stmt = $connect->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':service_id', $service_id);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->bindParam(':status', $status);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Subscription creation error: " . $e->getMessage());
        return false;
    }
}

function updateSubscription($id, $user_id, $service_id, $start_date, $end_date, $status) {
    global $connect;
    try {
        $sql = "
            BEGIN
                UPDATE ATTILA.Subscription
                SET user_id = :user_id,
                    service_id = :service_id,
                    start_date = TO_DATE(:start_date, 'YYYY-MM-DD'),
                    end_date = CASE WHEN :end_date IS NULL OR :end_date = '' THEN NULL ELSE TO_DATE(:end_date, 'YYYY-MM-DD') END,
                    status = :status
                WHERE id = :id;
            END;
        ";
        $stmt = $connect->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':service_id', $service_id);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Subscription update error: " . $e->getMessage());
        return false;
    }
}

function deleteSubscription($id) {
    global $connect;
    try {
        $sql = "
            BEGIN
                DELETE FROM ATTILA.Subscription WHERE id = :id;
            END;
        ";
        $stmt = $connect->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Subscription deletion error: " . $e->getMessage());
        return false;
    }
}

function getSubscription($id) {
    global $connect;
    try {
        $sql = "SELECT * FROM ATTILA.Subscription WHERE id = :id";
        $stmt = $connect->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Subscription retrieval error: " . $e->getMessage());
        return false;
    }
}

function getAllSubscriptions() {
    global $connect;
    try {
        $sql = "SELECT S.ID, S.USER_ID, S.START_DATE, S.END_DATE, S.STATUS, S.SERVICE_ID,
                U.USERNAME, 
                (SELECT COUNT(*) 
                FROM ATTILA.SUBSCRIPTION 
                WHERE USER_ID = S.USER_ID) AS TOTAL_SUBSCRIPTIONS
                FROM ATTILA.SUBSCRIPTION S
                LEFT JOIN ATTILA.USERS U ON S.USER_ID = U.USER_ID
                ORDER BY S.ID";
        $stmt = $connect->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Subscription list retrieval error: " . $e->getMessage());
        return [];
    }
}

function getAllUsers() {
    global $connect;
    try {
        $sql = "SELECT user_id, username FROM ATTILA.Users ORDER BY user_id";
        $stmt = $connect->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("User list retrieval error: " . $e->getMessage());
        return [];
    }
}

function getAllServicesSimple() {
    global $connect;
    try {
        $sql = "SELECT s.id, s.service_type, s.price, v.server_specs, w.storage_space as webstorage_size
                FROM ATTILA.Service s
                LEFT JOIN ATTILA.VPS v ON s.vps_id = v.id
                LEFT JOIN ATTILA.Webstorage w ON s.webstorage_id = w.id
                ORDER BY s.id";
        $stmt = $connect->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Service list retrieval error: " . $e->getMessage());
        return [];
    }
}

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;
    $service_id = !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null;
    $start_date = sanitizeInput($_POST['start_date']);
    $end_date = !empty($_POST['end_date']) ? sanitizeInput($_POST['end_date']) : null;
    $status = sanitizeInput($_POST['status']);

    if (empty($user_id) || empty($service_id) || empty($start_date) || empty($status)) {
        $error = "Minden kötelező mezőt ki kell tölteni!";
    } else {
        if (isset($_POST['create'])) {
            if (createSubscription($user_id, $service_id, $start_date, $end_date, $status)) {
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $error = "Hiba történt az előfizetés létrehozásakor!";
            }
        } elseif (isset($_POST['update'])) {
            $id = (int)$_POST['id'];
            if (updateSubscription($id, $user_id, $service_id, $start_date, $end_date, $status)) {
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $error = "Hiba történt az előfizetés frissítésekor!";
            }
        }
    }
} elseif (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if (deleteSubscription($id)) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $error = "Hiba történt az előfizetés törlésekor!";
    }
}

$editRow = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $editRow = getSubscription($id);
}

$rows = getAllSubscriptions();
$userOptions = getAllUsers();
$serviceOptions = getAllServicesSimple();

if (function_exists('printMenu')) printMenu();
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Előfizetések Kezelése</title>
    <style>
        .container { max-width: 900px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group select { width: 100%; padding: 8px; }
        .btn { padding: 8px 15px; cursor: pointer; }
        .error { color: red; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f5f5f5; }
        .actions { white-space: nowrap; }
        .actions a { margin-right: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Előfizetések Kezelése</h1>

        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="form">
            <h2><?= isset($_GET['edit']) ? 'Előfizetés Frissítése' : 'Új Előfizetés Létrehozása' ?></h2>
            <input type="hidden" name="id" value="<?= $editRow['ID'] ?? '' ?>">

            <div class="form-group">
                <label for="user_id">Felhasználó:</label>
                <select id="user_id" name="user_id" required>
                    <option value="">-- Válasszon felhasználót --</option>
                    <?php foreach ($userOptions as $user): ?>
                        <option value="<?= $user['USER_ID'] ?>"
                            <?= isset($editRow['USER_ID']) && $editRow['USER_ID'] == $user['USER_ID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['USERNAME']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="service_id">Szolgáltatás:</label>
                <select id="service_id" name="service_id" required>
                    <option value="">-- Válasszon szolgáltatást --</option>
                    <?php foreach ($serviceOptions as $service): ?>
                        <option value="<?= $service['ID'] ?>"
                            <?= isset($editRow['SERVICE_ID']) && $editRow['SERVICE_ID'] == $service['ID'] ? 'selected' : '' ?>>
                            <?= !empty($service['SERVER_SPECS']) ? 'VPS: ' . htmlspecialchars($service['SERVER_SPECS']) : 'VPS: Nincs' ?> |
                            <?= !empty($service['WEBSTORAGE_SIZE']) ? 'Webstorage: ' . htmlspecialchars($service['WEBSTORAGE_SIZE']) . ' MB' : 'Webstorage: Nincs' ?> |
                            Ár: <?= htmlspecialchars($service['PRICE']) ?> Ft
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="start_date">Kezdő dátum:</label>
                <input type="date" id="start_date" name="start_date" required
                       value="<?= isset($editRow['START_DATE']) ? date('Y-m-d', strtotime($editRow['START_DATE'])) : '' ?>">
            </div>

            <div class="form-group">
                <label for="end_date">Lejárat dátuma:</label>
                <input type="date" id="end_date" name="end_date"
                       value="<?= isset($editRow['END_DATE']) && $editRow['END_DATE'] ? date('Y-m-d', strtotime($editRow['END_DATE'])) : '' ?>">
            </div>

            <div class="form-group">
                <label for="status">Státusz:</label>
                <select id="status" name="status" required>
                    <option value="">-- Válasszon státuszt --</option>
                    <option value="Aktív" <?= (isset($editRow['STATUS']) && $editRow['STATUS'] === 'Aktív') ? 'selected' : '' ?>>Aktív</option>
                    <option value="Inaktív" <?= (isset($editRow['STATUS']) && $editRow['STATUS'] === 'Inaktív') ? 'selected' : '' ?>>Inaktív</option>
                </select>
            </div>

            <?php if (isset($_GET['edit'])): ?>
                <input type="submit" name="update" value="Frissítés" class="btn">
                <a href="?" class="btn">Mégse</a>
            <?php else: ?>
                <input type="submit" name="create" value="Létrehozás" class="btn">
            <?php endif; ?>
        </form>

        <?php

            $allSubscriptions = getAllSubscriptions();
            echo sprintf('<p>Összes előfizetés: %d</p>', htmlspecialchars($allSubscriptions[0]['TOTAL_SUBSCRIPTIONS'])); 
        
        ?>

        <table>
            <thead>
                <tr>
                    <th>Felhasználó</th>
                    <th>Szolgáltatás</th>
                    <th>Kezdő dátum</th>
                    <th>Lejárat dátuma</th>
                    <th>Státusz</th>
                    <th>Műveletek</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="7">Nincsenek előfizetés bejegyzések.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <?php 
                        $serviceDetails = '';
                        foreach ($serviceOptions as $service) {
                            if ($service['ID'] == $row['SERVICE_ID']) {
                                $serviceDetails = (!empty($service['SERVER_SPECS']) ? 'VPS: ' . htmlspecialchars($service['SERVER_SPECS']) : 'VPS: Nincs') . ' | ' .
                                                (!empty($service['WEBSTORAGE_SIZE']) ? 'Webstorage: ' . htmlspecialchars($service['WEBSTORAGE_SIZE']) . ' GB' : 'Webstorage: Nincs') . ' | ' .
                                                'Ár: ' . htmlspecialchars($service['PRICE']) . ' Ft';
                                break;
                            }
                        }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['USERNAME']) ?></td>
                            <td><?= $serviceDetails ?></td>
                            <td><?= htmlspecialchars($row['START_DATE']) ?></td>
                            <td><?= htmlspecialchars($row['END_DATE']) ?></td>
                            <td><?= htmlspecialchars($row['STATUS']) ?></td>
                            <td class="actions">
                                <a href="?edit=<?= $row['ID'] ?>" class="btn">Szerkesztés</a>
                                <a href="?delete=<?= $row['ID'] ?>"
                                   onclick="return confirm('Biztosan törölni szeretnéd?')"
                                   class="btn">Törlés</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php include 'html/footer.html'; ?>