<?php

namespace Classes\SteamCompletionist\Steam;

use Classes\Common\Database\DatabaseInterface;
use Classes\Common\Util\Util;
use Classes\Common\Logger\LoggerInterface;
use \Exception;

/**
 * Handles all Steam User related things.
 *
 * @author Felix Kastner <felix@chapterfain.com>
 */
class SteamUserSearch
{
    /**
     * Holds a array of configuration values used by the SteamUser Class.
     * @var array
     */
    private $config;

    /**
     * The database connection.
     * @var DatabaseInterface $db
     */
    private $db;

    /**
     * The logger, used to log debug messages.
     * @var LoggerInterface $logger
     */
    private $logger;

    /**
     * Utility Class.
     * @var Util $util
     */
    private $util;

    /**
     * Contains what we will be searching for.
     * @var int|string
     */
    private $searchString;

    /**
     * Constructor
     *
     * @param $config
     * @param DatabaseInterface $db
     * @param Util $util
     * @param LoggerInterface $logger
     */
    public function __construct($config, DatabaseInterface $db, Util $util, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->db = $db;
        $this->util = $util;
        $this->logger = $logger;
    }

    /**
     * Searches for a Steam ID 64, first in our local SteamCompletionist database, then through the Steam API
     *
     * @return bool|int|string
     */
    private function searchSteamID64()
    {
        // First we do a local check - see if we have that 64 bit ID in the SteamCompletionist database
        $this->db->prepare('SELECT `private` FROM `steamUserDB` WHERE `steamid` = ?');
        $this->db->execute(array($this->searchString), 's');
        $result = $this->db->fetch();

        if($result) {
            return $this->searchString;
        }

        // We're still here, so we need to query the remote servers via API call
        $userInfo = @json_decode($this->util->file_get_contents_curl('http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . $this->config['key'] . '&steamids=' . $this->searchString), true)['response']['players'][0];
        $this->db->prepare('INSERT INTO `steamAPIUsage` SET `module` = 1');
        $this->db->execute();

        if($userInfo) {
            return $userInfo['steamid'];
        }
        return false;
    }

    /**
     * Searches for a Steam Custom ID via API call.
     *
     * @return bool|string
     */
    private function searchSteamCustom()
    {
        $userInfo = @json_decode($this->util->file_get_contents_curl('http://api.steampowered.com/ISteamUser/ResolveVanityURL/v0001/?key=' . $this->config['key'] . '&vanityurl=' . urlencode($this->searchString)), true)['response']->steamId;
        $this->db->prepare('INSERT INTO `steamAPIUsage` SET `module` = 1');
        $this->db->execute();

        if($userInfo) {
            return $userInfo['steamid'];
        }
        return false;
    }

    /**
     * Searches for a Steam Persona in the local SteamCompletionist database.
     *
     * @return bool|string
     */
    private function searchSteamPersona()
    {
        $this->db->prepare('SELECT `steamid` FROM `steamUserDB` WHERE `personaname` = ?');
        $this->db->execute(array($this->searchString), 's');
        $result = $this->db->fetch();

        if($result) {
            return $result['steamid'];
        }
        return false;
    }

    /**
     * Searches through all possible methods to obtain a steamid to use.
     *
     * @param $searchString
     * @return string
     */
    public function search($searchString)
    {
        $this->searchString = $searchString;
        $result = '';

        if(is_numeric($this->searchString) && strlen($this->searchString) === 17 && $result = $this->searchSteamID64()) {
            // Possibly a 64 bit steam Id
            return $result;
        }

        // Next, we search for the custom URL
        if($result = $this->searchSteamCustom()) {
            return $result;
        }

        // If all else fails, we search for the personaname in our SteamCompletionist database
        if($result = $this->searchSteamPersona()) {
            return $result;
        }

        return false;
    }

}