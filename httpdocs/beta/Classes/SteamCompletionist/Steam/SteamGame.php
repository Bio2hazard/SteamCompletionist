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
    /** @var DatabaseInterface $db */
    private $db;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var Util $util */
    private $util;

    public $appId, $name, $hoursTotal, $hours2Weeks, $hoursStored, $internalName, $gameSlot, $achievementPercentage, $logo, $steamLogo, $status;

    /**
     * Constructor.
     *
     * @param $appId
     * @param \Classes\Common\Database\DatabaseInterface $db
     * @param \Classes\Common\Logger\LoggerInterface $logger
     * @param \Classes\Common\Util\Util $util
     * @param $name
     * @param $hoursTotal
     * @param $hours2Weeks
     * @param $hoursStored
     * @param $internalName
     * @param $gameSlot
     * @param $achievementPercentage
     * @param string $steamLogo
     */
    public function __construct($appId, DatabaseInterface $db, LoggerInterface $logger, Util $util, $name, $hoursTotal, $hours2Weeks, $hoursStored, $internalName, $gameSlot, $achievementPercentage, $steamLogo = '')
    {
        $this->appId = $appId;
        $this->db = $db;
        $this->logger = $logger;
        $this->util = $util;
        $this->name = $name;
        $this->hoursTotal = $hoursTotal;
        $this->hours2Weeks = $hours2Weeks;
        $this->hoursStored = $hoursStored;
        $this->internalName = $internalName;
        $this->gameSlot = $gameSlot;
        $this->achievementPercentage = $achievementPercentage;
        $this->logo = './img/game/'.$appId.'.jpg';
        $this->steamLogo = $steamLogo;
    }

    /**
     * Saves game data into the database
     *
     * @param $steamId
     * @throws \Exception
     */
    public function saveGame($steamId)
    {
        $this->db->prepare('INSERT INTO `steamGameDB` (`appid`, `name`, `internal`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `name` = ?, `internal` = ?');
        $this->db->execute(array($this->appId, $this->name, $this->internalName, $this->name, $this->internalName), 'issss');

        if(!file_exists($this->logo) && $this->steamLogo)
        {
            $game_logo = @$this->util->file_get_contents_curl($this->steamLogo);
            if(!@file_put_contents($this->logo, $game_logo)) {
                throw new \Exception('Creating game logo failed.');
            }
        }

        $this->db->prepare('INSERT INTO `ownedGamesDB` (`steamid`, `appid`, `hoursTotal`, `hours2Weeks`) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `hoursTotal` = ?, `hours2Weeks` = ?');
        $this->db->execute(array($steamId, $this->appId, $this->hoursTotal, $this->hours2Weeks, $this->hoursTotal, $this->hours2Weeks), 'sidddd');
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