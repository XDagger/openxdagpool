<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Pool\{DataReader, Uptime, Formatter};
use App\Pool\Config\{Parser as ConfigParser, Presenter as ConfigPresenter};
use App\Pool\Statistics\{Parser as StatisticsParser, Presenter as StatisticsPresenter, Stat as PoolStat};
use App\Pool\State\Parser as StateParser;

use App\Users\Leaderboard;
use App\Miners\Miner;
use App\FoundBlocks\FoundBlock;

use Carbon\Carbon;
use Auth;

class StatsController extends Controller
{
	protected $reader, $uptime, $format;

	public function __construct(DataReader $reader, Uptime $uptime, Formatter $format)
	{
		$this->reader = $reader;
		$this->uptime = $uptime;
		$this->format = $format;
	}

	public function index(Leaderboard $leaderboard)
	{
		$stats_presenter = new StatisticsPresenter($stats_parser = new StatisticsParser($this->reader->getLiveDataJson()));
		$config_presenter = new ConfigPresenter($config_parser = new ConfigParser($this->reader->getLiveDataJson()));
		$pool_hashrate = (float) $stats_parser->getPoolHashrate();
		$user_stats = [];

		if ($user = Auth::user()) {
			$user_hashrate_exact = $user->miners->sum('average_hashrate');

			$user_stats = [
				'user_hashrate' => $this->format->hashrate($user_hashrate_exact),
				'user_miners' => $user->miners->sum('machines_count'),
				'user_balance' => $this->format->balance($user_balance = $user->miners->sum('balance')),
				'user_balance_exact' => $this->format->fullBalance($user_balance),
				'user_earnings' => $this->format->balance($user_earnings = $user->miners->sum('earned')),
				'user_earnings_exact' => $this->format->fullBalance($user_earnings),
				'user_rank' => 'N/A',
			];

			foreach ($leaderboard->get() as $position => $entry) {
				if ($entry['user']->id === $user->id) {
					$user_stats['user_rank'] = '#' . ($position + 1);
					break;
				}
			}

			// append extra statistics for admin users
			if ($user->isAdministrator()) {
				$state_parser = new StateParser($this->reader->getLiveDataJson());
				$user_stats['is_administrator'] = true;
				$user_stats['pool_version'] = $state_parser->getPoolVersion();
				$user_stats['pool_state'] = $state_parser->getPoolState();
				$user_stats['pool_state_normal'] = $state_parser->isNormalPoolState();
			}
		}

		$pool_stat = PoolStat::orderBy('id', 'desc')->first();

		$last_blocks = FoundBlock::orderBy('id', 'desc')->where('found_at', '>', Carbon::now()->subDays(3))->limit(20)->get();
		if (count($last_blocks) >= 2) {
			$found_at_first = $last_blocks->first()->found_at;
			$found_at_last = $last_blocks->last()->found_at;
			$diff_seconds = $found_at_last->diffInSeconds($found_at_first);

			$block_found_every = $diff_seconds / count($last_blocks) / 60;
			if ($block_found_every > 60) {
				$block_found_every = '~' . round($block_found_every / 60, 2) . ' hours';
			} else {
				$block_found_every = '~' . round($block_found_every, 2) . ' minutes';
			}
		} else {
			$block_found_every = 'unknown';
		}

		$today_midnight = new Carbon('today midnight');
		$last_day = clone $today_midnight;
		$last_day->subDays(1);
		$last_week = clone $today_midnight;
		$last_week->subWeeks(1);
		$last_month = clone $today_midnight;
		$last_month->subMonths(1);

		return response()->json([
			'pool_hashrate' => $this->format->hashrate($stats_parser->getPoolHashrate()),
			'network_hashrate' => $this->format->hashrate($stats_parser->getNetworkHashrate()),
			'blocks' => $stats_parser->getNumberOfBlocks(),
			'main_blocks' => $stats_parser->getNumberOfMainBlocks(),
			'difficulty' => $stats_presenter->getReadableDifficulty(),
			'difficulty_exact' => $stats_presenter->getExactDifficulty(),
			'supply' => $this->format->wholeBalance($stats_parser->getSupply()),
			'blocks_last_day' => FoundBlock::where('found_at', '<', $today_midnight)->where('found_at', '>=', $last_day)->count(),
			'blocks_last_week' => FoundBlock::where('found_at', '<', $today_midnight)->where('found_at', '>=', $last_week)->count(),
			'blocks_last_month' => FoundBlock::where('found_at', '<', $today_midnight)->where('found_at', '>=', $last_month)->count(),
			'block_found_every' => $block_found_every,

			'miners' => number_format($pool_stat ? $pool_stat->active_miners : 0, 0, '.', ','),

			'fees' => $config_presenter->getFee(),
			'config' => $config_presenter->getFeeConfig(),

			'uptime' => $this->uptime->getReadableUptime(),
			'uptime_exact' => $this->uptime->getExactUptime(),
		] + $user_stats);
	}

	public function detailed()
	{
		$pool_hashrate = ['x' => [], 'Pool hashrate (Gh/s)' => []];
		$active_miners = ['x' => [], 'Active pool miners' => []];
		$network_hashrate = ['x' => [], 'Network hashrate (Gh/s)' => []];

		$stats = PoolStat::orderBy('id', 'asc')->where('created_at', '>', Carbon::now()->subDays(3))->get();

		foreach ($stats as $stat) {
			$datetime = $stat->created_at->subMinutes(5)->format('Y-m-d H:i');

			$pool_hashrate['x'][] = $datetime;
			$active_miners['x'][] = $datetime;
			$network_hashrate['x'][] = $datetime;

			$pool_hashrate['Pool hashrate (Gh/s)'][] = $stat->pool_hashrate / 1024 / 1024 / 1024;
			$network_hashrate['Network hashrate (Gh/s)'][] = $stat->network_hashrate / 1024 / 1024 / 1024;

			$active_miners['Active pool miners'][] = $stat->active_miners;
		}

		$found_blocks = ['x' => [], 'Found blocks' => []];
		$blocks = FoundBlock::selectRaw('DATE_FORMAT(found_at, "%Y-%m-%d") date, count(*) count')->where('found_at', '>', Carbon::now()->subMonths(1))->groupBy(\DB::raw('DATE_FORMAT(found_at, "%m-%d")'))->get();

		foreach ($blocks as $block) {
			$found_blocks['x'][] = $block->date;
			$found_blocks['Found blocks'][] = $block->count;
		}

		return response()->json([
			'pool_hashrate' => $pool_hashrate,
			'active_miners' => $active_miners,
			'found_blocks' => $found_blocks,
			'network_hashrate' => $network_hashrate,
		]);
	}
}
