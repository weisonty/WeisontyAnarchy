<?php
declare(strict_types=1);

namespace AdminTools\commands;

use AdminTools\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class UnmuteCommand extends Command {
    private Main $plugin;
    public function __construct(Main $plugin) {
        parent::__construct("unmute", "Unmute a player", "/unmute <player>");
        $this->plugin = $plugin;
        $this->setPermission("admintools.mute");
    }
    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (empty($args)) { $sender->sendMessage("§cUsage: §e/unmute <player>"); return true; }
        $name = $args[0];
        $senderName = $sender instanceof Player ? $sender->getName() : "Console";
        if ($this->plugin->getPunishmentManager()->unmute($name)) {
            $sender->sendMessage("§a$name has been unmuted.");
            $target = $this->plugin->getServer()->getPlayerByPrefix($name);
            if ($target !== null) $target->sendMessage("§aYou have been unmuted.");
            $this->plugin->logAdminAction($senderName, "UNMUTED", $name);
        } else {
            $sender->sendMessage("§c$name is not muted.");
        }
        return true;
    }
}
