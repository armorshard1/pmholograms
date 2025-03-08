<?php

declare(strict_types=1);

namespace armorshard\pmholograms;

use pocketmine\entity\Location;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;
use pocketmine\world\WorldManager;

final class Hologram {
	private ?HologramEntity $entity;
	private ?HologramChunkListener $chunkListener;

	/**
	 * @internal
	 * Do not use in plugins. Use Holograms::createHologram() instead
	 * @param array<string, mixed> $playerSet
	 */
	public function __construct(
		public readonly string $id,
		private string $title,
		private string $text,
		private readonly WorldManager $worldManager,
		public readonly Vector3 $pos,
		public readonly string $worldName,
		public readonly HologramVisibility $visibility,
		public readonly array $playerSet,
	) {
		$world = $worldManager->getWorldByName($worldName);
		if ($world !== null) {
			$this->entity = $this->createEntity($world);
			$this->entity->spawnToAll();

			$this->chunkListener = new HologramChunkListener($this->onChunkLoad(...), $this->onChunkUnload(...));
			$world->registerChunkListener($this->chunkListener, $pos->getFloorX() >> 4, $pos->getFloorZ() >> 4);
		} else {
			$this->entity = null;
			$this->chunkListener = null;
		}
	}

	public function getTitle() : string {
		return $this->title;
	}

	public function setTitle(string $title) : void {
		$this->setTitleAndText($title, $this->text);
	}

	public function getText() : string {
		return $this->text;
	}

	public function setText(string $text) : void {
		$this->setTitleAndText($this->title, $text);
	}

	public function setTitleAndText(string $title, string $text) : void {
		$this->title = $title;
		$this->text = $text;
		if ($this->entity !== null) {
			$this->entity->setNameTag($this->getNameTagText());
		}
	}

	/**
	 * @internal
	 * Do not use in plugins. Use Holograms::deleteHologram() or Holograms::deleteHologramById() instead
	 */
	public function close() : void {
		if ($this->chunkListener !== null) {
			$world = $this->worldManager->getWorldByName($this->worldName);
			if ($world !== null) {
				$world->unregisterChunkListener($this->chunkListener, $this->pos->getFloorX() >> 4, $this->pos->getFloorZ() >> 4);
			}
			$this->chunkListener = null;
		}
		if ($this->entity !== null) {
			$this->entity->flagForDespawn();
			$this->entity = null;
		}
	}

	/**
	 * @internal
	 * Do not use in plugins
	 */
	public function onWorldLoad(WorldLoadEvent $event) : void {
		$world = $event->getWorld();
		if ($world->getFolderName() === $this->worldName) {
			$this->entity = $this->createEntity($world);
			$this->entity->spawnToAll();

			$this->chunkListener = new HologramChunkListener($this->onChunkLoad(...), $this->onChunkUnload(...));
			$world->registerChunkListener($this->chunkListener, $this->pos->getFloorX() >> 4, $this->pos->getFloorZ() >> 4);
		}
	}

	/**
	 * @internal
	 * Do not use in plugins
	 */
	public function onWorldUnload(WorldUnloadEvent $event) : void {
		$world = $event->getWorld();
		if ($world->getFolderName() === $this->worldName) {
			$this->close();
		}
	}

	private function getNameTagText() : string {
		if ($this->title !== "") {
			if ($this->text !== "") {
				return $this->title . TextFormat::EOL . $this->text;
			} else {
				return $this->title;
			}
		} else {
			return $this->text;
		}
	}

	private function createEntity(World $world) : HologramEntity {
		$ent = new HologramEntity(Location::fromObject($this->pos, $world), $this->visibility, $this->playerSet);
		$ent->setNameTag($this->getNameTagText());
		return $ent;
	}

	private function onChunkUnload(int $chunkX, int $chunkZ, Chunk $chunk) : void {
		$this->entity = null;
	}

	private function onChunkLoad(int $chunkX, int $chunkZ, Chunk $chunk) : void {
		if ($this->entity === null) {
			$world = $this->worldManager->getWorldByName($this->worldName);
			if ($world !== null) {
				$this->entity = $this->createEntity($world);
				$this->entity->spawnToAll();
			}
		}
	}
}
