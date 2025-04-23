<?php
require_once('php/functions.php');
require_once('php/connection.php');

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $user_id = $_SESSION['user']['id'];
    $username = $_POST['felhasznalonev'];
    $email = $_POST['email'];
    $password = $_POST['jelszo'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $connect->prepare("SELECT * FROM ATTILA.USERS WHERE (EMAIL = :email OR USERNAME = :username) AND USER_ID != :user_id");
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        die("Az email vagy a felhasználónév már foglalt.");
    }

    $stmt = $connect->prepare("UPDATE ATTILA.USERS 
                               SET USERNAME = :username, EMAIL = :email, PASSWORD_HASH = :hashed_password 
                               WHERE USER_ID = :user_id");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':hashed_password', $hashed_password);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    header('Location: profile.php?message=Sikeres adat módosítás');
    exit;
}