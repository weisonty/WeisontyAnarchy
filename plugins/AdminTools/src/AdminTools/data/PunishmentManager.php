<?php
declare(strict_types=1);

namespace AdminTools\data;

use AdminTools\Main;

class PunishmentManager {

    private Main $plugin;
    private array $data = [];
    /** @var array<string, bool> */
    private array $frozenPlayers = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->load();
    }

    private function getFile(): string {
        return $this->plugin->getDataFolder() . "punishments.json";
    }

    public function load(): void {
        $file = $this->getFile();
        if (file_exists($file)) {
            $this->data = json_decode(file_get_contents($file), true) ?? [];
        }
    }

    public function save(): void {
        file_put_contents($this->getFile(), json_encode($this->data, JSON_PRETTY_PRINT));
    }

    // BAN
    public function ban(string $name, string $reason, string $banner, int $expires = -1): void {
        $key = strtolower($name);
        $this->data["bans"][$key] = [
            "name" => $name,
            "reason" => $reason,
            "banner" => $banner,
            "time" => time(),
            "expires" => $expires,
        ];
        $this->save();
    }

    public function unban(string $name): bool {
        $key = strtolower($name);
        if (isset($this->data["bans"][$key])) {
            unset($this->data["bans"][$key]);
            $this->save();
            return true;
        }
        return false;
    }

    public function isBanned(string $name): bool {
        $key = strtolower($name);
        if (!isset($this->data["bans"][$key])) return false;
        $ban = $this->data["bans"][$key];
        if ($ban["expires"] !== -1 && time() > $ban["expires"]) {
            $this->unban($name);
            return false;
        }
        return true;
    }

    public function getBanReason(string $name): string {
        return $this->data["bans"][strtolower($name)]["reason"] ?? "No reason given";
    }

    // MUTE
    public function mute(string $name, string $reason, string $muter, int $duration = 3600): void {
        $key = strtolower($name);
        $this->data["mutes"][$key] = [
            "name" => $name,
            "reason" => $reason,
            "muter" => $muter,
            "time" => time(),
            "expires" => time() + $duration,
        ];
        $this->save();
    }

    public function unmute(string $name): bool {
        $key = strtolower($name);
        if (isset($this->data["mutes"][$key])) {
            unset($this->data["mutes"][$key]);
            $this->save();
            return true;
        }
        return false;
    }

    public function isMuted(string $name): bool {
        $key = strtolower($name);
        if (!isset($this->data["mutes"][$key])) return false;
        $mute = $this->data["mutes"][$key];
        if (time() > $mute["expires"]) {
            $this->unmute($name);
            return false;
        }
        return true;
    }

    public function getMuteReason(string $name): string {
        return $this->data["mutes"][strtolower($name)]["reason"] ?? "No reason given";
    }

    public function getMuteExpiry(string $name): int {
        return $this->data["mutes"][strtolower($name)]["expires"] ?? 0;
    }

    // WARN
    public function warn(string $name, string $reason, string $warner): void {
        $key = strtolower($name);
        if (!isset($this->data["warnings"][$key])) {
            $this->data["warnings"][$key] = [];
        }
        $this->data["warnings"][$key][] = [
            "reason" => $reason,
            "warner" => $warner,
            "time" => time(),
        ];
        $this->save();
    }

    public function getWarnings(string $name): array {
        return $this->data["warnings"][strtolower($name)] ?? [];
    }

    public function getWarningCount(string $name): int {
        return count($this->getWarnings($name));
    }

    // FREEZE
    public function freeze(string $name): void {
        $this->frozenPlayers[strtolower($name)] = true;
    }

    public function unfreeze(string $name): void {
        unset($this->frozenPlayers[strtolower($name)]);
    }

    public function isFrozen(string $name): bool {
        return isset($this->frozenPlayers[strtolower($name)]);
    }
}
