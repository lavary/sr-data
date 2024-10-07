<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Satellite: string implements HasLabel
{
	case LANDSAT8 = 'landsat8';
	case LANDSAT9 = 'landsat9';
	case SENTINEL = 'sentinel';

	public function getLabel(): ?string
	{
		return match ($this) {
			self::LANDSAT8 => 'Landsat 8',
			self::LANDSAT9 => 'Landsat 9',
			self::SENTINEL => 'Sentinel',
		};
	}
}
