<?php
declare(strict_types=1);

namespace AdminTools\listener;

use AdminTools\Main;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\server\CommandEvent;

class AdminListener implements Listener {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();
        $pm = $this->plugin->getPunishmentManager();

        if ($pm->isBanned($name)) {
            $reason = $pm->getBanReason($name);
            $msg = str_replace("{reason}", $reason,
                $this->plugin->getConfig()->getNested("ban.permanent-message",
                    "§cYou are banned.\n§7Reason: {reason}"));
            $player->kick($msg, false);
        }
    }

    public function onChat(PlayerChatEvent $event): void {
        $player = $event->getPlayer();
        $name = $player->getName();
        $pm = $this->plugin->getPunishmentManager();

        if ($pm->isMuted($name)) {
            $event->cancel();
            $reason = $pm->getMuteReason($name);
            $expiry = $pm->getMuteExpiry($name);
            $remaining = max(0, $expiry - time());
            $mins = ceil($remaining / 60);
            $player->sendMessage("§cYou are muted. Reason: §e$reason §c| Expires in: §e{$mins}m");
        }
    }

    public function onMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        $pm = $this->plugin->getPunishmentManager();
        if ($pm->isFrozen($player->getName())) {
            if (!$player->hasPermission("admintools.bypass")) {
                $event->cancel();
            }
        }
    }
}
