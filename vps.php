<?php
require_once 'php/connection.php';
require_once 'php/functions.php';

if (!isAdmin()) { 
    header("Location: index.php");
    exit(); 
}

function createVPS($server_specs) {
    global $connect;
    try {
        $stmt = $connect->prepare("INSERT INTO ATTILA.VPS (server_specs) VALUES (?)");
        return $stmt->execute([$server_specs]);
    } catch (PDOException $e) {
        error_log("VPS creation error: " . $e->getMessage());
        return false;
    }
}

function updateVPS($id, $server_specs) {
    global $connect;
    try {
        $stmt = $connect->prepare("UPDATE ATTILA.VPS SET server_specs = ? WHERE id = ?");
        return $stmt->execute([$server_specs, $id]);
    } catch (PDOException $e) {
        error_log("VPS update error: " . $e->getMessage());
        return false;
    }
}

function deleteVPS($id) {
    global $connect;
    try {
        $stmt = $connect->prepare("DELETE FROM ATTILA.VPS WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("VPS deletion error: " . $e->getMessage());
        return false;
    }
}

function getVPS($id) {
    global $connect;
    try {
        $stmt = $connect->prepare("SELECT * FROM ATTILA.VPS WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("VPS retrieval error: " . $e->getMessage());
        return false;
    }
}

function getAllVPS() {
    global $connect;
    try {
        $stmt = $connect->prepare("SELECT * FROM ATTILA.VPS ORDER BY id");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("VPS list retrieval error: " . $e->getMessage());
        return [];
    }
}

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create']) || isset($_POST['update'])) {
        $server_specs = sanitizeInput($_POST['server_specs']);
        
        if (empty($server_specs)) {
            $error = "A szerver specifikáció megadása kötelező!";
        } else {
            if (isset($_POST['create'])) {
                if (createVPS($server_specs)) {
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $error = "Hiba történt a VPS létrehozásakor!";
                }
            } else {
                $id = (int)$_POST['id'];
                if (updateVPS($id, $server_specs)) {
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $error = "Hiba történt a VPS frissítésekor!";
                }
            }
        }
    }
} elseif (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if (deleteVPS($id)) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $error = "Hiba történt a VPS törlésekor!";
    }
}

$editRow = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $editRow = getVPS($id);
}

$rows = getAllVPS();

printMenu();
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VPS Kezelése</title>
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
        <h1>VPS Kezelése</h1>

        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="form">
            <h2><?= isset($_GET['edit']) ? 'VPS Frissítése' : 'Új VPS Létrehozása' ?></h2>
            <input type="hidden" name="id" value="<?= $editRow['ID'] ?? '' ?>">
            
            <div class="form-group">
                <label for="server_specs">Szerver specifikáció:</label>
                <input type="text" id="server_specs" name="server_specs" required 
                       value="<?= $editRow['SERVER_SPECS'] ?? '' ?>">
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
                    <th>Szerver specifikáció</th>
                    <th>Műveletek</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="2">Nincsenek VPS bejegyzések.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['SERVER_SPECS']) ?></td>
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