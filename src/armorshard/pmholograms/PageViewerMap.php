<?php

declare(strict_types=1);

namespace armorshard\pmholograms;

/**
 * @internal
 */
final class PageViewerMap {
	/** @var array<string, int> */
	private array $viewers = [];

	public function __construct() {}

	public function get(string $name) : int {
		return $this->viewers[$name] ?? 0;
	}

	public function set(string $name, int $page) : void {
		$this->viewers[$name] = $page;
	}
}
