<?php
require_once 'php/connection.php';
require_once 'php/functions.php';

function createPayment($user_id, $subscription_id, $amount, $due_date, $method) {
    global $connect;
    try {
        $sql = "
            DECLARE
                v_id NUMBER;
            BEGIN
                INSERT INTO ATTILA.Payment (user_id, subscription_id, amount, due_date, method)
                VALUES (:user_id, :subscription_id, :amount, TO_DATE(:due_date, 'YYYY-MM-DD'), :method)
                RETURNING payment_id INTO v_id;
            END;
        ";
        $stmt = $connect->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':subscription_id', $subscription_id);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':due_date', $due_date);
        $stmt->bindParam(':method', $method);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Payment creation error: " . $e->getMessage());
        return false;
    }
}

function updatePayment($payment_id, $user_id, $subscription_id, $amount, $due_date, $method) {
    global $connect;
    try {
        $sql = "
            BEGIN
                UPDATE ATTILA.Payment
                SET user_id = :user_id,
                    subscription_id = :subscription_id,
                    amount = :amount,
                    due_date = TO_DATE(:due_date, 'YYYY-MM-DD'),
                    method = :method
                WHERE payment_id = :payment_id;
            END;
        ";
        $stmt = $connect->prepare($sql);
        $stmt->bindParam(':payment_id', $payment_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':subscription_id', $subscription_id);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':due_date', $due_date);
        $stmt->bindParam(':method', $method);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Payment update error: " . $e->getMessage());
        return false;
    }
}

function deletePayment($payment_id) {
    global $connect;
    try {
        $sql = "
            BEGIN
                DELETE FROM ATTILA.Payment WHERE payment_id = :payment_id;
            END;
        ";
        $stmt = $connect->prepare($sql);
        $stmt->bindParam(':payment_id', $payment_id);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Payment deletion error: " . $e->getMessage());
        return false;
    }
}

function getPayment($payment_id) {
    global $connect;
    try {
        $sql = "SELECT * FROM ATTILA.Payment WHERE payment_id = :payment_id";
        $stmt = $connect->prepare($sql);
        $stmt->bindParam(':payment_id', $payment_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Payment retrieval error: " . $e->getMessage());
        return false;
    }
}

function getAllPayments() {
    global $connect;
    try {
        $sql = "SELECT p.*, u.username, s.service_id, sv.price, sv.service_type, 
                       v.server_specs, w.storage_space as webstorage_size,
                       s.start_date, s.end_date
                FROM ATTILA.Payment p 
                LEFT JOIN ATTILA.Users u ON p.user_id = u.user_id 
                LEFT JOIN ATTILA.Subscription s ON p.subscription_id = s.id
                LEFT JOIN ATTILA.Service sv ON s.service_id = sv.id
                LEFT JOIN ATTILA.VPS v ON sv.vps_id = v.id
                LEFT JOIN ATTILA.Webstorage w ON sv.webstorage_id = w.id
                ORDER BY p.payment_id";
        $stmt = $connect->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Payment list retrieval error: " . $e->getMessage());
        return [];
    }
}

function getAllUsers() {
    global $connect;
    try {
        $sql = "SELECT user_id, username FROM ATTILA.Users ORDER BY user_id";
        $stmt = $connect->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("User list retrieval error: " . $e->getMessage());
        return [];
    }
}

function getAllSubscriptions() {
    global $connect;
    try {
        $sql = "SELECT s.id, s.user_id, u.username, sv.price, sv.service_type, v.server_specs, w.storage_space as webstorage_size
                FROM ATTILA.Subscription s
                LEFT JOIN ATTILA.Users u ON s.user_id = u.user_id
                LEFT JOIN ATTILA.Service sv ON s.service_id = sv.id
                LEFT JOIN ATTILA.VPS v ON sv.vps_id = v.id
                LEFT JOIN ATTILA.Webstorage w ON sv.webstorage_id = w.id
                ORDER BY s.id";
        $stmt = $connect->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Subscription list retrieval error: " . $e->getMessage());
        return [];
    }
}

function getSubscriptionServicePrice($subscription_id) {
    global $connect;
    try {
        $sql = "SELECT sv.price 
                FROM ATTILA.Subscription s
                JOIN ATTILA.Service sv ON s.service_id = sv.id
                WHERE s.id = :subscription_id";
        $stmt = $connect->prepare($sql);
        $stmt->bindParam(':subscription_id', $subscription_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['PRICE'] : null;
    } catch (PDOException $e) {
        error_log("Service price retrieval error: " . $e->getMessage());
        return null;
    }
}

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;
    $subscription_id = !empty($_POST['subscription_id']) ? (int)$_POST['subscription_id'] : null;
    $amount = getSubscriptionServicePrice($subscription_id);
    $due_date = sanitizeInput($_POST['due_date']);
    $method = sanitizeInput($_POST['method']);

    if (empty($user_id) || empty($subscription_id) || empty($amount) || empty($due_date) || empty($method)) {
        $error = "Minden kötelező mezőt ki kell tölteni!";
    } else {
        if (isset($_POST['create'])) {
            if (createPayment($user_id, $subscription_id, $amount, $due_date, $method)) {
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $error = "Hiba történt a fizetés létrehozásakor!";
            }
        } elseif (isset($_POST['update'])) {
            $payment_id = (int)$_POST['payment_id'];
            if (updatePayment($payment_id, $user_id, $subscription_id, $amount, $due_date, $method)) {
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $error = "Hiba történt a fizetés frissítésekor!";
            }
        }
    }
} elseif (isset($_GET['delete'])) {
    $payment_id = (int)$_GET['delete'];
    if (deletePayment($payment_id)) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $error = "Hiba történt a fizetés törlésekor!";
    }
}

