<?php
require_once 'php/connection.php';
require_once 'php/functions.php';
printMenu();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $stmt = $connect->prepare("INSERT INTO ATTILA.FAQ (QUESTION, ANSWER) VALUES (?, ?)");
        $stmt->execute([$_POST['question'], $_POST['answer']]);
        $stmt = $connect->prepare("SELECT MAX(faq_id) FROM ATTILA.FAQ");
        $stmt->execute();
        $faq_id = $stmt->fetchColumn();
        $currentUser = $_SESSION['user'];
        $user_id = $currentUser['id'];
        $stmt_ask = $connect->prepare("INSERT INTO ATTILA.ASK (user_id, faq_id) VALUES (:user_id, :faq_id)");
        $stmt_ask->bindParam(':user_id', $user_id);
        $stmt_ask->bindParam(':faq_id', $faq_id);
        $stmt_ask->execute();

        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }
    elseif (isset($_POST['update'])) {
        $stmt = $connect->prepare("UPDATE ATTILA.FAQ SET QUESTION = ?, ANSWER = ? WHERE FAQ_ID = ?");
        $stmt->execute([$_POST['question'], $_POST['answer'], $_POST['faq_id']]);
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }
}
elseif (isset($_GET['delete'])) {
    $stmt = $connect->prepare("DELETE FROM ATTILA.FAQ WHERE FAQ_ID = ?");
    $stmt->execute([$_GET['delete']]);

    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

$editMode = false;
$currentItem = null;
if (isset($_GET['edit'])) {
    $stmt = $connect->prepare("SELECT * FROM ATTILA.FAQ WHERE FAQ_ID = ?");
    $stmt->execute([$_GET['edit']]);
    $currentItem = $stmt->fetch(PDO::FETCH_ASSOC);
    $editMode = true;
}

$stmt = $connect->prepare(" SELECT f.*, u.username 
                            FROM ATTILA.FAQ f
                            JOIN ATTILA.ASK a ON f.FAQ_ID = a.faq_id
                            JOIN ATTILA.Users u ON a.user_id = u.user_id
                            ORDER BY f.FAQ_ID
                            ");
$stmt->execute();
$faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>GYIK Kezelés</h1>

<form method="POST">
    <?php if ($editMode): ?>
        <input type="hidden" name="faq_id" value="<?= $currentItem['FAQ_ID'] ?>">
        <h2>GYIK Szerkesztése</h2>
        <input type="submit" name="update" value="Frissítés">
    <?php else: ?>
        <h2>Új GYIK Létrehozása</h2>
        <input type="submit" name="create" value="Létrehozás">
    <?php endif; ?>
    
    <input type="text" name="question" placeholder="Kérdés" 
           value="<?= $editMode ? $currentItem['QUESTION'] : '' ?>" required>
    <textarea name="answer" placeholder="Válasz" required><?= $editMode ? $currentItem['ANSWER'] : '' ?></textarea>
    
    <?php if ($editMode): ?>
        <a href="?">Mégse</a>
    <?php endif; ?>
</form>

<h2>GYIK Lista</h2>
<?php if (empty($faqs)): ?>
    <p>Nincsenek GYIK bejegyzések.</p>
<?php else: ?>
    <?php foreach ($faqs as $faq): ?>
        <div>
            <h3><?= $faq['QUESTION'] ?></h3>
            <p><?= $faq['ANSWER'] ?></p>
            <p><?= $faq['USERNAME'] ?></p>
            <div>
                <a href="?edit=<?= $faq['FAQ_ID'] ?>">Szerkesztés</a>
                <a href="?delete=<?= $faq['FAQ_ID'] ?>" onclick="return confirm('Biztosan törölni szeretnéd?')">Törlés</a>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>