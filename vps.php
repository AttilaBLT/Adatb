<?php
require_once 'php/connection.php';
require_once 'php/functions.php';
printMenu();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $stmt = $connect->prepare("INSERT INTO ATTILA.VPS (server_specs) VALUES (?)");
        $stmt->execute([
            $_POST['server_specs']
        ]);
        
    } elseif (isset($_POST['update'])) {
        $stmt = $connect->prepare("UPDATE ATTILA.VPS SET server_specs = ? WHERE id = ?");
        $stmt->execute([
            $_POST['server_specs'],
            $_POST['id']
        ]);
    }
} elseif (isset($_GET['delete'])) {
    $stmt = $connect->prepare("DELETE FROM ATTILA.VPS WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
}

$editRow = null;
if (isset($_GET['edit'])) {
    $stmt = $connect->prepare("SELECT * FROM ATTILA.VPS WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stmt = $connect->prepare("SELECT * FROM ATTILA.VPS ORDER BY id");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>VPS</h1>

<form method="POST">
    <h2><?= isset($_GET['edit']) ? 'Frissítés' : 'Létrehozás' ?></h2>
    <input type="hidden" name="id" value="<?= $editRow['ID'] ?? '' ?>">
    
    Szerver specifikációk: <input type="text" name="server_specs" required value="<?= $editRow['SERVER_SPECS'] ?? '' ?>"><br>
    
    <?php if (isset($_GET['edit'])): ?>
        <input type="submit" name="update" value="Frissítés">
        <a href="?">Mégse</a>
    <?php else: ?>
        <input type="submit" name="create" value="Létrehozás">
    <?php endif; ?>
</form>

<table border="1">
    <tr>
        <th>Szerver specifikációk</th>
        <th></th>
    </tr>
    <?php foreach ($rows as $row): ?>
    <tr>
        <td><?= $row['SERVER_SPECS'] ?></td>
        <td>
            <a href="?edit=<?= $row['ID'] ?>">Szerkesztés</a>
            <a href="?delete=<?= $row['ID'] ?>" onclick="return confirm('Are you sure?')">Törlés</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>