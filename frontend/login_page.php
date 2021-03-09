<?php

    if(!defined('EO_project_version'))
        die(header('Location: /'));

    if(isset($_SESSION['username']))
        die(header('Location: /'));
    
    $actions = ['login', 'register', 'forgotpw', 'startdemo'];
    if(count($path) > 0 && in_array($path[0], $actions)) {
        $action = $path[0];
        if(count($path) > 1)
            $subaction = str_replace('..','',$path[1]);
    } else {
        $action = $actions[0];
    }

    /**
     * Inject FB script only if enabled in page config
     */

    if(strlen($pageConfig::FB_APP_ID) > 0): ?>
        <script>
        window.fbAsyncInit = function() {
            FB.init({
                appId      : <?= $pageConfig::FB_APP_ID ?>,
                cookie     : <?= $cookieHandler->Check() ? 'true' : 'false' ?>,
                xfbml      : true,
                version    : '<?= $pageConfig::FB_APP_VERSION ?>',
            });
                
            FB.AppEvents.logPageView();
        };

        (function(d, s, id){
            var js, fjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)) {return;}
            js = d.createElement(s); js.id = id;
            js.src = "https://connect.facebook.net/en_US/sdk.js";
            fjs.parentNode.insertBefore(js, fjs);
        }(document, 'script', 'facebook-jssdk'));
            
        </script>
<?php endif ?>


<div class="guest-wrapper">
    <div class="guest-container <?=$action?>">
<?php
    $includePath = './frontend/guestPages/'.$action;
    if(isset($subaction)) $includePath .= '/'.$subaction;

    if(file_exists($includePath.'.php'))
        include_once($includePath.'.php');
    else
        include_once('./frontend/404.html');
?>
    </div>
</div>