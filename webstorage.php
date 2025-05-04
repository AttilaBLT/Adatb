<?php
require_once 'php/connection.php';
require_once 'php/functions.php';

function createWebstorage($storage_space) {
    global $connect;
    try {
        $stmt = $connect->prepare("INSERT INTO ATTILA.Webstorage (storage_space) VALUES (?)");
        return $stmt->execute([$storage_space]);
    } catch (PDOException $e) {
        error_log("Webstorage creation error: " . $e->getMessage());
        return false;
    }
}

function updateWebstorage($id, $storage_space) {
    global $connect;
    try {
        $stmt = $connect->prepare("UPDATE ATTILA.Webstorage SET storage_space = ? WHERE id = ?");
        return $stmt->execute([$storage_space, $id]);
    } catch (PDOException $e) {
        error_log("Webstorage update error: " . $e->getMessage());
        return false;
    }
}

function deleteWebstorage($id) {
    global $connect;
    try {
        $stmt = $connect->prepare("DELETE FROM ATTILA.Webstorage WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("Webstorage deletion error: " . $e->getMessage());
        return false;
    }
}

function getWebstorage($id) {
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

function getAllWebstorages() {
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
        $storage_space = sanitizeInput($_POST['storage_space']);
        
        if (empty($storage_space)) {
            $error = "A tárhely méret megadása kötelező!";
        } else {
            if (isset($_POST['create'])) {
                if (createWebstorage($storage_space)) {
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $error = "Hiba történt a tárhely létrehozásakor!";
                }
            } else {
                $id = (int)$_POST['id'];
                if (updateWebstorage($id, $storage_space)) {
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
    if (deleteWebstorage($id)) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $error = "Hiba történt a tárhely törlésekor!";
    }
}

$editRow = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $editRow = getWebstorage($id);
}

$rows = getAllWebstorages();

printMenu();
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tárhelyek Kezelése</title>
    <style>
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input[type="text"] { width: 100%; padding: 8px; }
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
        <h1>Tárhelyek Kezelése</h1>

        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="form">
            <h2><?= isset($_GET['edit']) ? 'Tárhely Frissítése' : 'Új Tárhely Létrehozása' ?></h2>
            <input type="hidden" name="id" value="<?= $editRow['ID'] ?? '' ?>">
            
            <div class="form-group">
                <label for="storage_space">Tárhely méret:</label>
                <input type="text" id="storage_space" name="storage_space" required 
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
                    <th>Tárhely méret</th>
                    <th>Műveletek</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="2">Nincsenek tárhely bejegyzések.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
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