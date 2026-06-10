<?php
declare(strict_types=1);

namespace AdminTools\commands;

use AdminTools\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class AdminLogCommand extends Command {
    private Main $plugin;
    public function __construct(Main $plugin) {
        parent::__construct("adminlog", "View recent admin actions", "/adminlog [lines]");
        $this->plugin = $plugin;
        $this->setPermission("admintools.log");
    }
    public function execute(CommandSender $sender, string $label, array $args): bool {
        $lines = isset($args[0]) && is_numeric($args[0]) ? min(50, max(5, (int)$args[0])) : 20;
        $file = $this->plugin->getDataFolder() . "logs/admin-actions.log";
        if (!file_exists($file)) {
            $sender->sendMessage("§7No admin log entries yet.");
            return true;
        }
        $content = file($file);
        $total = count($content);
        $slice = array_slice($content, max(0, $total - $lines));
        $sender->sendMessage("§e=== Last $lines Admin Actions ===");
        foreach ($slice as $line) {
            $sender->sendMessage("§7" . trim($line));
        }
        return true;
    }
}
