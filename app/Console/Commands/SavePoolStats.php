<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Pool\DataReader;
use App\Pool\Miners\Parser as MinersParser;
use App\Pool\Statistics\{Parser as StatisticsParser, Stat};
use App\Miners\Miner;

class SavePoolStats extends Command
{
	protected $signature = 'stats:pool';
	protected $description = 'Inserts latest pool stats.';

	protected $reader;

	public function __construct(DataReader $reader)
	{
		$this->reader = $reader;
		parent::__construct();
	}

	public function handle()
	{
		$stats = new StatisticsParser($this->reader->getLiveDataJson());
		$miners = new MinersParser($this->reader->getFastDataJson());

		$stat = new Stat([
			'pool_hashrate' => $stats->getPoolHashrate(),
			'total_unpaid_shares' => (float) $miners->getTotalUnpaidShares(),
			'network_hashrate' => $stats->getNetworkHashrate(),
			'active_miners' => $miners->getNumberOfActiveMiners(),
		]);

		$stat->save();

		$this->info('SavePoolStats completed successfully.');
	}
}
