<?php
declare(strict_types=1);

namespace AnarchyCore\commands;

use AnarchyCore\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class DelHomeCommand extends Command {

    private Main $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("delhome", "Delete a home", "/delhome <name>");
        $this->plugin = $plugin;
        $this->setPermission("anarchycore.delhome");
    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cIn-game only.");
            return true;
        }
        if (empty($args)) {
            $sender->sendMessage("§cUsage: §e/delhome <name>");
            return true;
        }
        $homeName = strtolower($args[0]);
        $pm = $this->plugin->getPlayerDataManager();
        if ($pm->deleteHome($sender->getName(), $homeName)) {
            $sender->sendMessage("§aHome §e$homeName §adeleted.");
        } else {
            $sender->sendMessage("§cHome '§e$homeName§c' not found.");
        }
        return true;
    }
}
