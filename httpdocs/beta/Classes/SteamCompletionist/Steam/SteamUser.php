<?php

namespace Classes\SteamCompletionist\Steam;

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

    public $games = array();
    public $slots = array();
    public $deleteList = array();

    private $statusString = array(
        0 => 'Offline',
        1 => 'Online',
        2 => 'Busy',
        3 => 'Away',
        4 => 'Snooze',
        5 => 'Looking to Trade',
        6 => 'Looking to Play',
    );

    private $statusClass = array(
        0 => '',
        1 => ' online',
        2 => ' online',
        3 => ' online',
        4 => ' online',
        5 => ' online',
        6 => ' online',
    );

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
     * Updates the slot a game is in and awards points if eligible.
     *
     * @param $appId
     * @param $slot
     * @throws \Exception
     */
    public function setSlot($appId, $slot)
    {
        if(!isset($this->games[$appId])) {
            throw new Exception('Can\'t change slot for game ' . $appId . ' as you do not own it.');
        }

        /** @var SteamGame $game */
        $game = $this->games[$appId];
        $removedGame = false;
        if($game && $game->status != 2) {
            if($slot == 0) {
                $this->db->prepare('UPDATE `ownedGamesDB` SET `gameSlot` = null, `hoursTotalStored` = 0.0 WHERE `steamid` = ? AND `appid` = ?');
                $this->db->execute(array($this->steamId, $game->appId), 'si');
                $removedGame = $game;
                foreach($this->slots as $key => $value) {
                    if($value == $game->appId)
                    {
                        unset($this->slots[$key]);
                        break;
                    }
                }
            } else {
                // Wipe pre-existing slot
                $this->db->prepare('UPDATE `ownedGamesDB` SET `gameSlot` = null, `hoursTotalStored` = 0.0 WHERE `steamid` = ? AND `gameSlot` = ?');
                $this->db->execute(array($this->steamId, $slot), 'si');

                // Set new slot
                $this->db->prepare('UPDATE `ownedGamesDB` SET `gameSlot` = ?, `hoursTotalStored` = `hoursTotal` WHERE `steamid` = ? AND `appid` = ?');
                $this->db->execute(array($slot, $this->steamId, $game->appId), 'isi');

                if(isset($this->slots[$slot]))
                    $removedGame = (isset($this->games[$this->slots[$slot]])) ? $this->games[$this->slots[$slot]] : false;

                $this->slots[$slot] = $game;
            }

            if($this->db->getAffected()) {
                $this->logger->addEntry('Game slot updated');

                if($removedGame)
                {
                    $points = (int)((($removedGame->hoursTotal)-($removedGame->hoursStored))*10);
                    $this->db->prepare('UPDATE `steamUserDB` SET `points` = `points` + ? WHERE `steamid` = ?');
                    $this->db->execute(array($points, $this->steamId), 'is');
                    $this->logger->addEntry($points . ' Points awarded for removing game ' . $removedGame->name . ' from a slot.');
                }
            } else {
                throw new Exception('Game slot update failed.');
            }
        } else {
            throw new Exception('Can\'t change slot of a blacklisted game.');
        }
    }


    /**
     * Gets the string to use for the user's current status
     *
     * @param $index
     * @return mixed
     */
    public function getStatusString($index)
    {
        return $this->statusString[$index];
    }

    /**
     * Gets the class to use for the user's current status
     *
     * @param $index
     * @return mixed
     */
    public function getStatusClass($index)
    {
        return $this->statusClass[$index];
    }

    /**
     * Loads userdata from the local database.
     * @throws Exception
     */
    public function loadLocalUserInfo()
    {
        try {
            $this->db->prepare('SELECT `personaname`, `personastate`, `points`, (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(`lastUpdate`)) as `lastUpdate`, `profileurl` FROM `steamUserDB` WHERE `steamid` = ?');
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
        } catch(Exception $e) {
            throw $e;
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
            throw new Exception('The steam servers failed to respond to the user info request, probably due to heavy load.');
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
     * @throws Exception
     */
    private function cacheUser($avatarUrl)
    {
        try {
            $this->db->prepare('INSERT INTO `steamUserDB` (`steamid`, `personaname`, `personastate`, `profileurl`)
                            VALUES (?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE `personaname` = ?, `personastate` = ?, `profileurl` = ?, `lastUpdate` = now()');
            $data = array($this->steamId, $this->personaName, $this->personaState, $this->profileUrl,
                $this->personaName, $this->personaState, $this->profileUrl);
            $this->db->execute($data, 'ssissis');
            $this->logger->addEntry('Updated local userdata.');

            $steamAvatar = @$this->util->file_get_contents_curl($avatarUrl);

            if(!file_exists($this->avatar) || (md5($steamAvatar) != md5(file_get_contents($this->avatar))))
            {
                file_put_contents($this->avatar, $steamAvatar);
                $this->logger->addEntry('New local avatar created.');
            }
        } catch(Exception $e) {
            throw $e;
        }
    }

    public function loadLocalGames() {
        $this->db->prepare('SELECT a.`appid`, a.`hoursTotal`, a.`hours2Weeks`, a.`hoursTotalStored`, a.`gameSlot`, a.`achievPer`, b.`name`, b.`internal` FROM `ownedGamesDB` AS a, `steamGameDB` AS b WHERE a.`appid` = b.`appid` AND a.`steamid` = ? ORDER BY a.`hours2Weeks` DESC, b.`name` ASC');
        $this->db->execute(array($this->steamId), 's');
        $result = $this->db->fetch();

        if($result)
        {
            // Games Found
            do {
                $this->games[$result['appid']] = new SteamGame($result['appid'], $this->db, $this->logger, $this->util, $result['name'], $result['hoursTotal'], $result['hours2Weeks'], $result['hoursTotalStored'], $result['internal'], $result['gameSlot'], $result['achievPer']);
                if($result['gameSlot']) {
                    $this->slots[$result['gameSlot']] = $result['appid'];
                }
            } while ($result = $this->db->fetch());

            $this->logger->addEntry('Grabbed game data from local database.');
        } else {
            // No Games Found

            // @todo: notify that games need to be loaded in still
            //if(!$this->force)
            //    $this->steamLoadGames(false);
        }
    }

    public function loadSteamGames()
    {
        $this->logger->addEntry('Requesting game data from Steam servers.');

        $counter = 0;
        $gameData = null;
        $gameList = array();

        while($counter <= 15 && !$gameData = @simplexml_load_string($this->util->file_get_contents_curl($this->profileUrl . '/games/?xml=1&amp;l=english'))) {
            $this->logger->addEntry('Request failed.');
            sleep(1);
            $counter++;
            $this->logger->addEntry('Requesting game data from Steam servers.');
        }

        if(!$gameData) {
            throw new Exception('The steam servers failed to respond to the game data requests, probably due to heavy load.');
        }

        if($gameData->error) {
            throw new Exception('The game data could not be retrieved. Is your profile set to private? Steam Completionist requires your profile to be set to "Public" in order to retrieve your list of games.');
        }

        foreach($gameData->games->game as $gameObject) {
            $internal = substr(substr((string)$gameObject->globalStatsLink, 32),0, -14);

            // If we previously loaded this game locally we can carry over some parameters
            if(isset($this->games[(string)$gameObject->appID])) {
                $game = new SteamGame((string)$gameObject->appID,
                    $this->db,
                    $this->logger,
                    $this->util,
                    (string)$gameObject->name,
                    (string)$gameObject->hoursOnRecord,
                    (string)$gameObject->hoursLast2Weeks,
                    $this->games[(string)$gameObject->appID]->hoursStored,
                    $internal,
                    $this->games[(string)$gameObject->appID]->gameSlot,
                    $this->games[(string)$gameObject->appID]->achievementPercentage,
                    (string)$gameObject->logo);
            } else {
                $game = new SteamGame((string)$gameObject->appID,
                    $this->db,
                    $this->logger,
                    $this->util,
                    (string)$gameObject->name,
                    (string)$gameObject->hoursOnRecord,
                    (string)$gameObject->hoursLast2Weeks,
                    '0.0',
                    $internal,
                    null,
                    0,
                    (string)$gameObject->logo);
            }

            $game->saveGame($this->steamId);
            $gameList[$game->appId] = $game;

        }

        $removedGames = array_diff_key($this->games, $gameList);

        /** @var SteamGame $value */
        foreach($removedGames as $value)
        {
            if($value->gameSlot) {
                $this->setSlot($value->appId, 0);
            }
            $this->deleteList[$value->appId] = $value;
            $value->deleteGame($this->steamId);
        }

        $this->games = $gameList;
        $this->logger->addEntry('Retrieved game data from steam servers.');
    }

}