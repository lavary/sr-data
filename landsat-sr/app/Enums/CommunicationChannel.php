<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CommunicationChannel: string implements HasLabel
{
	case EMAIL = 'email';
	case SLACK = 'slack';

	public function getLabel(): ?string
	{
		return match ($this) {
			self::EMAIL => 'Email',
			self::SLACK => 'Slack',
		};
	}
}
