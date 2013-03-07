<?php

namespace Classes\SteamCompletionist\SteamUser;

use Classes\Common\Database\DatabaseInterface;
use Classes\Common\Util\Util;
use Classes\Common\Logger\LoggerInterface;
use \Exception;

/**
 * Handles all steam user account related things.
 *
 * @author Felix Kastner <felix@chapterfain.com>
 */
class SteamUser
{
    private $config, $steamId;

    /** @var DatabaseInterface $db */
    private $db;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var Util $util */
    private $util;

    public $personaName, $personaState, $profileUrl, $avatar, $points, $lastUpdate;

    /**
     * Constructor.
     *
     * @param $config
     * @param $steamId
     * @param DatabaseInterface $db
     * @param Util $util
     * @param LoggerInterface $logger
     */
    public function __construct($config, $steamId, DatabaseInterface $db, Util $util, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->steamId = $steamId;
        $this->db = $db;
        $this->util = $util;
        $this->logger = $logger;

        $this->avatar = './img/avatar/'.$steamId.'.jpg';
    }

    /**
     * Loads userdata from the local database.
     */
    public function loadLocalUserInfo()
    {
        $this->db->prepare('SELECT `personaname`, `personastate`, `points`, (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(`lastUpdate`)) as `lastUpdate`, `profileurl` FROM steamUserDB WHERE steamid = ?');
        $this->db->execute(array($this->steamId), 's');
        $result = $this->db->fetch();

        if($result) {
            $this->personaName = $result['personaname'];
            $this->personaState = $result['personastate'];
            $this->points = $result['points'];
            $this->lastUpdate = $result['lastUpdate'];
            $this->profileUrl = $result['profileurl'];
            $this->logger->addEntry('Grabbed userdata from local database.');
        } else {
            $this->loadRemoteUserInfo();
        }
    }

    /**
     * Loads userdata from the steam servers.
     *
     * @throws Exception
     */
    public function loadRemoteUserInfo()
    {
        $userInfo = @json_decode($this->util->file_get_contents_curl('http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . $this->config['key'] . '&steamids=' . $this->steamId))->response->players[0];
        if(!$userInfo) {
            throw new Exception('The steam servers failed to respond to the request, probably due to heavy load.');
        }
        $this->logger->addEntry('Grabbed userdata from steam servers.');

        $this->personaName = $userInfo->personaname;
        $this->personaState = $userInfo->personastate;
        $this->profileUrl = $userInfo->profileurl;
        $this->cacheUser($userInfo->avatar);
    }

    /**
     * Caches the user into the user database and updates / creates user's avatar.
     *
     * @param string $avatarUrl URL of Steam Avatar
     */
    private function cacheUser($avatarUrl)
    {
        $this->db->prepare('INSERT INTO steamUserDB (steamid, personaname, personastate, profileurl)
                            VALUES (?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE personaname = ?, personastate = ?, profileurl = ?, lastUpdate = now()');
        $data = array($this->steamId, $this->personaName, $this->personaState, $this->profileUrl,
                                      $this->personaName, $this->personaState, $this->profileUrl);
        $this->db->execute($data, 'ssissis');
        $this->logger->addEntry('Updated local userdata.');

        $steamAvatar = $this->util->file_get_contents_curl($avatarUrl);

        if(!file_exists($this->avatar) || (md5($steamAvatar) != md5(file_get_contents($this->avatar))))
        {
            file_put_contents($this->avatar, $steamAvatar);
            $this->logger->addEntry('New local avatar created.');
        }
    }

}