<?php
require_once('functions.php');
require_once('connection.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') 
{
    $email = $_POST['email'];
    $password = $_POST['password'];

    $search = $connect->prepare("SELECT USER_ID, USERNAME, PASSWORD_HASH FROM ATTILA.USERS WHERE EMAIL=:email");
    $search->bindParam(':email', $email, PDO::PARAM_STR);
    $search->execute();
    $result = $search->fetch(PDO::FETCH_ASSOC);
    $storedHash = $result['PASSWORD_HASH'];
    
    if (password_verify($password, $storedHash))
    {
        $_SESSION['user']['id'] = $result['USER_ID'];
        $_SESSION['user']['username'] = $result['USERNAME'];
    } 
    else 
    {
        $_SESSION['errormessage'] = "<h3>Helytelen email vagy jelszó!</h3>";
    }
}

if (isset($_SESSION['errormessage'])) {
    header('Location: ../login.php?error=Helytelen email vagy jelszó!');
} else {
    header('Location: ../index.php');
}