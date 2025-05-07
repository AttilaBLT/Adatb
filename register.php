<?php
require_once('php/functions.php');

printMenu();

?>
<main>
    <div class="register-container">
        <div class="register-box">
            <h2>Regisztráció</h2>
            <form action="php/registerCheck.php" method="post">
                <div class="input-container">
                    <label for="felhasznalonev">Felhasználónév:</label>
                    <input type="text" id="felhasznalonev" name="felhasznalonev" required>
                </div>
                <div class="input-container">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="input-container">
                    <label for="password">Jelszó:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="input-container">
                    <label for="confirm-password">Jelszó megerősítése:</label>
                    <input type="password" id="confirm-password" name="confirm-password" required>
                </div>
                <button type="submit" class="register-button">Regisztráció</button>
                <div class="register-link">
                    <a href="login.php">Már van fiókod? Jelentkezz be!</a>
                </div>
            </form>
        </div>
    </div>
</main>
<?php include 'html/footer.html'; ?>