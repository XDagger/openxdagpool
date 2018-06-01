<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Pool\{DataReader, Core, CoreCallException};
use App\Pool\Config\Parser as ConfigParser;
use App\FoundBlocks\FoundBlock;
use App\Payouts\Payout;

use Carbon\Carbon;

class ImportFoundBlocks extends Command
{
	protected $signature = 'blocks:import';
	protected $description = 'Imports / handles all found blocks and payouts.';

	protected $reader;

	public function __construct(DataReader $reader)
	{
		$this->reader = $reader;
		parent::__construct();
	}

	public function handle()
	{
		if (env('DISABLE_BLOCKS_IMPORT')) {
			$this->line('Blocks import is disabled in .env file.');
			$this->info('ImportFoundBlocks completed successfully.');
			return;
		}

		$core = new Core;
		$config = new ConfigParser($this->reader->getLiveDataJson());

		// TODO: block reward may decrease in the future
		$fee = 1024 * ($config->getFee() / 100);

		$imported = $invalidated = 0;

		// import at most 20000 new found blocks / run
		for ($i = 0; $i < 20000; $i++) {
			try {
				$block_json = $core->call('block');
			} catch (CoreCallException $ex) {
				$this->line('Stopped importing blocks - unable to call core.');
				break;
			}

			$block_json = @json_decode($block_json, true);

			if ($block_json === false || $block_json === null) {
				$this->line('Stopped importing blocks - unable to parse response json.');
				break;
			}

			if (isset($block_json['result']))
				break;

			$block = FoundBlock::where('hash', $block_json['properties']['hash'])->first();
			if (!$block) {
				$block = new FoundBlock([
					'address' => $block_json['properties']['balance_address'],
					'hash' => $block_json['properties']['hash'],
					'payout' => round(1024 - $fee, 2),
					'fee' => round($fee, 2),
				]);
			} else {
				$block->payouts()->delete();
			}

			$block->precise_found_at = Carbon::parse($block_json['properties']['time']);
			$block->save();

			// insert payouts
			foreach ($block_json['payouts'] as $payout) {
				$new_payout = new Payout([
					'found_block_id' => $block->id,
					'recipient' => $payout['address'],
					'amount' => $payout['amount'],
				]);

				$new_payout->precise_made_at = Carbon::parse($payout['time']);
				$new_payout->save();
			}

			$imported++;
		}

		// invalidate at most 20000 found blocks / run
		for ($i = 0; $i < 20000; $i++) {
			try {
				$block_json = $core->call('blockInvalidated');
			} catch (CoreCallException $ex) {
				$this->line('Stopped invalidating found blocks - unable to call core.');
				break;
			}

			$block_json = @json_decode($block_json, true);

			if ($block_json === false) {
				$this->line('Stopped invalidating found blocks - unable to parse response json.');
				break;
			}

			if (isset($block_json['result']))
				break;

			$block = FoundBlock::where('hash', $block_json['invalidateBlock'])->first();
			if (!$block)
				continue;

			$block->payouts()->delete();
			$block->delete();

			$invalidated++;
		}

		$this->line('Imported ' . $imported . ' found blocks.');
		$this->line('Ivalidated ' . $invalidated . ' already imported found blocks.');
		$this->info('ImportFoundBlocks completed successfully.');
	}
}
