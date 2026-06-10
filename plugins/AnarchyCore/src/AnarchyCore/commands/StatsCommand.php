<?php
declare(strict_types=1);

namespace AnarchyCore\commands;

use AnarchyCore\Main;
use AnarchyCore\ui\MenuManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class StatsCommand extends Command {

    private Main $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("stats", "View server stats and leaderboard", "/stats");
        $this->plugin = $plugin;
        $this->setPermission("anarchycore.stats");
    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cIn-game only.");
            return true;
        }
        MenuManager::sendStatsMenu($sender, $this->plugin->getPlayerDataManager());
        return true;
    }
}
