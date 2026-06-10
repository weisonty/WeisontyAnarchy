<?php
declare(strict_types=1);

namespace AnarchyCore;

use AnarchyCore\commands\SpawnCommand;
use AnarchyCore\commands\HomeCommand;
use AnarchyCore\commands\SetHomeCommand;
use AnarchyCore\commands\DelHomeCommand;
use AnarchyCore\commands\RTPCommand;
use AnarchyCore\commands\MsgCommand;
use AnarchyCore\commands\ReplyCommand;
use AnarchyCore\commands\TpaCommand;
use AnarchyCore\commands\TpAcceptCommand;
use AnarchyCore\commands\TpDenyCommand;
use AnarchyCore\commands\ProfileCommand;
use AnarchyCore\commands\StatsCommand;
use AnarchyCore\data\PlayerDataManager;
use AnarchyCore\listener\PlayerListener;
use AnarchyCore\npc\NPCManager;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Main extends PluginBase {

    private static Main $instance;
    private PlayerDataManager $playerDataManager;
    private NPCManager $npcManager;
    private Config $logConfig;

    public static function getInstance(): Main {
        return self::$instance;
    }

    public function onLoad(): void {
        self::$instance = $this;
    }

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->saveResource("config.yml");

        @mkdir($this->getDataFolder() . "players/");
        @mkdir($this->getDataFolder() . "logs/");

        $this->playerDataManager = new PlayerDataManager($this);
        $this->npcManager = new NPCManager($this);

        $this->registerCommands();
        $this->registerListeners();

        $this->getLogger()->info("§aAnarchyCore §fenabled! Weisonty Anarchy is live.");
    }

    public function onDisable(): void {
        if (isset($this->playerDataManager)) {
            $this->playerDataManager->saveAll();
        }
        $this->getLogger()->info("§cAnarchyCore disabled. All data saved.");
    }

    private function registerCommands(): void {
        $map = $this->getServer()->getCommandMap();
        $map->registerAll("anarchycore", [
            new SpawnCommand($this),
            new HomeCommand($this),
            new SetHomeCommand($this),
            new DelHomeCommand($this),
            new RTPCommand($this),
            new MsgCommand($this),
            new ReplyCommand($this),
            new TpaCommand($this),
            new TpAcceptCommand($this),
            new TpDenyCommand($this),
            new ProfileCommand($this),
            new StatsCommand($this),
        ]);
    }

    private function registerListeners(): void {
        $pm = $this->getServer()->getPluginManager();
        $pm->registerEvents(new PlayerListener($this), $this);
    }

    public function getPlayerDataManager(): PlayerDataManager {
        return $this->playerDataManager;
    }

    public function getNPCManager(): NPCManager {
        return $this->npcManager;
    }

    public function logAction(string $type, string $message): void {
        $logDir = $this->getDataFolder() . "logs/";
        $file = $logDir . date("Y-m-d") . ".log";
        $line = "[" . date("H:i:s") . "] [$type] $message\n";
        file_put_contents($file, $line, FILE_APPEND);
    }
}
