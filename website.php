<?php
require_once 'php/connection.php';
require_once 'php/functions.php';

function createWebsite($user_id, $webstorage_id, $address) {
    global $connect;
    try {
        $stmt = $connect->prepare("INSERT INTO ATTILA.WEBSITE (USER_ID, WEBSTORAGE_ID, ADDRESS) VALUES (?, ?, ?)");
        return $stmt->execute([$user_id, $webstorage_id, $address]);
    } catch (PDOException $e) {
        error_log("Website creation error: " . $e->getMessage());
        return false;
    }
}

function updateWebsite($id, $user_id, $webstorage_id, $address) {
    global $connect;
    try {
        $stmt = $connect->prepare("UPDATE ATTILA.WEBSITE SET USER_ID = ?, WEBSTORAGE_ID = ?, ADDRESS = ? WHERE ID = ?");
        return $stmt->execute([$user_id, $webstorage_id, $address, $id]);
    } catch (PDOException $e) {
        error_log("Website update error: " . $e->getMessage());
        return false;
    }
}

function deleteWebsite($id) {
    global $connect;
    try {
        $stmt = $connect->prepare("DELETE FROM ATTILA.WEBSITE WHERE ID = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("Website deletion error: " . $e->getMessage());
        return false;
    }
}

function getWebsite($id) {
    global $connect;
    try {
        $stmt = $connect->prepare("SELECT * FROM ATTILA.WEBSITE WHERE ID = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Website retrieval error: " . $e->getMessage());
        return false;
    }
}

function getAllWebsites() {
    global $connect;
    try {
        $stmt = $connect->prepare("SELECT w.*, ws.STORAGE_SPACE, u.USERNAME 
                                 FROM ATTILA.WEBSITE w 
                                 LEFT JOIN ATTILA.WEBSTORAGE ws ON w.WEBSTORAGE_ID = ws.ID 
                                 LEFT JOIN ATTILA.USERS u ON w.USER_ID = u.USER_ID
                                 ORDER BY w.ID");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Website list retrieval error: " . $e->getMessage());
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

function getWebstorages() {
    global $connect;
    try {
        $stmt = $connect->prepare("SELECT id, storage_space FROM ATTILA.WEBSTORAGE ORDER BY id");
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
        $webstorage_id = (int)$_POST['webstorage_id'];
        $address = sanitizeInput($_POST['address']);
        
        if (empty($address) || $webstorage_id <= 0) {
            $error = "A cím és a szerver mezők kitöltése kötelező!";
        } else {
            if (!isset($_SESSION['user'])) {
                $error = "Nincs bejelentkezve felhasználó!";
            } else {
                $currentUser = $_SESSION['user'];
                if (!isset($currentUser['id'])) {
                    $error = "Hibás felhasználói adatok!";
                } else {
                    if (isset($_POST['create'])) {
                        if (createWebsite($currentUser['id'], $webstorage_id, $address)) {
                            header("Location: " . $_SERVER['PHP_SELF']);
                            exit();
                        } else {
                            $error = "Hiba történt a weboldal létrehozásakor!";
                        }
                    } else {
                        $id = (int)$_POST['id'];
                        if (updateWebsite($id, $currentUser['id'], $webstorage_id, $address)) {
                            header("Location: " . $_SERVER['PHP_SELF']);
                            exit();
                        } else {
                            $error = "Hiba történt a weboldal frissítésekor!";
                        }
                    }
                }
            }
        }
    }
} elseif (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if (deleteWebsite($id)) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $error = "Hiba történt a weboldal törlésekor!";
    }
}

$editRow = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $editRow = getWebsite($id);
}

$rows = getAllWebsites();
$vpsOptions = getVPSServers();
$webstorageOptions = getWebstorages();

printMenu();
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weboldalak Kezelése</title>
    <style>
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input[type="text"],
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
        <h1>Weboldalak Kezelése</h1>

        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="form">
            <h2><?= isset($_GET['edit']) ? 'Weboldal Frissítése' : 'Új Weboldal Létrehozása' ?></h2>
            <input type="hidden" name="id" value="<?= $editRow['ID'] ?? '' ?>">
            
            <div class="form-group">
                <label for="webstorage_id">Webtárhely:</label>
                <select id="webstorage_id" name="webstorage_id" required>
                    <option value="">-- Válasszon ki egy webtárhelyet --</option>
                    <?php foreach ($webstorageOptions as $ws): ?>
                        <option value="<?= $ws['ID'] ?>"
                            <?= isset($editRow['WEBSTORAGE_ID']) && $editRow['WEBSTORAGE_ID'] == $ws['ID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ws['STORAGE_SPACE']) ?> MB
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="address">Domain:</label>
                <input type="text" id="address" name="address" required 
                       value="<?= $editRow['ADDRESS'] ?? '' ?>">
            </div>
            
            <?php if (isset($_GET['edit'])): ?>
                <input type="submit" name="update" value="Frissítés" class="btn">
                <a href="?" class="btn">Mégse</a>
            <?php else: ?>
                <input type="submit" name="create" value="Létrehozás" class="btn">
            <?php endif; ?>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Felhasználó</th>
                    <th>Webtárhely</th>
                    <th>Cím</th>
                    <th>Műveletek</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="4">Nincsenek weboldal bejegyzések.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['USERNAME']) ?></td>
                            <td><?= htmlspecialchars($row['STORAGE_SPACE'] ?? 'Nincs') ?> MB</td>
                            <td><?= htmlspecialchars($row['ADDRESS']) ?></td>
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