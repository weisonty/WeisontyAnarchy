<?php
declare(strict_types=1);

namespace AnarchyCore\ui;

use AnarchyCore\data\PlayerDataManager;
use AnarchyCore\Main;
use pocketmine\form\Form;
use pocketmine\player\Player;

class MenuManager {

    public static function sendProfileMenu(Player $player, array $data, PlayerDataManager $pm): void {
        $name = $data["name"] ?? "Unknown";
        $kills = $data["kills"] ?? 0;
        $deaths = $data["deaths"] ?? 0;
        $kdr = $deaths > 0 ? round($kills / $deaths, 2) : $kills;
        $playtime = $pm->formatPlaytime($data["playtime"] ?? 0);
        $firstJoin = $data["first_join"] ?? "Unknown";
        $lastSeen = $data["last_seen"] ?? "Unknown";
        $homeCount = count($data["homes"] ?? []);
        $onlineStatus = $pm->isOnline($name) ? "§aOnline" : "§7Offline";

        $form = new class([
            "type" => "custom_form",
            "title" => "§lProfile: §r§e$name",
            "content" => [
                ["type" => "label", "text" => "§7Status: $onlineStatus"],
                ["type" => "label", "text" => "§7Kills: §a$kills"],
                ["type" => "label", "text" => "§7Deaths: §c$deaths"],
                ["type" => "label", "text" => "§7KDR: §e$kdr"],
                ["type" => "label", "text" => "§7Playtime: §6$playtime"],
                ["type" => "label", "text" => "§7Homes: §b$homeCount"],
                ["type" => "label", "text" => "§7First Join: §f$firstJoin"],
                ["type" => "label", "text" => "§7Last Seen: §f$lastSeen"],
            ]
        ]) implements Form {
            private array $data;
            public function __construct(array $data) { $this->data = $data; }
            public function jsonSerialize(): array { return $this->data; }
            public function handleResponse(Player $player, $data): void {}
        };

        $player->sendForm($form);
    }

    public static function sendStatsMenu(Player $player, PlayerDataManager $pm): void {
        $top = $pm->getTopKills(10);
        $content = "§e§lTop Killers - Weisonty Anarchy\n\n";
        foreach ($top as $i => $entry) {
            $rank = $i + 1;
            $content .= "§6#{$rank} §f{$entry["name"]} §7- §c{$entry["kills"]} kills\n";
        }
        if (empty($top)) {
            $content .= "§7No data yet. Go kill someone!\n";
        }

        $form = new class([
            "type" => "modal",
            "title" => "§lServer Statistics",
            "content" => $content,
            "button1" => "§aClose",
            "button2" => "",
        ]) implements Form {
            private array $data;
            public function __construct(array $data) { $this->data = $data; }
            public function jsonSerialize(): array { return $this->data; }
            public function handleResponse(Player $player, $data): void {}
        };

        $player->sendForm($form);
    }

    public static function sendMainMenu(Player $player, Main $plugin): void {
        $pm = $plugin->getPlayerDataManager();
        $data = $pm->get($player->getName());
        $kills = $data["kills"] ?? 0;
        $deaths = $data["deaths"] ?? 0;
        $playtime = $pm->formatPlaytime($data["playtime"] ?? 0);
        $form = new class([
            "type" => "form",
            "title" => "§l§4Weisonty Anarchy",
            "content" => "§7Kills: §a{$kills}  §7Deaths: §c{$deaths}  §7Playtime: §6{$playtime}",
            "buttons" => [
                ["text" => "§lMy Profile"],
                ["text" => "§lServer Stats"],
                ["text" => "§lTeleport Menu"],
                ["text" => "§lSettings"],
                ["text" => "§cClose"],
            ]
        ], $player, $plugin) implements Form {
            private array $data;
            private Player $p;
            private Main $plugin;
            public function __construct(array $data, Player $p, Main $plugin) {
                $this->data = $data; $this->p = $p; $this->plugin = $plugin;
            }
            public function jsonSerialize(): array { return $this->data; }
            public function handleResponse(Player $player, $data): void {
                $pm = $this->plugin->getPlayerDataManager();
                match((int)$data) {
                    0 => MenuManager::sendProfileMenu($player, $pm->get($player->getName()), $pm),
                    1 => MenuManager::sendStatsMenu($player, $pm),
                    2 => MenuManager::sendTeleportMenu($player, $this->plugin),
                    3 => MenuManager::sendSettingsMenu($player, $this->plugin),
                    default => null,
                };
            }
        };
        $player->sendForm($form);
    }

