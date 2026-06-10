<?php
declare(strict_types=1);

namespace AntiCheatLite\listener;

use AntiCheatLite\Main;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;

class AntiCheatListener implements Listener {

    private Main $plugin;
    /** @var array<string, array{x: float, y: float, z: float, tick: int}> */
    private array $lastPos = [];
    /** @var array<string, int> */
    private array $airTicks = [];
    /** @var array<string, float> */
    private array $lastFallY = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();

        if (!$this->plugin->getConfig()->getNested("enabled", true)) return;
        if ($this->plugin->isBypassed($name)) return;

        $from = $event->getFrom();
        $to = $event->getTo();

        // Speed check
        if ($this->plugin->getConfig()->getNested("checks.speed.enabled", true)) {
            $dx = $to->getX() - $from->getX();
            $dz = $to->getZ() - $from->getZ();
            $speed = sqrt($dx * $dx + $dz * $dz) * 20; // blocks/second approx
            $maxSpeed = (float)$this->plugin->getConfig()->getNested("checks.speed.max-speed", 12.0);
            if ($speed > $maxSpeed) {
                $this->plugin->getSpeedCheck()->flag($player, round($speed, 2) . " b/s (max {$maxSpeed})");
            }
        }

        // Fly check
        if ($this->plugin->getConfig()->getNested("checks.fly.enabled", true)) {
            if (!$player->isOnGround() && !$player->getAllowFlight()) {
                $this->airTicks[$name] = ($this->airTicks[$name] ?? 0) + 1;
                $maxAir = (int)$this->plugin->getConfig()->getNested("checks.fly.max-air-ticks", 20);
                if ($this->airTicks[$name] > $maxAir) {
                    $this->plugin->getFlyCheck()->flag($player, "air ticks: {$this->airTicks[$name]}");
                }
            } else {
                $this->airTicks[$name] = 0;
            }
        }

        // Teleport abuse check (very high instant movement)
        if ($this->plugin->getConfig()->getNested("checks.teleport_abuse.enabled", true)) {
            $dist = $from->distance($to);
            $maxDist = (float)$this->plugin->getConfig()->getNested("checks.teleport_abuse.max-distance-per-tick", 30.0);
            if ($dist > $maxDist) {
                $this->plugin->getTpAbuseCheck()->flag($player, "moved " . round($dist, 1) . " blocks in one tick");
            }
        }

        // NoFall check
        if ($this->plugin->getConfig()->getNested("checks.nofall.enabled", true)) {
            $minFall = (float)$this->plugin->getConfig()->getNested("checks.nofall.min-fall-distance", 4.0);
            if (!isset($this->lastFallY[$name])) {
                $this->lastFallY[$name] = $to->getY();
            }
            $fallDist = $this->lastFallY[$name] - $to->getY();
            if ($to->getY() < $this->lastFallY[$name]) {
                // Falling
            } else {
                if ($fallDist > $minFall && $player->isOnGround()) {
                    // Should have taken damage - check for nofall
                    if ($player->getHealth() === $player->getMaxHealth()) {
                        $this->plugin->getNoFallCheck()->flag($player, "fell " . round($fallDist, 1) . " blocks with no damage");
                    }
                }
                $this->lastFallY[$name] = $to->getY();
            }
        }
    }

    public function onChat(PlayerChatEvent $event): void {
        $player = $event->getPlayer();
        if ($this->plugin->isBypassed($player->getName())) return;
        if ($this->plugin->getConfig()->getNested("checks.spam.enabled", true)) {
            $this->plugin->getSpamCheck()->checkChat($player);
        }
    }
}
