<?php

declare(strict_types=1);

namespace armorshard\pmholograms;

use pocketmine\entity\Location;
use pocketmine\utils\TextFormat;
use pocketmine\world\format\Chunk;
use pocketmine\world\Position;

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
		public readonly Position $pos,
		public readonly HologramVisibility $visibility,
		public readonly array $playerSet,
	) {
		$this->entity = $this->createEntity();
		$this->entity->spawnToAll();

		$this->chunkListener = new HologramChunkListener($this->onChunkLoad(...), $this->onChunkUnload(...));
		$pos->getWorld()->registerChunkListener($this->chunkListener, $pos->getFloorX() >> 4, $pos->getFloorZ() >> 4);
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
			$this->pos->getWorld()->unregisterChunkListener($this->chunkListener, $this->pos->getFloorX() >> 4, $this->pos->getFloorZ() >> 4);
			$this->chunkListener = null;
		}
		if ($this->entity !== null) {
			$this->entity->flagForDespawn();
			$this->entity = null;
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

	private function createEntity() : HologramEntity {
		$ent = new HologramEntity(Location::fromObject($this->pos, $this->pos->getWorld()), $this->visibility, $this->playerSet);
		$ent->setNameTag($this->getNameTagText());
		return $ent;
	}

	private function onChunkUnload(int $chunkX, int $chunkZ, Chunk $chunk) : void {
		$this->entity = null;
	}

	private function onChunkLoad(int $chunkX, int $chunkZ, Chunk $chunk) : void {
		if ($this->entity === null) {
			$this->entity = $this->createEntity();
			$this->entity->spawnToAll();
		}
	}
}
