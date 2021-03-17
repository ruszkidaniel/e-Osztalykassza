<?php

    if(!defined('EO_project_version'))
        die(header('Location: /'));

    require_once('./frontend/document_header.php');

    if(!isLogged()) {
        require_once('./frontend/login_page.php');
    } else { ?>

<!-- container start -->
<div id="container">

    <?php require_once('./frontend/sidebar.php'); ?>

    <main>

<?php

        // load sidebar

        require_once('./frontend/sidebar.php');

        // check if page exists
        $page = count($path) == 0 ? 'main' : $path[0];
        $file = './frontend/pages/'.$page.'.php';
        if(!file_exists($file))
            $file = './frontend/404.html';

        // load page
        require_once($file);

        if(isset($loaded) && method_exists($loaded, 'init')) {
            if(method_exists($loaded, 'setPath'))
                $loaded->setPath($path);

            if(method_exists($loaded, 'setDataManager'))
                $loaded->setDataManager($dataManager);

            $run = $loaded->init($userClassPermissions, $userGlobalPermissions);
            if(!$run) {
                require_once('./frontend/403.html');
            }
        } ?>

    </main>

</div>
<!-- container end -->

    <?php
    
    }

    require_once('./frontend/document_footer.php');