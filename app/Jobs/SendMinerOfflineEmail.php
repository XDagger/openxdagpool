<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;

use App\Mail\MinerWentOffline;
use App\Miners\Miner;

class SendMinerOfflineEmail implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public $tries = 5;
	protected $miner_id;

	public function __construct($miner_id)
	{
		$this->miner_id = $miner_id;
	}

	public function handle()
	{
		$miner = Miner::find($this->miner_id);

		if (!$miner)
			return;

		Mail::to($miner->user->email, $miner->user->nick)->send(new MinerWentOffline($miner));
	}
}
