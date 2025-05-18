<?php
require_once('php/functions.php');
require_once('php/connection.php');

if (!isAdmin()) { 
    header("Location: index.php"); 
    exit(); 
}

printMenu();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['role'])) {
    $user_id = (int)$_POST['user_id'];
    $role = ($_POST['role'] === 'admin') ? 'admin' : 'user';
    $stmt = $connect->prepare("UPDATE ATTILA.USERS SET ROLE = :role WHERE USER_ID = :user_id");
    $stmt->bindParam(':role', $role);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    header("Location: users.php");
    exit();
}

$stmt = $connect->query("SELECT USER_ID, USERNAME, EMAIL, ROLE FROM ATTILA.USERS ORDER BY USER_ID");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<main>
    <div class="container">
        <h2>Felhasználók kezelése</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Felhasználónév</th>
                    <th>Email</th>
                    <th>Szerep</th>
                    <th>Művelet</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['USER_ID']) ?></td>
                    <td><?= htmlspecialchars($user['USERNAME']) ?></td>
                    <td><?= htmlspecialchars($user['EMAIL']) ?></td>
                    <td><?= htmlspecialchars($user['ROLE']) ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?= $user['USER_ID'] ?>">
                            <select name="role">
                                <option value="user" <?= $user['ROLE'] === 'user' ? 'selected' : '' ?>>user</option>
                                <option value="admin" <?= $user['ROLE'] === 'admin' ? 'selected' : '' ?>>admin</option>
                            </select>
                            <button type="submit">Mentés</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
<?php include 'html/footer.html'; ?>