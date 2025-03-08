<?php

declare(strict_types=1);

namespace armorshard\pmholograms;

use Closure;
use pocketmine\math\Vector3;
use pocketmine\world\ChunkListener;
use pocketmine\world\format\Chunk;

/**
 * @internal
 */
class HologramChunkListener implements ChunkListener {
	/**
	 * @param Closure(int, int, Chunk) : void $onChunkLoadedCallback
	 * @param Closure(int, int, Chunk) : void $onChunkUnoadedCallback
	 */
	public function __construct(private readonly Closure $onChunkLoadedCallback, private readonly Closure $onChunkUnoadedCallback) {}

	public function onChunkLoaded(int $chunkX, int $chunkZ, Chunk $chunk) : void {
		($this->onChunkLoadedCallback)($chunkX, $chunkZ, $chunk);
	}

	public function onChunkUnloaded(int $chunkX, int $chunkZ, Chunk $chunk) : void {
		($this->onChunkUnoadedCallback)($chunkX, $chunkZ, $chunk);
	}

	public function onChunkChanged(int $chunkX, int $chunkZ, Chunk $chunk) : void {}

	public function onChunkPopulated(int $chunkX, int $chunkZ, Chunk $chunk) : void {}

	public function onBlockChanged(Vector3 $block) : void {}
}
