<?PHP
if(substr($_SERVER['HTTP_HOST'], 0, 4) !== 'www.') {
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
use Classes\Common\Database\MySqlDb as MySqlDb;
use Classes\Common\Database\PdoDb as PdoDb;
use Classes\Common\User\User as User;
use Classes\Common\OpenID\LightOpenID as LightOpenID;
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8" />
    <title>Mapper Test</title>
</head>
<body>
<?php
try {
    $c = new Pimple();

    $c['config'] = $c->share(function() {
        return new Config();
    });

    /** @todo change DB functions to take $c['config']->db as array instead of individual values */
    $c['db'] = $c->share(function($c) {
        return new PdoDb($c['config']->db['host'], $c['config']->db['user'], $c['config']->db['pass'], $c['config']->db['schema'], $c['config']->db['port'], $c['config']->db['persistent']);
    });

    $c['user'] = $c->share(function($c) {
        return new User($c['config']->session, $_SERVER, $_SESSION, $_COOKIE, $c['db']);
    });

    $c['openid'] = $c->share(function($c) {
        return new LightOpenID($c['config']->openId);
    });

    /** @var \Classes\Common\Database\DatabaseInterface $db */
    $db = $c['db'];

    /** @var \Classes\Common\OpenID\LightOpenID $openid  */
    $openid = $c['openid'];

    /** @var \Classes\Common\User\User $user */
    $user = $c['user'];

    $_SESSION = $user->session;
    $_COOKIE = $user->cookie;

    if($openid->mode === 'id_res' && $openid->validate()) {
        // Login Successful
        $user->login(substr($openid->data['openid_claimed_id'],36));
        $_SESSION = $user->session;
        $_COOKIE = $user->cookie;
        header('Location: http://' . $_SERVER['HTTP_HOST'] . substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?')));
    } elseif($openid->mode) {
        /** @todo proper "Hey, the login process failed!" message. */
        echo 'Login failed ( ' . $openid->mode . ' )';
    }

    if(isset($_GET['login'])) {
        header('Location: ' . $openid->authUrl());
    } elseif(isset($_GET['logout'])) {
        $user->logout();
        $_SESSION = $user->session;
        $_COOKIE = $user->cookie;
        header('Location: http://' . $_SERVER['HTTP_HOST'] . substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?')));
    }

    echo 'Logged in as user: ' . $user->userId . '<br>' . PHP_EOL;

} catch (Exception $e) {
    echo $e->getMessage();
}

if($user->userId) {
    echo '<a href="?logout">Log out</a>';
} else {
    echo '<a href="?login">Log in</a>';
}

?>
</body>
</html>