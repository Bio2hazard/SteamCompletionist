<?php

namespace Classes\Common\User;

use Classes\Common\Database\DatabaseInterface;

/**
 * Description
 *
 * @author Felix Kastner <felix@chapterfain.com>
 */
class User
{

    public $userId = 0, $session, $cookie;
    private $config, $server;

    /** @var DatabaseInterface $db */
    private $db;

    /**
     * Constructor. Calls checkLogin()
     *
     * @param array $config         Array containing configuration for session & cookie security
     * @param array $server         Injected $_SERVER array
     * @param array $session        Injected $_SESSION array
     * @param array $cookie         Injected $_COOKIES array
     * @param DatabaseInterface $db Database interface
     */
    public function __construct($config, $server, $session, $cookie, DatabaseInterface $db)
    {
        $this->config = $config;
        $this->server = $server;
        $this->session = $session;
        $this->cookie = $cookie;
        $this->db = $db;

        $this->checkLogin();
    }

    /**
     * Sets up sessions & cookies after a successful login
     *
     * @param int $userid
     */
    public function login($userid)
    {
        $this->userId = $userid;
        $this->createSession();
        $this->createCookie();
    }

    /**
     * Calling createSession after setting the userId to 0 will reset the session & cookie
     */
    public function logout()
    {
        $this->userId = 0;
        $this->createSession();
    }

    /**
     * Checks whether the user is logged in or not
     *
     * @return bool returns true when logged in, false when not logged in
     */
    public function loggedIn()
    {
        if($this->userId)
        {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks both Session and Cookie for valid stored login information
     * If successful, $this->userId gets populated with the active login ID
     */
    private function checkLogin()
    {
        if($this->verifySession()) {
            $this->userId = $this->session['id'];
        }
        if($this->userId === 0) {
            if($this->verifyCookie()) {
                $this->userId = $this->cookie['userid'];
                $this->createSession();
            }
        }
    }

    /**
     * Creates a new valid session.
     * Calls voidCookie for cleanup if login failed.
     */
    private function createSession()
    {
        session_regenerate_id(TRUE);
        $this->session = array();
        $this->session['legit'] = $this->sessionHash();
        $this->session['id'] = $this->userId;
        if(!$this->userId) {
            $this->voidCookie();
        }
    }

    /**
     * Creates a new valid cookie.
     */
    private function createCookie()
    {
        $salt = openssl_random_pseudo_bytes(16);
        $hash = hash('sha256', $salt . $this->userId);

        $this->db->prepare('UPDATE `' . $this->config['authTable'] . '` SET `authSalt` = ? WHERE `' . $this->config['userColumn'] . '` = ?');
        $this->db->execute(array($salt, $this->userId), 'ss');

        setcookie('userid', $this->userId, time() + $this->config['cookieLifetime']);
        setcookie('auth', $hash, time() + $this->config['cookieLifetime']);
    }

    /**
     * Removes the cookies
     */
    private function voidCookie() {
        if(isset($this->cookie['auth'])) {
            setcookie('auth', '', time()-3600);
            unset($this->cookie['auth']);
        }
        if(isset($this->cookie['userid'])) {
            setcookie('userid', '', time()-3600);
            unset($this->cookie['userid']);
        }
    }

    /**
     * Verifies the auth token stored in the cookie using the salt stored in the database.
     *
     * @return bool true = cookie is valid; false = cookie is invalid
     */
    private function verifyCookie() {
        if(isset($this->cookie['auth'])
            && isset($this->cookie['userid'])
            && !empty($this->cookie['auth'])
            && !empty($this->cookie['userid'])
            && ($this->cookie['userid'] > 0)
        ) {
            $this->db->prepare('SELECT `authSalt` FROM `' . $this->config['authTable'] . '` WHERE `' . $this->config['userColumn'] . '` = ?');
            $this->db->execute(array($this->cookie['userid']), 's');
            $result = $this->db->fetch();

            if(!$result) {
                return false;
            }

            $dbHash = hash('sha256', $result['authSalt'] . $this->cookie['userid']);

            if($dbHash === $this->cookie['auth']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verifies whether the session data can be trusted or not.
     *
     * @return bool true = session is valid; false = session is invalid
     */
    private function verifySession() {
        if((isset($this->session['id']))
            && (is_numeric($this->session['id']))
            && (isset($this->session['legit']))
            && ($this->session['legit'] === $this->sessionHash())
        ) {
            return true;
        }
        return false;
    }

    /**
     * Generates a secure session hash based on a safeword + HTTP_USER_AGENT + IP Octets
     *
     * @return string
     */
    private function sessionHash() {
        $hash = $this->config['safeword'];
        $hash .= $this->server['HTTP_USER_AGENT'];
        $ipBlocks = explode('.', $this->server['REMOTE_ADDR']);
        for($i = 0; $i < $this->config['ipcheck']; $i++) {
            $hash .= $ipBlocks[$i];
        }
        return md5($hash);
    }
}