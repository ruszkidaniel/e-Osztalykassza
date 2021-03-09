<?php

    class VerificationHelper {

        function __construct($pageConfig, $dataManager) {
            $this->pageConfig = $pageConfig;
            $this->dataManager = $dataManager;
        }

        function HandleRequest($postData, $registerData) {
            if(isset($postData['sendcode'])) {
                if(!isset($registerData['FullName'], $registerData['Email']))
                    throw new Exception('data_mismatch');

                $verifyCode = $this->GenerateCode($registerData['UserID'], 'email');

                $message = $this->GenerateVerificationEmail($registerData, $verifyCode);
                
                $to = $registerData['FullName'] . " <".$registerData['Email'].">";

                $subject = "e-Osztálykassza felhasználói fiók regisztráció";

                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

                $headers .= 'From: <noreply@'.$this->pageConfig::WEBSITE_DOMAIN.'>' . "\r\n";

                mail($to,$subject,$message,$headers);
            }
        }

        function GenerateCode($userID, $type) {
            $code = $this->dataManager->FindVerificationCode($userID, $type);

            if(!$code) {
                $code = random_characters(16);
                $this->dataManager->InsertNewVerificationCode($code, $userID, $type);
            } else {
                $this->dataManager->UpdateVerificationCode($code['Code']);
                $code = $code['Code'];
            }

            return $code;
        }

        function GenerateVerificationEmail($registerData, $verifyCode) {
            $address = $this->pageConfig::WEBSITE_ADDRESS;
            $logo = $this->pageConfig::EMAIL_LOGO;
            $verifyurl = $address . 'register/verify/' . $verifyCode;

            $email = '<h3>Kedves '.$registerData['FullName'].'!</h3>'.PHP_EOL;
            $email .= '<p>Valaki az Ön adataival regisztrált az <a href="'.$address.'" style="text-decoration: none; font-weight: bold">e-Osztálykassza</a> oldalra. Amennyiben nem Ön volt, kérjük, hagyja figyelmen kívül ezt a levelet.</p>'.PHP_EOL;
            $email .= '<p>A regisztrációja befejezéséhez <a href="'.$verifyurl.'">kattintson erre a hivatkozásra</a>, majd kövesse a további teendőket.</p>'.PHP_EOL;
            $email .= '<p>Amennyiben a kattintás nem megoldható, kérjük másolja ki, majd nyissa meg egy új ablakban az alábbi hivatkozást:<br><a href="'.$verifyurl.'">'.$verifyurl.'</a></p>'.PHP_EOL;
            $email .= '<p>Köszönjük, hogy az <strong>e-Osztálykassza</strong> szolgáltatást választotta!<br>Amennyiben kérdése lenne, bátran forduljon hozzánk valamelyik elérhetőségünkön!</p>'.PHP_EOL;

            $email .= '<img src="'.$logo.'" style="float: left; height: 32px"><span style="font-weight: bold; font-size: 12pt; padding-left: 30px; line-height: 32px;">e-Osztálykassza</span>'.PHP_EOL;
            return $email;
        }

    }