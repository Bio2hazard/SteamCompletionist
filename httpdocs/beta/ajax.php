<?PHP
/**
 * Handles all AJAX requests.
 * Mostly just the same as index.php
 *
 * @author Felix Kastner <felix@chapterfain.com>
 */
if (substr($_SERVER['HTTP_HOST'], 0, 4) !== 'www.') {
    header('Location: http://www.' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
}

// To be disabled once the website is entirely done
error_reporting(E_ALL | E_STRICT);

// Set up the session ( SGS = Steam Game Session )
session_name('SGS');
session_start();

// We need to set the time out to make sure out steam API requests go through
ini_set('default_socket_timeout', 30);

require 'Classes/Common/AutoLoader/AutoLoader.php';
require '../../private/Config.php';

$autoLoader = new Classes\Common\AutoLoader\AutoLoader();

use Classes\Common\DI\Pimple as Pimple;
use Classes\Common\Database\PdoDb as PdoDb;
use Classes\Common\User\User as User;
use Classes\Common\OpenID\LightOpenID as LightOpenID;
use Classes\Common\Logger\DbLogger as DbLogger;
use Classes\Common\Util\Util as Util;
use Classes\SteamCompletionist\Steam\SteamUser as SteamUser;

try {
    $c = new Pimple();

    $c['config'] = $c->share(function () {
        return new Config();
    });

    $c['db'] = $c->share(function ($c) {
        return new PdoDb($c['config']->db);
    });

    $c['logger'] = $c->share(function ($c) {
        return new DbLogger($c['config']->logger, $c['db']);
    });

    $c['user'] = $c->share(function ($c) {
        return new User($c['config']->session, $_SERVER, $_SESSION, $_COOKIE, $c['db'], $c['logger']);
    });

    $c['util'] = $c->share(function ($c) {
        return new Util($c['logger']);
    });

    /** @var \Classes\Common\Database\DatabaseInterface $db */
    $db = $c['db'];

    /** @var User $user */
    $user = $c['user'];

    /** @var \Classes\Common\Logger\LoggerInterface $logger */
    $logger = $c['logger'];

    /** @var Util $util */
    $util = $c['util'];

    $_SESSION = $user->session;
    $_COOKIE = $user->cookie;

    $logger->setUser($user->userId);
    $logger->setIP($_SERVER['REMOTE_ADDR']);

    // If user is not logged in, then the AJAX module has nothing to do.
    if (!$user->loggedIn()) {
        throw new Exception('Not logged in.');
    }

    $c['steamUser'] = $c->share(function ($c) {
        return new SteamUser($c['config']->steam, $c['user']->userId, $c['db'], $c['util'], $c['logger']);
    });

    /** @var SteamUser $steamUser */
    $steamUser = $c['steamUser'];


    $return = array();

    switch ($_GET['mode']) {
        case 'getsteamdata':
            $steamUser->loadLocalUserInfo();
            $steamUser->loadLocalGames();
            $steamUser->loadRemoteUserInfo();
            $steamUser->loadRemoteGames();
            $return['steamuser'] = $steamUser->getUserData();
            $return['steamgames'] = $steamUser->getGameData();
            $return['deletelist'] = $steamUser->getDeleteList();
            break;

        case 'savegamestatus':
            // Sanity Checks:
            if (!isset($_GET['value']) || !is_numeric($_GET['value']) || $_GET['value'] > 2 || $_GET['value'] < 0) {
                throw new Exception('Invalid game slot.');
            }
            $status = $_GET['value'];

            if (!$_GET['gid'] || !is_numeric($_GET['gid'])) {
                die(json_encode(array("errorlog" => "Invalid game id. ")));
            }
            $gameId = $_GET['gid'];

            $steamUser->loadLocalUserInfo();

            if ($steamUser->loadLocalGame($gameId)) {
                $steamUser->setStatus($gameId, $status);
            }
            $return['steamuser'] = $steamUser->getUserData();
            break;

        case 'savegameslot':
            // Sanity Checks:
            if (!isset($_GET['value']) || !is_numeric($_GET['value']) || $_GET['value'] > 10 || $_GET['value'] < 0) {
                throw new Exception('Invalid game slot.');
            }
            $slot = $_GET['value'];

            if (!$_GET['gid'] || !is_numeric($_GET['gid'])) {
                throw new Exception('Invalid game id.');
            }
            $gameId = $_GET['gid'];

            $steamUser->loadLocalUserInfo();

            if ($steamUser->loadLocalGame($gameId)) {
                $steamUser->setSlot($gameId, $slot);
            }
            $return['steamuser'] = $steamUser->getUserData();
            break;

        case 'achievpercent':
            // Sanity Checks:
            if (!$_GET['gid'] || !is_numeric($_GET['gid'])) {
                throw new Exception('Invalid game id.');
            }
            $gameId = $_GET['gid'];

            $steamUser->loadLocalGame($gameId);

            if (isset($steamUser->games[$gameId])) {
                /** @var \Classes\SteamCompletionist\Steam\SteamGame $game */
                $game = $steamUser->games[$gameId];
                if ($game->community) {
                    $game->getAchievementPercentage($user->userId);
                    $return['gameachiev'] = array('gameid' => $gameId, 'percentage' => $game->achievementPercentage);
                }
            }
            break;

        case 'savenumtobeat':
            // Sanity Checks:
            if (!isset($_GET['value']) || !is_numeric($_GET['value']) || $_GET['value'] > 10 || $_GET['value'] < 1) {
                throw new Exception('Invalid number for to beat slots.');
            }
            $newToBeatNum = $_GET['value'];

            $steamUser->loadLocalUserInfo();
            $steamUser->loadLocalGames();
            $steamUser->setNumToBeat($newToBeatNum);

            break;

        case 'saveconsiderbeaten':
            // Sanity Checks:
            if (!isset($_GET['value']) || !is_numeric($_GET['value']) || $_GET['value'] > 1 || $_GET['value'] < 0) {
                throw new Exception('Invalid number for consider beaten toggle.');
            }
            $newConsiderBeaten = $_GET['value'];

            $steamUser->loadLocalUserInfo();
            $steamUser->setConsiderBeaten($newConsiderBeaten);

            break;

        case 'savehidequickstats':
            // Sanity Checks:
            if (!isset($_GET['value']) || !is_numeric($_GET['value']) || $_GET['value'] > 1 || $_GET['value'] < 0) {
                throw new Exception('Invalid number for hide quick stats toggle.');
            }
            $newHideQuickStats = $_GET['value'];

            $steamUser->loadLocalUserInfo();
            $steamUser->setHideQuickStats($newHideQuickStats);

            break;

        case 'savehideaccountstats':
            // Sanity Checks:
            if (!isset($_GET['value']) || !is_numeric($_GET['value']) || $_GET['value'] > 1 || $_GET['value'] < 0) {
                throw new Exception('Invalid number for hide account stats toggle.');
            }
            $newHideAccountStats = $_GET['value'];

            $steamUser->loadLocalUserInfo();
            $steamUser->setHideAccountStats($newHideAccountStats);

            break;

        default:
            throw new Exception('No mode specified.');
            break;
    }


    $logger->addEntry('Ajax request finished');
    echo json_encode($return);
} catch (Exception $e) {
    echo json_encode(array('errorlog' => $e->getMessage()));
}