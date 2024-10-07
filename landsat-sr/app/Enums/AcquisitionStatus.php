<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AcquisitionStatus: string implements HasLabel
{
	case PENDING = 'pending';
	case READY = 'ready';

	public function getLabel(): ?string
	{
		return match ($this) {
			self::PENDING => 'Pending',
			self::READY => 'Ready',
		};
	}
}
