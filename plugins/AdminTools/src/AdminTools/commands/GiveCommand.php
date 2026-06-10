<?php
declare(strict_types=1);

namespace AdminTools\commands;

use AdminTools\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\StringToItemParser;
use pocketmine\player\Player;

class GiveCommand extends Command {
    private Main $plugin;
    public function __construct(Main $plugin) {
        parent::__construct("give", "Give items to a player", "/give <player> <item> [amount]");
        $this->plugin = $plugin;
        $this->setPermission("admintools.give");
    }
    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (count($args) < 2) { $sender->sendMessage("§cUsage: §e/give <player> <item> [amount]"); return true; }
        $target = $this->plugin->getServer()->getPlayerByPrefix($args[0]);
        if ($target === null) { $sender->sendMessage("§cPlayer not found."); return true; }

        $itemString = $args[1];
        $amount = isset($args[2]) && is_numeric($args[2]) ? max(1, min(64, (int)$args[2])) : 1;

        $item = StringToItemParser::getInstance()->parse($itemString);
        if ($item === null) {
            $sender->sendMessage("§cUnknown item: §e$itemString");
            $sender->sendMessage("§7Use item names like: §esword §7or §ediamonds§7, §eapple§7, §estone§7, etc.");
            return true;
        }

        $item->setCount($amount);
        $target->getInventory()->addItem($item);
        $senderName = $sender instanceof Player ? $sender->getName() : "Console";
        $sender->sendMessage("§aGave §e{$amount}x {$item->getName()}§a to §e{$target->getName()}§a.");
        $target->sendMessage("§aYou received §e{$amount}x {$item->getName()}§a from §e$senderName§a.");
        $this->plugin->logAdminAction($senderName, "GAVE", $target->getName(), "{$amount}x {$item->getName()}");
        return true;
    }
}
