<?php
declare(strict_types=1);

namespace AnarchyCore\commands;

use AnarchyCore\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class TpDenyCommand extends Command {

    private Main $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("tpdeny", "Deny a teleport request", "/tpdeny");
        $this->plugin = $plugin;
        $this->setPermission("anarchycore.tpa");
    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cIn-game only.");
            return true;
        }
        $name = $sender->getName();
        if (!isset(TpaCommand::$pendingRequests[$name])) {
            $sender->sendMessage("§cNo pending teleport request.");
            return true;
        }
        $req = TpaCommand::$pendingRequests[$name];
        unset(TpaCommand::$pendingRequests[$name]);
        $requester = $this->plugin->getServer()->getPlayerByPrefix($req["from"]);
        if ($requester !== null && $requester->isOnline()) {
            $requester->sendMessage("§c$name denied your teleport request.");
        }
        $sender->sendMessage("§7Teleport request denied.");
        return true;
    }
}
