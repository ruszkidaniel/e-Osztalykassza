<?php

class MainPage extends BasePage {

    public function init($userPermissions, $globalPermissions) {

        if(isset($_POST['class'])) {
            $success = $this->selectClass($_POST['class']);
            if($success) return true;
        }
        
        $classes = $this->dataManager->GetUserClassrooms($_SESSION['UserID']);

        $this->classes = [];
        foreach($classes as $val) {
            $this->classes[$val['SchoolName']][] = $val;
        }

        $this->classDOM = '';

        $domElements = [];
        foreach($this->classes as $school => $classes) {
            $domElements[] = '<optgroup label="'.addslashes($school).'">';
            foreach($classes as $class) {
                $selected = isset($_SESSION['ClassID']) && $_SESSION['ClasSID'] == $class['ClassID'] ? 'selected' : '';
                $domElements[] = '<option value="'.$class['ClassID'].'"'.$selected.'>'.htmlspecialchars($class['ClassName']).'</option>'.PHP_EOL;
            }
            $domElements[] = '</optgroup>';
        }
        
        $this->classDOM = implode(PHP_EOL, $domElements);
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
        ';
        if(isset($_SESSION['PendingInvites']))
        foreach($_SESSION['PendingInvites'] as $invite) {
            echo '<div class="box colored fit-content align-center text-center">
                <h2>Meghívó egy osztályba</h2>
                <p><span>'.htmlentities($invite['Inviter']).'</span> meghívta Önt a(z) <strong><span>'.htmlentities($invite['ClassName']).'</span></strong> nevű osztályba!</p>
                <p>A meghívás elfogadásához kattintson az <strong>Elfogadás</strong> gombra.</p>
                <p>Ha nem szeretne csatlakozni, kattintson az <strong>Elutasítás</strong> gombra.</p>
                <hr>
                <div class="flex-spread">
                    <a href="/invite/deny/'.$invite['InviteCode'].'" class="btn"><span class="text-red">Elutasítás</span></a>
                    <a href="/invite/accept/'.$invite['InviteCode'].'" class="btn"><span class="text-green">Elfogadás</span></a>
                </div>
            </div>';
        }
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