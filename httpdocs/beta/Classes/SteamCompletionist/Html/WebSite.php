<?php

namespace Classes\SteamCompletionist\Html;

use Classes\SteamCompletionist\Steam\SteamGame;
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

    private function privateProfile() {
        ?>
        <div class="private">
            <h1>
                <strong>Your Profile is set to private :(</strong>
            </h1>
            <h2>
                <strong>Steam Completionist</strong> can only display your games if you set your profile to "public".<br>
                <a href="steam://url/SteamIDEditPage">Click here to go to your profile settings in the steam client</a> -&gt; <a href="">Then click here to retry</a><br>
                Here is a picture to illustrate:
            </h2>
                <img src="img/profile.png"/>
        </div>
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
                                <img class="avatar<?=$this->steamUser->getStatusClass($this->steamUser->personaState)?>" src="http://media.steampowered.com/steamcommunity/public/images/avatars/fc/<?=$this->steamUser->avatarHash?>.jpg" width="32" height="32" alt="Steam avatar"/>
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

    private function toBeatBar() {
        ?>
        <header class="games">
            <div class="game_body">
                <div class="game_boxes">
                    <h2 class="beattag">Beat these games! <span>You have <span id="points"><?= $this->steamUser->points ?></span> points</span></h2>
                    <?PHP
                    // For now we assume a "to beat" list of 5 games. Can be expanded / user-set later on
                    for($x = 1; $x <= 5; $x++)
                    {
                        // Create the game box / empty box
                        $string = '<div id="game_box_'.$x.'" class="game_box">';
                        if(isset($this->steamUser->slots[$x]) && $this->steamUser->slots[$x]) {
                            $string .= '<img class="game_image"
                                                src="' . $this->steamUser->games[$this->steamUser->slots[$x]]->logo . '"
                                                width="184" height="69"
                                                alt="' . $this->steamUser->games[$this->steamUser->slots[$x]]->name . '"
                                                data-gameid="' . $this->steamUser->games[$this->steamUser->slots[$x]]->appId . '"
                                                data-minutestotal="' . $this->steamUser->games[$this->steamUser->slots[$x]]->minutesTotal . '"
                                                data-minutes2weeks="' . $this->steamUser->games[$this->steamUser->slots[$x]]->minutes2Weeks . '"
                                                data-achievper="' . $this->steamUser->games[$this->steamUser->slots[$x]]->achievementPercentage . '" />';
                        } else {
                            $string .= '<div class="empty_game_box"></div>';
                        }
                        $string .= '</div>';
                        echo $string;
                    }
                    ?>
                </div>
            </div>
        </header>
    <?PHP
    }

    private function gamesList() {
        ?>
        <div class="list_wrapper">
            <div class="list_body">
                <div class="list_boxes">
                    <?PHP
                    // Iterate through all games and create the game cards
                    foreach($this->steamUser->games as $gameObject)
                    {
                        /** @var SteamGame $gameObject */

                        // Games that are in the "to beat" list need the disabled CSS class which this adds
                        $disabled = '';
                        if($gameObject->gameSlot) {
                            $disabled = ' disabled';
                        }

                        // If the game has neither a logo nor a icon, it's probably a test app not meant to be played ( e.g. dota 2 test, X-Com PreOrder Bonus )
                        if($gameObject->logoHash || $gameObject->iconHash) {

                            $logo = 'http://media.steampowered.com/steamcommunity/public/images/apps/' . $gameObject->appId . '/';
                            if($gameObject->logoHash) {
                                $logo .= $gameObject->logoHash;
                            } else {
                                $logo .= $gameObject->iconHash;
                            }
                            $logo .= '.jpg';

                            // Only attach data-achievper if it has community stats
                            $achievements = '';
                            if($gameObject->community) {
                                $achievements = 'data-achievper="' . $gameObject->achievementPercentage . '"';
                            }

                        echo '<div id="' . $gameObject->appId . '" class="list_box' . $disabled . '">
                                    <img class="game_image"
                                    src="' . $logo . '"
                                    width="184" height="69"
                                    alt="' . htmlentities( stripslashes( $gameObject->name ) ) . '"
                                    data-gameid="' . $gameObject->appId . '"
                                    data-minutestotal="' . $gameObject->minutesTotal . '"
                                    data-minutes2weeks="' . $gameObject->minutes2Weeks . '"
                                    ' . $achievements . ' />
                                 </div>';
                        }
                    }

                    // While the page is still in active development, display debug data at the bottom
                    // @todo: convert seconds to Days : Hours : Minutes : Seconds
                    echo '<p>Local data last updated '.$this->steamUser->lastUpdate.' seconds ago.</p>';
                    echo '<p id="debuglog"> JS Debug: </p>';
                    echo '<p>&nbsp;<br/>&nbsp;</p>';
                    ?>
                </div>
            </div>
        </div>
    <?PHP
    }

    private function footer() {
        ?>
        <footer>
            <p class="credits">Powered by <a href="http://steampowered.com">Steam</a></p>
        </footer>
        <?PHP echo ($this->error) ? '<div id="error">' . $this->error . '</div>' : ''; ?>
        <div class="fader" draggable="false"></div>
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

        if($this->steamUser && $this->steamUser->profileState == 3) {
            $this->toBeatBar();
            $this->gamesList();
        } elseif($this->steamUser) {
            // Profile is private.
            $this->privateProfile();
        }

        $this->footer();

    }

}