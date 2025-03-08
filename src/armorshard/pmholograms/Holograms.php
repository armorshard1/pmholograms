<?php

declare(strict_types=1);

namespace armorshard\pmholograms;

use InvalidArgumentException;
use pocketmine\player\Player;
use pocketmine\world\Position;
use function array_search;
use function is_string;

final class Holograms {
	/** @var array<string, Hologram> */
	private static array $holograms = [];

	/**
	 * @param array<string|Player> $playerList
	 */
	public static function createHologram(string $id, string $title, string $text, Position $pos, HologramVisibility $visibility, array $playerList) : Hologram {
		$playerSet = [];
		foreach ($playerList as $p) {
			$isPlayer = $p instanceof Player;
			if (!is_string($p) && !$isPlayer) {
				throw new InvalidArgumentException();
			}
			$playerSet[$isPlayer ? $p->getName() : $p] = true;
		}
		if (isset(self::$holograms[$id])) {
			throw new HologramsException("Hologram with id `$id` already exists");
		}
		$h = new Hologram($id, $title, $text, $pos, $visibility, $playerSet);
		self::$holograms[$id] = $h;
		return $h;
	}

	public static function getHologramById(string $id) : ?Hologram {
		return self::$holograms[$id] ?? null;
	}

	public static function deleteHologram(Hologram $hologram) : void {
		$hologram->close();
		$k = array_search($hologram, self::$holograms, true);
		if ($k !== false) {
			unset(self::$holograms[$k]);
		}
	}

	public static function deleteHologramById(string $id) : void {
		$h = self::getHologramById($id);
		if ($h !== null) {
			$h->close();
			unset(self::$holograms[$id]);
		}
	}
}
