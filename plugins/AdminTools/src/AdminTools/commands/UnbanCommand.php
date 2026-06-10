<?php
declare(strict_types=1);

namespace AdminTools\commands;

use AdminTools\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class UnbanCommand extends Command {
    private Main $plugin;
    public function __construct(Main $plugin) {
        parent::__construct("unban", "Unban a player", "/unban <player>");
        $this->plugin = $plugin;
        $this->setPermission("admintools.ban");
    }
    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (empty($args)) { $sender->sendMessage("§cUsage: §e/unban <player>"); return true; }
        $name = $args[0];
        $senderName = $sender instanceof Player ? $sender->getName() : "Console";
        if ($this->plugin->getPunishmentManager()->unban($name)) {
            $sender->sendMessage("§a$name has been unbanned.");
            $this->plugin->logAdminAction($senderName, "UNBANNED", $name);
        } else {
            $sender->sendMessage("§c$name is not banned.");
        }
        return true;
    }
}
