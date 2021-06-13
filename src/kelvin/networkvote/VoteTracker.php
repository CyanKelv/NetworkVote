<?php

declare(strict_types=1);

namespace kelvin\networkvote;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;

class VoteTracker extends PluginBase{

    const RET_NOT_VOTED = 0;
    const RET_VOTED = 1;
    // Data not loaded
    const RET_INVALID = 2;

    /** @var VoteTracker|null */
    private static $instance = null;
    /** @var Database */
    private $database;
    /** @var \DateTimeZone */
    public $timezone;
    /** Arrays of server's ip and port */
    private $loaded;

	public function onEnable() : void{
        $this->getLogger()->info("Enabling NetworkVote...");
		if(!isset(self::$instance)) {
            self::$instance = $this;
        }
		$this->saveDefaultConfig();
		$this->database = new Database($this);
		// Timezone following voting site
		$this->timezone = new \DateTimeZone("EST5EDT");
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getLogger()->info("NetworkVote enabled!");
	}

	public static function getInstance() : ?VoteTracker{
	    return self::$instance;
    }

    private function getDatabase() : Database{
	    return $this->database;
    }

    /**
     * Claim player vote, returns true on successful claim and false otherwise
     *
     * @param Player $player
     * @param $ip
     * @param $port
     * @return bool
     */
    public function claimVote($player, $ip, $port) : bool{
        $name = $player->getLowerCaseName();
        $result = $this->hasVotedOn($player, $ip, $port);
        if($result === self::RET_NOT_VOTED){
            $this->voteOn($player, $ip, $port);
            return true;
        } else if($result === self::RET_VOTED){
            return false;
        } else if($result === self::RET_INVALID){
            $this->getLogger()->alert($player->getName() . " vote tracking data is not loaded!");
            return false;
        }
    }

    /**
     * Check if player had voted on specific ip and port
     *
     * @param Player $player
     * @param string $ip
     * @param int $port
     */
    public function hasVotedOn($player, $ip, $port){
	    $name = $player->getLowerCaseName();
        if(isset($this->loaded[$name])){
            if(in_array($ip . ":" . $port, $this->loaded[$name])){
                return self::RET_VOTED;
            } else {
                return self::RET_NOT_VOTED;
            }
        } else {
            return self::RET_INVALID;
        }
    }

    /**
     * Updates server voting status and returns
     * true on successful insert/update OR false otherwise
     *
     * @param Player $player
     * @param string $ip
     * @param int $port
     *
     * @return bool
     */
    public function voteOn($player, $ip, $port){
        $name = $player->getLowerCaseName();
        $this->checkForReset($player);
        if(isset($this->loaded[$name])){
            $parsed = $ip . ":" . $port;
            if(!in_array($parsed, $this->loaded[$name])){
                $this->loaded[$name][] = $parsed;
            }
            $date = new \DateTime("now", $this->timezone);
            $this->getDatabase()->updateData($player, $this->loaded[$name], $date->getTimestamp());
            return true;
        }
        return false;
    }

    /**
     * Run check on daily votes reset
     *
     * @param Player $player
     */
    public function checkForReset($player): void{
        $name = $player->getLowerCaseName();
        if(isset($this->loaded[$name])){
            if(count($this->loaded[$name]) === 0){
                return;
            }
            $this->getDatabase()->getData($player, function($data) use($player, $name){
                if($data !== null){
                    $resetTimestamp = (new \DateTime("today", $this->timezone))->getTimestamp();
                    if($resetTimestamp > $data["lastupdated"]){
                        $this->loaded[$name] = [];
                        //$this->getDatabase()->updateData($player, $this->loaded[$name], $now->getTimestamp());
                        $this->getDatabase()->removeData($player);
                    }
                }
            });
        }
    }

    /**
     * Attempt to load player data
     *
     * @param Player $player
     */
    public function loadData($player){
        $name = $player->getLowerCaseName();
	    if(!isset($this->loaded[$name])){
	        $this->getDatabase()->getData($player, function($data) use($name){
	            if($data !== null){
                    $this->loaded[$name] = json_decode($data["servers"], true);
                } else {
	                $this->loaded[$name] = [];
                }
            });
	        $this->checkForReset($player);
        }
    }

    /**
     * Unload player data
     *
     * @param Player $player
     */
    public function unloadData($player){
        $name = $player->getLowerCaseName();
        if(isset($this->loaded[$name])){
            unset($this->loaded[$name]);
        }
    }

	public function onDisable() : void{
	    if(isset($this->database)){
            $this->database->closeDatabase();
        }
		$this->getLogger()->info("NetworkVote disabled!");
	}
}
