<?php

    if(!defined('EO_project_version'))
        die(header('Location: /'));

    if(!$cookieHandler->Check()) { ?>

<div class="cookiebox flex-spread">
    <div class="text">
        <h2>Figyelem!</h2>
        <p>Az oldal működéséhez elengedhetetlen, hogy a böngésző eltárolja az aktuális munkamenetet.<br>Ehhez az oldal internetes <strong>sütiket használ</strong>.</p>
    </div>
    <a href="/accept-cookies?back=<?=urlencode(implode('/',$path))?>" class="accept">Elfogadom</a>
</div>

<?php } ?>
</body>
</html>