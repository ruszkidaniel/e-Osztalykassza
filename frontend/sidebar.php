<?php

    if(!defined('EO_project_version'))
        die(header('Location: /'));

?>

<div id="sidebar">
    <div>
        <h3>Felhasználó</h3>
        <nav>
            <a href="/main"><i class="fas fa-home"></i> Főoldal</a>
            <a href="/profile"><i class="fas fa-user-alt"></i> Profil</a>
            <a href="/logout"><i class="fas fa-sign-out-alt"></i> Kijelentkezés</a>
        </nav>
    </div>
    <div>
        <h3>Osztály</h3>
<?php

    echo isset($_SESSION['ClassInfo']) ? '<p>Osztály: <strong>'.htmlentities($_SESSION['ClassInfo']['ClassName']).'</strong></p>' : '<p>Nincs kiválasztva osztály.</p>';

    if(isset($_SESSION['ClassInfo'])) {

?>
        <nav>
            <a href="/dashboard"><i class="fas fa-plus-circle"></i> Áttekintés</a>
            <a href="/members"><i class="fas fa-users"></i> Tagok</a>
            <a href="/settings"><i class="fas fa-cog"></i> Beállítások</a>
        </nav>
<?php

    }

?>
    </div>
</div>