<?php

    class Config {
        const WEBSITE_DOMAIN = 'local.blckdg.me';
        const WEBSITE_ADDRESS = 'https://local.blckdg.me/';

        const FB_APP_ID = '2818937918318854';
        const FB_APP_VERSION = 'v10.0';

        const REG_USERNAME_REGEX = '/^[a-z0-9 öüóőúűéáí._:,]+$/';
        const REG_FULLNAME_REGEX = '/^[a-z0-9 öüóőúűéáí]+$/';

        const REG_USERNAME_MIN = 6;
        const REG_USERNAME_MAX = 32;

        const REG_PASSWORD_MIN = 6;
        const REG_EMAIL_MAX = 64;

        const REG_FULLNAME_MIN = 6;
        const REG_FULLNAME_MAX = 32;

        const EMAIL_LOGO = self::WEBSITE_ADDRESS . "images/icon.png";

        const VERIFY_CODES_MIN_DIFF = 5*60;

        const MAX_LOGIN_ATTEMPTS = 5;
        const LOGIN_BAN_INTERVAL = 60;

        const REQUEST_MIN_TITLE_LENGTH = 3;
        const REQUEST_MAX_TITLE_LENGTH = 32;

        const REQUEST_MIN_DESCRIPTION_LENGTH = 10;
        const REQUEST_MAX_DESCRIPTION_LENGTH = 2000;
        
        const CLASS_PERMISSIONS = [
            'MANAGE_MEMBERS', 'MANAGE_PAYS',
            'MANAGE_SETTINGS', 'MANAGE_PERMISSIONS', 'MANAGE_REQUESTS',
            'MANAGE_POSTS'
        ];

        const GLOBAL_PERMISSIONS = [
            'LOGIN', 'USE_ADMIN_PAGE', 'VIEW_ALL_CLASSES', 'MODIFY_ALL_CLASSES',
            'MANAGE_USERS'
        ];

        const INVITE_MAIL_TEMPLATE = '<h3>Tisztelt Cím!</h3>
        <br>
        <p>Meghívást kapott az <strong>e-Osztálykassza</strong> szolgáltatásra.</p>
        <p>A szolgáltatás lényege, hogy az iskolai osztálypénzgyűjtést leegyszerűsítse, és könnyen adminisztrálhatóvá tegye az osztályprogramok szervezéséhez, iskolai ügyek intézéséhez.</p>
        <p>Amennyiben elfogadja a meghívást, kérjük kattintson az alábbi hivatkozásra, vagy másolja be a böngészője címsorába<br><a href="{{inviteurl}}">{{inviteurl}}</a></p>
        <br>
        <p>Ha nem élne a lehetőséggel, kattintson <a href="{{declineurl}}">erre a szövegre</a> a meghívás elutasításához.</p>
        <p>Ha a továbbiakban nem szeretne meghívást kapni erre az oldalra, <a href="{{optouturl}}">kattintson ide</a>!</p>
        <br>
        <img src="'.Config::EMAIL_LOGO.'" style="float: left; height: 32px"><span style="font-weight: bold; font-size: 12pt; padding-left: 30px; line-height: 32px;">e-Osztálykassza</span>';
    }

?>