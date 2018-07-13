<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Pool\{DataReader, BalancesChecker};
use App\Pool\Miners\Parser as MinersParser;
use App\Pool\Statistics\Stat as PoolStat;
use App\Miners\ReferenceHashrate;

use App\Miners\Miner;
use Carbon\Carbon;

class SaveMinerStats extends Command
{
	protected $signature = 'stats:miners';
	protected $description = 'Updates all status / info fields for each registered miner and inserts it\'s statistics.';

	protected $reader, $balances;

	public function __construct(DataReader $reader, BalancesChecker $balances)
	{
		$this->reader = $reader;
		$this->balances = $balances;
		parent::__construct();
	}

	public function handle()
	{
		$stat = PoolStat::orderBy('id', 'desc')->first();
		if (!$stat)
			return;

		$miners_parser = new MinersParser($this->reader->getFastDataJson());
		$reference = new ReferenceHashrate();
		$reference->compute($miners_parser, $stat);

		Miner::unguard();
		foreach (Miner::all() as $miner) {
			$balance = $miner->balance;

			if (!$miner->balance_updated_at || $miner->balance_updated_at <= Carbon::now()->subMinutes(5)) {
				$balance = $this->balances->getBalance($miner->address);

				if ($balance === null)
					$balance = $miner->balance; // don't update balance to zero if we are currently unable to obtain it
				else
					$balance = (float) $balance;

				$miner->balance_updated_at = Carbon::now();
			}

			if (($pool_miner = $miners_parser->getMiner($miner->address, $version_gt_024)) === null) {
				$miner->fill([
					'status' => 'offline',
					'ip_and_port' => null,
					'machines_count' => 0,
					'hashrate' => 0,
					'average_hashrate' => 0,
					'unpaid_shares' => 0,
					'balance' => $balance,
					'earned' => $miner->payouts()->sum('amount'),
				]);

				$miner->save();

				try {
					$miner->stats()->create([
						'unpaid_shares' => 0,
						'hashrate' => 0,
					]);
				} catch (\Illuminate\Database\QueryException $ex) {
					// the miner might have been deleted just now in web UI, silence the exception and continue with the loop
				}

				continue;
			}

			try {
				$miner_stat = $miner->stats()->create([
					'unpaid_shares' => $pool_miner->getUnpaidShares(),
					'hashrate' => 0,
				]);
			} catch (\Illuminate\Database\QueryException $ex) {
				// the miner might have been deleted just now in web UI, silence the exception and continue with the loop
				continue;
			}

			if ($version_gt_024) {
				$hashrate = $pool_miner->getStatus() === 'active' ? $miner->getAugmentedHashrate($pool_miner->getHashrate()) : $pool_miner->getHashrate();
			} else {
				$hashrate = $miner->getEstimatedHashrate($stat, $pool_miner->getStatus() === 'active');
			}

			$miner->fill([
				'status' => $pool_miner->getStatus(),
				'ip_and_port' => $pool_miner->getIpsAndNames(),
				'machines_count' => $pool_miner->getMachinesCount(),
				'hashrate' => $hashrate,
				'average_hashrate' => $pool_miner->getStatus() === 'active' ? $miner->getAverageHashrate($stat) : $hashrate,
				'unpaid_shares' => $pool_miner->getUnpaidShares(),
				'balance' => $balance,
				'earned' => $miner->payouts()->sum('amount'),
			]);

			$miner->save();

			$miner_stat->hashrate = $hashrate;
			$miner_stat->save();
		}

		$this->info('SaveMinerStats completed successfully.');
	}
}
