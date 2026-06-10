<?php
declare(strict_types=1);

namespace AnarchyCore\npc;

use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

class NPCEntity extends Human {

    private string $npcType;
    private string $npcName;

    public function __construct(Location $location, CompoundTag $nbt, string $npcType, string $npcName) {
        parent::__construct($location, \pocketmine\entity\Skin::fromLegacy(str_repeat("\x00", 64 * 32 * 4)), $nbt);
        $this->npcType = $npcType;
        $this->npcName = $npcName;
        $this->setNameTag($npcName);
        $this->setNameTagAlwaysVisible(true);
        $this->setNameTagVisible(true);
        $this->setNoAi(true);
        $this->setScale(1.0);
    }

    public function getNPCType(): string {
        return $this->npcType;
    }

    public function getNPCName(): string {
        return $this->npcName;
    }

    public function attack(EntityDamageEvent $source): void {
        $source->cancel();
    }

    public function onUpdate(int $currentTick): bool {
        return parent::onUpdate($currentTick);
    }

    public function saveNBT(): CompoundTag {
        $nbt = parent::saveNBT();
        $nbt->setString("npc_type", $this->npcType);
        $nbt->setString("npc_name", $this->npcName);
        return $nbt;
    }
}
