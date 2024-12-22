<?php

namespace broad;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use broad\tasks\BroadcastTask;

class Main extends PluginBase {

    private $broadcastInterval;
    private $currentMessageId = 0;

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->broadcastInterval = $this->getConfig()->get("broadcast-interval", 300) * 20;
        $this->scheduleBroadcastTask();
    }

    private function scheduleBroadcastTask(): void {
        $this->getScheduler()->scheduleRepeatingTask(new BroadcastTask($this), $this->broadcastInterval);
    }

    public function getMessages(): array {
        return $this->getConfig()->get("messages", []);
    }

    public function addMessage(string $message): void {
        $messages = $this->getMessages();
        $id = count($messages);
        $messages[$id] = $message;
        $this->getConfig()->set("messages", $messages);
        $this->getConfig()->save();
    }

    public function deleteMessage(int $id): void {
        $messages = $this->getMessages();
        if (isset($messages[$id])) {
            unset($messages[$id]);
            $messages = array_values($messages); // Réindexer les messages
            $this->getConfig()->set("messages", $messages);
            $this->getConfig()->save();
            if ($this->currentMessageId >= count($messages)) {
                $this->currentMessageId = 0; // Réinitialiser l'ID du message actuel si nécessaire
            }
        }
    }

    public function setBroadcastInterval(int $seconds): void {
        $this->broadcastInterval = $seconds * 20;
        $this->getConfig()->set("broadcast-interval", $seconds); // Enregistrer l'intervalle dans la configuration
        $this->getConfig()->save();
        $this->getScheduler()->cancelAllTasks();
        $this->scheduleBroadcastTask();
    }

    public function forceBroadcast(): void {
        $messages = $this->getMessages();
        if (!empty($messages)) {
            $message = $messages[array_rand($messages)];
            $this->getServer()->broadcastMessage($message);
        }
    }

    public function getCurrentMessageId(): int {
        return $this->currentMessageId;
    }

    public function incrementMessageId(): void {
        $this->currentMessageId++;
        if ($this->currentMessageId >= count($this->getMessages())) {
            $this->currentMessageId = 0;
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "bc") {
            if (empty($args)) {
                $sender->sendMessage("Available subcommands:");
                $sender->sendMessage("/bc annonce <message> - Broadcast a message");
                $sender->sendMessage("/bc force - Force broadcast messages");
                $sender->sendMessage("/bc set time <seconds> - Set broadcast interval");
                $sender->sendMessage("/bc set message <message> - Add a new broadcast message");
                $sender->sendMessage("/bc delete <id> - Delete a broadcast message by ID");
                $sender->sendMessage("/bc id - List all broadcast messages with their IDs");
                return true;
            }

            $subCommand = array_shift($args);

            switch ($subCommand) {
                case "annonce":
                    if (!$sender->hasPermission("broadcastplugin.broadcast")) {
                        $sender->sendMessage("You do not have permission to use this command.");
                        return true;
                    }
                    if (empty($args)) {
                        $sender->sendMessage("Usage: /bc annonce <message>");
                        return true;
                    }
                    $message = implode(" ", $args);
                    $this->getServer()->broadcastMessage("§d[" . $sender->getName() . "]: §f" . $message);
                    return true;

                case "force":
                    if (!$sender->hasPermission("broadcastplugin.forcebroadcast")) {
                        $sender->sendMessage("You do not have permission to use this command.");
                        return true;
                    }
                    $this->forceBroadcast();
                    $sender->sendMessage("Broadcast messages have been sent.");
                    return true;

                case "set":
                    if (empty($args)) {
                        $sender->sendMessage("Usage: /bc set <subcommand> [args]");
                        return true;
                    }

                    $setSubCommand = array_shift($args);

                    switch ($setSubCommand) {
                        case "time":
                            if (!$sender->hasPermission("broadcastplugin.setbroadcasttime")) {
                                $sender->sendMessage("You do not have permission to use this command.");
                                return true;
                            }
                            if (empty($args) || !is_numeric($args[0])) {
                                $sender->sendMessage("Usage: /bc set time <seconds>");
                                return true;
                            }
                            $seconds = (int)$args[0];
                            $this->setBroadcastInterval($seconds);
                            $sender->sendMessage("Broadcast interval set to $seconds seconds.");
                            return true;

                        case "message":
                            if (!$sender->hasPermission("broadcastplugin.setbroadcastmessage")) {
                                $sender->sendMessage("You do not have permission to use this command.");
                                return true;
                            }
                            if (empty($args)) {
                                $sender->sendMessage("Usage: /bc set message <message>");
                                return true;
                            }
                            $message = implode(" ", $args);
                            $this->addMessage($message);
                            $sender->sendMessage("Broadcast message added.");
                            return true;

                        default:
                            $sender->sendMessage("Unknown subcommand: $setSubCommand");
                            return true;
                    }

                case "delete":
                    if (!$sender->hasPermission("broadcastplugin.delbroadcastmessage")) {
                        $sender->sendMessage("You do not have permission to use this command.");
                        return true;
                    }
                    if (empty($args) || !is_numeric($args[0])) {
                        $sender->sendMessage("Usage: /bc delete <id>");
                        return true;
                    }
                    $id = (int)$args[0];
                    $this->deleteMessage($id);
                    $sender->sendMessage("Broadcast message deleted.");
                    return true;

                case "id":
                    if (!$sender->hasPermission("broadcastplugin.broadcastid")) {
                        $sender->sendMessage("You do not have permission to use this command.");
                        return true;
                    }
                    $messages = $this->getMessages();
                    foreach ($messages as $id => $message) {
                        $sender->sendMessage("ID: $id - Message: $message");
                    }
                    return true;

                default:
                    $sender->sendMessage("Unknown subcommand: $subCommand");
                    return true;
            }
        }
        return false;
    }
}