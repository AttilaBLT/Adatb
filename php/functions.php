<?php
session_start();

function isAdmin() {
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}

function printMenu(){
    $menu = file_get_contents('html/header.html');    
    if (isset($_SESSION['user']['id']))
    {
        $adminMenu = '';
        $adminProfileMenu = '';
        if (isAdmin()) {
            $adminMenu = '
                <li><a href="service.php">Szolgáltatás</a></li>
                <li class="dropdown">
                    <a href="#">Bérlés</a>
                    <ul class="dropdown-menu">
                        <li><a href="vps.php">VPS</a></li>
                        <li><a href="webstorage.php">Webstorage</a></li>
                    </ul>
                </li>
            ';
            // Csak adminnak jelenik meg a Felhasználók menüpont a profil alatt
            $adminProfileMenu = '<li><a href="users.php">Felhasználók</a></li>';
        }
        $menu = str_replace('::login', '
            <li><a href="index.php">Kezdőlap</a></li>
            <li><a href="website.php">Website</a></li>
            <li><a href="payment.php">Fizetés</a></li>
            <li><a href="databases.php">Adatbázisok</a></li>'
            . $adminMenu .
            '<li><a href="subscription.php">Előfizetés</a></li>
            <li><a href="faq.php">GYIK</a></li>
            <li><a href="invoice.php">Számla generálás</a></li>
            <li class="dropdown profile">
                <a href="#">Profil</a>
                <ul class="dropdown-menu">'
                    . $adminProfileMenu . '
                    <li><a href="profile.php">Profilom</a></li>
                    <li><a href="profile_edit.php">Módosítás</a></li>
                    <li><a href="php/logout.php">Kilépés</a></li>
                </ul>
            </li>
            <button class="dark-mode-toggle">🌙</button>', $menu);  
    } 
    else
    {
        $menu = str_replace('::login', '
            <li><a href="login.php">Bejelentkezés</a></li>
            <li><a href="register.php">Regisztráció</a></li>', $menu);
    }
    echo $menu;
}

function printHtml($html){
    echo file_get_contents($html);
}

function isLoggedIn(){
    if(!isset($_SESSION['user']['id'])){
        header("Location: login.php");
        exit();
    }
}