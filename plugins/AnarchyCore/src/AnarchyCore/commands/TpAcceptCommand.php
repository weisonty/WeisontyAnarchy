<?php
declare(strict_types=1);

namespace AnarchyCore\commands;

use AnarchyCore\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class TpAcceptCommand extends Command {

    private Main $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("tpaccept", "Accept a teleport request", "/tpaccept");
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
        $expire = (int)($this->plugin->getConfig()->getNested("tpa.expire-seconds", 60));
        if ((time() - $req["time"]) > $expire) {
            unset(TpaCommand::$pendingRequests[$name]);
            $sender->sendMessage("§cThat teleport request has expired.");
            return true;
        }
        $requester = $this->plugin->getServer()->getPlayerByPrefix($req["from"]);
        if ($requester === null || !$requester->isOnline()) {
            unset(TpaCommand::$pendingRequests[$name]);
            $sender->sendMessage("§cThat player is no longer online.");
            return true;
        }
        unset(TpaCommand::$pendingRequests[$name]);
        $requester->teleport($sender->getPosition());
        $sender->sendMessage("§aTeleport accepted. §e{$requester->getName()} §ateleported to you.");
        $requester->sendMessage("§aTeleported to §e$name§a!");
        return true;
    }
}
