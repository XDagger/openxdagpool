<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Setting, Carbon\Carbon;
use App\Jobs\SendUserMessage;
use App\Miners\ReferenceHashrate;
use App\Users\User;

use App\Pool\DataReader;
use App\Pool\State\Parser as StateParser;
use App\Pool\Statistics\Parser as StatisticsParser;
use App\Pool\Miners\Parser as MinersParser;

class SendAdminAlerts extends Command
{
	protected $signature = 'alerts:pool';
	protected $description = 'Send zero hashrate, abnormal state and reference miner offline admin notifications.';

	protected $reader, $users;

	public function __construct(DataReader $reader)
	{
		$this->reader = $reader;
		parent::__construct();
	}

	public function handle()
	{
		$this->users = User::where('active', true)->where('administrator', true)->get();

		if (!count($this->users)) {
			$this->line('No active admin users, not sending admin alerts.');
			$this->info('SendAdminAlerts completed successfully.');
			return;
		}

		// zero pool hashrate notification
		$stats_parser = new StatisticsParser($this->reader->getLiveDataJson());

		if (!$this->isNotificationCooldown('zero_hashrate') && ((float) $stats_parser->getPoolHashrate()) == 0) {
			$this->sendNotification('zero_hashrate', 'Zero pool hashrate - pool down?', 'there is zero hashrate on our pool, either no one is mining at our pool or the pool crashed. Please check pool\'s status.');
			$this->info('SendAdminAlerts completed successfully.');
			return; // do not send any other notifications when pool is down
		} else if ($this->isNotificationCooldown('zero_hashrate') && ((float) $stats_parser->getPoolHashrate()) > 0) {
			$this->sendNotification('zero_hashrate', 'Pool hashrate back up', 'great news! Pool hashrate is again greater than zero.');
			$this->cancelNotificationCooldown('zero_hashrate');
		}

		// abnormal pool daemon state notification
		$state_parser = new StateParser($this->reader->getLiveDataJson());

		if (!$this->isNotificationCooldown('pool_state') && !$state_parser->isNormalPoolState()) {
			$this->sendNotification('pool_state', 'Abnormal pool daemon state', 'pool daemon is currently in state "' . $state_parser->getPoolState() . '". Outside normal operation, some OpenXDAGPool services might not work correctly. Please check the pool daemon.');
		} else if ($this->isNotificationCooldown('pool_state') && $state_parser->isNormalPoolState()) {
			$this->sendNotification('pool_state', 'Pool daemon state switched back to normal', 'pool daemon state is currently normal. Full operation of OpenXDAGPool is restored.');
			$this->cancelNotificationCooldown('pool_state');
		}

		// reference miner offline notification
		$reference = new ReferenceHashrate();

		if ($reference->shouldBeUsed()) {
			$miners_parser = new MinersParser($this->reader->getFastDataJson());
			$pool_miner = $miners_parser->getMiner($miner_address = Setting::get('reference_miner_address'));
			$is_offline = !$pool_miner || $pool_miner->getStatus() !== 'active';

			if (!$this->isNotificationCooldown('reference_miner_offline') && $is_offline) {
				$this->sendNotification('reference_miner_offline', 'Reference miner offline', 'pool reference miner "' . $miner_address . '" is offline, please check it\'s status.');
			} else if ($this->isNotificationCooldown('reference_miner_offline') && !$is_offline) {
				$this->sendNotification('reference_miner_offline', 'Reference miner back online', 'pool reference miner "' . $miner_address . '" is back online.');
				$this->cancelNotificationCooldown('reference_miner_offline');
			}
		} else {
			$this->cancelNotificationCooldown('reference_miner_offline');
		}

		$this->info('SendAdminAlerts completed successfully.');
	}

	protected function isNotificationCooldown($name)
	{
		$last_sent_at = Setting::get("alert_{$name}_sent_at");

		if (!$last_sent_at)
			return false;

		$last_sent_at = Carbon::createFromFormat('Y-m-d H:i:s', $last_sent_at);

		return $last_sent_at->addDays(3) > Carbon::now();
	}

	protected function cancelNotificationCooldown($name)
	{
		Setting::forget("alert_{$name}_sent_at");
		return Setting::save();
	}

	protected function sendNotification($name, $subject, $message)
	{
		foreach ($this->users as $user) {
			$this->line("Sending '$subject' notification to admin email '{$user->email}'...");
			SendUserMessage::dispatch($user->id, $subject, $message);
		}

		$last_sent_at = Carbon::now();
		Setting::set("alert_{$name}_sent_at", $last_sent_at->toDateTimeString());

		return Setting::save();
	}
}
