<?php

declare(strict_types=1);

namespace armorshard\pmholograms;

final readonly class HologramPage {
	public function __construct(public string $title, public string $text) {}
}
