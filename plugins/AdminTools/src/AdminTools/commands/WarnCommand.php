<?php
declare(strict_types=1);

namespace AdminTools\commands;

use AdminTools\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class WarnCommand extends Command {
    private Main $plugin;
    public function __construct(Main $plugin) {
        parent::__construct("warn", "Warn a player", "/warn <player> <reason>");
        $this->plugin = $plugin;
        $this->setPermission("admintools.warn");
    }
    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (count($args) < 2) { $sender->sendMessage("§cUsage: §e/warn <player> <reason>"); return true; }
        $name = $args[0];
        $reason = implode(" ", array_slice($args, 1));
        $senderName = $sender instanceof Player ? $sender->getName() : "Console";

        $this->plugin->getPunishmentManager()->warn($name, $reason, $senderName);
        $count = $this->plugin->getPunishmentManager()->getWarningCount($name);
        $max = (int)$this->plugin->getConfig()->getNested("warn.max-warnings", 3);

        $target = $this->plugin->getServer()->getPlayerByPrefix($name);
        if ($target !== null) {
            $target->sendMessage("§c§lWARNING §r§c($count/$max): §f$reason §7- By $senderName");
        }
        $sender->sendMessage("§aWarned $name ($count/$max): $reason");

        if ($this->plugin->getConfig()->getNested("notifications.warn-broadcast", true)) {
            $this->plugin->getServer()->broadcastMessage("§8[§6WARN§8] §f$name §7warned by §f$senderName §7($reason) [$count/$max]");
        }

        if ($count >= $max) {
            $action = $this->plugin->getConfig()->getNested("warn.action-on-max", "kick");
            if ($action === "kick" && $target !== null) {
                $target->kick("§cYou have been kicked for reaching max warnings.", false);
            } elseif ($action === "ban") {
                $this->plugin->getPunishmentManager()->ban($name, "Max warnings reached", "AutoMod");
                if ($target !== null) $target->kick("§cBanned: Max warnings reached.", false);
            }
        }

        $this->plugin->logAdminAction($senderName, "WARNED", $name, $reason);
        return true;
    }
}
