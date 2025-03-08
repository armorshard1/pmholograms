<?php

declare(strict_types=1);

namespace armorshard\pmholograms;

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;

/**
 * @internal
 */
class HologramEntity extends Entity {
	/**
	 * @param array<string, mixed> $playerSet
	 */
	public function __construct(Location $location, private HologramVisibility $visibility, private array $playerSet) {
		parent::__construct($location);
	}

	protected function initEntity(CompoundTag $nbt) : void {
		parent::initEntity($nbt);
		$this->setHasGravity(false);
		$this->setCanClimb(false);
		$this->setCanSaveWithChunk(false);
		$this->setNoClientPredictions(true);
		$this->setNameTagVisible(true);
		$this->setNameTagAlwaysVisible(true);
		$this->setScale(0.01);
	}

	public function spawnTo(Player $player) : void {
		if ($this->visibility === HologramVisibility::BlockList && !isset($this->playerSet[$player->getName()])) {
			parent::spawnTo($player);
		} elseif ($this->visibility === HologramVisibility::AllowList && isset($this->playerList[$player->getName()])) {
			parent::spawnTo($player);
		}
	}

	protected function getInitialSizeInfo() : EntitySizeInfo {
		return new EntitySizeInfo(0.01, 0.01);
	}

	protected function getInitialDragMultiplier() : float {
		return 0.5;
	}

	protected function getInitialGravity() : float {
		return 0.0;
	}

	public static function getNetworkTypeId() : string {
		return EntityIds::PLAYER;
	}

	public function isFireProof() : bool {
		return true;
	}

	public function canBeMovedByCurrents() : bool {
		return false;
	}

	public function attack(EntityDamageEvent $source) : void {}
}
