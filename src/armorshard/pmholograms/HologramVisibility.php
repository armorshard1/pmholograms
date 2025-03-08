<?php

declare(strict_types=1);

namespace armorshard\pmholograms;

enum HologramVisibility {
	/**
	 * Only players in the player list can see the hologram
	 */
	case AllowList;

	/**
	 * Only players *not* in the player list can see the hologram
	 */
	case BlockList;
}
