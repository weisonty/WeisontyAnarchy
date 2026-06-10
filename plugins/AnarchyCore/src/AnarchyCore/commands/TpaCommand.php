<?php
declare(strict_types=1);

namespace AnarchyCore\commands;

use AnarchyCore\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class TpaCommand extends Command {

    private Main $plugin;
    /** @var array<string, array{from: string, time: int}> */
    public static array $pendingRequests = [];
    /** @var array<string, int> */
    private static array $cooldowns = [];

    public function __construct(Main $plugin) {
        parent::__construct("tpa", "Request to teleport to a player", "/tpa <player>");
        $this->plugin = $plugin;
        $this->setPermission("anarchycore.tpa");
    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cIn-game only.");
            return true;
        }
        if (empty($args)) {
            $sender->sendMessage("§cUsage: §e/tpa <player>");
            return true;
        }
        $cfg = $this->plugin->getConfig()->get("tpa", []);
        $cooldown = (int)($cfg["cooldown-seconds"] ?? 30);
        $name = $sender->getName();

        if (isset(self::$cooldowns[$name]) && (time() - self::$cooldowns[$name]) < $cooldown) {
            $remaining = $cooldown - (time() - self::$cooldowns[$name]);
            $sender->sendMessage("§cTPA cooldown: §e{$remaining}s");
            return true;
        }

        $target = $this->plugin->getServer()->getPlayerByPrefix($args[0]);
        if ($target === null || !$target->isOnline()) {
            $sender->sendMessage("§cPlayer not found.");
            return true;
        }
        if ($target->getName() === $name) {
            $sender->sendMessage("§cYou can't teleport to yourself.");
            return true;
        }

        self::$pendingRequests[$target->getName()] = ["from" => $name, "time" => time()];
        self::$cooldowns[$name] = time();

        $target->sendMessage("§e$name §7wants to teleport to you.");
        $target->sendMessage("§7Type §a/tpaccept §7to accept or §c/tpdeny §7to deny. (Expires in {$cfg["expire-seconds"]}s)");
        $sender->sendMessage("§7Teleport request sent to §e{$target->getName()}§7.");

        return true;
    }
}
