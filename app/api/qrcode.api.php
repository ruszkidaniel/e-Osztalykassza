<?php

    if(!isset($_GET['url'])) 
        die();
    
    $qrcode = new QRCode($_GET['url'], [ 's' => 'qr-l' ]);

    $qrcode->output_image();
?>