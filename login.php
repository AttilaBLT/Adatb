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
        <div class="registration-form">
            <h2>Bejelentkezés</h2>
            <form action="php/loginCheck.php" method="post">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="text" id="email" name="email" class="adatok" required>
                </div>
                <div class="form-group">
                    <label for="password">Jelszó:</label>
                    <input type="password" id="password" name="password" class="adatok" required>
                </div>
                <div class="form-group">
                    <a href="register.php" id="not-registered">Még nem regisztráltál?</a>
                </div>
                <button type="submit">Bejelentkezés</button>
            </form>
        </div>
    </main>
</body>
</html>