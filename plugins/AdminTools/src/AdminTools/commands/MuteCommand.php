<?php
declare(strict_types=1);

namespace AdminTools\commands;

use AdminTools\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class MuteCommand extends Command {
    private Main $plugin;
    public function __construct(Main $plugin) {
        parent::__construct("mute", "Mute a player", "/mute <player> [duration(s)] [reason]");
        $this->plugin = $plugin;
        $this->setPermission("admintools.mute");
    }
    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (empty($args)) { $sender->sendMessage("§cUsage: §e/mute <player> [duration(s)] [reason]"); return true; }
        $name = $args[0];
        $duration = (int)($this->plugin->getConfig()->getNested("mute.default-duration", 3600));
        $reason = $this->plugin->getConfig()->getNested("mute.default-reason", "Muted");
        if (isset($args[1]) && is_numeric($args[1])) {
            $duration = (int)$args[1];
            $reason = count($args) > 2 ? implode(" ", array_slice($args, 2)) : $reason;
        } elseif (isset($args[1])) {
            $reason = implode(" ", array_slice($args, 1));
        }
        $senderName = $sender instanceof Player ? $sender->getName() : "Console";
        $this->plugin->getPunishmentManager()->mute($name, $reason, $senderName, $duration);
        $mins = ceil($duration / 60);
        $sender->sendMessage("§a$name muted for {$mins}m. Reason: $reason");
        $target = $this->plugin->getServer()->getPlayerByPrefix($name);
        if ($target !== null) $target->sendMessage("§cYou have been muted for {$mins}m. Reason: §e$reason");
        if ($this->plugin->getConfig()->getNested("notifications.mute-broadcast", true)) {
            $this->plugin->getServer()->broadcastMessage("§8[§eMUTE§8] §f$name §7was muted by §f$senderName §7($reason)");
        }
        $this->plugin->logAdminAction($senderName, "MUTED", $name, $reason);
        return true;
    }
}
