<?php
require_once('connection.php');

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $felhasznalonev = $_POST['felhasznalonev'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Ellenőrzés, hogy az email vagy felhasználónév már létezik-e
    $stmt = $connect->prepare("SELECT * FROM ATTILA.USERS WHERE EMAIL = :email OR USERNAME = :felhasznalonev");
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':felhasznalonev', $felhasznalonev);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row['EMAIL'] === $email) {
            $_SESSION['error'] = "Ez az email cím már foglalt!";
        } else {
            $_SESSION['error'] = "Ez a felhasználónév már foglalt!";
        }
        header('Location: ../register.php?error=Ez az email/felhasználónév már foglalt');
        exit;
    }

    // Új felhasználó beszúrása
    $stmt = $connect->prepare("INSERT INTO ATTILA.USERS (USERNAME, EMAIL, PASSWORD_HASH) VALUES (:felhasznalonev, :email, :hashed_password)");
    $stmt->bindParam(':felhasznalonev', $felhasznalonev);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':hashed_password', $hashed_password);
    $stmt->execute();

    header('Location: ../login.php?message=Sikeres Regisztráció');
    exit;
}