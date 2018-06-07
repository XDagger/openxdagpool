<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Pool\Formatter;
use App\Pool\Statistics\Stat as PoolStat;
use Auth;

class HashrateController extends Controller
{
	protected $format;

	public function __construct(Formatter $format)
	{
		$this->middleware('auth');
		$this->middleware('active');

		$this->format = $format;
	}

	public function minerGraph($uuid, $type)
	{
		if (($miner = Auth::user()->miners()->where('uuid', $uuid)->first()) === null)
			return redirect()->back()->with('error', 'Miner not found.');

		if (!in_array($type, ['latest', 'daily']))
			$type = 'latest';

		return view('user.hashrate.miner-graph', [
			'miner' => $miner,
			'current_hashrate' => $this->format->hashrate($miner->hashrate),
			'average_hashrate' => $this->format->hashrate($miner->average_hashrate),
			'graph_data' => $this->getGraphData($miner, $type),
			'type' => $type,
			'activeTab' => 'miners',
		]);
	}

	public function userGraph($type)
	{
		$user = Auth::user();

		return view('user.hashrate.user-graph', [
			'current_hashrate' => $this->format->hashrate($user->getHashrateSum()),
			'average_hashrate' => $this->format->hashrate($user->getAverageHashrateSum()),
			'graph_data' => $this->getGraphData($user, $type),
			'type' => $type,
			'activeTab' => 'hashrate',
		]);
	}

	protected function getGraphData($model, $type)
	{
		$graph = ['x' => [], 'Hashrate (Mh/s)' => []];

		if ($type == 'daily')
			$stats = $model->getDailyHashrate();
		else
			$stats = $model->getLatestHashrate();

		$last_date = null;

		foreach ($stats as $stat) {
			$date = is_array($stat) ? $stat['date'] : $stat->date;
			$hashrate = (is_array($stat) ? $stat['hashrate'] : $stat->hashrate);

			$current_date = Carbon::parse($date);

			if ($last_date) {
				while ($last_date->addDays(1) < $date) {
					$graph['x'][] = $last_date->format($type == 'daily' ? 'Y-m-d' : 'Y-m-d H:00');
					$graph['Hashrate (Mh/s)'][] = 0;
				}
			}

			$last_date = Carbon::parse($date);

			$graph['x'][] = $date;
			$graph['Hashrate (Mh/s)'][] = $hashrate / 1024 / 1024;
		}

		return json_encode($graph);
	}
}
