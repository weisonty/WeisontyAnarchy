<?php
declare(strict_types=1);

namespace AntiCheatLite\checks;

use AntiCheatLite\Main;
use pocketmine\player\Player;

class FlyCheck {
    private Main $plugin;
    public function __construct(Main $plugin) { $this->plugin = $plugin; }
    public function flag(Player $player, string $detail): void {
        $this->plugin->addViolation($player->getName(), "FLY", $detail);
    }
}
