<?php
require_once('php/connection.php');
require_once('php/functions.php');

printMenu();

if (isset($_SESSION['user']['id']))
{
    header('Location: index.php');
}

if (isset($_SESSION['errormessage']))
{
    echo $_SESSION['errormessage'];
    unset($_SESSION['errormessage']);
}

?>

<main>
    <div class="login-container">
        <div class="login-box">
            <h2>Bejelentkezés</h2>
            <form action="php/loginCheck.php" method="post">
                <div class="input-container">
                    <label for="email">Email:</label>
                    <input type="text" id="email" name="email" required>
                </div>
                <div class="input-container">
                    <label for="password">Jelszó:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="login-button">Bejelentkezés</button>
                <div class="register-link">
                    <a href="register.php">Még nem regisztráltál?</a>
                </div>
            </form>
        </div>
    </div>
</main>
<?php include 'html/footer.html'; ?>