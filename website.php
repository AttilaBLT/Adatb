<?php
require_once 'php/connection.php';
require_once 'php/functions.php';
printMenu();

// Get all VPS options for the dropdown
$vpsStmt = $connect->prepare("SELECT id, server_specs FROM ATTILA.VPS ORDER BY id");
$vpsStmt->execute();
$vpsOptions = $vpsStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $currentUser = $_SESSION['user'];
        $user_id = $currentUser['id'];
        $stmt = $connect->prepare("INSERT INTO ATTILA.Website (user_id, server_id, address) VALUES (?, ?, ?)");
        $stmt->execute([
            $user_id,
            $_POST['server_id'],
            $_POST['address']
        ]);
        
    } elseif (isset($_POST['update'])) {
        $currentUser = $_SESSION['user'];
        $user_id = $currentUser['id'];
        $stmt = $connect->prepare("UPDATE ATTILA.Website SET user_id = ?, server_id = ?, address = ? WHERE id = ?");
        $stmt->execute([
            $user_id,
            $_POST['server_id'],
            $_POST['address'],
            $_POST['id']
        ]);
    }
} elseif (isset($_GET['delete'])) {
    $stmt = $connect->prepare("DELETE FROM ATTILA.Website WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
}

$editRow = null;
if (isset($_GET['edit'])) {
    $stmt = $connect->prepare("SELECT * FROM ATTILA.Website WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stmt = $connect->prepare("SELECT w.*, v.server_specs 
                          FROM ATTILA.Website w 
                          LEFT JOIN ATTILA.VPS v ON w.server_id = v.id 
                          ORDER BY w.id");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>Websites</h1>

<form method="POST">
    <h2><?= isset($_GET['edit']) ? 'Update' : 'Create' ?></h2>
    <input type="hidden" name="id" value="<?= $editRow['ID'] ?? '' ?>">
    
    Szerver: 
    <select name="server_id">
        <option value="">-- Select Server --</option>
        <?php foreach ($vpsOptions as $vps): ?>
            <option value="<?= $vps['ID'] ?>" 
                <?= isset($editRow['SERVER_ID']) && $editRow['SERVER_ID'] == $vps['ID'] ? 'selected' : '' ?>>
                <?= $vps['SERVER_SPECS'] ?>
            </option>
        <?php endforeach; ?>
    </select><br>
    
    Cím: <input type="text" name="address" required value="<?= $editRow['ADDRESS'] ?? '' ?>"><br>
    
    <?php if (isset($_GET['edit'])): ?>
        <input type="submit" name="update" value="Update">
        <a href="?">Cancel</a>
    <?php else: ?>
        <input type="submit" name="create" value="Create">
    <?php endif; ?>
</form>

<table border="1">
    <tr>
        <th>Szerver</th>
        <th>Cím</th>
    </tr>
    <?php foreach ($rows as $row): ?>
    <tr>
        <td><?= $row['SERVER_SPECS'] ?? 'None' ?></td>
        <td><?= $row['ADDRESS'] ?></td>
        <td>
            <a href="?edit=<?= $row['ID'] ?>">Edit</a>
            <a href="?delete=<?= $row['ID'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>