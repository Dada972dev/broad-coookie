<?php

namespace broad\tasks;

use pocketmine\scheduler\Task;
use broad\Main;

class BroadcastTask extends Task {

    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun(): void {
        $messages = $this->plugin->getMessages();
        if (!empty($messages)) {
            $currentMessageId = $this->plugin->getCurrentMessageId();
            if (isset($messages[$currentMessageId])) {
                $message = $messages[$currentMessageId];
                $this->plugin->getServer()->broadcastMessage($message); // Modifiez cette ligne
                $this->plugin->incrementMessageId();
            }
        }
    }
}