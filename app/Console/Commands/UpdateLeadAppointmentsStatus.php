<?php

namespace App\Console\Commands;

use App\Models\LeadAppointment;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateLeadAppointmentsStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update lead appointments status to no_show if training_date has passed and status was scheduled';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now()->startOfDay();

        LeadAppointment::whereDate('training_date', '<', $now)
            ->where('status', 'scheduled')
            ->update(['status' => 'no_show']);

        $this->info('Lead appointments statuses updated successfully.');
    }
}
