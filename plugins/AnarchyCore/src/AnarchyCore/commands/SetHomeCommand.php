<?php
declare(strict_types=1);

namespace AnarchyCore\commands;

use AnarchyCore\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class SetHomeCommand extends Command {

    private Main $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("sethome", "Set a home at your current location", "/sethome <name>");
        $this->plugin = $plugin;
        $this->setPermission("anarchycore.sethome");
    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cIn-game only.");
            return true;
        }
        if (empty($args)) {
            $sender->sendMessage("§cUsage: §e/sethome <name>");
            return true;
        }
        $homeName = strtolower($args[0]);
        if (!preg_match('/^[a-z0-9_]{1,16}$/', $homeName)) {
            $sender->sendMessage("§cHome name must be 1-16 characters (a-z, 0-9, _).");
            return true;
        }

        $pos = $sender->getPosition();
        $location = [
            "x" => $pos->getX(),
            "y" => $pos->getY(),
            "z" => $pos->getZ(),
            "world" => $sender->getWorld()->getFolderName(),
        ];

        $pm = $this->plugin->getPlayerDataManager();
        if ($pm->setHome($sender->getName(), $homeName, $location)) {
            $sender->sendMessage("§aHome §e$homeName §aset!");
        } else {
            $max = (int)($this->plugin->getConfig()->get("homes")["max-homes"] ?? 5);
            $sender->sendMessage("§cYou have reached the maximum of §e$max §chomes. Delete one first with §e/delhome§c.");
        }
        return true;
    }
}
