<?php

abstract class BasePage {

    abstract function init($userPermissions, $globalPermissions);

    function setPath($path) { $this->path = $path; }
    function setIntro($text) { $this->intro = $text; }
    function setDataManager($dataManager) { $this->dataManager = $dataManager; }
    function setPageConfig($pageConfig) { $this->pageConfig = $pageConfig; }
    
    function echoHeader() {
        echo '
        <div class="text-center">
            <img src="/images/icon.png"><br>
            <h1>e-Osztálykassza</h1>';
        if(isset($this->intro)) echo '<p class="intro">'.$this->intro.'</p>';
        echo '</div>';
    }
}