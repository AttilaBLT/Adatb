<?php
require_once 'php/connection.php';
require_once 'php/functions.php';

if (!isAdmin()) { 
    header("Location: index.php");
    exit(); 
}

function createService($price, $service_type, $vps_id, $webstorage_id) {
    global $connect;
    try {
        error_log("Creating service with params: price=$price, type=$service_type, vps=$vps_id, ws=$webstorage_id");
        $stmt = $connect->prepare("INSERT INTO ATTILA.Service (price, service_type, vps_id, webstorage_id) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$price, $service_type, $vps_id ?: null, $webstorage_id ?: null]);
        if (!$result) {
            error_log("Service creation failed: " . implode(", ", $stmt->errorInfo()));
        }
        return $result;
    } catch (PDOException $e) {
        error_log("Service creation error: " . $e->getMessage());
        error_log("SQL State: " . $e->getCode());
        error_log("Error Info: " . implode(", ", $stmt->errorInfo()));
        return false;
    }
}

function updateService($id, $price, $service_type, $vps_id, $webstorage_id) {
    global $connect;
    try {
        $stmt = $connect->prepare("UPDATE ATTILA.Service SET price = ?, service_type = ?, vps_id = ?, webstorage_id = ? WHERE id = ?");
        return $stmt->execute([$price, $service_type, $vps_id ?: null, $webstorage_id ?: null, $id]);
    } catch (PDOException $e) {
        error_log("Service update error: " . $e->getMessage());
        return false;
    }
}

function deleteService($id) {
    global $connect;
    try {
        $stmt = $connect->prepare("DELETE FROM ATTILA.Service WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("Service deletion error: " . $e->getMessage());
        return false;
    }
}

function getService($id) {
    global $connect;
    try {
        $stmt = $connect->prepare("SELECT * FROM ATTILA.Service WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Service retrieval error: " . $e->getMessage());
        return false;
    }
}

function getAllServices() {
    global $connect;
    try {
        $stmt = $connect->prepare("SELECT S.ID, S.SERVICE_TYPE, S.PRICE, 
                                        V.SERVER_SPECS, 
                                        W.STORAGE_SPACE AS WEBSTORAGE_SIZE,
                                        (SELECT COUNT(*) 
                                        FROM ATTILA.SERVICE 
                                        WHERE SERVICE_TYPE = S.SERVICE_TYPE) AS TOTAL_SERVICES
                                        FROM ATTILA.SERVICE S
                                        LEFT JOIN ATTILA.VPS V ON S.VPS_ID = V.ID
                                        LEFT JOIN ATTILA.WEBSTORAGE W ON S.WEBSTORAGE_ID = W.ID
                                        ORDER BY S.ID");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Service list retrieval error: " . $e->getMessage());
        return [];
    }
}

function getVPSServers() {
    global $connect;
    try {
        $stmt = $connect->prepare("SELECT id, server_specs FROM ATTILA.VPS ORDER BY id");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("VPS list retrieval error: " . $e->getMessage());
        return [];
    }
}

function getWebStorageOptions() {
    global $connect;
    try {
        $stmt = $connect->prepare("SELECT id, storage_space FROM ATTILA.Webstorage ORDER BY id");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Webstorage list retrieval error: " . $e->getMessage());
        return [];
    }
}

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create']) || isset($_POST['update'])) {
        $price = (float)$_POST['price'];
        $service_type = sanitizeInput($_POST['service_type']);
        $vps_id = !empty($_POST['vps_id']) ? (int)$_POST['vps_id'] : null;
        $webstorage_id = !empty($_POST['webstorage_id']) ? (int)$_POST['webstorage_id'] : null;
        
        error_log("Form submission: price=$price, type=$service_type, vps=$vps_id, ws=$webstorage_id");
        
        if (empty($service_type)) {
            $error = "A szolgáltatás típusa megadása kötelező!";
        } elseif ($price <= 0) {
            $error = "Az árnak pozitívnak kell lennie!";
        } else {
            if (isset($_POST['create'])) {
                if (createService($price, $service_type, $vps_id, $webstorage_id)) {
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $error = "Hiba történt a szolgáltatás létrehozásakor! Kérjük, ellenőrizze a naplófájlt a részletes hibaüzenetért.";
                }
            } else {
                $id = (int)$_POST['id'];
                if (updateService($id, $price, $service_type, $vps_id, $webstorage_id)) {
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $error = "Hiba történt a szolgáltatás frissítésekor!";
                }
            }
        }
    }
} elseif (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if (deleteService($id)) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $error = "Hiba történt a szolgáltatás törlésekor!";
    }
}

