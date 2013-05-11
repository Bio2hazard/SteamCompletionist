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
class SteamUser
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
     * The steamId of the Steam User.
     * @var string
     */
    public $steamId;

    /**
     * The display name of the Steam User.
     * @var string
     */
    public $personaName;

    /**
     * The status of the Steam User, see $statusString.
     * @var int
     */
    public $personaState;

    /**
     * URL to the Steam User's profile page.
     * @var string
     */
    public $profileUrl;

    /**
     * Hash string of the Steam User's avatar to display it from the steam servers.
     * @var string
     */
    public $avatarHash;

    /**
     * The amount of points this Steam User has.
     * @var int
     */
    public $points;

    /**
     * Number of seconds since this Steam User was last updated from the steam servers.
     * @var int
     */
    public $lastUpdate;

    /**
     * The privacy state of the Steam User's profile. We need this to be 3 ( public ).
     * @var int
     */
    public $profileState;

    /**
     * Number of "to beat" slots for this Steam User.
     * @var int
     */
    public $toBeatNum = 5;

    /**
     * Toggle whether beaten games are considered for adding a random game to the "to beat" list.
     * @var int
     */
    public $considerBeaten = 0;

    /**
     * Toggle whether the quick stats are shown or not.
     * @var int
     */
    public $hideQuickStats = 0;

    /**
     * Toggle whether the account stats are shown or not.
     * @var int
     */
    public $hideAccountStats = 0;

    /**
     * Toggle whether the social bar is shown or not.
     * @var int
     */
    public $hideSocial = 0;

    /**
     * Name of the game the Steam User is currently playing.
     * @var string
     */
    public $gameName = '';

    /**
     * Array that holds all Steam Games that this Steam User owns.
     * @var array
     */
    public $games = array();

    /**
     * Array that holds the appIDs of the games in the "to beat" slots.
     * @var array
     */
    public $slots = array();

    /**
     * Array that holds the appIDs of games that are no longer part of this Steam User and thus need to be removed ( free weekends ).
     * @var array
     */
    public $deleteList = array();

    /**
     * Array that maps to $personaState. Used to display the status.
     * @var array
     */
    private $statusString = array(
        0 => 'Offline',
        1 => 'Online',
        2 => 'Busy',
        3 => 'Away',
        4 => 'Snooze',
        5 => 'Looking to Trade',
        6 => 'Looking to Play',
        99 => 'In-Game: ',
    );

    /**
     * Array that maps to $personaState. Used to apply a different class to the status box depending on $personaState.
     * @var array
     */
    private $statusClass = array(
        0 => '',
        1 => ' online',
        2 => ' online',
        3 => ' online',
        4 => ' online',
        5 => ' online',
        6 => ' online',
        99 => ' ingame',
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
     * Gets list of games for AJAX request.
     *
     * @return array
     */
    public function getGameData()
    {
        $returnArray = array();
        foreach ($this->games as $game) {

            /** @var SteamGame $game */

            if ($game->logoHash || $game->iconHash) {
                if ($game->logoHash) {
                    $logo = $game->logoHash;
                } else {
                    $logo = $game->iconHash;
                }
                if ($game->community) {
                    $returnArray[$game->appId] = array('appid' => $game->appId, 'name' => $game->name, 'status' => $game->gameStatus, 'minutestotal' => $game->minutesTotal, 'minutes2weeks' => $game->minutes2Weeks, 'achievper' => $game->achievementPercentage, 'imagehash' => $logo, 'numowned' => $game->numOwned, 'numbeaten' => $game->numBeaten, 'numblacklisted' => $game->numBlacklisted);
                } else {
                    $returnArray[$game->appId] = array('appid' => $game->appId, 'name' => $game->name, 'status' => $game->gameStatus, 'minutestotal' => $game->minutesTotal, 'minutes2weeks' => $game->minutes2Weeks, 'imagehash' => $logo, 'numowned' => $game->numOwned, 'numbeaten' => $game->numBeaten, 'numblacklisted' => $game->numBlacklisted);
                }
            }
        }
        return $returnArray;
    }

    /**
     * Gets list of games to delete for AJAX request.
     *
     * @return array
     */
    public function getDeleteList()
    {
        $returnArray = array();
        foreach ($this->deleteList as $game) {
            $returnArray[$game->appId] = array('appid' => $game->appId);
        }
        return $returnArray;
    }

    /**
     * Returns basic Steam User data in a array.
     *
     * @return mixed
     */
    public function getUserData()
    {
        if ($this->gameName) {
           $state = 99;
        } else {
            $state = $this->personaState;
        }
        $returnArray['class'] = $this->getStatusClass($state);
        $returnArray['avatar'] = 'http://media.steampowered.com/steamcommunity/public/images/avatars/' . substr($this->avatarHash, 0, 2) . '/' . $this->avatarHash . '.jpg';
        $returnArray['status'] = $this->getStatusString($state).$this->gameName;
        $returnArray['name'] = htmlentities(stripslashes($this->personaName));
        $returnArray['points'] = $this->points;
        return $returnArray;
    }

    /**
     * Sets the number of "to beat" slots for the steam user.
     *
     * @param $numToBeat
     */
    public function setNumToBeat($numToBeat)
    {
        $oldToBeat = $this->toBeatNum;
        $this->logger->addEntry('Setting new numToBeat. numToBeat:' . $numToBeat . ' oldToBeat:' . $oldToBeat);

        if ($numToBeat < $oldToBeat) {
            for ($x = $oldToBeat; $x > $numToBeat; $x--) {
                if (isset($this->slots[$x])) {
                    $this->setSlot($this->slots[$x], 0);
                }
            }
        }

        $this->db->prepare('UPDATE `steamUserDB` SET `toBeatNum` = ? WHERE `steamid` = ?');
        $this->db->execute(array($numToBeat, $this->steamId), 'is');
        $this->toBeatNum = $numToBeat;
    }

    /**
     * Updates the considerBeaten flag for the user.
     * considerBeaten is a toggle that makes the website consider beaten games for random game selection.
     *
     * @param $considerBeaten
     */
    public function setConsiderBeaten($considerBeaten)
    {
        $this->db->prepare('UPDATE `steamUserDB` SET considerBeaten = ? WHERE `steamid` = ?');
        $this->db->execute(array($considerBeaten, $this->steamId), 'is');
        $this->considerBeaten = $considerBeaten;
    }

    /**
     * Updates the hideQuickStats flag for the user.
     * hideQuickStats is a toggle that makes the website hide the quick stats on active unit cards.
     *
     * @param $hideQuickStats
     */
    public function setHideQuickStats($hideQuickStats)
    {
        $this->db->prepare('UPDATE `steamUserDB` SET `hideQuickStats` = ? WHERE `steamid` = ?');
        $this->db->execute(array($hideQuickStats, $this->steamId), 'is');
        $this->hideQuickStats = $hideQuickStats;
    }

    /**
     * Updates the hideAccountStats flag for the user.
     * hideAccountStats is a toggle that makes the website hide the google chart that shows percentages of beaten, played, unplayed and blacklisted games.
     *
     * @param $hideAccountStats
     */
    public function setHideAccountStats($hideAccountStats)
    {
        $this->db->prepare('UPDATE `steamUserDB` SET `hideAccountStats` = ? WHERE `steamid` = ?');
        $this->db->execute(array($hideAccountStats, $this->steamId), 'is');
        $this->hideAccountStats = $hideAccountStats;
    }

    /**
     * Updates the hideSocial flag for the user.
     * hideSocial is a toggle that makes the website hide the social bar at the bottom of the site ( for antisocial people like myself! ).
     *
     * @param $hideSocial
     */
    public function setHideSocial($hideSocial)
    {
        $this->db->prepare('UPDATE `steamUserDB` SET `hideSocial` = ? WHERE `steamid` = ?');
        $this->db->execute(array($hideSocial, $this->steamId), 'is');
        $this->hideSocial = $hideSocial;
    }

    /**
     * Updates the status of a game.
     *
     * @param $appId
     * @param $status
     * @throws \Exception
     */
    public function setStatus($appId, $status)
    {
        if (!isset($this->games[$appId])) {
            throw new Exception('Can\'t change status for game ' . $appId . ' as you do not own it.');
        }

        /** @var SteamGame $game */
        $game = $this->games[$appId];

        $oldStatus = $game->gameStatus;

        if ($game->gameSlot) {
            $this->setSlot($appId, 0);
        }

        $this->db->prepare('UPDATE `ownedGamesDB` SET `gameStatus` = ? WHERE `steamid` = ? AND `appid` = ?');
        $this->db->execute(array($status, $this->steamId, $game->appId), 'isi');

        $queryNewStatus = '';
        $queryOldStatus = '';

        switch($status) {
            case 1:
                $queryNewStatus = '`beaten` = `beaten` + 1';
                break;

            case 2:
                $queryNewStatus = '`blacklisted` = `blacklisted` + 1';
                break;
        }

        switch($oldStatus) {
            case 1:
                $queryOldStatus = '`beaten` = `beaten` - 1';
                break;

            case 2:
                $queryOldStatus = '`blacklisted` = `blacklisted` - 1';
                break;
        }

        if($queryNewStatus && $queryOldStatus) {
            $query = $queryNewStatus . ', ' . $queryOldStatus;
        } else {
            $query = $queryNewStatus . $queryOldStatus;
        }

        $this->db->prepare('UPDATE `steamGameDB` SET ' . $query . ' WHERE `appid` = ?');
        $this->db->execute(array($game->appId), 'i');

        $game->gameStatus = $status;

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
        if (!isset($this->games[$appId])) {
            throw new Exception('Can\'t change slot for game ' . $appId . ' as you do not own it.');
        }

        /** @var SteamGame $game */
        $game = $this->games[$appId];
        $removedGame = false;
        if ($game && $game->gameStatus != 2) {
            if ($slot == 0) {
                $this->db->prepare('UPDATE `ownedGamesDB` SET `gameSlot` = null, `minutesTotalStored` = 0 WHERE `steamid` = ? AND `appid` = ?');
                $this->db->execute(array($this->steamId, $game->appId), 'si');
                $removedGame = $game;
                foreach ($this->slots as $key => $value) {
                    if ($value == $game->appId) {
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

                if (isset($this->slots[$slot]))
                    $removedGame = (isset($this->games[$this->slots[$slot]])) ? $this->games[$this->slots[$slot]] : false;

                $this->slots[$slot] = $game->appId;
            }

            if ($this->db->getAffected()) {
                $this->logger->addEntry('Game slot updated');

                if ($removedGame) {
                    $points = (int)((($removedGame->minutesTotal) - ($removedGame->minutesStored)));
                    $this->db->prepare('UPDATE `steamUserDB` SET `points` = `points` + ? WHERE `steamid` = ?');
                    $this->db->execute(array($points, $this->steamId), 'is');
                    $this->logger->addEntry($points . ' Points awarded for removing game ' . $removedGame->name . ' from a slot.');
                    $this->points = $this->points + $points;
                }
            } else {
                throw new Exception('Game slot update failed.');
            }
        } else {
            throw new Exception('Can\'t change slot of a blacklisted game.');
        }
    }


    /**
     * Gets the string to use for the user's current status.
     *
     * @param $index
     * @return mixed
     */
    public function getStatusString($index)
    {
        return $this->statusString[$index];
    }

    /**
     * Gets the class to use for the user's current status.
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
            $this->db->prepare('SELECT `personaname`, `personastate`, `points`, (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(`lastUpdate`)) AS `lastUpdate`, `profileurl`, `profilestate`, `avatar`, `toBeatNum`, `considerBeaten`, `hideQuickStats`, `hideAccountStats`, `hideSocial`, `gameName` FROM `steamUserDB` WHERE `steamid` = ?');
            $this->db->execute(array($this->steamId), 's');
            $result = $this->db->fetch();

            if ($result && $result['profilestate'] === '3') {
                $this->personaName = $result['personaname'];
                $this->personaState = $result['personastate'];
                $this->points = $result['points'];
                $this->lastUpdate = $result['lastUpdate'];
                $this->profileUrl = $result['profileurl'];
                $this->profileState = $result['profilestate'];
                $this->avatarHash = $result['avatar'];
                $this->toBeatNum = $result['toBeatNum'];
                $this->considerBeaten = $result['considerBeaten'];
                $this->hideQuickStats = $result['hideQuickStats'];
                $this->hideAccountStats = $result['hideAccountStats'];
                $this->hideSocial = $result['hideSocial'];
                $this->gameName = $result['gameName'];
                $this->logger->addEntry('Grabbed userdata from local database.');
            } else {
                $this->loadRemoteUserInfo();
            }
        } catch (Exception $e) {
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
        $userInfo = @json_decode($this->util->file_get_contents_curl('http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . $this->config['key'] . '&steamids=' . $this->steamId), true)['response']['players'][0];
        $this->db->prepare('INSERT INTO `steamAPIUsage` SET `module` = 1');
        $this->db->execute();

        if (!$userInfo) {
            throw new Exception('The steam servers failed to respond to the user info request, probably due to heavy load.');
        }
        $this->logger->addEntry('Grabbed userdata from steam servers.');

        $this->personaName = $userInfo['personaname'];
        $this->personaState = $userInfo['personastate'];
        $this->profileState = $userInfo['communityvisibilitystate'];
        $this->profileUrl = substr($userInfo['profileurl'], 26);
        $this->avatarHash = substr($userInfo['avatar'], -44, -4);
        if (isset($userInfo['gameextrainfo'])) {
            $this->gameName = $userInfo['gameextrainfo'];
        } else {
            $this->gameName = '';
        }
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
            $this->db->prepare('INSERT INTO `steamUserDB` (`steamid`, `personaname`, `personastate`, `profileurl`, avatar, `profilestate`, `gameName`)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE `personaname` = ?, `personastate` = ?, `profileurl` = ?, `avatar` = ?, `profilestate` = ?, `gameName` = ?, `lastUpdate` = now()');
            $data = array($this->steamId, $this->personaName, $this->personaState, $this->profileUrl, $this->avatarHash, $this->profileState, $this->gameName,
                $this->personaName, $this->personaState, $this->profileUrl, $this->avatarHash, $this->profileState, $this->gameName);
            $this->db->execute($data, 'ssissississis');
            $this->logger->addEntry('Updated local userdata.');
            // Legacy implementation that involved storing the avatar locally

//            $steamAvatar = @$this->util->file_get_contents_curl($avatarUrl);
//
//            if(!file_exists($this->avatar) || (md5($steamAvatar) != md5(file_get_contents($this->avatar))))
//            {
//                file_put_contents($this->avatar, $steamAvatar);
//                $this->logger->addEntry('New local avatar created.');
//            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Loads a single game. Used to verify if the account owns a specific game.
     *
     * @param int $gameId ID of the game to load
     * @return bool true if account owns game : false if account does not own the game in question
     */
    public function loadLocalGame($gameId)
    {
        $this->db->prepare('SELECT a.`minutesTotal`, a.`minutes2Weeks`, a.`minutesTotalStored`, a.`gameSlot`, a.`gameStatus`, a.`achievPer`, b.`name`, b.`community`, b.`logo`, b.`icon`, b.`owned`, b.`beaten`, b.`blacklisted` FROM `ownedGamesDB` AS a, `steamGameDB` AS b WHERE a.`appid` = b.`appid` AND a.`steamid` = ? AND a.`appid` = ?');
        $this->db->execute(array($this->steamId, $gameId), 'si');
        $result = $this->db->fetch();

        if ($result) {
            $this->games[$gameId] = new SteamGame($gameId, $this->config, $this->db, $this->logger, $this->util, $result['name'], $result['minutesTotal'], $result['minutes2Weeks'], $result['minutesTotalStored'], $result['gameSlot'], $result['gameStatus'], $result['achievPer'], $result['logo'], $result['icon'], $result['community'], $result['owned'], $result['beaten'], $result['blacklisted']);
            if ($result['gameSlot']) {
                $this->slots[$result['gameSlot']] = $gameId;
            }
            return true;
        } else {
            return false;
        }

    }

    /**
     * Loads all Steam Games owned by this Steam User from the local database.
     * Will fall back to remote loading if no games for this Steam User are stored in the local database.
     */
    public function loadLocalGames()
    {
        $this->db->prepare('SELECT a.`appid`, a.`minutesTotal`, a.`minutes2Weeks`, a.`minutesTotalStored`, a.`gameSlot`, a.`gameStatus`, a.`achievPer`, b.`name`, b.`community`, b.`logo`, b.`icon`, b.`owned`, b.`beaten`, b.`blacklisted` FROM `ownedGamesDB` AS a, `steamGameDB` AS b WHERE a.`appid` = b.`appid` AND a.`steamid` = ? ORDER BY a.`minutes2Weeks` DESC, b.`name` ASC');
        $this->db->execute(array($this->steamId), 's');
        $result = $this->db->fetch();

        if ($result) {
            // Games Found
            do {
                $this->games[$result['appid']] = new SteamGame($result['appid'], $this->config, $this->db, $this->logger, $this->util, $result['name'], $result['minutesTotal'], $result['minutes2Weeks'], $result['minutesTotalStored'], $result['gameSlot'], $result['gameStatus'], $result['achievPer'], $result['logo'], $result['icon'], $result['community'], $result['owned'], $result['beaten'], $result['blacklisted']);
                if ($result['gameSlot']) {
                    $this->slots[$result['gameSlot']] = $result['appid'];
                }
            } while ($result = $this->db->fetch());

            $this->logger->addEntry('Grabbed game data from local database.');
        } else {
            // No Games Found
            $this->loadRemoteGames();
        }
    }

    /**
     * Loads all Steam Games owned by this Steam User from the steam servers.
     * If loadLocalGames is called before loadRemoteGames, each game will have extra data available to it which is only stored in the local database.
     *
     * @throws \Exception
     */
    public function loadRemoteGames()
    {
        $this->logger->addEntry('Requesting game data from Steam servers.');

        $gameData = null;
        $gameList = array();

        $gameData = @json_decode($this->util->file_get_contents_curl('http://api.steampowered.com/IPlayerService/GetOwnedGames/v0001/?key=' . $this->config['key'] . '&steamid=' . $this->steamId . '&include_appinfo=1&include_played_free_games=1&format=json'), true)['response'];
        $this->db->prepare('INSERT INTO `steamAPIUsage` SET `module` = 2');
        $this->db->execute();


        if (!$gameData || isset($gameData['error']) || !isset($gameData['games'])) {
            throw new Exception('The game data could not be retrieved. Is your profile set to private? Steam Completionist requires your profile to be set to "Public" in order to retrieve a list of your games.');
        }

        $gameData = $gameData['games'];

        foreach ($gameData as $game) {

            // If a game has not been played or has no community stats, we need to set them to a default value to prevent undefined index messages
            $game['playtime_forever'] = isset($game['playtime_forever']) ? $game['playtime_forever'] : 0;
            $game['playtime_2weeks'] = isset($game['playtime_2weeks']) ? $game['playtime_2weeks'] : 0;
            $game['has_community_visible_stats'] = isset($game['has_community_visible_stats']) ? $game['has_community_visible_stats'] : false;

            // If we previously loaded this game locally we can carry over some parameters
            if (isset($this->games[$game['appid']])) {
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
                    $game['has_community_visible_stats'],
                    $this->games[$game['appid']]->numOwned,
                    $this->games[$game['appid']]->numBeaten,
                    $this->games[$game['appid']]->numBlacklisted);
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
                    $game['has_community_visible_stats'],
                    1,
                    0,
                    0);
            }

            $game->saveGame($this->steamId);
            $gameList[$game->appId] = $game;
        }

        $removedGames = array_diff_key($this->games, $gameList);

        /** @var SteamGame $value */
        foreach ($removedGames as $value) {
            if ($value->gameSlot) {
                $this->setSlot($value->appId, 0);
            }
            $this->deleteList[$value->appId] = $value;
            $value->deleteGame($this->steamId);
        }

        $this->games = $gameList;
        $this->logger->addEntry('Retrieved game data from steam servers.');
    }

}