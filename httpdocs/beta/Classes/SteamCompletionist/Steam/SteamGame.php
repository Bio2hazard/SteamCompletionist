<?php

namespace Classes\SteamCompletionist\Steam;

use Classes\Common\Database\DatabaseInterface;
use Classes\Common\Logger\LoggerInterface;
use Classes\Common\Util\Util;
/**
 * Holds all information pertaining to a single game
 *
 * @todo: Describe all variables, brush up comments
 * @author Felix Kastner <felix@chapterfain.com>
 */
class SteamGame
{
    private $config;

    /** @var DatabaseInterface $db */
    private $db;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var Util $util */
    private $util;

    public $appId, $name, $minutesTotal, $minutes2Weeks, $minutesStored, $gameSlot, $achievementPercentage, $logoHash, $iconHash, $community, $gameStatus;

    /**
     * Constructor
     *
     * @param $appId
     * @param $config
     * @param \Classes\Common\Database\DatabaseInterface $db
     * @param \Classes\Common\Logger\LoggerInterface $logger
     * @param \Classes\Common\Util\Util $util
     * @param $name
     * @param $minutesTotal
     * @param $minutes2Weeks
     * @param $minutesStored
     * @param $gameSlot
     * @param $gameStatus
     * @param $achievementPercentage
     * @param $logoHash
     * @param $iconHash
     * @param $community
     */
    public function __construct($appId, $config, DatabaseInterface $db, LoggerInterface $logger, Util $util, $name, $minutesTotal, $minutes2Weeks, $minutesStored, $gameSlot, $gameStatus, $achievementPercentage, $logoHash, $iconHash, $community)
    {
        $this->appId = $appId;
        $this->config = $config;
        $this->db = $db;
        $this->logger = $logger;
        $this->util = $util;
        $this->name = $name;
        $this->minutesTotal = $minutesTotal;
        $this->minutes2Weeks = $minutes2Weeks;
        $this->minutesStored = $minutesStored;
        $this->gameSlot = $gameSlot;
        $this->gameStatus = $gameStatus;
        $this->achievementPercentage = $achievementPercentage;
        $this->logoHash = $logoHash;
        $this->iconHash = $iconHash;
        $this->community = $community;
    }

    /**
     * Contacts the steam servers and grabs the achievement list for that game and that user. Then, a percentage is calculated and stored.
     *
     * @param $steamId
     */
    public function getAchievementPercentage($steamId)
    {
        $achievements = @json_decode($this->util->file_get_contents_curl('http://api.steampowered.com/ISteamUserStats/GetPlayerAchievements/v0001/?key=' . $this->config['key'] . '&steamid=' . $steamId . '&appid=' . $this->appId))->playerstats;
        $curlSuccess = false;
        $percentage = 142;

        if(isset($achievements->success) && $achievements->success === true) {
            $curlSuccess = true;
            $total = 0;
            $achieved = 0;
            foreach($achievements->achievements as $value) {
                $total++;
                if($value->achieved)
                    $achieved++;
            }
            $percentage = round(($achieved / $total)*100);
            $this->logger->addEntry('Achievements for '.$achievements->gameName.' loaded successfully. ');
        } elseif(isset($achievements->success) && $achievements->success === false) {
            $curlSuccess = true;
            $this->logger->addEntry('Achievements for ' . $achievements->gameName . ' could not be loaded: ' . $achievements->error);
        }

        if($curlSuccess) {
            $this->db->prepare('UPDATE ownedGamesDB SET `achievPer` = ? WHERE `steamid` = ? AND `appid` = ?');
            $this->db->execute(array($percentage, $steamId, $this->appId), 'isi');
            $this->achievementPercentage = $percentage;
        }
    }

    /**
     * Saves game data into the database
     *
     * @param $steamId
     * @throws \Exception
     */
    public function saveGame($steamId)
    {
        $this->db->prepare('INSERT INTO `steamGameDB` (`appid`, `name`, `community`, `logo`, `icon`) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `name` = ?, `community` = ?, `logo` = ?, `icon` = ?');
        $this->db->execute(array($this->appId, $this->name, $this->community, $this->logoHash, $this->iconHash, $this->name, $this->community, $this->logoHash, $this->iconHash), 'isisssiss');

        // Legacy code for storing the game logo locally
//        if(!file_exists($this->logo) && $this->steamLogo)
//        {
//            $game_logo = @$this->util->file_get_contents_curl($this->steamLogo);
//            if(!@file_put_contents($this->logo, $game_logo)) {
//                throw new \Exception('Creating game logo failed.');
//            }
//        }

        $this->db->prepare('INSERT INTO `ownedGamesDB` (`steamid`, `appid`, `minutesTotal`, `minutes2Weeks`) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `minutesTotal` = ?, `minutes2Weeks` = ?');
        $this->db->execute(array($steamId, $this->appId, $this->minutesTotal, $this->minutes2Weeks, $this->minutesTotal, $this->minutes2Weeks), 'siiiii');
    }

    /**
     * Deletes a game from the owned games database
     *
     * @param $steamId
     */
    public function deleteGame($steamId)
    {
        $this->db->prepare('DELETE FROM `ownedGamesDB` WHERE `steamid` = ? AND `appid` = ?');
        $this->db->execute(array($steamId, $this->appId), 'si');
    }
}