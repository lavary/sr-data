<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Band: string implements HasLabel
{
	case RGB = 'rgb';
	case NDVI = 'ndvi';

	public function getLabel(): ?string
	{
		return match ($this) {
			self::RGB => 'True color',
			self::NDVI => 'NDVI',
		};
	}
}