$editRow = null;
if (isset($_GET['edit'])) {
    $payment_id = (int)$_GET['edit'];
    $editRow = getPayment($payment_id);
}

$rows = getAllPayments();
$userOptions = getAllUsers();
$subscriptionOptions = getAllSubscriptions();

if (function_exists('printMenu')) printMenu();
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fizetések Kezelése</title>
    <style>
        .container { max-width: 900px; margin: 0 auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="date"],
        .form-group select { width: 100%; padding: 8px; }
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
        <h1>Fizetések Kezelése</h1>

        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="form">
            <h2><?= isset($_GET['edit']) ? 'Fizetés Frissítése' : 'Új Fizetés Létrehozása' ?></h2>
            <input type="hidden" name="payment_id" value="<?= $editRow['PAYMENT_ID'] ?? '' ?>">

            <div class="form-group">
                <label for="user_id">Felhasználó:</label>
                <select id="user_id" name="user_id" required>
                    <option value="">-- Válasszon felhasználót --</option>
                    <?php foreach ($userOptions as $user): ?>
                        <option value="<?= $user['USER_ID'] ?>"
                            <?= isset($editRow['USER_ID']) && $editRow['USER_ID'] == $user['USER_ID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['USERNAME']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="subscription_id">Előfizetés:</label>
                <select id="subscription_id" name="subscription_id" required>
                    <option value="">-- Válasszon előfizetést --</option>
                    <?php foreach ($subscriptionOptions as $subscription): ?>
                        <option value="<?= $subscription['ID'] ?>"
                            data-price="<?= $subscription['PRICE'] ?>"
                            <?= isset($editRow['SUBSCRIPTION_ID']) && $editRow['SUBSCRIPTION_ID'] == $subscription['ID'] ? 'selected' : '' ?>>
                            Előfizetés #<?= $subscription['ID'] ?> (Felhasználó: <?= htmlspecialchars($subscription['USERNAME']) ?>)
                            <?php if (!empty($subscription['SERVER_SPECS'])): ?>
                                - VPS: <?= htmlspecialchars($subscription['SERVER_SPECS']) ?>
                            <?php endif; ?>
                            <?php if (!empty($subscription['WEBSTORAGE_SIZE'])): ?>
                                - Webstorage: <?= htmlspecialchars($subscription['WEBSTORAGE_SIZE']) ?> MB
                            <?php endif; ?>
                            - Ár: <?= htmlspecialchars($subscription['PRICE']) ?> Ft
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="due_date">Fizetési határidő:</label>
                <input type="date" id="due_date" name="due_date" required
                       value="<?= isset($editRow['DUE_DATE']) ? date('Y-m-d', strtotime($editRow['DUE_DATE'])) : '' ?>">
            </div>

            <div class="form-group">
                <label for="method">Fizetési mód:</label>
                <select id="method" name="method" required>
                    <option value="">-- Válasszon fizetési módot --</option>
                    <option value="Bankkártya" <?= (isset($editRow['METHOD']) && $editRow['METHOD'] === 'Bankkártya') ? 'selected' : '' ?>>Bankkártya</option>
                    <option value="Átutalás" <?= (isset($editRow['METHOD']) && $editRow['METHOD'] === 'Átutalás') ? 'selected' : '' ?>>Átutalás</option>
                </select>
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
                    <th>Felhasználó</th>
                    <th>Előfizetés</th>
                    <th>Fizetési határidő</th>
                    <th>Fizetési mód</th>
                    <th>Műveletek</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="7">Nincsenek fizetés bejegyzések.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['USERNAME']) ?></td>
                            <td>
                                <?php if (!empty($row['SERVER_SPECS'])): ?>
                                    VPS: <?= htmlspecialchars($row['SERVER_SPECS']) ?><br>
                                <?php endif; ?>
                                <?php if (!empty($row['WEBSTORAGE_SIZE'])): ?>
                                    Webstorage: <?= htmlspecialchars($row['WEBSTORAGE_SIZE']) ?> MB<br>
                                <?php endif; ?>
                                Kezdés: <?= htmlspecialchars($row['START_DATE']) ?><br>
                                Lejárat: <?= htmlspecialchars($row['END_DATE']) ?><br>
                                Összeg: <?= htmlspecialchars($row['AMOUNT']) ?> Ft
                            </td>
                            <td><?= htmlspecialchars($row['DUE_DATE']) ?></td>
                            <td><?= htmlspecialchars($row['METHOD']) ?></td>
                            <td class="actions">
                                <a href="?edit=<?= $row['PAYMENT_ID'] ?>" class="btn">Szerkesztés</a>
                                <a href="?delete=<?= $row['PAYMENT_ID'] ?>"
                                   onclick="return confirm('Biztosan törölni szeretnéd?')"
                                   class="btn">Törlés</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script>
        function updateAmount() {
            const subscriptionSelect = document.getElementById('subscription_id');
            const amountInput = document.getElementById('amount');
            const selectedOption = subscriptionSelect.options[subscriptionSelect.selectedIndex];
            
            if (selectedOption.value) {
                amountInput.value = selectedOption.getAttribute('data-price');
            } else {
                amountInput.value = '';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('subscription_id').value) {
                updateAmount();
            }
        });
    </script>
</body>
</html>
