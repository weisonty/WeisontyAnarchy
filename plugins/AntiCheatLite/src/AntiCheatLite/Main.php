<?php
declare(strict_types=1);

namespace AntiCheatLite;

use AntiCheatLite\checks\FlyCheck;
use AntiCheatLite\checks\SpeedCheck;
use AntiCheatLite\checks\NoFallCheck;
use AntiCheatLite\checks\SpamCheck;
use AntiCheatLite\checks\TeleportAbuseCheck;
use AntiCheatLite\commands\ACAlertCommand;
use AntiCheatLite\commands\ACBypassCommand;
use AntiCheatLite\listener\AntiCheatListener;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase {

    private static Main $instance;
    private FlyCheck $flyCheck;
    private SpeedCheck $speedCheck;
    private NoFallCheck $noFallCheck;
    private SpamCheck $spamCheck;
    private TeleportAbuseCheck $tpAbuseCheck;
    /** @var array<string, bool> */
    private array $bypassedPlayers = [];
    /** @var array<string, array> */
    private array $violations = [];

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

        $this->flyCheck = new FlyCheck($this);
        $this->speedCheck = new SpeedCheck($this);
        $this->noFallCheck = new NoFallCheck($this);
        $this->spamCheck = new SpamCheck($this);
        $this->tpAbuseCheck = new TeleportAbuseCheck($this);

        $map = $this->getServer()->getCommandMap();
        $map->registerAll("anticheat", [
            new ACAlertCommand($this),
            new ACBypassCommand($this),
        ]);

        $this->getServer()->getPluginManager()->registerEvents(new AntiCheatListener($this), $this);
        $this->getLogger()->info("§aAntiCheatLite enabled. Log-only mode active.");
    }

    public function getFlyCheck(): FlyCheck { return $this->flyCheck; }
    public function getSpeedCheck(): SpeedCheck { return $this->speedCheck; }
    public function getNoFallCheck(): NoFallCheck { return $this->noFallCheck; }
    public function getSpamCheck(): SpamCheck { return $this->spamCheck; }
    public function getTpAbuseCheck(): TeleportAbuseCheck { return $this->tpAbuseCheck; }

    public function isBypassed(string $name): bool {
        return isset($this->bypassedPlayers[strtolower($name)]);
    }

    public function toggleBypass(string $name): bool {
        $key = strtolower($name);
        if (isset($this->bypassedPlayers[$key])) {
            unset($this->bypassedPlayers[$key]);
            return false;
        }
        $this->bypassedPlayers[$key] = true;
        return true;
    }

    public function addViolation(string $name, string $check, string $detail): void {
        $key = strtolower($name);
        if (!isset($this->violations[$key])) $this->violations[$key] = [];
        $this->violations[$key][] = ["check" => $check, "detail" => $detail, "time" => time()];

        $count = count($this->violations[$key]);
        $threshold = (int)$this->getConfig()->get("violation-threshold", 3);

        $this->logAlert($name, $check, $detail);

        if ($count % $threshold === 0) {
            $this->notifyAdmins("§8[§4AC§8] §f$name §7flagged for §e$check §7(x$count): §f$detail");
        }
    }

    public function getViolations(string $name): array {
        return $this->violations[strtolower($name)] ?? [];
    }

    private function logAlert(string $player, string $check, string $detail): void {
        $file = $this->getDataFolder() . "logs/anticheat.log";
        $line = "[" . date("Y-m-d H:i:s") . "] ALERT | Player: $player | Check: $check | Detail: $detail\n";
        file_put_contents($file, $line, FILE_APPEND);
        if ($this->getConfig()->getNested("alerts.log-all-to-console", false)) {
            $this->getLogger()->info("[AC] $player | $check | $detail");
        }
    }

    public function notifyAdmins(string $message): void {
        $perm = $this->getConfig()->getNested("alerts.admin-permission", "anticheat.admin");
        foreach ($this->getServer()->getOnlinePlayers() as $p) {
            if ($p->hasPermission($perm)) {
                $p->sendMessage($message);
            }
        }
        $this->getServer()->getLogger()->info(strip_tags(str_replace("§", "", $message)));
    }
}
