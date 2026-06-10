<?php
declare(strict_types=1);

namespace AntiCheatLite\commands;

use AntiCheatLite\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class ACBypassCommand extends Command {
    private Main $plugin;
    public function __construct(Main $plugin) {
        parent::__construct("acbypass", "Toggle anti-cheat bypass", "/acbypass <player>");
        $this->plugin = $plugin;
        $this->setPermission("anticheat.admin");
    }
    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (empty($args)) { $sender->sendMessage("§cUsage: §e/acbypass <player>"); return true; }
        $name = $args[0];
        $state = $this->plugin->toggleBypass($name);
        $sender->sendMessage($state
            ? "§aAnti-cheat bypass §eENABLED§a for §f$name§a."
            : "§aAnti-cheat bypass §cDISABLED§a for §f$name§a.");
        $target = $this->plugin->getServer()->getPlayerByPrefix($name);
        if ($target !== null) {
            $target->sendMessage($state
                ? "§aYou have been granted anti-cheat bypass."
                : "§cYour anti-cheat bypass has been removed.");
        }
        return true;
    }
}
