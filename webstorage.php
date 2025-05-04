<?php
require_once 'php/connection.php';
require_once 'php/functions.php';

function createWebStorage($name, $description, $storage_space) {
    global $connect;
    try {
        $stmt = $connect->prepare("INSERT INTO ATTILA.Webstorage (name, description, storage_space) VALUES (?, ?, ?)");
        return $stmt->execute([$name, $description, $storage_space]);
    } catch (PDOException $e) {
        error_log("Webstorage creation error: " . $e->getMessage());
        return false;
    }
}

function updateWebStorage($id, $name, $description, $storage_space) {
    global $connect;
    try {
        $stmt = $connect->prepare("UPDATE ATTILA.Webstorage SET name = ?, description = ?, storage_space = ? WHERE id = ?");
        return $stmt->execute([$name, $description, $storage_space, $id]);
    } catch (PDOException $e) {
        error_log("Webstorage update error: " . $e->getMessage());
        return false;
    }
}

function deleteWebStorage($id) {
    global $connect;
    try {
        $stmt = $connect->prepare("DELETE FROM ATTILA.Webstorage WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("Webstorage deletion error: " . $e->getMessage());
        return false;
    }
}

function getWebStorage($id) {
    global $connect;
    try {
        $stmt = $connect->prepare("SELECT * FROM ATTILA.Webstorage WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Webstorage retrieval error: " . $e->getMessage());
        return false;
    }
}

function getAllWebStorage() {
    global $connect;
    try {
        $stmt = $connect->prepare("SELECT * FROM ATTILA.Webstorage ORDER BY id");
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
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        $storage_space = (int)$_POST['storage_space'];
        
        if (empty($name) || $storage_space <= 0) {
            $error = "A név és a tárhely mezők kitöltése kötelező, és a tárhelynek pozitív számnak kell lennie!";
        } else {
            if (isset($_POST['create'])) {
                if (createWebStorage($name, $description, $storage_space)) {
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $error = "Hiba történt a tárhely létrehozásakor!";
                }
            } else {
                $id = (int)$_POST['id'];
                if (updateWebStorage($id, $name, $description, $storage_space)) {
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $error = "Hiba történt a tárhely frissítésekor!";
                }
            }
        }
    }
} elseif (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if (deleteWebStorage($id)) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $error = "Hiba történt a tárhely törlésekor!";
    }
}

$editRow = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $editRow = getWebStorage($id);
}

$rows = getAllWebStorage();

printMenu();
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webstorage Kezelés</title>
    <style>
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea { width: 100%; padding: 8px; }
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
        <h1>Webstorage Kezelés</h1>

        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="form">
            <h2><?= isset($_GET['edit']) ? 'Tárhely Frissítése' : 'Új Tárhely Létrehozása' ?></h2>
            <input type="hidden" name="id" value="<?= $editRow['ID'] ?? '' ?>">
            
            <div class="form-group">
                <label for="name">Név:</label>
                <input type="text" id="name" name="name" required 
                       value="<?= $editRow['NAME'] ?? '' ?>">
            </div>
            
            <div class="form-group">
                <label for="description">Leírás:</label>
                <input type="text" id="description" name="description" 
                       value="<?= $editRow['DESCRIPTION'] ?? '' ?>">
            </div>
            
            <div class="form-group">
                <label for="storage_space">Tárhely (MB):</label>
                <input type="number" id="storage_space" name="storage_space" required min="1" 
                       value="<?= $editRow['STORAGE_SPACE'] ?? '' ?>">
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
                    <th>Név</th>
                    <th>Leírás</th>
                    <th>Tárhely (MB)</th>
                    <th>Műveletek</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="4">Nincsenek tárhely bejegyzések.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['NAME']) ?></td>
                            <td><?= htmlspecialchars($row['DESCRIPTION']) ?></td>
                            <td><?= htmlspecialchars($row['STORAGE_SPACE']) ?></td>
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
</body>
</html>