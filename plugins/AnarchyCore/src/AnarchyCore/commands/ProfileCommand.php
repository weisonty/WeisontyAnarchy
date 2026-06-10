<?php
declare(strict_types=1);

namespace AnarchyCore\commands;

use AnarchyCore\Main;
use AnarchyCore\ui\MenuManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class ProfileCommand extends Command {

    private Main $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("profile", "View a player profile", "/profile [player]");
        $this->plugin = $plugin;
        $this->setPermission("anarchycore.profile");
    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cIn-game only.");
            return true;
        }
        $targetName = !empty($args) ? $args[0] : $sender->getName();
        $pm = $this->plugin->getPlayerDataManager();

        // Check cache or disk
        $data = null;
        if ($pm->isOnline($targetName)) {
            $data = $pm->get($targetName);
        } else {
            $file = $this->plugin->getDataFolder() . "players/" . strtolower($targetName) . ".json";
            if (file_exists($file)) {
                $data = json_decode(file_get_contents($file), true);
            }
        }

        if ($data === null) {
            $sender->sendMessage("§cPlayer '§e$targetName§c' not found.");
            return true;
        }

        MenuManager::sendProfileMenu($sender, $data, $pm);
        return true;
    }
}
