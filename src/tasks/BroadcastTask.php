class BroadcastTask extends Task {

    private $plugin;
    private $messages;

    public function __construct(BroadcastPlugin $plugin) {
        $this->plugin = $plugin;
        $this->messages = $this->plugin->getConfig()->get("messages", []);
    }

    public function onRun(int $currentTick): void {
        if (empty($this->messages)) {
            return;
        }

        $message = $this->messages[array_rand($this->messages)];
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $player->sendMessage($message);
        }
    }
}