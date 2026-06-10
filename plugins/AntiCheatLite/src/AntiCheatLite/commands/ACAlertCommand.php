<?php
declare(strict_types=1);

namespace AntiCheatLite\commands;

use AntiCheatLite\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class ACAlertCommand extends Command {
    private Main $plugin;
    public function __construct(Main $plugin) {
        parent::__construct("acalerts", "View anti-cheat alerts", "/acalerts [player] [lines]");
        $this->plugin = $plugin;
        $this->setPermission("anticheat.admin");
    }
    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (!empty($args[0]) && !is_numeric($args[0])) {
            $name = $args[0];
            $violations = $this->plugin->getViolations($name);
            if (empty($violations)) {
                $sender->sendMessage("§7No violations recorded for §e$name§7.");
                return true;
            }
            $lines = isset($args[1]) ? (int)$args[1] : 20;
            $slice = array_slice($violations, -$lines);
            $sender->sendMessage("§e=== Violations: $name (" . count($violations) . " total) ===");
            foreach ($slice as $v) {
                $t = date("H:i:s", $v["time"]);
                $sender->sendMessage("§7[$t] §c{$v["check"]}§7: §f{$v["detail"]}");
            }
        } else {
            $lines = isset($args[0]) ? (int)$args[0] : 20;
            $file = $this->plugin->getDataFolder() . "logs/anticheat.log";
            if (!file_exists($file)) { $sender->sendMessage("§7No alerts logged."); return true; }
            $content = file($file);
            $slice = array_slice($content, -$lines);
            $sender->sendMessage("§e=== Last $lines Anti-Cheat Alerts ===");
            foreach ($slice as $line) {
                $sender->sendMessage("§7" . trim($line));
            }
        }
        return true;
    }
}
