<?php
declare(strict_types=1);

namespace AdminTools\commands;

use AdminTools\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\inventory\PlayerInventory;

class InvSeeCommand extends Command {
    private Main $plugin;
    public function __construct(Main $plugin) {
        parent::__construct("invsee", "View a player's inventory", "/invsee <player>");
        $this->plugin = $plugin;
        $this->setPermission("admintools.invsee");
    }
    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (!$sender instanceof Player) { $sender->sendMessage("§cIn-game only."); return true; }
        if (empty($args)) { $sender->sendMessage("§cUsage: §e/invsee <player>"); return true; }
        $target = $this->plugin->getServer()->getPlayerByPrefix($args[0]);
        if ($target === null) { $sender->sendMessage("§cPlayer not found."); return true; }

        $inventory = $target->getInventory();
        $senderName = $sender->getName();

        $sender->sendMessage("§e=== {$target->getName()}'s Inventory ===");
        $items = $inventory->getContents();
        if (empty($items)) {
            $sender->sendMessage("§7  (empty)");
        } else {
            foreach ($items as $slot => $item) {
                $sender->sendMessage("§7  Slot §e$slot§7: §f" . $item->getName() . " §7x" . $item->getCount());
            }
        }

        // Armor
        $sender->sendMessage("§e=== Armor ===");
        foreach ($target->getArmorInventory()->getContents() as $slot => $item) {
            $sender->sendMessage("§7  Slot §e$slot§7: §f" . $item->getName());
        }

        $this->plugin->logAdminAction($senderName, "INVSEE", $target->getName());
        return true;
    }
}
