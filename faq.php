<?php
require_once 'php/connection.php';
require_once 'php/functions.php';

function createFAQ($question, $answer, $user_id) {
    global $connect;
    try {
        $connect->beginTransaction();
        
        $stmt = $connect->prepare("INSERT INTO ATTILA.FAQ (QUESTION, ANSWER) VALUES (?, ?)");
        $stmt->execute([$question, $answer]);
        
        $stmt = $connect->prepare("SELECT FAQ_ID FROM ATTILA.FAQ WHERE ROWID = (SELECT MAX(ROWID) FROM ATTILA.FAQ)");
        $stmt->execute();
        $faq_id = $stmt->fetchColumn();
        
        if (!$faq_id) {
            throw new PDOException("Nem sikerült lekérni az újonnan létrehozott FAQ ID-t");
        }
        
        error_log("Created FAQ with ID: " . $faq_id);
        
        $stmt_ask = $connect->prepare("INSERT INTO ATTILA.ASK (user_id, faq_id) VALUES (?, ?)");
        $stmt_ask->execute([$user_id, $faq_id]);
        
        $connect->commit();
        return true;
    } catch (PDOException $e) {
        $connect->rollBack();
        error_log("FAQ creation error: " . $e->getMessage());
        error_log("SQL State: " . $e->getCode());
        if (isset($stmt)) {
            error_log("Error Info: " . print_r($stmt->errorInfo(), true));
        }
        return false;
    }
}

function updateFAQ($faq_id, $question, $answer) {
    global $connect;
    try {
        $stmt = $connect->prepare("UPDATE ATTILA.FAQ SET QUESTION = ?, ANSWER = ? WHERE FAQ_ID = ?");
        return $stmt->execute([$question, $answer, $faq_id]);
    } catch (PDOException $e) {
        error_log("FAQ update error: " . $e->getMessage());
        return false;
    }
}

function deleteFAQ($faq_id) {
    global $connect;
    try {
        $stmt = $connect->prepare("DELETE FROM ATTILA.FAQ WHERE FAQ_ID = ?");
        return $stmt->execute([$faq_id]);
    } catch (PDOException $e) {
        error_log("FAQ deletion error: " . $e->getMessage());
        return false;
    }
}

function getFAQ($faq_id) {
    global $connect;
    try {
        $stmt = $connect->prepare("SELECT * FROM ATTILA.FAQ WHERE FAQ_ID = ?");
        $stmt->execute([$faq_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("FAQ retrieval error: " . $e->getMessage());
        return false;
    }
}

function getAllFAQs() {
    global $connect;
    try {
        $stmt = $connect->prepare("SELECT f.*, u.username 
                                 FROM ATTILA.FAQ f
                                 JOIN ATTILA.ASK a ON f.FAQ_ID = a.faq_id
                                 JOIN ATTILA.Users u ON a.user_id = u.user_id
                                 ORDER BY f.FAQ_ID");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("FAQ list retrieval error: " . $e->getMessage());
        return [];
    }
}

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create']) || isset($_POST['update'])) {
        $question = sanitizeInput($_POST['question']);
        $answer = sanitizeInput($_POST['answer']);
        
        if (empty($question) || empty($answer)) {
            $error = "A kérdés és a válasz mezők kitöltése kötelező!";
        } else {
            if (isset($_POST['create'])) {
                if (!isset($_SESSION['user'])) {
                    $error = "Nincs bejelentkezve felhasználó!";
                } else {
                    $currentUser = $_SESSION['user'];
                    error_log("Current user data: " . print_r($currentUser, true));
                    
                    if (!isset($currentUser['id'])) {
                        $error = "Hibás felhasználói adatok!";
                    } else {
                        error_log("Attempting to create FAQ with user_id: " . $currentUser['id']);
                        if (createFAQ($question, $answer, $currentUser['id'])) {
                            header("Location: " . $_SERVER['PHP_SELF']);
                            exit();
                        } else {
                            $error = "Hiba történt a GYIK létrehozásakor! Ellenőrizd a hibaüzeneteket!";
                        }
                    }
                }
            } else {
                $faq_id = (int)$_POST['faq_id'];
                if (updateFAQ($faq_id, $question, $answer)) {
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $error = "Hiba történt a GYIK frissítésekor!";
                }
            }
        }
    }
} elseif (isset($_GET['delete'])) {
    $faq_id = (int)$_GET['delete'];
    if (deleteFAQ($faq_id)) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $error = "Hiba történt a GYIK törlésekor!";
    }
}

$editMode = false;
$currentItem = null;
if (isset($_GET['edit'])) {
    $faq_id = (int)$_GET['edit'];
    $currentItem = getFAQ($faq_id);
    $editMode = $currentItem !== false;
}

$faqs = getAllFAQs();

printMenu();
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GYIK Kezelés</title>
    <style>
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input[type="text"],
        .form-group textarea { width: 100%; padding: 8px; }
        .form-group textarea { height: 150px; }
        .btn { padding: 8px 15px; cursor: pointer; }
        .error { color: red; margin-bottom: 15px; }
        .faq-item { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; }
        .faq-actions { margin-top: 10px; }
        .faq-actions a { margin-right: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>GYIK Kezelés</h1>

        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="form">
            <?php if ($editMode): ?>
                <input type="hidden" name="faq_id" value="<?= $currentItem['FAQ_ID'] ?>">
                <h2>GYIK Szerkesztése</h2>
                <input type="submit" name="update" value="Frissítés" class="btn">
            <?php else: ?>
                <h2>Új GYIK Létrehozása</h2>
                <input type="submit" name="create" value="Létrehozás" class="btn">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="question">Kérdés:</label>
                <input type="text" id="question" name="question" 
                       value="<?= $editMode ? $currentItem['QUESTION'] : '' ?>" required>
            </div>
            
            <div class="form-group">
                <label for="answer">Válasz:</label>
                <textarea id="answer" name="answer" required><?= $editMode ? $currentItem['ANSWER'] : '' ?></textarea>
            </div>
            
            <?php if ($editMode): ?>
                <a href="?" class="btn">Mégse</a>
            <?php endif; ?>
        </form>

        <h2>GYIK Lista</h2>
        <?php if (empty($faqs)): ?>
            <p>Nincsenek GYIK bejegyzések.</p>
        <?php else: ?>
            <?php foreach ($faqs as $faq): ?>
                <div class="faq-item">
                    <h3><?= htmlspecialchars($faq['QUESTION']) ?></h3>
                    <p><?= nl2br(htmlspecialchars($faq['ANSWER'])) ?></p>
                    <p><small>Felhasználó: <?= htmlspecialchars($faq['USERNAME']) ?></small></p>
                    <div class="faq-actions">
                        <a href="?edit=<?= $faq['FAQ_ID'] ?>" class="btn">Szerkesztés</a>
                        <a href="?delete=<?= $faq['FAQ_ID'] ?>" 
                           onclick="return confirm('Biztosan törölni szeretnéd?')" 
                           class="btn">Törlés</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php include 'html/footer.html'; ?>