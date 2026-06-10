<?php
declare(strict_types=1);

namespace AnarchyCore\commands;

use AnarchyCore\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\block\VanillaBlocks;

class RTPCommand extends Command {

    private Main $plugin;
    /** @var array<string, int> */
    private array $cooldowns = [];

    public function __construct(Main $plugin) {
        parent::__construct("rtp", "Randomly teleport in the world", "/rtp");
        $this->plugin = $plugin;
        $this->setPermission("anarchycore.rtp");
    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cIn-game only.");
            return true;
        }
        $name = $sender->getName();
        $cfg = $this->plugin->getConfig()->get("rtp", []);
        $cooldown = (int)($cfg["cooldown-seconds"] ?? 300);

        if (isset($this->cooldowns[$name])) {
            $remaining = $cooldown - (time() - $this->cooldowns[$name]);
            if ($remaining > 0) {
                $sender->sendMessage("§cRTP cooldown: §e{$remaining}s §cremaining.");
                return true;
            }
        }

        $sender->sendMessage("§7Finding a safe location...");

        $minDist = (int)($cfg["min-distance"] ?? 500);
        $maxDist = (int)($cfg["max-distance"] ?? 10000);
        $maxAttempts = (int)($cfg["max-attempts"] ?? 30);
        $worldName = $this->plugin->getConfig()->getNested("server.spawn-world", "world");
        $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($worldName);

        if ($world === null) {
            $sender->sendMessage("§cWorld not loaded. Contact an admin.");
            return true;
        }

        for ($i = 0; $i < $maxAttempts; $i++) {
            $angle = lcg_value() * 2 * M_PI;
            $dist = mt_rand($minDist, $maxDist);
            $x = (int)($dist * cos($angle));
            $z = (int)($dist * sin($angle));

            // Load the chunk and find safe Y
            $world->loadChunk($x >> 4, $z >> 4);
            $y = $world->getHighestBlockAt($x, $z);

            if ($y === null || $y < 5) continue;
            $pos = new Position($x + 0.5, $y + 1, $z + 0.5, $world);

            // Check block is not in void and is safe
            $block = $world->getBlockAt($x, $y, $z);
            if ($block->isSolid()) {
                $sender->teleport($pos);
                $this->cooldowns[$name] = time();
                $sender->sendMessage("§aRandomly teleported! Coordinates: §e{$x}, {$y}, {$z}");
                $this->plugin->logAction("RTP", "$name RTP'd to $x, $y, $z");
                return true;
            }
        }

        $sender->sendMessage("§cCould not find a safe location. Try again.");
        return true;
    }
}
