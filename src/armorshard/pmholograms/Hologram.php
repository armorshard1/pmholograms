<?php

declare(strict_types=1);

namespace armorshard\pmholograms;

use pocketmine\entity\Location;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;
use pocketmine\world\WorldManager;
use function array_values;
use function count;
use function in_array;

final class Hologram {
	/** @var list<HologramPage> */
	private array $pages;
	/**
	 * We need to spawn an entity for each page
	 * @var array<int, HologramEntity>
	 */
	private array $entities = [];
	private PageViewerMap $viewMap;
	private ?HologramChunkListener $chunkListener;

	/**
	 * @internal
	 * Do not use in plugins. Use Holograms::createHologram() instead
	 * @param list<HologramPage>   $pages
	 * @param array<string, mixed> $playerSet
	 */
	public function __construct(
		public readonly string $id,
		array $pages,
		private readonly WorldManager $worldManager,
		public readonly Vector3 $pos,
		public readonly string $worldName,
		public readonly HologramVisibility $visibility,
		public readonly array $playerSet,
	) {
		$this->pages = array_values($pages);
		$this->viewMap = new PageViewerMap();
		$world = $worldManager->getWorldByName($worldName);
		if ($world !== null) {
			$this->createAndSpawnEntities($world);

			$this->chunkListener = new HologramChunkListener($this->onChunkLoad(...), $this->onChunkUnload(...));
			$world->registerChunkListener($this->chunkListener, $pos->getFloorX() >> 4, $pos->getFloorZ() >> 4);
		} else {
			$this->entities = [];
			$this->chunkListener = null;
		}
	}

	public function addPage(HologramPage $page) : void {
		$this->setPage(count($this->pages), $page);
	}

	public function setPage(int $index, HologramPage $page) : void {
		if ($index < 0 || $index >= count($this->pages)) {
			throw new HologramsException("Page index `$index` is out of bounds. Must be between 0 and " . count($this->pages));
		}
		$this->pages[$index] = $page;
		if ($this->entities === []) {
			return;
		}
		if (isset($this->entities[$index])) {
			$this->entities[$index]->setNameTag(self::getNameTagText($page));
		} else {
			$world = $this->worldManager->getWorldByName($this->worldName);
			if ($world !== null) {
				$ent = new HologramEntity(Location::fromObject($this->pos, $world), $index, $this->visibility, $this->playerSet, $this->viewMap);
				$ent->setNameTag(self::getNameTagText($page));
				$ent->spawnToAll();
				$this->entities[$index] = $ent;
			}
		}
	}

	/**
	 * @return list<HologramPage>
	 */
	public function getPages() : array {
		return $this->pages;
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
		if ($this->entities !== []) {
			foreach ($this->entities as $ent) {
				$ent->flagForDespawn();
			}
			$this->entities = [];
		}
	}

	/**
	 * @internal
	 * Do not use in plugins
	 */
	public function onWorldLoad(World $world) : void {
		if ($world->getFolderName() === $this->worldName) {
			$this->createAndSpawnEntities($world);

			$this->chunkListener = new HologramChunkListener($this->onChunkLoad(...), $this->onChunkUnload(...));
			$world->registerChunkListener($this->chunkListener, $this->pos->getFloorX() >> 4, $this->pos->getFloorZ() >> 4);
		}
	}

	/**
	 * @internal
	 * Do not use in plugins
	 */
	public function onWorldUnload(World $world) : void {
		if ($world->getFolderName() === $this->worldName) {
			$this->close();
		}
	}

	/**
	 * @internal
	 * Do not use in plugins
	 */
	public function onDamage(HologramEntity $entity, Player $damager) : void {
		if (!in_array($entity, $this->entities, true)) {
			return;
		}
		$currentPage = $this->viewMap->get($damager->getName());
		if ($damager->isSneaking()) {
			//Prev
			$nextPage = ($currentPage - 1 + count($this->pages)) % count($this->pages);
		} else {
			//Next
			$nextPage = ($currentPage + 1) % count($this->pages);
		}
		if ($nextPage !== $currentPage) {
			$this->viewMap->set($damager->getName(), $nextPage);
			$this->entities[$currentPage]->despawnFrom($damager);
			$this->entities[$nextPage]->spawnTo($damager);
		}
	}

	private static function getNameTagText(HologramPage $page) : string {
		if ($page->title !== "") {
			if ($page->text !== "") {
				return $page->title . TextFormat::EOL . $page->text;
			} else {
				return $page->title;
			}
		} else {
			return $page->text;
		}
	}

	private function createAndSpawnEntities(World $world) : void {
		foreach ($this->pages as $idx => $page) {
			$ent = new HologramEntity(Location::fromObject($this->pos, $world), $idx, $this->visibility, $this->playerSet, $this->viewMap);
			$ent->setNameTag(self::getNameTagText($page));
			$ent->spawnToAll();
			$this->entities[$idx] = $ent;
		}
	}

	private function onChunkUnload(int $chunkX, int $chunkZ, Chunk $chunk) : void {
		$this->entities = [];
	}

	private function onChunkLoad(int $chunkX, int $chunkZ, Chunk $chunk) : void {
		if ($this->entities === []) {
			$world = $this->worldManager->getWorldByName($this->worldName);
			if ($world !== null) {
				$this->createAndSpawnEntities($world);
			}
		}
	}
}
