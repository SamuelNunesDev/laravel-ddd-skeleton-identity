<?php

declare(strict_types=1);

namespace App\Modules\Organization\Domain\ValueObjects;

enum OrganizationContextSource: string
{
    case Session = 'session';
    case AccessToken = 'access_token';
    case ClientCredentials = 'client_credentials';
}