$editRow = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $editRow = getService($id);
}

$rows = getAllServices();
$vpsOptions = getVPSServers();
$webstorageOptions = getWebStorageOptions();

printMenu();
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Szolgáltatások Kezelése</title>
    <style>
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input[type="number"],
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
        <h1>Szolgáltatások Kezelése</h1>

        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="form">
            <h2><?= isset($_GET['edit']) ? 'Szolgáltatás Frissítése' : 'Új Szolgáltatás Létrehozása' ?></h2>
            <input type="hidden" name="id" value="<?= $editRow['ID'] ?? '' ?>">
            
            <div class="form-group">
                <label for="price">Ár:</label>
                <input type="number" id="price" name="price" step="0.01" min="0" required 
                       value="<?= $editRow['PRICE'] ?? '' ?>">
            </div>
            
            <div class="form-group">
                <label for="service_type">Szolgáltatás típusa:</label>
                <select id="service_type" name="service_type" required>
                    <option value="">-- Válasszon típust --</option>
                    <option value="VPS" <?= isset($editRow['SERVICE_TYPE']) && $editRow['SERVICE_TYPE'] == 'VPS' ? 'selected' : '' ?>>VPS</option>
                    <option value="Webstorage" <?= isset($editRow['SERVICE_TYPE']) && $editRow['SERVICE_TYPE'] == 'Webstorage' ? 'selected' : '' ?>>Webstorage</option>
                    <option value="Bundle" <?= isset($editRow['SERVICE_TYPE']) && $editRow['SERVICE_TYPE'] == 'Bundle' ? 'selected' : '' ?>>Bundle</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="vps_id">VPS:</label>
                <select id="vps_id" name="vps_id">
                    <option value="">-- Válasszon VPS-t --</option>
                    <?php foreach ($vpsOptions as $vps): ?>
                        <option value="<?= $vps['ID'] ?>" 
                            <?= isset($editRow['VPS_ID']) && $editRow['VPS_ID'] == $vps['ID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($vps['SERVER_SPECS']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="webstorage_id">Webstorage:</label>
                <select id="webstorage_id" name="webstorage_id">
                    <option value="">-- Válasszon Webstorage-t --</option>
                    <?php foreach ($webstorageOptions as $ws): ?>
                        <option value="<?= $ws['ID'] ?>" 
                            <?= isset($editRow['WEBSTORAGE_ID']) && $editRow['WEBSTORAGE_ID'] == $ws['ID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ws['STORAGE_SPACE']) ?>
                        </option>
                    <?php endforeach; ?>
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

        $allVPS = getAllServices();
        echo sprintf('<p>Összes VPS: %d</p>', htmlspecialchars($allVPS[0]['TOTAL_SERVICES'])); 

        ?>

        <table>
            <thead>
                <tr>
                    <th>Ár</th>
                    <th>Szolgáltatás típusa</th>
                    <th>VPS</th>
                    <th>Webstorage</th>
                    <th>Műveletek</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="5">Nincsenek szolgáltatás bejegyzések.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['PRICE']) ?></td>
                            <td><?= htmlspecialchars($row['SERVICE_TYPE']) ?></td>
                            <td><?= htmlspecialchars($row['SERVER_SPECS'] ?? 'Nincs') ?></td>
                            <td><?= htmlspecialchars($row['WEBSTORAGE_SIZE'] ?? 'Nincs') ?></td>
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