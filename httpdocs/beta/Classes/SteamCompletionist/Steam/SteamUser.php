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

    public $personaName, $personaState, $profileUrl, $avatarHash, $points, $lastUpdate, $profileState;

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
    }

    /**
     * Gets list of games for AJAX request
     *
     * @return array
     */
    public function getGameData()
    {
        $returnArray = array();
        foreach($this->games as $game) {
            if($game->logoHash || $game->iconHash) {
                if($game->community) {
                    $returnArray[$game->appId] = array('name' => $game->name, 'minutestotal' => $game->minutesTotal, 'minutes2weeks' => $game->minutes2Weeks, 'achievper' => $game->achievementPercentage);
                } else {
                    $returnArray[$game->appId] = array('name' => $game->name, 'minutestotal' => $game->minutesTotal, 'minutes2weeks' => $game->minutes2Weeks);
                }
            }
        }
        return $returnArray;
    }

    /**
     * Gets list of games to delete for AJAX request
     *
     * @return array
     */
    public function getDeleteList()
    {
        $returnArray = array();
        foreach($this->deleteList as $game) {
            $returnArray[$game->appId] = array();
        }
        return $returnArray;
    }

    /**
     * Gets user data for AJAX request
     *
     * @return mixed
     */
    public function getUserData()
    {
        $returnArray['class'] = $this->getStatusClass($this->personaState);
        $returnArray['status'] = $this->getStatusString($this->personaState);
        $returnArray['name'] = $this->personaName;
        return $returnArray;
    }

    /**
     * Updates the status of a game
     *
     * @param $appId
     * @param $status
     * @throws \Exception
     */
    public function setStatus($appId, $status)
    {
        if(!isset($this->games[$appId])) {
            throw new Exception('Can\'t change status for game ' . $appId . ' as you do not own it.');
        }

        /** @var SteamGame $game */
        $game = $this->games[$appId];
        if($game && $game->gameStatus != 1) {

            if($game->gameSlot) {
                $this->setSlot($appId, 0);
            }

            $this->db->prepare('UPDATE `ownedGamesDB` SET `gameStatus` = ? WHERE `steamid` = ? AND `appid` = ?');
            $this->db->execute(array($status, $this->steamId, $game->appId), 'isi');

            $game->gameStatus = $status;

        } else {
            throw new Exception('Can\'t change status of a beaten game.');
        }
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
        if($game && $game->gameStatus != 2) {
            if($slot == 0) {
                $this->db->prepare('UPDATE `ownedGamesDB` SET `gameSlot` = null, `minutesTotalStored` = 0 WHERE `steamid` = ? AND `appid` = ?');
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
                $this->db->prepare('UPDATE `ownedGamesDB` SET `gameSlot` = null, `minutesTotalStored` = 0 WHERE `steamid` = ? AND `gameSlot` = ?');
                $this->db->execute(array($this->steamId, $slot), 'si');

                // Set new slot
                $this->db->prepare('UPDATE `ownedGamesDB` SET `gameSlot` = ?, `minutesTotalStored` = minutesTotal WHERE `steamid` = ? AND `appid` = ?');
                $this->db->execute(array($slot, $this->steamId, $game->appId), 'isi');

                if(isset($this->slots[$slot]))
                    $removedGame = (isset($this->games[$this->slots[$slot]])) ? $this->games[$this->slots[$slot]] : false;

                $this->slots[$slot] = $game;
            }

            if($this->db->getAffected()) {
                $this->logger->addEntry('Game slot updated');

                if($removedGame)
                {
                    $points = (int)((($removedGame->minutesTotal)-($removedGame->minutesStored)));
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
            $this->db->prepare('SELECT `personaname`, `personastate`, `points`, (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(`lastUpdate`)) as `lastUpdate`, `profileurl`, `profilestate`, `avatar` FROM `steamUserDB` WHERE `steamid` = ?');
            $this->db->execute(array($this->steamId), 's');
            $result = $this->db->fetch();

            if($result && $result['profilestate'] === '3') {
                $this->personaName = $result['personaname'];
                $this->personaState = $result['personastate'];
                $this->points = $result['points'];
                $this->lastUpdate = $result['lastUpdate'];
                $this->profileUrl = $result['profileurl'];
                $this->profileState = $result['profilestate'];
                $this->avatarHash = $result['avatar'];
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
        //$userInfo = @json_decode($this->util->file_get_contents_curl('http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . $this->config['key'] . '&steamids=' . $this->steamId))->response->players[0];
        $userInfo = @json_decode($this->util->file_get_contents_curl('http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . $this->config['key'] . '&steamids=' . $this->steamId), true)['response']['players'][0];

        if(!$userInfo) {
            throw new Exception('The steam servers failed to respond to the user info request, probably due to heavy load.');
        }
        $this->logger->addEntry('Grabbed userdata from steam servers.');

        $this->personaName = $userInfo['personaname'];
        $this->personaState = $userInfo['personastate'];
        $this->profileState = $userInfo['communityvisibilitystate'];
        $this->profileUrl = substr($userInfo['profileurl'], 26);
        $this->avatarHash = substr($userInfo['avatar'], -44, -4);
        $this->cacheUser();
    }

    /**
     * Caches the user into the user database and updates / creates user's avatar.
     *
     * @throws Exception
     */
    private function cacheUser()
    {
        try {
            $this->db->prepare('INSERT INTO `steamUserDB` (`steamid`, `personaname`, `personastate`, `profileurl`, `avatar`, `profilestate`)
                            VALUES (?, ?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE `personaname` = ?, `personastate` = ?, `profileurl` = ?, `avatar` = ?, `profilestate` = ?, `lastUpdate` = now()');
            $data = array($this->steamId, $this->personaName, $this->personaState, $this->profileUrl, $this->avatarHash, $this->profileState,
                $this->personaName, $this->personaState, $this->profileUrl, $this->avatarHash, $this->profileState);
            $this->db->execute($data, 'ssissisissi');
            $this->logger->addEntry('Updated local userdata.');
            // Legacy implementation that involved storing the avatar locally

//            $steamAvatar = @$this->util->file_get_contents_curl($avatarUrl);
//
//            if(!file_exists($this->avatar) || (md5($steamAvatar) != md5(file_get_contents($this->avatar))))
//            {
//                file_put_contents($this->avatar, $steamAvatar);
//                $this->logger->addEntry('New local avatar created.');
//            }
        } catch(Exception $e) {
            throw $e;
        }
    }

    /**
     * Loads a single game
     *
     * @param int $gameId ID of the game to load
     * @return bool true if account owns game : false if account does not own the game in question
     */
    public function loadLocalGame($gameId)
    {
        $this->db->prepare('SELECT a.`minutesTotal`, a.`minutes2Weeks`, a.`minutesTotalStored`, a.`gameSlot`, a.`gameStatus`, a.`achievPer`, b.`name`, b.`community`, b.`logo`, b.`icon` FROM `ownedGamesDB` AS a, `steamGameDB` AS b WHERE a.`appid` = b.`appid` AND a.`steamid` = ? AND a.`appid` = ?');
        $this->db->execute(array($this->steamId, $gameId), 'si');
        $result = $this->db->fetch();

        if($result)
        {
            $this->games[$gameId] = new SteamGame($gameId, $this->config, $this->db, $this->logger, $this->util, $result['name'], $result['minutesTotal'], $result['minutes2Weeks'], $result['minutesTotalStored'], $result['gameSlot'], $result['gameStatus'], $result['achievPer'], $result['logo'], $result['icon'], $result['community']);
            if($result['gameSlot']) {
                $this->slots[$result['gameSlot']] = $gameId;
            }
            return true;
        } else {
            return false;
        }

    }

    public function loadLocalGames()
    {
        $this->db->prepare('SELECT a.`appid`, a.`minutesTotal`, a.`minutes2Weeks`, a.`minutesTotalStored`, a.`gameSlot`, a.`gameStatus`, a.`achievPer`, b.`name`, b.`community`, b.`logo`, b.`icon` FROM `ownedGamesDB` AS a, `steamGameDB` AS b WHERE a.`appid` = b.`appid` AND a.`steamid` = ? ORDER BY a.`minutes2Weeks` DESC, b.`name` ASC');
        $this->db->execute(array($this->steamId), 's');
        $result = $this->db->fetch();

        if($result)
        {
            // Games Found
            do {
                $this->games[$result['appid']] = new SteamGame($result['appid'], $this->config, $this->db, $this->logger, $this->util, $result['name'], $result['minutesTotal'], $result['minutes2Weeks'], $result['minutesTotalStored'], $result['gameSlot'], $result['gameStatus'], $result['achievPer'], $result['logo'], $result['icon'], $result['community']);
                if($result['gameSlot']) {
                    $this->slots[$result['gameSlot']] = $result['appid'];
                }
            } while ($result = $this->db->fetch());

            $this->logger->addEntry('Grabbed game data from local database.');
        } else {
            // No Games Found
            $this->loadRemoteGames();
        }
    }

    public function loadRemoteGames()
    {
        $this->logger->addEntry('Requesting game data from Steam servers.');

        $gameData = null;
        $gameList = array();

        $gameData = @json_decode($this->util->file_get_contents_curl('http://api.steampowered.com/IPlayerService/GetOwnedGames/v0001/?key=' . $this->config['key'] . '&steamid=' . $this->steamId . '&include_appinfo=1&include_played_free_games=1&format=json'), true)['response'];

        if(!$gameData || isset($gameData['error']) || !isset($gameData['games'])) {
            throw new Exception('The game data could not be retrieved. Is your profile set to private? Steam Completionist requires your profile to be set to "Public" in order to retrieve a list of your games.');
        }

        $gameData = $gameData['games'];

        foreach($gameData as $game) {

            // If a game has not been played or has no community stats, we need to set them to a default value to prevent undefined index messages
            $game['playtime_forever'] = isset($game['playtime_forever']) ? $game['playtime_forever'] : 0;
            $game['playtime_2weeks'] = isset($game['playtime_2weeks']) ? $game['playtime_2weeks'] : 0;
            $game['has_community_visible_stats'] = isset($game['has_community_visible_stats']) ? $game['has_community_visible_stats'] : false;

            // If we previously loaded this game locally we can carry over some parameters
            if(isset($this->games[$game['appid']])) {
                $game = new SteamGame($game['appid'],
                    $this->config,
                    $this->db,
                    $this->logger,
                    $this->util,
                    $game['name'],
                    $game['playtime_forever'],
                    $game['playtime_2weeks'],
                    $this->games[$game['appid']]->minutesStored,
                    $this->games[$game['appid']]->gameSlot,
                    $this->games[$game['appid']]->gameStatus,
                    $this->games[$game['appid']]->achievementPercentage,
                    $game['img_logo_url'],
                    $game['img_icon_url'],
                    $game['has_community_visible_stats']);
            } else {
                $game = new SteamGame($game['appid'],
                    $this->config,
                    $this->db,
                    $this->logger,
                    $this->util,
                    $game['name'],
                    $game['playtime_forever'],
                    $game['playtime_2weeks'],
                    0,
                    null,
                    0,
                    0,
                    $game['img_logo_url'],
                    $game['img_icon_url'],
                    $game['has_community_visible_stats']);
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