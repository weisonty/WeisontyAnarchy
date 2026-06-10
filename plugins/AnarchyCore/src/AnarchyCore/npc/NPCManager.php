<?php
declare(strict_types=1);

namespace AnarchyCore\npc;

use AnarchyCore\Main;
use AnarchyCore\ui\MenuManager;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEntityEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\world\Position;

class NPCManager implements Listener {

    private Main $plugin;
    /** @var array<string, NPCEntity> */
    private array $npcs = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);

        // Delay NPC spawning until worlds are loaded
        $plugin->getScheduler()->scheduleDelayedTask(new class($this) extends \pocketmine\scheduler\Task {
            private NPCManager $manager;
            public function __construct(NPCManager $m) { $this->manager = $m; }
            public function onRun(): void { $this->manager->spawnAllNPCs(); }
        }, 40);
    }

    public function spawnAllNPCs(): void {
        if (!$this->plugin->getConfig()->getNested("npc.enabled", true)) return;

        $npcs = $this->plugin->getConfig()->getNested("npc.npcs", []);
        foreach ($npcs as $id => $cfg) {
            $this->spawnNPC($id, $cfg);
        }
    }

    private function spawnNPC(string $id, array $cfg): void {
        $worldName = $cfg["world"] ?? "world";
        $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($worldName);
        if ($world === null) {
            $this->plugin->getLogger()->warning("NPC world '$worldName' not loaded for NPC '$id'");
            return;
        }

        $pos = new Location(
            (float)($cfg["x"] ?? 0),
            (float)($cfg["y"] ?? 65),
            (float)($cfg["z"] ?? 0),
            $world,
            0.0,
            0.0
        );

        $nbt = \pocketmine\entity\EntityDataHelper::createBaseNBT($pos);
        $entity = new NPCEntity($pos, $nbt, $cfg["type"] ?? "HELP", $cfg["name"] ?? "§eNPC");
        $entity->setNameTagAlwaysVisible(true);
        $entity->setNameTagVisible(true);
        $entity->spawnToAll();

        $this->npcs[$id] = $entity;
        $this->plugin->getLogger()->info("Spawned NPC: $id ({$cfg["type"]}) at {$cfg["x"]}, {$cfg["y"]}, {$cfg["z"]}");
    }

    public function onPlayerInteractEntity(PlayerInteractEntityEvent $event): void {
        $entity = $event->getEntity();
        $player = $event->getPlayer();
        if ($entity instanceof NPCEntity) {
            $event->cancel();
            MenuManager::sendNPCMenu($player, $entity->getNPCType(), $this->plugin);
        }
    }
}
