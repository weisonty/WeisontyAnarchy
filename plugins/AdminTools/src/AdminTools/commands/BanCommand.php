<?php
declare(strict_types=1);

namespace AdminTools\commands;

use AdminTools\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class BanCommand extends Command {
    private Main $plugin;
    public function __construct(Main $plugin) {
        parent::__construct("ban", "Ban a player", "/ban <player> [reason]");
        $this->plugin = $plugin;
        $this->setPermission("admintools.ban");
    }
    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (empty($args)) { $sender->sendMessage("§cUsage: §e/ban <player> [reason]"); return true; }
        $name = $args[0];
        $reason = count($args) > 1 ? implode(" ", array_slice($args, 1)) : $this->plugin->getConfig()->getNested("ban.default-reason", "Banned");
        $senderName = $sender instanceof Player ? $sender->getName() : "Console";

        $this->plugin->getPunishmentManager()->ban($name, $reason, $senderName);

        $target = $this->plugin->getServer()->getPlayerByPrefix($name);
        $msg = str_replace("{reason}", $reason, $this->plugin->getConfig()->getNested("ban.permanent-message", "§cBanned: {reason}"));
        if ($target !== null) $target->kick($msg, false);

        $sender->sendMessage("§a$name has been banned. Reason: $reason");
        if ($this->plugin->getConfig()->getNested("notifications.ban-broadcast", true)) {
            $this->plugin->getServer()->broadcastMessage("§8[§cBAN§8] §f$name §7was banned by §f$senderName §7($reason)");
        }
        $this->plugin->logAdminAction($senderName, "BANNED", $name, $reason);
        return true;
    }
}
