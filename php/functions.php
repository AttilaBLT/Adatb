<?php
session_start();

function printMenu(){
    $menu = file_get_contents('html/header.html');    
    if (isset($_SESSION['user']['id']))
    {
        $menu = str_replace('::login',' <a href="profile.php" class="nav-link">Profil</a>
                                        <a href="kosar.php" class="nav-link">Kosár tartalma</a>
                                        <a href="faq.php" class="nav-link">FAQ</a>
                                        <a class="nav-link" href="php/logout.php">Kilépés</a>',$menu);  
    } 
    else
    {
        $menu = str_replace('::login','<a href="login.php" class="nav-link">Bejelentkezés</a><a href="register.php" class="nav-link">Regisztráció</a>',$menu);
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