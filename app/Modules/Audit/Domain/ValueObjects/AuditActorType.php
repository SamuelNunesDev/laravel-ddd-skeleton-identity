<?php

declare(strict_types=1);

namespace App\Modules\Audit\Domain\ValueObjects;

enum AuditActorType: string
{
    case Identity = 'identity';
    case OAuthClient = 'oauth_client';
    case System = 'system';
}
