<?php

declare(strict_types=1);

namespace App\Modules\Installation\Infrastructure\Console;

use App\Modules\Installation\Application\DTO\RecoverOwnerData;
use App\Modules\Installation\Application\Ports\In\RecoverInstallationOwner;
use App\Shared\Application\Contracts\UuidGenerator;
use App\Shared\Domain\ValueObjects\CorrelationContext;
use App\Shared\Domain\ValueObjects\RequestId;
use App\Shared\Domain\ValueObjects\TraceId;
use Illuminate\Console\Command;

final class RecoverInstallationOwnerCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'installation:recover-owner
        {--confirm-owner-recovery : Explicitly confirm the sensitive recovery procedure}';

    /**
     * @var string
     */
    protected $description = 'Reset the installation owner to a temporary password through an audited server procedure';

    public function handle(RecoverInstallationOwner $recovery, UuidGenerator $uuidGenerator): int
    {
        if (! $this->option('confirm-owner-recovery')) {
            $this->error('Pass --confirm-owner-recovery after reviewing the recovery procedure.');

            return self::FAILURE;
        }

        if (! stream_isatty(STDIN)) {
            $this->error('Owner recovery requires a real interactive terminal.');

            return self::FAILURE;
        }

        $password = $this->secret('New temporary password');
        $confirmation = $this->secret('Confirm new temporary password');

        if (! is_string($password) || $password === '' || ! hash_equals($password, (string) $confirmation)) {
            $this->error('Temporary password confirmation did not match.');

            return self::FAILURE;
        }

        $recovery->recover(new RecoverOwnerData(
            temporaryPassword: $password,
            correlation: new CorrelationContext(
                requestId: new RequestId($uuidGenerator->generate()),
                traceId: TraceId::generate(),
            ),
        ));

        $this->info('The owner credential was reset and the operation was audited.');

        return self::SUCCESS;
    }
}
