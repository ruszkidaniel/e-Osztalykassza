<?php

    $inviteCode = count($path) > 2 ? $path[2] : false;
    $invite = new InviteManager($dataManager, $inviteCode);

    $text = $invite->ask(false);
    echo $text;