<?php
session_start();

function printMenu(){
    $menu = file_get_contents('html/header.html');    
    if (isset($_SESSION['user']['id']))
    {
        $menu = str_replace('::login', '
            <li><a href="index.php">Kezdőlap</a></li>
            <li class="dropdown">
                <a href="#">Bérlés</a>
                <ul class="dropdown-menu">
                    <li><a href="vps.php">VPS</a></li>
                    <li><a href="webstorage.php">Webstorage</a></li>
                </ul>
            </li>
            <li><a href="faq.php">GYIK</a></li>
            <li class="dropdown profile">
                <a href="#">Profil</a>
                <ul class="dropdown-menu">
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