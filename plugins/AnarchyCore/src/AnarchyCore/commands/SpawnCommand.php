<?php
declare(strict_types=1);

namespace AnarchyCore\commands;

use AnarchyCore\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\world\Position;

class SpawnCommand extends Command {

    private Main $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("spawn", "Teleport to world spawn", "/spawn");
        $this->plugin = $plugin;
        $this->setPermission("anarchycore.spawn");
    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cThis command can only be used in-game.");
            return true;
        }
        $spawnCfg = $this->plugin->getConfig()->get("spawn", []);
        $worldName = $spawnCfg["world"] ?? "world";
        $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($worldName);
        if ($world === null) {
            $sender->sendMessage("§cSpawn world not found! Contact an admin.");
            return true;
        }
        $pos = new Position(
            (float)($spawnCfg["x"] ?? 0),
            (float)($spawnCfg["y"] ?? 65),
            (float)($spawnCfg["z"] ?? 0),
            $world
        );
        $sender->teleport($pos);
        $sender->sendMessage("§aTeleported to spawn!");
        $this->plugin->logAction("COMMAND", $sender->getName() . " used /spawn");
        return true;
    }
}
