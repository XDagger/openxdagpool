<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TruncateFoundBlocks extends Command
{
	protected $signature = 'blocks:truncate';
	protected $description = 'Truncates all found blocks and payouts.';

	public function handle()
	{
		\DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
		\DB::statement('TRUNCATE payouts;');
		\DB::statement('TRUNCATE found_blocks;');
		\DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
		$this->info('TruncateFoundBlocks completed successfully.');
	}
}
