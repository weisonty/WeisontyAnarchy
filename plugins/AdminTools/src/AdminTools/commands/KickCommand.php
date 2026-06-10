<?php
declare(strict_types=1);

namespace AdminTools\commands;

use AdminTools\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class KickCommand extends Command {
    private Main $plugin;
    public function __construct(Main $plugin) {
        parent::__construct("kick", "Kick a player", "/kick <player> [reason]");
        $this->plugin = $plugin;
        $this->setPermission("admintools.kick");
    }
    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (empty($args)) { $sender->sendMessage("§cUsage: §e/kick <player> [reason]"); return true; }
        $target = $this->plugin->getServer()->getPlayerByPrefix($args[0]);
        if ($target === null) { $sender->sendMessage("§cPlayer not found."); return true; }
        $reason = count($args) > 1 ? implode(" ", array_slice($args, 1)) : $this->plugin->getConfig()->getNested("kick.default-reason", "Kicked");
        $senderName = $sender instanceof Player ? $sender->getName() : "Console";
        $target->kick("§cYou were kicked.\n§7Reason: §f$reason", false);
        $sender->sendMessage("§a{$target->getName()} has been kicked.");
        $this->plugin->logAdminAction($senderName, "KICKED", $target->getName(), $reason);
        return true;
    }
}
