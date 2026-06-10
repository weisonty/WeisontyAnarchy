<?php
declare(strict_types=1);

namespace AdminTools\commands;

use AdminTools\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class TpCommand extends Command {
    private Main $plugin;
    public function __construct(Main $plugin) {
        parent::__construct("tp", "Teleport to a player or coordinates", "/tp <player> [target|x y z]");
        $this->plugin = $plugin;
        $this->setPermission("admintools.tp");
    }
    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (empty($args)) { $sender->sendMessage("§cUsage: §e/tp <player> [target]"); return true; }

        $senderName = $sender instanceof Player ? $sender->getName() : "Console";

        if (count($args) === 1) {
            if (!$sender instanceof Player) { $sender->sendMessage("§cIn-game only for single-arg /tp"); return true; }
            $target = $this->plugin->getServer()->getPlayerByPrefix($args[0]);
            if ($target === null) { $sender->sendMessage("§cPlayer not found."); return true; }
            $sender->teleport($target->getPosition());
            $sender->sendMessage("§aTeleported to §e{$target->getName()}§a.");
            $this->plugin->logAdminAction($senderName, "TP", $target->getName());
        } elseif (count($args) >= 2) {
            $who = $this->plugin->getServer()->getPlayerByPrefix($args[0]);
            if ($who === null) { $sender->sendMessage("§cPlayer not found."); return true; }
            if (count($args) >= 4 && is_numeric($args[1]) && is_numeric($args[2]) && is_numeric($args[3])) {
                $world = $sender instanceof Player ? $sender->getWorld() : $this->plugin->getServer()->getWorldManager()->getDefaultWorld();
                $who->teleport(new \pocketmine\world\Position((float)$args[1], (float)$args[2], (float)$args[3], $world));
                $sender->sendMessage("§aTeleported §e{$who->getName()}§a to {$args[1]}, {$args[2]}, {$args[3]}.");
            } else {
                $target = $this->plugin->getServer()->getPlayerByPrefix($args[1]);
                if ($target === null) { $sender->sendMessage("§cTarget player not found."); return true; }
                $who->teleport($target->getPosition());
                $sender->sendMessage("§aTeleported §e{$who->getName()}§a to §e{$target->getName()}§a.");
                $this->plugin->logAdminAction($senderName, "TP", "{$who->getName()} -> {$target->getName()}");
            }
        }
        return true;
    }
}
