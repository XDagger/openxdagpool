<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands = [
		Commands\DownloadLiveData::class,
		Commands\DownloadFastData::class,
		Commands\CreateDataSnapshot::class,
		Commands\PoolCron::class,
		Commands\ImportFoundBlocks::class,
		Commands\SaveMinerStats::class,
		Commands\SavePoolStats::class,
		Commands\SendMinerAlerts::class,
		Commands\SendAdminAlerts::class,
		Commands\RemoveInactiveMinersHistory::class,
	];

	/**
	 * Define the application's command schedule.
	 *
	 * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule)
	{
		$schedule->command('data:live')->everyMinute()->withoutOverlapping();
		$schedule->command('data:fast')->everyFiveMinutes()->withoutOverlapping();
		$schedule->command('pool:cron')->everyFiveMinutes()->withoutOverlapping();
		$schedule->command('blocks:import')->everyFiveMinutes()->withoutOverlapping();
	}

	/**
	 * Register the commands for the application.
	 *
	 * @return void
	 */
	protected function commands()
	{
		$this->load(__DIR__.'/Commands');

		require base_path('routes/console.php');
	}
}
