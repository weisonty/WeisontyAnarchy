<?php
declare(strict_types=1);

namespace AdminTools;

use AdminTools\commands\BanCommand;
use AdminTools\commands\UnbanCommand;
use AdminTools\commands\KickCommand;
use AdminTools\commands\MuteCommand;
use AdminTools\commands\UnmuteCommand;
use AdminTools\commands\WarnCommand;
use AdminTools\commands\FreezeCommand;
use AdminTools\commands\TpCommand;
use AdminTools\commands\TpHereCommand;
use AdminTools\commands\InvSeeCommand;
use AdminTools\commands\GiveCommand;
use AdminTools\commands\AdminLogCommand;
use AdminTools\data\PunishmentManager;
use AdminTools\listener\AdminListener;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase {

    private static Main $instance;
    private PunishmentManager $punishmentManager;

    public static function getInstance(): Main {
        return self::$instance;
    }

    public function onLoad(): void {
        self::$instance = $this;
    }

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->saveResource("config.yml");
        @mkdir($this->getDataFolder() . "logs/");

        $this->punishmentManager = new PunishmentManager($this);

        $this->registerCommands();
        $this->getServer()->getPluginManager()->registerEvents(new AdminListener($this), $this);

        $this->getLogger()->info("§aAdminTools enabled.");
    }

    public function onDisable(): void {
        if (isset($this->punishmentManager)) {
            $this->punishmentManager->save();
        }
    }

    private function registerCommands(): void {
        $map = $this->getServer()->getCommandMap();
        $map->registerAll("admintools", [
            new BanCommand($this),
            new UnbanCommand($this),
            new KickCommand($this),
            new MuteCommand($this),
            new UnmuteCommand($this),
            new WarnCommand($this),
            new FreezeCommand($this),
            new TpCommand($this),
            new TpHereCommand($this),
            new InvSeeCommand($this),
            new GiveCommand($this),
            new AdminLogCommand($this),
        ]);
    }

    public function getPunishmentManager(): PunishmentManager {
        return $this->punishmentManager;
    }

    public function logAdminAction(string $admin, string $action, string $target, string $reason = ""): void {
        $line = "[" . date("Y-m-d H:i:s") . "] [$action] Admin: $admin | Target: $target" . ($reason ? " | Reason: $reason" : "") . "\n";
        $file = $this->getDataFolder() . "logs/admin-actions.log";
        file_put_contents($file, $line, FILE_APPEND);

        if ($this->getConfig()->getNested("logging.broadcast-to-admins", true)) {
            $perm = $this->getConfig()->getNested("logging.admin-permission", "admintools.kick");
            foreach ($this->getServer()->getOnlinePlayers() as $p) {
                if ($p->hasPermission($perm)) {
                    $p->sendMessage("§8[§4ADMIN§8] §7$admin $action $target" . ($reason ? " ($reason)" : ""));
                }
            }
            $this->getServer()->getLogger()->info("[ADMIN] $admin $action $target" . ($reason ? " ($reason)" : ""));
        }
    }
}
