<?php
require_once 'php/connection.php';
require_once 'php/functions.php';

printMenu();
isLoggedIn();

function getAllDatabasesByUser($user_id) {
    global $connect;
    $stmt = $connect->prepare("SELECT * FROM ATTILA.DATABASES WHERE USER_ID = :user_id ORDER BY ID");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function createDatabase($user_id, $name, $size_mb) {
    global $connect;
    $stmt = $connect->prepare("INSERT INTO ATTILA.DATABASES (USER_ID, NAME, SIZE_MB) VALUES (:user_id, :name, :size_mb)");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':size_mb', $size_mb);
    return $stmt->execute();
}

function deleteDatabase($id, $user_id) {
    global $connect;
    $stmt = $connect->prepare("DELETE FROM ATTILA.DATABASES WHERE ID = :id AND USER_ID = :user_id");
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':user_id', $user_id);
    return $stmt->execute();
}

$user_id = $_SESSION['user']['id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $name = trim($_POST['name']);
        $size_mb = (int)$_POST['size_mb'];
        if ($name === '' || $size_mb < 1) {
            $error = "Minden mező kitöltése kötelező, a méret legyen pozitív szám!";
        } else {
            if (!createDatabase($user_id, $name, $size_mb)) {
                $error = "Hiba történt az adatbázis létrehozásakor!";
            } else {
                header("Location: databases.php");
                exit();
            }
        }
    } elseif (isset($_POST['delete'])) {
        $id = (int)$_POST['id'];
        if (!deleteDatabase($id, $user_id)) {
            $error = "Hiba történt az adatbázis törlésekor!";
        } else {
            header("Location: databases.php");
            exit();
        }
    }
}

$rows = getAllDatabasesByUser($user_id);
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Adatbázisok Kezelése</title>
    <style>
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input[type="text"], .form-group input[type="number"] { width: 100%; padding: 8px; }
        .btn { padding: 8px 15px; cursor: pointer; }
        .error { color: red; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f5f5f5; }
        .actions { white-space: nowrap; }
        .actions form { display: inline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Adatbázisok Kezelése</h1>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="form">
            <h2>Új adatbázis létrehozása</h2>
            <div class="form-group">
                <label for="name">Adatbázis neve:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="size_mb">Becsült tárhely kapacitás (MB):</label>
                <input type="number" id="size_mb" name="size_mb" min="1" required>
            </div>
            <input type="submit" name="create" value="Létrehozás" class="btn">
        </form>

        <h2>Adatbázisok</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Név</th>
                    <th>Méret (MB)</th>
                    <th>Létrehozva</th>
                    <th>Műveletek</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="5">Nincsenek adatbázis bejegyzések.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['ID']) ?></td>
                            <td><?= htmlspecialchars($row['NAME']) ?></td>
                            <td><?= htmlspecialchars($row['SIZE_MB']) ?></td>
                            <td><?= htmlspecialchars($row['CREATED_AT']) ?></td>
                            <td class="actions">
                                <form method="post" onsubmit="return confirm('Biztosan törölni szeretnéd?');">
                                    <input type="hidden" name="id" value="<?= $row['ID'] ?>">
                                    <button type="submit" name="delete" class="btn">Törlés</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php include 'html/footer.html'; ?>
</body>
</html>