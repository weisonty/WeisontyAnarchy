<?php
declare(strict_types=1);

namespace AdminTools\commands;

use AdminTools\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class FreezeCommand extends Command {
    private Main $plugin;
    public function __construct(Main $plugin) {
        parent::__construct("freeze", "Freeze or unfreeze a player", "/freeze <player>");
        $this->plugin = $plugin;
        $this->setPermission("admintools.freeze");
    }
    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (empty($args)) { $sender->sendMessage("§cUsage: §e/freeze <player>"); return true; }
        $target = $this->plugin->getServer()->getPlayerByPrefix($args[0]);
        if ($target === null) { $sender->sendMessage("§cPlayer not found."); return true; }
        $pm = $this->plugin->getPunishmentManager();
        $senderName = $sender instanceof Player ? $sender->getName() : "Console";
        if ($pm->isFrozen($target->getName())) {
            $pm->unfreeze($target->getName());
            $target->sendMessage("§aYou have been unfrozen.");
            $sender->sendMessage("§aUnfroze {$target->getName()}.");
            $this->plugin->logAdminAction($senderName, "UNFROZE", $target->getName());
        } else {
            $pm->freeze($target->getName());
            if ($this->plugin->getConfig()->getNested("freeze.notify-player", true)) {
                $target->sendMessage($this->plugin->getConfig()->getNested("freeze.freeze-message", "§cYou have been frozen."));
            }
            $sender->sendMessage("§aFroze {$target->getName()}.");
            $this->plugin->logAdminAction($senderName, "FROZE", $target->getName());
        }
        return true;
    }
}
