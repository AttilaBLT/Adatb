<?php
require_once 'php/connection.php';
require_once 'php/functions.php';
printMenu();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $stmt = $connect->prepare("INSERT INTO ATTILA.Webstorage (name, description, storage_space) VALUES (?, ?, ?)");
        $stmt->execute([
            $_POST['name'],
            $_POST['description'],
            $_POST['storage_space']
        ]);
        
    } elseif (isset($_POST['update'])) {
        $stmt = $connect->prepare("UPDATE ATTILA.Webstorage SET name = ?, description = ?, storage_space = ? WHERE id = ?");
        $stmt->execute([
            $_POST['name'],
            $_POST['description'],
            $_POST['storage_space'],
            $_POST['id']
        ]);
    }
} elseif (isset($_GET['delete'])) {
    $stmt = $connect->prepare("DELETE FROM ATTILA.Webstorage WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
}

$editRow = null;
if (isset($_GET['edit'])) {
    $stmt = $connect->prepare("SELECT * FROM ATTILA.Webstorage WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editRow = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stmt = $connect->prepare("SELECT * FROM ATTILA.Webstorage ORDER BY id");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>Webstorage</h1>

<form method="POST">
    <h2><?= isset($_GET['edit']) ? 'Frissítés' : 'Létrehozás' ?></h2>
    <input type="hidden" name="id" value="<?= $editRow['ID'] ?? '' ?>">
    
    Név: <input type="text" name="name" required value="<?= $editRow['NAME'] ?? '' ?>"><br>
    Leírás: <input type="text" name="description" value="<?= $editRow['DESCRIPTION'] ?? '' ?>"><br>
    Tárhely: <input type="number" name="storage_space" required value="<?= $editRow['STORAGE_SPACE'] ?? '' ?>"><br>
    
    <?php if (isset($_GET['edit'])): ?>
        <input type="submit" name="update" value="Frissítés">
        <a href="?">Mégse</a>
    <?php else: ?>
        <input type="submit" name="create" value="Létrehozás">
    <?php endif; ?>
</form>

<table border="1">
    <tr>
        <th>Név</th>
        <th>Leírás</th>
        <th>Tárhely</th>
        <th></th>
    </tr>
    <?php foreach ($rows as $row): ?>
    <tr>
        <td><?= $row['NAME'] ?></td>
        <td><?= $row['DESCRIPTION'] ?></td>
        <td><?= $row['STORAGE_SPACE'] ?></td>
        <td>
            <a href="?edit=<?= $row['ID'] ?>">Szerkesztés</a>
            <a href="?delete=<?= $row['ID'] ?>" onclick="return confirm('Are you sure?')">Törlés</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>