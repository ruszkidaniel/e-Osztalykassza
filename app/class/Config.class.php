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
    }

?>