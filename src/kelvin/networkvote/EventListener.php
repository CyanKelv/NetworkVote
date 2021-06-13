<?php

declare(strict_types=1);

namespace kelvin\networkvote;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;

class EventListener implements Listener {

    public function __construct(VoteTracker $plugin){
        $this->plugin = $plugin;
    }

    public function onPreLogin(PlayerPreLoginEvent $event){
        $this->plugin->loadData($event->getPlayer());
    }

    public function onQuit(PlayerQuitEvent $event){
        $this->plugin->unloadData($event->getPlayer());
    }

}

?>
