<?php
/**
 * Class Config holds project-wide configurations and settings
 * Adjust as desired.
 *
 * @author Felix Kastner <felix@chapterfain.com>
 */
class Config
{
    public $db = array();
    public $session = array();
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->db['host'] = 'localhost';
        $this->db['user'] = 'username';
        $this->db['pass'] = 'password';
        $this->db['schema'] = 'schema';
        $this->db['port'] = 3306;
        $this->db['persistent'] = true;
        $this->db['engine'] = 'mysql';
        $this->db['options'] = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC);
        $this->session['safeword'] = 'replace_me';
        $this->session['ipcheck'] = 2;
        $this->session['authTable'] = 'steamUserDB';
        $this->session['userColumn'] = 'steamid';
        $this->session['cookieLifetime'] = 30758400;
        $this->openId['host'] = 'http://www.sample.net';
        $this->openId['identity'] = 'http://steamcommunity.com/openid';
        $this->openId['return'] = 'http://www.sample.net';
        $this->logger['debugLog'] = false;
        $this->logger['userLog'] = true;
        $this->steam['key'] = 'KEY_GOES_HERE';
    }
}
?>
