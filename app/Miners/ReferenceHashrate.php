<?php

namespace App\Miners;

use Setting;
use App\Pool\Statistics\Stat as PoolStat;
use App\Pool\Miners\Parser as MinersParser;

class ReferenceHashrate
{
	protected $miner_address, $target_hashrate;

	public function __construct()
	{
		$this->miner_address = Setting::get('reference_miner_address');
		$this->target_hashrate = Setting::get('reference_miner_hashrate') * 1073741824; // convert Gh/s to h/s
	}

	public function compute(MinersParser $miners_parser, PoolStat $when)
	{
		if (!$this->shouldBeUsed())
			return $this->resetCoefficient();

		$pool_miner = $miners_parser->getMiner($this->miner_address, $version_gt_024);
		if (!$pool_miner || $pool_miner->getStatus() !== 'active')
			return $this->resetCoefficient();

		$miner = Miner::where('address', $this->miner_address)->first();

		if (!$version_gt_024)
			$miner_hashrate = $miner->getEstimatedHashrate($when, false);
		else
			$miner_hashrate = $pool_miner->getHashrate();

		if ($miner_hashrate <= 0)
			return $this->resetCoefficient();

		Setting::set('reference_miner_coefficient', $this->target_hashrate / $miner_hashrate);
		Setting::save();
	}

	public function getCoefficient()
	{
		return Setting::get('reference_miner_coefficient', 1);
	}

	public function resetCoefficient()
	{
		Setting::set('reference_miner_coefficient', 1);
		Setting::save();
	}

	public function shouldBeUsed()
	{
		return $this->miner_address && $this->target_hashrate > 0 && Miner::where('address', $this->miner_address)->exists();
	}
}
