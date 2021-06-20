<?php

declare(strict_types=1);

namespace kelvin\networkvote;

use Closure;
use pocketmine\Player;
use poggit\libasynql\ConfigException;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;

class Database {

    /** @var DataConnector */
    private $db;

    public function __construct(VoteTracker $plugin){
        $this->plugin = $plugin;
        $this->initializeDatabase();
    }

    public function initializeDatabase(){
        try{
            $this->db = libasynql::create($this->plugin, $this->plugin->getConfig()->get("database"), [
                "sqlite" => "sqlite.sql",
                "mysql" => "mysql.sql"
            ]);
            $this->db->executeGeneric("network.init_votes");
            $this->db->waitAll();
        } catch(SqlError $error){
            $this->plugin->getLogger()->error($error->getMessage());
            $this->plugin->getServer()->getPluginManager()->disablePlugin($this->plugin);
        } catch(ConfigException $error){
            $this->plugin->getLogger()->error($error->getMessage());
            $this->plugin->getServer()->getPluginManager()->disablePlugin($this->plugin);
        }
    }

    /**
     * Query player's data
     *
     * @param Player $player
     * @param Closure $callback
     */
    public function getData($player, $callback){
        $name = $player->getLowerCaseName();
        $this->db->executeSelect("network.get_vote", ["name" => $name], function(array $rows) use($callback){
            if(empty($rows)){
                // No record on database
                $callback(null);
                return false;
            }
            $callback($rows[0]);
            return true;
        });
    }

    /**
     * @param Player $player
     * @param array $data
     * @param int $timestamp
     */
    public function updateData($player, $data, $timestamp){
        $name = $player->getLowerCaseName();
        $this->db->executeChange("network.update_vote", ["name" => $name, "servers" => json_encode($data), "timestamp" => $timestamp]);
    }

    /**
     * @param Player $player
     */
    public function removeData($player){
        $name = $player->getLowerCaseName();
        $this->db->executeChange("network.delete_vote", ["name" => $name]);
    }

    /**
     * Clean up player data daily on reset
     */
    public function cleanData(){
        $resetTimestamp = (new \DateTime("today", $this->plugin->timezone))->getTimestamp();
        $this->db->executeSelect("network.get_vote_by_date", ["timestamp" => $resetTimestamp], function(array $rows){
            foreach($rows as $result){
                $this->db->executeChange("network.delete_vote", ["name" => $result["name"]]);
            }
        });
    }

    public function closeDatabase(){
        if(isset($this->db)){
            $this->db->close();
            $this->plugin->getLogger()->info("Database closed.");
        }
    }

}