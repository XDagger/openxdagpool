<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Pool\{DataReader, Core, CoreCallException};
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

		$imported = $invalidated = 0;
		$insert_payouts = [];

		// import at most 2000 new found blocks / run
		for ($i = 0; $i < 2000; $i++) {
			try {
				$block_json = $core->call('block');
			} catch (CoreCallException $ex) {
				$this->line('Stopped importing blocks - unable to call core.');
				break;
			}

			$block_json = @json_decode($block_json, true);

			if ($block_json === null) {
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
					'payout' => 0,
					'fee' => 0,
				]);
			} else {
				$block->payouts()->delete();
			}

			$block->precise_found_at = Carbon::parse($block_json['properties']['time']);
			$block->save();

			// process payouts
			$payouts_sum = 0;
			foreach ($block_json['payouts'] as $payout) {
				if ($payout['amount'] == 0)
					continue; // don't import payouts with  zero amount

				$made_at = Carbon::parse($payout['time']);

				$insert_payouts[] = [
					'found_block_id' => $block->id,
					'recipient' => $payout['address'],
					'amount' => $payout['amount'],
					'made_at' => $made_at->format('Y-m-d H:i:s'),
					'made_at_milliseconds' => floor($made_at->micro / 1000),
					'created_at' => $now = Carbon::now()->format('Y-m-d H:i:s'),
					'updated_at' => $now,
				];

				$payouts_sum += $payout['amount'];
			}

			if (count($insert_payouts) > 1000) {
				Payout::insert($insert_payouts);
				$insert_payouts = [];
			}

			$block->payout = $payouts_sum;
			$block->fee = 1024 - $payouts_sum;
			$block->save();

			$imported++;
		}

		if (count($insert_payouts) > 0) {
			Payout::insert($insert_payouts);
			$insert_payouts = [];
		}

		// invalidate at most 2000 found blocks / run
		for ($i = 0; $i < 2000; $i++) {
			try {
				$block_json = $core->call('blockInvalidated');
			} catch (CoreCallException $ex) {
				$this->line('Stopped invalidating found blocks - unable to call core.');
				break;
			}

			$block_json = @json_decode($block_json, true);

			if ($block_json === null) {
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
		$this->line('Invalidated ' . $invalidated . ' already imported found blocks.');
		$this->info('ImportFoundBlocks completed successfully.');
	}
}
