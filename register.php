<?php
require_once('php/functions.php');

printMenu();

?>
    <main>
        <div class="registration-form">
            <h2>Regisztráció</h2>
            <form action="php/registerCheck.php" method="post">
                <div class="form-group">
                    <label for="felhasznalonev">Felhasználónév:</label>
                    <input type="text" id="felhasznalonev" name="felhasznalonev" class="adatok" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" class="adatok" required>
                </div>
                <div class="form-group">
                    <label for="password">Jelszó:</label>
                    <input type="password" id="password" name="password" class="adatok" required>
                </div>
                <div class="form-group">
                    <label for="confirm-password">Jelszó megerősítése:</label>
                    <input type="password" id="confirm-password" name="confirm-password" class="adatok" required>
                </div>
                <button type="submit">Regisztráció</button>
                <?php if(isset($_SESSION['error'])) { ?>
                    <p style="color: red;"><?php echo $_SESSION['error']; ?></p>
                <?php } ?>
            </form>
        </div>
    </main>
</body>
</html>