    public static function sendTeleportMenu(Player $player, Main $plugin): void {
        $form = new class([
            "type" => "form",
            "title" => "§lTeleport Menu",
            "content" => "§7Where would you like to go?",
            "buttons" => [
                ["text" => "§a§lGo to Spawn"],
                ["text" => "§b§lRandom Teleport (RTP)"],
                ["text" => "§e§lMy Homes"],
                ["text" => "§7Back"],
            ]
        ], $player, $plugin) implements Form {
            private array $data;
            private Player $p;
            private Main $plugin;
            public function __construct(array $data, Player $p, Main $plugin) {
                $this->data = $data; $this->p = $p; $this->plugin = $plugin;
            }
            public function jsonSerialize(): array { return $this->data; }
            public function handleResponse(Player $player, $data): void {
                $server = $this->plugin->getServer();
                switch ((int)$data) {
                    case 0:
                        $server->dispatchCommand($player, "spawn");
                        break;
                    case 1:
                        $server->dispatchCommand($player, "rtp");
                        break;
                    case 2:
                        $homes = $this->plugin->getPlayerDataManager()->getHomes($player->getName());
                        if (empty($homes)) {
                            $player->sendMessage("§cNo homes set. Use §e/sethome <name>§c.");
                        } else {
                            $list = implode("§7, §e", array_keys($homes));
                            $player->sendMessage("§6Homes: §e$list");
                            $player->sendMessage("§7Use §e/home <name> §7to teleport.");
                        }
                        break;
                    case 3:
                        MenuManager::sendMainMenu($player, $this->plugin);
                        break;
                }
            }
        };
        $player->sendForm($form);
    }

    public static function sendSettingsMenu(Player $player, Main $plugin): void {
        $pm = $plugin->getPlayerDataManager();
        $data = $pm->get($player->getName());
        $msgToggle = (bool)($data["settings"]["msg_toggle"] ?? true);
        $deathMsg = (bool)($data["settings"]["death_messages"] ?? true);

        $form = new class([
            "type" => "custom_form",
            "title" => "§lMy Settings",
            "content" => [
                ["type" => "toggle", "text" => "§fAllow Private Messages", "default" => $msgToggle],
                ["type" => "toggle", "text" => "§fShow Death Messages", "default" => $deathMsg],
            ]
        ], $player, $plugin) implements Form {
            private array $data;
            private Player $p;
            private Main $plugin;
            public function __construct(array $data, Player $p, Main $plugin) {
                $this->data = $data; $this->p = $p; $this->plugin = $plugin;
            }
            public function jsonSerialize(): array { return $this->data; }
            public function handleResponse(Player $player, $data): void {
                if (!is_array($data)) return;
                $pm = $this->plugin->getPlayerDataManager();
                $pData = $pm->get($player->getName());
                $pData["settings"]["msg_toggle"] = (bool)$data[0];
                $pData["settings"]["death_messages"] = (bool)$data[1];
                $pm->set($player->getName(), $pData);
                $pm->save($player->getName());
                $player->sendMessage("§aSettings saved!");
            }
        };
        $player->sendForm($form);
    }

    public static function sendNPCMenu(Player $player, string $type, Main $plugin): void {
        match($type) {
            "SERVER_INFO" => self::sendServerInfoMenu($player, $plugin),
            "RTP" => $plugin->getServer()->dispatchCommand($player, "rtp"),
            "STATS" => self::sendStatsMenu($player, $plugin->getPlayerDataManager()),
            "HELP" => self::sendHelpMenu($player),
            "SETTINGS" => self::sendSettingsMenu($player, $plugin),
            default => null,
        };
    }

    private static function sendServerInfoMenu(Player $player, Main $plugin): void {
        $online = count($plugin->getServer()->getOnlinePlayers());
        $max = $plugin->getServer()->getMaxPlayers();
        $form = new class([
            "type" => "modal",
            "title" => "§l§4Weisonty Anarchy",
            "content" => "§l§4ANARCHY SURVIVAL\n\n§r§7- No rules, no land claims\n§7- PvP always on\n§7- Griefing allowed\n§7- Survive or die\n\n§7Players online: §a{$online}§7/§a{$max}\n\n§eType /help for commands.",
            "button1" => "§aOK",
            "button2" => "",
        ]) implements Form {
            private array $data;
            public function __construct(array $data) { $this->data = $data; }
            public function jsonSerialize(): array { return $this->data; }
            public function handleResponse(Player $player, $data): void {}
        };
        $player->sendForm($form);
    }

    private static function sendHelpMenu(Player $player): void {
        $form = new class([
            "type" => "modal",
            "title" => "§lCommand Help",
            "content" => implode("\n", [
                "§e§l--- Survival ---",
                "§e/spawn §7- Go to spawn",
                "§e/rtp §7- Random teleport",
                "§e/sethome <n> §7- Set a home",
                "§e/home [n] §7- Go to home",
                "§e/delhome <n> §7- Delete home",
                "",
                "§e§l--- Social ---",
                "§e/msg <player> <msg> §7- PM a player",
                "§e/reply <msg> §7- Reply to PM",
                "§e/tpa <player> §7- Request TP",
                "§e/tpaccept §7- Accept TP request",
                "§e/tpdeny §7- Deny TP request",
                "",
                "§e§l--- Info ---",
                "§e/profile [player] §7- View profile",
                "§e/stats §7- Leaderboard",
            ]),
            "button1" => "§aClose",
            "button2" => "",
        ]) implements Form {
            private array $data;
            public function __construct(array $data) { $this->data = $data; }
            public function jsonSerialize(): array { return $this->data; }
            public function handleResponse(Player $player, $data): void {}
        };
        $player->sendForm($form);
    }
}
