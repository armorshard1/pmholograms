<?php

declare(strict_types=1);

namespace armorshard\pmholograms;

use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;

/**
 * @internal
 */
final class HologramEntity extends Entity {
	/**
	 * @param array<string, mixed> $playerSet
	 */
	public function __construct(
		Location $location,
		private readonly int $page,
		private HologramVisibility $visibility,
		private array $playerSet,
		private readonly PageViewerMap $viewMap
	) {
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
	}

	public function spawnTo(Player $player) : void {
		$name = $player->getName();
		if ($this->visibility === HologramVisibility::BlockList && !isset($this->playerSet[$name]) && $this->viewMap->get($name) === $this->page) {
			parent::spawnTo($player);
		} elseif ($this->visibility === HologramVisibility::AllowList && isset($this->playerSet[$name]) && $this->viewMap->get($name) === $this->page) {
			parent::spawnTo($player);
		}
	}

	protected function getInitialSizeInfo() : EntitySizeInfo {
		return new EntitySizeInfo(0.5, 0.5);
	}

	protected function getInitialDragMultiplier() : float {
		return 0.5;
	}

	protected function getInitialGravity() : float {
		return 0.0;
	}

	public static function getNetworkTypeId() : string {
		return EntityIds::FALLING_BLOCK;
	}

	public function isFireProof() : bool {
		return true;
	}

	public function canBeMovedByCurrents() : bool {
		return false;
	}

	protected function syncNetworkData(EntityMetadataCollection $properties) : void{
		parent::syncNetworkData($properties);

		$properties->setInt(EntityMetadataProperties::VARIANT, TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId(VanillaBlocks::AIR()->getStateId()));
	}

	public function attack(EntityDamageEvent $source) : void {
		$source->call();
		if($source->isCancelled()){
			return;
		}

		$this->setLastDamageCause($source);
	}
}
