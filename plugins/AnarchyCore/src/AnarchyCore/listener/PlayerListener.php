<?php
declare(strict_types=1);

namespace AnarchyCore\listener;

use AnarchyCore\Main;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\player\Player;
use pocketmine\world\Position;

class PlayerListener implements Listener {

    private Main $plugin;
    /** @var array<string, float> */
    private array $lastMessage = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();
        $pm = $this->plugin->getPlayerDataManager();

        $pm->load($name);
        $data = $pm->get($name);
        $data["join_time"] = time();
        $pm->set($name, $data);

        $isFirstJoin = !file_exists($this->plugin->getDataFolder() . "players/" . strtolower($name) . ".json");

        // Set join message
        $joinMsg = $this->plugin->getConfig()->getNested("messages.join", "§7[§a+§7] §f{player}");
        $event->setJoinMessage(str_replace("{player}", $name, $joinMsg));

        // Welcome message
        if ($isFirstJoin) {
            $msg = str_replace("{player}", $name, $this->plugin->getConfig()->getNested("messages.first-join", "§e{player} §7joined for the first time!"));
            $this->plugin->getServer()->broadcastMessage($msg);
            $player->sendMessage("§aWelcome to §lWeisonty Anarchy§a! Type §e/help §afor commands.");
            $player->sendMessage("§7This is a no-rules anarchy server. PvP and griefing are allowed!");
        } else {
            $playtime = $pm->formatPlaytime($data["playtime"] ?? 0);
            $msg = str_replace(["{player}", "{playtime}"], [$name, $playtime],
                $this->plugin->getConfig()->getNested("messages.welcome-back", "§7Welcome back, §e{player}§7!"));
            $player->sendMessage($msg);
        }

        // Teleport to spawn on first join
        $spawnCfg = $this->plugin->getConfig()->get("spawn", []);
        $worldName = $spawnCfg["world"] ?? "world";
        $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($worldName);
        if ($world !== null) {
            $x = (float)($spawnCfg["x"] ?? 0);
            $y = (float)($spawnCfg["y"] ?? 65);
            $z = (float)($spawnCfg["z"] ?? 0);
            $player->teleport(new Position($x, $y, $z, $world));
        }

        $this->plugin->logAction("JOIN", "$name joined the server from " . $player->getNetworkSession()->getIp());
    }

    public function onQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();
        $pm = $this->plugin->getPlayerDataManager();

        // Save last location
        $pos = $player->getPosition();
        $pm->setLastLocation($name, [
            "x" => $pos->getX(),
            "y" => $pos->getY(),
            "z" => $pos->getZ(),
            "world" => $player->getWorld()->getFolderName(),
            "yaw" => $player->getLocation()->getYaw(),
            "pitch" => $player->getLocation()->getPitch(),
        ]);

        $pm->updatePlaytime($name);
        $pm->unload($name);

        $leaveMsg = $this->plugin->getConfig()->getNested("messages.leave", "§7[§c-§7] §f{player}");
        $event->setQuitMessage(str_replace("{player}", $name, $leaveMsg));

        $this->plugin->logAction("LEAVE", "$name left the server");
    }

    public function onDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();
        $pm = $this->plugin->getPlayerDataManager();
        $pm->addDeath($name);

        $cause = $player->getLastDamageCause();
        $killerName = "unknown";

        if ($cause instanceof EntityDamageByEntityEvent) {
            $killer = $cause->getDamager();
            if ($killer instanceof Player) {
                $killerName = $killer->getName();
                $pm->addKill($killerName);
                $msg = str_replace(["{player}", "{killer}"], [$name, $killerName],
                    $this->plugin->getConfig()->getNested("messages.death", "§c{player} was slain by {killer}"));
                $event->setDeathMessage($msg);
                $this->plugin->logAction("KILL", "$killerName killed $name");
            }
        } else {
            $msg = str_replace("{player}", $name,
                $this->plugin->getConfig()->getNested("messages.death-natural", "§c{player} died"));
            $event->setDeathMessage($msg);
        }

        $this->plugin->logAction("DEATH", "$name died (killer: $killerName)");
    }

    public function onRespawn(PlayerRespawnEvent $event): void {
        $player = $event->getPlayer();
        $pm = $this->plugin->getPlayerDataManager();
        $data = $pm->get($player->getName());

        // Try last location first
        $lastLoc = $data["last_location"] ?? null;
        if ($lastLoc !== null) {
            $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($lastLoc["world"] ?? "world");
            if ($world !== null) {
                $pos = new Position((float)$lastLoc["x"], (float)$lastLoc["y"], (float)$lastLoc["z"], $world);
                $event->setRespawnPosition($pos);
                return;
            }
        }

        // Fall back to spawn
        $spawnCfg = $this->plugin->getConfig()->get("spawn", []);
        $worldName = $spawnCfg["world"] ?? "world";
        $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($worldName);
        if ($world !== null) {
            $pos = new Position(
                (float)($spawnCfg["x"] ?? 0),
                (float)($spawnCfg["y"] ?? 65),
                (float)($spawnCfg["z"] ?? 0),
                $world
            );
            $event->setRespawnPosition($pos);
        }
    }

    public function onChat(PlayerChatEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();
        $msg = $event->getMessage();

        $antispam = $this->plugin->getConfig()->getNested("antispam.enabled", true);
        if ($antispam) {
            $limit = (int)$this->plugin->getConfig()->getNested("antispam.messages-per-second", 3);
            $now = microtime(true);
            if (isset($this->lastMessage[$name]) && ($now - $this->lastMessage[$name]) < (1.0 / $limit)) {
                $event->cancel();
                $player->sendMessage("§cYou are sending messages too fast!");
                return;
            }
            $this->lastMessage[$name] = $now;
        }

        if ($this->plugin->getConfig()->getNested("logging.chat", false)) {
            $this->plugin->logAction("CHAT", "$name: $msg");
        }
    }
}
