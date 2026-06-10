<?php
declare(strict_types=1);

namespace AdminTools\commands;

use AdminTools\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class TpHereCommand extends Command {
    private Main $plugin;
    public function __construct(Main $plugin) {
        parent::__construct("tphere", "Teleport a player to you", "/tphere <player>");
        $this->plugin = $plugin;
        $this->setPermission("admintools.tp");
    }
    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (!$sender instanceof Player) { $sender->sendMessage("§cIn-game only."); return true; }
        if (empty($args)) { $sender->sendMessage("§cUsage: §e/tphere <player>"); return true; }
        $target = $this->plugin->getServer()->getPlayerByPrefix($args[0]);
        if ($target === null) { $sender->sendMessage("§cPlayer not found."); return true; }
        $target->teleport($sender->getPosition());
        $sender->sendMessage("§aTeleported §e{$target->getName()}§a to you.");
        $target->sendMessage("§aTeleported to §e{$sender->getName()}§a.");
        $this->plugin->logAdminAction($sender->getName(), "TPHERE", $target->getName());
        return true;
    }
}
