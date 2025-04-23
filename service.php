<?php
require_once 'php/connection.php';
require_once 'php/functions.php';
printMenu();

$vpsStmt = $connect->prepare("SELECT id, server_specs FROM ATTILA.VPS ORDER BY id");
$vpsStmt->execute();
$vpsOptions = $vpsStmt->fetchAll(PDO::FETCH_ASSOC);

$webstorageStmt = $connect->prepare("SELECT id, storage_space FROM ATTILA.Webstorage ORDER BY id");
$webstorageStmt->execute();
$webstorageOptions = $webstorageStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $stmt = $connect->prepare("INSERT INTO ATTILA.Service (price, service_type, vps_id, webstorage_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $_POST['price'],
            $_POST['service_type'],
            $_POST['vps_id'] ?: null,
            $_POST['webstorage_id'] ?: null
        ]);
        
    } elseif (isset($_POST['update'])) {
        $stmt = $connect->prepare("UPDATE ATTILA.Service SET price = ?, service_type = ?, vps_id = ?, webstorage_id = ? WHERE id = ?");
        $stmt->execute([
            $_POST['price'],
            $_POST['service_type'],
            $_POST['vps_id'] ?: null,
            $_POST['webstorage_id'] ?: null,
            $_POST['id']
        ]);
    }
} elseif (isset($_GET['delete'])) {
    $stmt = $connect->prepare("DELETE FROM ATTILA.Service WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
}

$editRow = null;
if (isset($_GET['edit'])) {
    $stmt = $connect->prepare("SELECT * FROM ATTILA.Service WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stmt = $connect->prepare("SELECT s.*, v.server_specs, w.storage_space as webstorage_size 
                          FROM ATTILA.Service s
                          LEFT JOIN ATTILA.VPS v ON s.vps_id = v.id 
                          LEFT JOIN ATTILA.Webstorage w ON s.webstorage_id = w.id
                          ORDER BY s.id");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>Services</h1>

<form method="POST">
    <h2><?= isset($_GET['edit']) ? 'Update' : 'Create' ?> Service</h2>
    <input type="hidden" name="id" value="<?= $editRow['ID'] ?? '' ?>">
    
    Ár: <input type="number" step="0.01" name="price" required value="<?= $editRow['PRICE'] ?? '' ?>"><br>
    
    Service típus: 
    <select name="service_type" required>
        <option value="">-- Select típus --</option>
        <option value="VPS" <?= isset($editRow['SERVICE_TYPE']) && $editRow['SERVICE_TYPE'] == 'VPS' ? 'selected' : '' ?>>VPS</option>
        <option value="Webstorage" <?= isset($editRow['SERVICE_TYPE']) && $editRow['SERVICE_TYPE'] == 'Webstorage' ? 'selected' : '' ?>>Webstorage</option>
        <option value="Bundle" <?= isset($editRow['SERVICE_TYPE']) && $editRow['SERVICE_TYPE'] == 'Bundle' ? 'selected' : '' ?>>Bundle</option>
    </select><br>
    
    VPS: 
    <select name="vps_id">
        <option value="">-- VPS --</option>
        <?php foreach ($vpsOptions as $vps): ?>
            <option value="<?= $vps['ID'] ?>" 
                <?= isset($editRow['VPS_ID']) && $editRow['VPS_ID'] == $vps['ID'] ? 'selected' : '' ?>>
                <?= $vps['SERVER_SPECS'] ?>
            </option>
        <?php endforeach; ?>
    </select><br>
    
    Webstorage: 
    <select name="webstorage_id">
        <option value="">-- Webstorage --</option>
        <?php foreach ($webstorageOptions as $ws): ?>
            <option value="<?= $ws['ID'] ?>" 
                <?= isset($editRow['WEBSTORAGE_ID']) && $editRow['WEBSTORAGE_ID'] == $ws['ID'] ? 'selected' : '' ?>>
                <?= $ws['STORAGE_SPACE'] ?>
            </option>
        <?php endforeach; ?>
    </select><br>
    
    <?php if (isset($_GET['edit'])): ?>
        <input type="submit" name="update" value="Frissítés">
        <a href="?">Cancel</a>
    <?php else: ?>
        <input type="submit" name="create" value="Létrehozás">
    <?php endif; ?>
</form>

<table border="1">
    <tr>
        <th>Ár</th>
        <th>Service típus</th>
        <th>VPS</th>
        <th>Webstorage</th>
    </tr>
    <?php foreach ($rows as $row): ?>
    <tr>
        <td><?= $row['PRICE'] ?></td>
        <td><?= $row['SERVICE_TYPE'] ?></td>
        <td><?= $row['SERVER_SPECS'] ?? 'Nincs' ?></td>
        <td><?= $row['WEBSTORAGE_SIZE'] ?? 'Nincs' ?></td>
        <td>
            <a href="?edit=<?= $row['ID'] ?>">Szerkesztés</a>
            <a href="?delete=<?= $row['ID'] ?>" onclick="return confirm('Biztos?')">Törlés</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>