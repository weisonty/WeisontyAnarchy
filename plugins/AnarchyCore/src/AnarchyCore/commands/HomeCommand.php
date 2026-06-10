<?php
declare(strict_types=1);

namespace AnarchyCore\commands;

use AnarchyCore\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\world\Position;

class HomeCommand extends Command {

    private Main $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("home", "Teleport to a home", "/home [name]");
        $this->plugin = $plugin;
        $this->setPermission("anarchycore.home");
    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cIn-game only.");
            return true;
        }
        $name = $sender->getName();
        $pm = $this->plugin->getPlayerDataManager();
        $homes = $pm->getHomes($name);

        if (empty($homes)) {
            $sender->sendMessage("§cYou have no homes set. Use §e/sethome <name>§c to set one.");
            return true;
        }

        if (empty($args)) {
            if (count($homes) === 1) {
                $homeName = array_key_first($homes);
            } else {
                $list = implode("§7, §e", array_keys($homes));
                $sender->sendMessage("§6Your homes: §e$list");
                $sender->sendMessage("§7Usage: §e/home <name>");
                return true;
            }
        } else {
            $homeName = strtolower($args[0]);
        }

        $home = $pm->getHome($name, $homeName);
        if ($home === null) {
            $sender->sendMessage("§cHome '§e$homeName§c' not found.");
            return true;
        }

        $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($home["world"] ?? "world");
        if ($world === null) {
            $sender->sendMessage("§cThe world for home '§e$homeName§c' no longer exists.");
            return true;
        }

        $pos = new Position((float)$home["x"], (float)$home["y"], (float)$home["z"], $world);
        $sender->teleport($pos);
        $sender->sendMessage("§aTeleported to home §e$homeName§a!");
        return true;
    }
}
