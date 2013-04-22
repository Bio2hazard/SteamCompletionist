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
    /**
     * Holds a error message to display.
     * @var string
     */
    public $error = '';

    /**
     * Holds the currently active Steam User.
     * @var SteamUser $steamUser
     */
    private $steamUser;


    /**
     * Sets active Steam User.
     *
     * @param SteamUser $steamUser
     */
    public function setSteamUser(SteamUser $steamUser)
    {
        $this->steamUser = $steamUser;
    }

    /**
     * Creates the header of the website with dynamic title based on the logged in Steam User.
     *
     * @param $title
     */
    private function header($title)
    {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="utf-8"/>
            <title><?= $title ?></title>
            <link rel='icon' href='../../../favicon.png' type='image/png' />
            <link rel="stylesheet" href="../../../css/reset.css" type="text/css"/>
            <link rel="stylesheet" href="../../../css/jquery-ui-1.10.0.custom.min.css" type="text/css"/>
            <link rel="stylesheet" href="../../../css/main.css" type="text/css"/>
            <script type="text/javascript" src="https://www.google.com/jsapi"></script>
            <script type="text/javascript" src="../../../js/jquery-1.8.3.min.js"></script>
            <script type="text/javascript" src="../../../js/jquery-ui-1.10.0.custom.min.js"></script>
            <script type="text/javascript" src="../../../js/main.js"></script>
            <!--[if IE]>
            <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
            <!--[if lt IE 9]>
            <script src="http://ie7-js.googlecode.com/svn/version/2.1(beta4)/IE9.js"></script><![endif]-->
        </head>
        <body class="home">
    <?PHP
    }

    /**
     * Displays the "hey your profile is private" message.
     */
    private function privateProfile()
    {
        ?>
        <div class="private">
            <h1>
                <strong>Your Profile is set to private :(</strong>
            </h1>

            <h2>
                <strong>SteamCompletionist.net</strong> can only display your games if you set your profile to
                "public".<br>
                <a href="steam://url/SteamIDEditPage">Click here to go to your profile settings in the steam client</a>
                -&gt; <a href="">Then click here to retry</a><br>
                Here is a picture to illustrate:
            </h2>
            <img src="../../../img/profile.png"/>
        </div>
    <?PHP
    }

    /**
     * Displays the userbar, which contains the website's headline, the buttons and the user box.
     */
    private function userBar()
    {
        ?>
        <header class="header">
            <div class="body">
                <h1 class="webtitle">SteamCompletionist.net: <strong>Beat your games</strong></h1>

                <div class="steamuser">
                    <?PHP
                    if ($this->steamUser) {
                        //Logged In
                        $userdata = $this->steamUser->getUserData();
                        ?>
                        <div class="logged">
                            <p class="loader"><img src="../../../img/loading.gif" alt="Loading" height="16" width="16"><br/>Working...
                            </p>
                            <button id="showbeat" class="topbutton">Toggle showing games you've beaten</button>
                            <button id="showblacklisted" class="topbutton">Toggle showing games you've blacklisted
                            </button>
                            <button id="fillslot" class="topbutton">Add random game</button>
                            <button id="refresh" class="topbutton">Refresh</button>
                            <button id="showsettings" class="topbutton">Settings</button>
                            <button id="showhelp" class="topbutton">FAQ & Terms</button>
                            <button id="logout" class="topbutton">Logout</button>
                            <div id="userdata">
                                <img
                                    class="avatar<?= $userdata['class'] ?>"
                                    src="<?= $userdata['avatar'] ?>"
                                    width="32" height="32" alt="Steam avatar"/>
                                <span
                                    class="statustext<?= $userdata['class'] ?>"><?= $userdata['name'] ?>
                                    <br/>
                                    <?= $userdata['status'] ?>
                                    <br/>
                                    <span id="points"><?= $this->steamUser->points ?></span> Points
                                </span>
                            </div>
                        </div>
                    <?PHP
                    } else {
                        // Logged Out
                        ?>
                        <div class="login">
                            <button id="showhelp" class="topbutton">FAQ & Terms</button>
                            <div class="loginsteam">
                                <a href="?login">
                                    <img class="steamlogin" src="../../../img/sits_small.png" width="154" height="23"
                                         alt="Sign in through Steam"/>
                                </a>
                            </div>
                            <div class="cookielaw">Due to silly EU laws: When you log into this website, a cookie will be saved. Please see the FAQ button for more information.</div>
                        </div>
                    <?PHP
                    }
                    ?>
                </div>
            </div>
        </header>
    <?PHP
    }

    /**
     * Displays the Steam User's "to beat" bar.
     */
    private function toBeatBar($toBeatNum, $hideAccountStats)
    {
        ?>
        <header class="games">
            <div class="game_body">
                <div class="game_boxes">
                    <h2 class="beattag">Beat these games!</h2>
                    <?PHP
                    //if $hideAccountStats is true, we attach style="display:none" into the div box.
                    $accountCSS = '';
                    if($hideAccountStats) {
                        $accountCSS = ' style="display:none"';
                    }
                    echo '<div id="account_stats"' . $accountCSS . ' class="account_stat_box"></div>';

                    for ($x = 1; $x <= $toBeatNum; $x++) {
                        $string = '';
                        $beaten = '';
                        // Create the game box / empty box
                        if (isset($this->steamUser->slots[$x]) && $this->steamUser->slots[$x]) {
                            /** @var SteamGame $game */
                            $game = $this->steamUser->games[$this->steamUser->slots[$x]];
                            $logo = 'http://media.steampowered.com/steamcommunity/public/images/apps/' . $game->appId . '/';
                            if ($game->logoHash) {
                                $logo .= $game->logoHash;
                            } else {
                                $logo .= $game->iconHash;
                            }
                            $logo .= '.jpg';


                            if ($game->gameStatus == 1) {
                                $beaten .= ' beaten';
                            }  else if(!$game->minutesTotal) {
                                $beaten .= ' unplayed';
                            }

                            // Only attach data-achievper if it has community stats
                            $achievements = '';
                            if ($game->community) {
                                $achievements = 'data-achievper="' . $game->achievementPercentage . '"';
                            }

                            $string .= '<img class="game_image"
                                                src="' . $logo . '"
                                                width="184" height="69"
                                                alt="' . htmlentities(stripslashes($game->name)) . '"
                                                data-gameid="' . $game->appId . '"
                                                data-minutestotal="' . $game->minutesTotal . '"
                                                data-minutes2weeks="' . $game->minutes2Weeks . '"
                                                data-status="' . $game->gameStatus . '"
                                                data-numowned="' . $game->numOwned . '"
                                                data-numbeaten="' . $game->numBeaten . '"
                                                data-numblacklisted="' . $game->numBlacklisted . '"
                                                ' . $achievements . ' />';
                        } else {
                            $string .= '<div class="empty_game_box"></div>';
                        }
                        $string .= '</div>';
                        // We need to append the actual box now that we know whether the game has been marked as beaten or not
                        echo '<div id="game_box_' . $x . '" class="game_box' . $beaten . '">' . $string;
                    }
                    ?>
                </div>
            </div>
        </header>
    <?PHP
    }

    /**
     * Displays the Steam User's list of games.
     */
    private function gamesList()
    {
        ?>
        <div class="list_wrapper">
            <div class="list_body">
                <div class="list_boxes">
                    <?PHP
                    // Iterate through all games and create the game cards
                    foreach ($this->steamUser->games as $gameObject) {
                        /** @var SteamGame $gameObject */

                        // Games that are in the "to beat" list need the disabled CSS class which this adds
                        $disabled = '';
                        if ($gameObject->gameStatus == 1) {
                            $disabled .= ' beaten';
                        } else if(!$gameObject->minutesTotal) {
                            $disabled .= ' unplayed';
                        }

                        if ($gameObject->gameSlot) {
                            $disabled .= ' disabled';
                        }

                        if ($gameObject->gameStatus == 2) {
                            $disabled = ' blacklisted';
                        }

                        // If the game has neither a logo nor a icon, it's probably a test app not meant to be played ( e.g. dota 2 test, X-Com PreOrder Bonus )
                        if ($gameObject->logoHash || $gameObject->iconHash) {

                            $logo = 'http://media.steampowered.com/steamcommunity/public/images/apps/' . $gameObject->appId . '/';
                            if ($gameObject->logoHash) {
                                $logo .= $gameObject->logoHash;
                            } else {
                                $logo .= $gameObject->iconHash;
                            }
                            $logo .= '.jpg';

                            // Only attach data-achievper if it has community stats
                            $achievements = '';
                            if ($gameObject->community) {
                                $achievements = 'data-achievper="' . $gameObject->achievementPercentage . '"';
                            }

                            echo '<div id="' . $gameObject->appId . '" class="list_box' . $disabled . '">
                                    <img class="game_image"
                                    src="' . $logo . '"
                                    width="184" height="69"
                                    alt="' . htmlentities(stripslashes($gameObject->name)) . '"
                                    data-gameid="' . $gameObject->appId . '"
                                    data-minutestotal="' . $gameObject->minutesTotal . '"
                                    data-minutes2weeks="' . $gameObject->minutes2Weeks . '"
                                    data-status="' . $gameObject->gameStatus . '"
                                    data-numowned="' . $gameObject->numOwned . '"
                                    data-numbeaten="' . $gameObject->numBeaten . '"
                                    data-numblacklisted="' . $gameObject->numBlacklisted . '"
                                    ' . $achievements . ' />
                                 </div>';
                        }
                    }

                    // While the page is still in active development, display debug data at the bottom
                    //echo '<p>Local data last updated '.$this->steamUser->lastUpdate.' seconds ago.</p>';
                    //echo '<p id="debuglog"> JS Debug: </p>';
                    //echo '<p>&nbsp;<br/>&nbsp;</p>';
                    ?>
                </div>
            </div>
        </div>
    <?PHP
    }

    /**
     * Displays the settings pop-up window. Also contains the state of some toggles.
     */
    private function settings($toBeatNum, $considerBeaten, $hideQuickStats, $hideAccountStats)
    {
        ?>
        <div id="settings" data-tobeat="<?= $toBeatNum ?>" data-considerbeaten="<?= $considerBeaten ?>"
             data-hidequickstats="<?= $hideQuickStats ?>" data-hideaccountstats="<?= $hideAccountStats ?>">
            <div class="settings">
                <form id="scsettings" autocomplete="off">
                    <label for="numgames">Number of games you wish to play concurrently</label>
                    <select name="numgames" id="numgames">
                        <?PHP
                        for ($x = 1; $x <= 10; $x++) {
                            if ($x == $toBeatNum) {
                                echo '<option selected>' . $x . '</option>';
                            } else {
                                echo '<option>' . $x . '</option>';
                            }
                        }
                        ?>
                    </select>

                    <div id='numgamesslider'></div>
                    <br>
                    <?PHP
                    if ($considerBeaten) {
                        ?><input id="considerbeaten" type="checkbox" name="considerbeaten" checked><?PHP
                    } else {
                        ?><input id="considerbeaten" type="checkbox" name="considerbeaten"><?PHP
                    }
                    ?>
                    <label for="considerbeaten">Consider beaten game for random game selection</label>
                    <br>
                    <?PHP
                    if ($hideQuickStats) {
                        ?><input id="hidequickstats" type="checkbox" name="hidequickstats" checked><?PHP
                    } else {
                        ?><input id="hidequickstats" type="checkbox" name="hidequickstats"><?PHP
                    }
                    ?>
                    <label for="hidequickstats">Hide the quick stats shown in the bottom right corner of games with
                        active info cards</label>
                    <br>
                    <?PHP
                    if ($hideAccountStats) {
                        ?><input id="hideaccountstats" type="checkbox" name="hideaccountstats" checked><?PHP
                    } else {
                        ?><input id="hideaccountstats" type="checkbox" name="hideaccountstats"><?PHP
                    }
                    ?>
                    <label for="hideaccountstats">Hide the account stats shown as the left-most entry in the "Beat these games" category</label>
                </form>
            </div>
        </div>
    <?PHP
    }

    /**
     * Displays the HTML for the Terms / FAQ pop-up window. Also includes the userid for JS.
     *
     * @param int $userid
     */
    private function terms($userid = 0)
    {
        ?>
        <div id="terms" data-user="<?= $userid ?>">
            <div class="terms">
                <p><strong>Are you collecting my Steam login information?</strong><br>
                    Absolutely not! The whole login process actually takes place on the Steam website and is completely
                    independent from my website.
                    It uses a system called <a href="http://en.wikipedia.org/wiki/OpenId">OpenID</a>. More information
                    on this can be found on valves <a href="http://steamcommunity.com/dev">Web API documentation</a>
                    page.
                    If that is not enough for you, you can also check the source code of this website yourself at <a
                        href="https://github.com/Bio2hazard/SteamCompletionist">GitHub</a>. ;)</p>

                <p><br><strong>Why does my profile have to be set to public?</strong><br>
                    Any setting other than "Public" makes it so the website cannot retrieve any games-related
                    information for your account, rendering the site pointless.
                    The implications of having your account set to public means that strangers on the internet can view
                    what games you own and how much you have been playing them if they know either your Steam ID or your
                    Steam vanity name.
                    Personally the thought of that doesn't bother me, but your mileage might vary.
                </p>

                <p><br><strong>What cookies?</strong><br>
                    My website saves a total of 3 cookies, which are necessary to provide you with the best experience.<br>
                    The first cookie is a so called session cookie. This cookie provides a easy and secure way for the
                    website to remember you, so long as you do not close your browser.<br>
                    Then we have the userid cookie. This cookie simply contains your steamid.<br>
                    Finally, we have the auth cookie. This cookie contains a secure hash that is randomly and securely
                    generated for you. The means to recreate the hash is stored in the database and together it can be used to authentify you.<br>
                    The userid and auth cookies are used to remember you throughout several sessions and ensure that you
                    don't have to type in your login credentials on every visit.<br>
                    All of the cookies are keyed to this domain and are not used to track you or anything like that.<br>
                    <b>They do not contain any of your steam login credentials. My website does not even have access to your steam credentials!</b>
                </p>

                <p><br><strong>What's the point of this website?</strong><br>
                    To motivate you to beat your Steam games, of course! People pick up plenty of Steam games that they
                    forget about. My website aims to provide an easy way to display and launch your games and of course
                    award motivational points for playing games!</p>

                <p><br><strong>Points?</strong><br>
                    Yes! Every minute you spend playing a game on your "to beat" list earns you points when you remove
                    the game from the "to beat" list! They serve no purpose at all but hey, points!</p>

                <p><br><strong>What Steam information do you store?</strong><br>
                    I only store things that make your browsing experience better, nothing more and nothing else.<br>
                    Our general user database stores: Steam ID, Display name, last status, URL to your community
                    profile, your avatar's hash and your profile visibility state.<br>
                    Our game db stores information on specific games: App ID, Game Name, Community Enabled, Logo Hash
                    and Icon Hash.<br>
                    Finally, our owned games db combines our user database with our game database: App ID, Steam ID,
                    total minutes played, minutes played in the last 2 weeks and achievement percentage.<br>
                    This information is stored for the sole purpose of displaying the website as fast as possible.
                    Contacting the Steam servers to get up-to-date data can take up to 20 seconds, so on every request
                    my website caches vital information in the local database to ensure the site loads quickly. None of
                    the data is shared with any 3rd parties or used commercially.</p>

                <p><br><strong>Are you affiliated with Steam?</strong><br>
                    No. I love Steam and use it daily, but this is just a fan site using tools available to everyone who
                    has a (free) API key.</p>

                <p><br><strong>I am currently looking for a job!</strong><br>
                    So if you have use for a PHP / Web Programmer ( I can also do C/C++/C#, Adobe FLEX and more! ),
                    please contact me via email: <a href="mailto:felix@chapterfain.com">felix@chapterfain.com</a></p>
            </div>

        </div>
    <?PHP
    }

    /**
     * Displays the footer.
     */
    private function footer()
    {
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
     * Builds the website by calling the individual methods that make up the website.
     */
    public function display()
    {
        if ($this->steamUser) {
            $this->header($this->steamUser->personaName . ' - SteamCompletionist.net');
        } else {
            $this->header('SteamCompletionist.net');
        }

        $this->userBar();

        if ($this->steamUser && $this->steamUser->profileState == 3) {
            $this->toBeatBar($this->steamUser->toBeatNum, $this->steamUser->hideAccountStats);
            $this->gamesList();
        } elseif ($this->steamUser) {
            // Profile is private.
            $this->privateProfile();
        }

        if ($this->steamUser) {
            $this->settings($this->steamUser->toBeatNum, $this->steamUser->considerBeaten, $this->steamUser->hideQuickStats, $this->steamUser->hideAccountStats);
            $this->terms($this->steamUser->steamId);
        } else {
            $this->terms();
        }

        $this->footer();

    }

}