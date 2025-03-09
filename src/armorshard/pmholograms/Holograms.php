<?php

declare(strict_types=1);

namespace armorshard\pmholograms;

use InvalidArgumentException;
use LogicException;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\EventPriority;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use function array_search;
use function is_string;

final class Holograms {
	/** @var array<string, Hologram> */
	private static array $holograms = [];
	private static ?PluginBase $plugin = null;

	public static function init(PluginBase $plugin) : void {
		if (self::$plugin !== null) {
			throw new LogicException("Virion was already initialized");
		}
		self::$plugin = $plugin;
		$plugin->getServer()->getPluginManager()->registerEvent(WorldLoadEvent::class, self::onWorldLoad(...), EventPriority::MONITOR, $plugin);
		$plugin->getServer()->getPluginManager()->registerEvent(WorldUnloadEvent::class, self::onWorldUnload(...), EventPriority::MONITOR, $plugin);
		$plugin->getServer()->getPluginManager()->registerEvent(EntityDamageEvent::class, self::onDamage(...), EventPriority::MONITOR, $plugin, true);
	}

	/**
	 * Create and spawn a hologram
	 * @param list<HologramPage>   $pages
	 * @param array<string|Player> $playerList
	 */
	public static function createHologram(string $id, array $pages, Vector3 $pos, string $worldName, HologramVisibility $visibility, array $playerList) : Hologram {
		if (self::$plugin === null) {
			throw new LogicException("Cannot call creatHologram before calling init");
		}
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
		$h = new Hologram($id, $pages, self::$plugin->getServer()->getWorldManager(), $pos, $worldName, $visibility, $playerSet);
		self::$holograms[$id] = $h;
		return $h;
	}

	public static function getHologramById(string $id) : ?Hologram {
		return self::$holograms[$id] ?? null;
	}

	/**
	 * Destroy the given hologram
	 */
	public static function deleteHologram(Hologram $hologram) : void {
		$hologram->close();
		$k = array_search($hologram, self::$holograms, true);
		if ($k !== false) {
			unset(self::$holograms[$k]);
		}
	}

	/**
	 * Destroy the hologram with the given id, if any
	 */
	public static function deleteHologramById(string $id) : void {
		$h = self::getHologramById($id);
		if ($h !== null) {
			$h->close();
			unset(self::$holograms[$id]);
		}
	}

	private static function onWorldLoad(WorldLoadEvent $event) : void {
		foreach (self::$holograms as $hologram) {
			$hologram->onWorldLoad($event->getWorld());
		}
	}

	private static function onWorldUnload(WorldUnloadEvent $event) : void {
		foreach (self::$holograms as $hologram) {
			$hologram->onWorldUnload($event->getWorld());
		}
	}

	private static function onDamage(EntityDamageEvent $event) : void {
		if (!($event instanceof EntityDamageByEntityEvent)) {
			return;
		}
		$entity = $event->getEntity();
		if (!($entity instanceof HologramEntity)) {
			return;
		}
		$damager = $event->getDamager();
		if (!($damager instanceof Player)) {
			return;
		}
		foreach (self::$holograms as $hologram) {
			$hologram->onDamage($entity, $damager);
		}
	}

	private function __construct() {
		throw new LogicException("Cannot construct Holograms class");
	}
}
