<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class RefreshSubscriptionStatuses extends Command
{
    protected $signature = 'subscriptions:refresh-status';

    protected $description = 'Met à jour les statuts des abonnements (pending -> active, active -> expired) et des adhérents selon la date du jour';

    public function handle(SubscriptionService $subscriptions): int
    {
        $result = $subscriptions->refreshStatuses();

        $this->info(sprintf(
            'Abonnements activés : %d, expirés : %d, adhérents mis à jour : %d.',
            $result['activated'],
            $result['expired'],
            $result['membersUpdated']
        ));

        return self::SUCCESS;
    }
}
