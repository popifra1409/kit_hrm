<?php

namespace App\Console\Commands;

use App\Models\CensusCampaign;
use Illuminate\Console\Command;

class CloseExpiredCensusCampaigns extends Command
{
    protected $signature = 'census:close-expired';

    protected $description = 'Passe automatiquement en statut "closed" les campagnes de recensement dont la date de fin est dépassée.';

    public function handle(): int
    {
        $campaigns = CensusCampaign::where('status', 'open')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->get();

        foreach ($campaigns as $campaign) {
            $campaign->update(['status' => 'closed']);
            $this->info("Campagne « {$campaign->name} » clôturée automatiquement (fin prévue le {$campaign->ends_at->format('d/m/Y H:i')}).");
        }

        if ($campaigns->isEmpty()) {
            $this->info('Aucune campagne à clôturer.');
        }

        return self::SUCCESS;
    }
}
