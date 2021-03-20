<?php

class MainPage extends BasePage {

    public function init($userPermissions, $globalPermissions) {
        $this->classes = $this->dataManager->GetUserClassrooms($_SESSION['UserID']);

        if(isset($_POST['class'])) {
            $success = $this->selectClass($_POST['class']);
            if($success) return true;
        }

        $this->classDOM = '';

        if(count($this->classes) > 0) {
            $domElements = [];
            foreach($this->classes as $class) {
                $selected = isset($_SESSION['ClassID']) && $_SESSION['ClasSID'] == $class['ClassID'] ? 'selected' : '';
                $domElements[] = '<option value="'.$class['ClassID'].'"'.$selected.'>'.htmlspecialchars($class['ClassName']).'</option>'.PHP_EOL;
            }
            $this->classDOM = implode(PHP_EOL, $domElements);
        }
        $this->classDOM .= '<option value="-1">Új osztály létrehozása</option>';

        $this->run();
        return true;
    }

    private function run() {
        $this->setIntro('Üdvözöljük az e-Osztálykassza szolgáltatás főoldalán!');
        $this->echoHeader();
        echo '
        <div class="box">
            <h3>Bevezetés</h3>
            <p>A szolgáltatás célja, hogy az iskolai osztálypénz gyűjtést könnyedén lehessen intézni, és egy átlátható felületen lehetőség legyen az eddigi begyűjtött összeget részletezni, új kérvényeket létrehozni és befizetéseket teljesíteni.</p>
            <p>A befizetési kérelmek áttekintésénél látható az, hogy ki fizette be a kért összeget, és kinek mennyi tartozása van még egy kérvényből.</p>
            <h3>Személyreszabás</h3>
            <p>A létrehozott osztályokat személyre lehet szabni:</p>
            <ul>
                <li>meg lehet hívni új tagokat</li>
                <li>az áttekintő oldalon bejegyzéseket és szavazásokat lehet közzétenni</li>
                <li>kérvényeket lehet kiállítani befizetésre</li>
                <li>a tagoknak különböző jogosultságokat lehet adni: megadható, hogy a fentebbiek közül kinek mihez legyen joga.</li>
            </ul>
        </div>
        <div class="box">    
            <h3>Kezdés</h3>
            <p>Az osztályát létrehozhatja ezen az oldalon, vagy ha több osztályhoz van hozzáférése, itt kiválaszthatja, hogy melyiket szeretné kezelni éppen.</p>
            <form method="POST" action="/" id="class-select">
                <label for="class">
                Osztály kiválasztása:
                <select name="class" id="class">
                    '. $this->classDOM .'
                </select>
                <input type="submit" value="Kiválaszt">
                </label>
            </form>
        </div>
        <hr>
        ';
    }

    private function selectClass($class) {
        if($class == -1)
            redirect_to_url('/new');

        $classInfo = $this->dataManager->GetClassInfo($class);
        if(!$classInfo || !isset($classInfo['ClassID'], $classInfo['ClassName'], $classInfo['Description']))
            return false;

        $_SESSION['ClassInfo'] = $classInfo;
        redirect_to_url('/dashboard');
    }

}

$loaded = new MainPage();

?>