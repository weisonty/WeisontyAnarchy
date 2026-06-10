<?php
declare(strict_types=1);

namespace AnarchyCore\data;

use AnarchyCore\Main;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class PlayerDataManager {

    private Main $plugin;
    /** @var array<string, array> */
    private array $cache = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    private function getFile(string $name): string {
        return $this->plugin->getDataFolder() . "players/" . strtolower($name) . ".json";
    }

    public function load(string $name): void {
        $file = $this->getFile($name);
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true) ?? [];
        } else {
            $data = $this->getDefault($name);
        }
        $this->cache[strtolower($name)] = $data;
    }

    public function save(string $name): void {
        $key = strtolower($name);
        if (isset($this->cache[$key])) {
            file_put_contents($this->getFile($name), json_encode($this->cache[$key], JSON_PRETTY_PRINT));
        }
    }

    public function saveAll(): void {
        foreach ($this->cache as $name => $_) {
            $this->save($name);
        }
    }

    public function unload(string $name): void {
        $this->save($name);
        unset($this->cache[strtolower($name)]);
    }

    public function get(string $name): array {
        $key = strtolower($name);
        if (!isset($this->cache[$key])) {
            $this->load($name);
        }
        return $this->cache[$key];
    }

    public function set(string $name, array $data): void {
        $this->cache[strtolower($name)] = $data;
    }

    public function getDefault(string $name): array {
        return [
            "name" => $name,
            "first_join" => date("Y-m-d H:i:s"),
            "last_seen" => date("Y-m-d H:i:s"),
            "playtime" => 0,
            "kills" => 0,
            "deaths" => 0,
            "homes" => [],
            "last_location" => null,
            "last_msg_sender" => null,
            "settings" => [
                "msg_toggle" => true,
                "death_messages" => true,
                "join_messages" => true,
            ],
            "join_time" => time(),
        ];
    }

    public function addKill(string $name): void {
        $data = $this->get($name);
        $data["kills"] = ($data["kills"] ?? 0) + 1;
        $this->set($name, $data);
        $this->save($name);
    }

    public function addDeath(string $name): void {
        $data = $this->get($name);
        $data["deaths"] = ($data["deaths"] ?? 0) + 1;
        $this->set($name, $data);
        $this->save($name);
    }

    public function getKDR(string $name): float {
        $data = $this->get($name);
        $kills = $data["kills"] ?? 0;
        $deaths = $data["deaths"] ?? 0;
        if ($deaths === 0) return (float)$kills;
        return round($kills / $deaths, 2);
    }

    public function setHome(string $name, string $homeName, array $location): bool {
        $data = $this->get($name);
        $maxHomes = (int)($this->plugin->getConfig()->get("homes")["max-homes"] ?? 5);
        if (!isset($data["homes"][$homeName]) && count($data["homes"]) >= $maxHomes) {
            return false;
        }
        $data["homes"][$homeName] = $location;
        $this->set($name, $data);
        $this->save($name);
        return true;
    }

    public function getHome(string $name, string $homeName): ?array {
        $data = $this->get($name);
        return $data["homes"][$homeName] ?? null;
    }

    public function getHomes(string $name): array {
        $data = $this->get($name);
        return $data["homes"] ?? [];
    }

    public function deleteHome(string $name, string $homeName): bool {
        $data = $this->get($name);
        if (!isset($data["homes"][$homeName])) return false;
        unset($data["homes"][$homeName]);
        $this->set($name, $data);
        $this->save($name);
        return true;
    }

    public function setLastLocation(string $name, array $location): void {
        $data = $this->get($name);
        $data["last_location"] = $location;
        $this->set($name, $data);
    }

    public function updatePlaytime(string $name): void {
        $data = $this->get($name);
        $joinTime = $data["join_time"] ?? time();
        $data["playtime"] = ($data["playtime"] ?? 0) + (time() - $joinTime);
        $data["join_time"] = time();
        $data["last_seen"] = date("Y-m-d H:i:s");
        $this->set($name, $data);
    }

    public function formatPlaytime(int $seconds): string {
        $d = floor($seconds / 86400);
        $h = floor(($seconds % 86400) / 3600);
        $m = floor(($seconds % 3600) / 60);
        if ($d > 0) return "{$d}d {$h}h {$m}m";
        if ($h > 0) return "{$h}h {$m}m";
        return "{$m}m";
    }

    public function getTopKills(int $limit = 10): array {
        $results = [];
        $dir = $this->plugin->getDataFolder() . "players/";
        foreach (scandir($dir) as $file) {
            if (str_ends_with($file, ".json")) {
                $d = json_decode(file_get_contents($dir . $file), true);
                if ($d) $results[] = ["name" => $d["name"] ?? "?", "kills" => $d["kills"] ?? 0];
            }
        }
        usort($results, fn($a, $b) => $b["kills"] <=> $a["kills"]);
        return array_slice($results, 0, $limit);
    }

    public function isOnline(string $name): bool {
        return isset($this->cache[strtolower($name)]);
    }
}
