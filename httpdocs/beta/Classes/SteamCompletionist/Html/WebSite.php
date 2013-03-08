<?php

namespace Classes\SteamCompletionist\Html;

use Classes\SteamCompletionist\Steam\SteamUser;

/**
 * WebSite is the template for the Steam Completionist website.
 * The idea is that at if at any point anything fails, it will display it as function as it can.
 *
 * Simply put: No steam user id? Display the login page. Any errors? Display a appropriate error pop up. Etc.
 *
 * @author Felix Kastner <felix@chapterfain.com>
 */
class WebSite
{
    public $error = '';

    /** @var SteamUser $steamUser */
    private $steamUser;

    private $webTitle;

    /**
     * Sets steam user
     *
     * @param SteamUser $steamUser
     */
    public function setSteamUser(SteamUser $steamUser)
    {
        $this->steamUser = $steamUser;
    }

    /**
     * @param $title
     */
    private function header($title) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="utf-8" />
            <title><?= $title ?></title>
            <link rel="stylesheet" href="css/reset.css" type="text/css" />
            <link rel="stylesheet" href="css/jquery-ui-1.10.0.custom.min.css" type="text/css" />
            <link rel="stylesheet" href="css/main.css" type="text/css" />
            <script type="text/javascript" src="js/jquery-1.8.3.min.js"></script>
            <script type="text/javascript" src="js/jquery-ui-1.10.0.custom.min.js"></script>
            <script type="text/javascript" src="js/main.js"></script>
            <!--[if IE]><script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
            <!--[if lt IE 9]><script src="http://ie7-js.googlecode.com/svn/version/2.1(beta4)/IE9.js"></script><![endif]-->
        </head>
        <body class="home">
        <?PHP
    }

    private function userBar() {
        ?>
        <header class="header">
            <div class="body">
                <h1 class="webtitle">Steam Completionist: <strong>Beat your games</strong></h1>
                <div class="steamuser">
                    <?PHP
                    if($this->steamUser) {
                        //Logged In
                        ?>
                        <div class="logged">
                            <p class="loader"><img src="./img/loading.gif" alt="Loading" height="16" width="16"><br/>Working...</p>
                            <button id="refresh" class="topbutton">Refresh</button>
                            <button id="settings" class="topbutton">Settings</button>
                            <div id="userdata">
                                <img class="avatar<?=$this->steamUser->getStatusClass($this->steamUser->personaState)?>" src="<?=$this->steamUser->avatar?>" width="32" height="32" alt="Steam avatar"/>
                                <span class="statustext<?=$this->steamUser->getStatusClass($this->steamUser->personaState)?>"><?=$this->steamUser->personaName?><br/>
                                    <?=$this->steamUser->getStatusString($this->steamUser->personaState)?>
                                </span>
                            </div>
                        </div>
                        <?PHP
                    } else {
                        // Logged Out
                        ?>
                        <p class="login">
                            <a href="?login">
                                <img class="login" src="./img/sits_small.png" width="154" height="23" alt="Sign in through Steam"/>
                            </a>
                        </p>
                        <?PHP
                    }
                    ?>
                </div>
            </div>
        </header>
        <?PHP
    }

    private function footer() {
        ?>
        <footer>
            <p class="credits">Powered by <a href="http://steampowered.com">Steam</a></p>
        </footer>
        <?PHP echo ($this->error) ? '<div id="error">' . $this->error . '</div>' : ''; ?>
        <div id="alert"></div>
        </body>
        </html>
        <?PHP
    }

    /**
     * Display the website
     */
    public function display() {
        if($this->steamUser) {
            $this->header($this->steamUser->personaName . ' - Steam Completionist');
        } else {
            $this->header('Steam Completionist');
        }

        $this->userBar();

        $this->footer();

    }

}