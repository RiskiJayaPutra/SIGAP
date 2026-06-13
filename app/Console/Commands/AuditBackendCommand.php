<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:audit-backend-command')]
#[Description('Command description')]
class AuditBackendCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
