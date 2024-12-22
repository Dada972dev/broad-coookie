<?php

namespace BroadcastPlugin;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\utils\Config;
use BroadcastPlugin\tasks\BroadcastTask;

class BroadcastPlugin extends PluginBase {

    private Config $config;

    public function onEnable(): void {
        $this->loadConfig();
        $this->getScheduler()->scheduleRepeatingTask(new BroadcastTask($this), 2400); // 2400 ticks = 2 minutes
    }

    private function loadConfig(): void {
        $this->saveDefaultConfig();
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
    }

    public function getMessages(): array {
        return $this->config->get("messages", []);
    }
}