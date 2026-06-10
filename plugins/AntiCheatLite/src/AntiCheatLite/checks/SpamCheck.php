<?php
declare(strict_types=1);

namespace AntiCheatLite\checks;

use AntiCheatLite\Main;
use pocketmine\player\Player;

class SpamCheck {
    private Main $plugin;
    /** @var array<string, array<float>> */
    private array $chatHistory = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function checkChat(Player $player): void {
        $name = $player->getName();
        $now = microtime(true);
        $cfg = $this->plugin->getConfig()->get("checks", [])["spam"] ?? [];
        $maxMsg = (int)($cfg["max-messages"] ?? 5);
        $perSec = (int)($cfg["per-seconds"] ?? 3);

        if (!isset($this->chatHistory[$name])) {
            $this->chatHistory[$name] = [];
        }

        // Remove old entries
        $this->chatHistory[$name] = array_filter(
            $this->chatHistory[$name],
            fn($t) => ($now - $t) <= $perSec
        );
        $this->chatHistory[$name][] = $now;

        if (count($this->chatHistory[$name]) > $maxMsg) {
            $this->plugin->addViolation($name, "SPAM", count($this->chatHistory[$name]) . " msgs in {$perSec}s");
        }
    }
}
