<?PHP
/**
 * Main index file.
 * Prepares everything and then uses the WebSite class to display the website.
 *
 * @author Felix Kastner <felix@chapterfain.com>
 * @todo: Use the search for vanity URL steam API call
 */

// Set up the session ( SGS = Steam Game Session )
session_name('SGS');
session_start();

// We need to set the time out to make sure out steam API requests go through
ini_set('default_socket_timeout', 30);

require 'Classes/Common/AutoLoader/AutoLoader.php';
require '../private/Config.php';

$autoLoader = new Classes\Common\AutoLoader\AutoLoader();

use Classes\Common\DI\Pimple as Pimple;
use Classes\Common\Database\PdoDb as Db;
use Classes\Common\User\User as User;
use Classes\Common\OpenID\LightOpenID as LightOpenID;
use Classes\Common\Logger\DbLogger as DbLogger;
use Classes\Common\Util\Util as Util;
use Classes\SteamCompletionist\Steam\SteamUser as SteamUser;
use Classes\SteamCompletionist\Steam\SteamUserSearch as SteamUserSearch;
use Classes\SteamCompletionist\Html\WebSite as WebSite;

$website = new WebSite();

try {
    $c = new Pimple();

    $c['config'] = $c->share(function () {
        return new Config();
    });

    $c['db'] = $c->share(function ($c) {
        return new Db($c['config']->db);
    });

    $c['logger'] = $c->share(function ($c) {
        return new DbLogger($c['config']->logger, $c['db']);
    });

    $c['user'] = $c->share(function ($c) {
        return new User($c['config']->session, $_SERVER, $_SESSION, $_COOKIE, $c['db'], $c['logger']);
    });

    $c['openid'] = $c->share(function ($c) {
        return new LightOpenID($c['config']->openId);
    });

    $c['util'] = $c->share(function ($c) {
        return new Util($c['logger']);
    });

    /** @var \Classes\Common\Database\DatabaseInterface $db */
    $db = $c['db'];

    /** @var LightOpenID $openid */
    $openid = $c['openid'];

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

    if ($openid->mode === 'id_res' && $openid->validate()) {
        // Login Successful
        $user->userId = substr($openid->data['openid_claimed_id'], 36);

        $c['steamUser'] = $c->share(function ($c) {
            return new SteamUser($c['config']->steam, $c['user']->userId, $c['db'], $c['util'], $c['logger'], true);
        });

        /** @var SteamUser $steamUser */
        $steamUser = $c['steamUser'];

        $steamUser->loadLocalUserInfo();

        $user->login($user->userId);

        $_SESSION = $user->session;
        $_COOKIE = $user->cookie;

        header('Location: http://' . $_SERVER['HTTP_HOST'] . substr($_SERVER['REQUEST_URI'], 0, (strpos($_SERVER['REQUEST_URI'], '?')-1)) . '/' . $user->userId . '/');
    } elseif ($openid->mode) {
        throw new Exception('Login through Steam failed.');
    }

    if (isset($_GET['login'])) {
        header('Location: ' . $openid->authUrl());
    } elseif (isset($_GET['logout'])) {
        $user->logout();
        $_SESSION = $user->session;
        $_COOKIE = $user->cookie;
        header('Location: http://' . $_SERVER['HTTP_HOST'] . substr($_SERVER['REQUEST_URI'], 0, (strpos($_SERVER['REQUEST_URI'], '?')-1)));
    } elseif ($user->loggedIn() && !isset($_GET['user'])) {
        header('Location: http://' . $_SERVER['HTTP_HOST'] . substr($_SERVER['REQUEST_URI'], 0, (strpos($_SERVER['REQUEST_URI'], '?')-1)) . '/' . $user->userId . '/');
    }

    // If user is not logged in and isn't looking at someone elses profile, we can exit out of the try block and show the website in logged out state.
    if (!isset($_GET['user'])) {
        throw new Exception;
    }

    $c['steamUser'] = $c->share(function ($c) {
        return new SteamUser($c['config']->steam, $_GET['user'], $c['db'], $c['util'], $c['logger'], $_GET['user'] === $c['user']->userId);
    });

    /** @var User $loggedUser */
    $loggedUser = $c['user'];

    /** @var SteamUser $steamUser */
    $steamUser = $c['steamUser'];

    // Load the local userdata ( will fall back to remote loading if no local data is available )
    $steamUser->loadLocalUserInfo();

    // We need at least the localUserInfo loaded in order to tell whether this profile is set to private or not.
    if($steamUser->private && !$steamUser->isOwner) {
        throw new Exception('This profile is private.');
    }

    // Load the local gamedata ( will fall back to remote loading if no local data is available )
    $steamUser->loadLocalGames();

    // Populate websites' steamUser
    $website->setSteamUser($steamUser);
    $website->setUser($loggedUser);

} catch (Exception $e) {
    $website->error = $e->getMessage();
}

$website->display();

try {
    $logger->addEntry('Finished');
} catch (Exception $e) {
}