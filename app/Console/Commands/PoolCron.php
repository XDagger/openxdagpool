<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PoolCron extends Command
{
	protected $signature = 'pool:cron';
	protected $description = 'Run required cron jobs in succession.';

	public function handle()
	{
		if (!env('APP_DEBUG'))
			sleep(40);

		$this->call('data:snapshot');
		$this->call('stats:pool');
		$this->call('stats:miners');
		$this->call('alerts:miners');
		$this->call('alerts:pool');
		$this->call('miners:remove-inactive-history');
		$this->info('PoolCron completed successfully.');
	}
}
