<?php

namespace App\Users;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

use App\Miners\Miner;
use App\Payouts\Payout;

use Carbon\Carbon;
use Auth;

class User extends Authenticatable
{
	use Notifiable;
	use \App\Support\HasUuid;

	protected $fillable = ['nick', 'email', 'password'];
	protected $hidden = ['password', 'remember_token'];
	protected $dates = ['created_at', 'updated_at', 'last_seen_at'];

	/* relations */
	public function miners()
	{
		return $this->hasMany(Miner::class);
	}

	/* attributes */
	public function getDisplayNickAttribute()
	{
		if (!$this->anonymous_profile)
			return $this->nick;

		$user = Auth::user();
		if ($user && ($user->isAdministrator() || $user->id === $this->id))
			return $this->nick;

		return 'anonymous';
	}

	/* methods */
	public function isActive()
	{
		return $this->active;
	}

	public function isAdministrator()
	{
		return $this->administrator;
	}

	public function getPayoutsListingNonPaged()
	{
		$addresses = $this->miners->pluck('address');
		return Payout::whereIn('recipient', $addresses ?: ['none'])->orderBy('made_at', 'asc')->orderBy('id', 'asc')->get();
	}

	public function getPayoutsListing($page = null)
	{
		$addresses = $this->miners->pluck('address');
		$query = Payout::whereIn('recipient', $addresses ?: ['none'])->orderBy('made_at', 'asc')->orderBy('id', 'asc');

		if (!$page) {
			$count = clone $query;
			return $query->paginate(500, ['*'], 'page', ceil($count->count('*') / 500));
		}

		return $query->paginate(500);
	}

	public function getPayoutsSum()
	{
		$addresses = $this->miners->pluck('address');
		return Payout::whereIn('recipient', $addresses ?: ['none'])->sum('amount');
	}

	public function getPayoutsCount()
	{
		$addresses = $this->miners->pluck('address');
		return Payout::whereIn('recipient', $addresses ?: ['none'])->count();
	}

	public function getDailyPayouts()
	{
		$addresses = $this->miners->pluck('address');
		return Payout::selectRaw('sum(amount) total, DATE_FORMAT(made_at, "%Y-%m-%d") date')->whereIn('recipient', $addresses ?: ['none'])->groupBy('date')->orderBy('date')->get();
	}

	public function exportPayoutsToCsv($filename)
	{
		$addresses = $this->miners->pluck('address');
		$in_clause = array_fill(0, count($addresses), '?');

		return \DB::statement('SELECT "_Date and time" made_at, "Sender" sender, "Recipient" recipient, "Amount" amount
			UNION ALL SELECT CONCAT(p.made_at, ".", LPAD(p.made_at_milliseconds, 3, "0")) made_at, b.address sender, p.recipient, p.amount FROM payouts p
			LEFT JOIN found_blocks b ON p.found_block_id = b.id WHERE p.recipient IN (' . implode(', ', $in_clause) . ')
			ORDER BY made_at ASC, p.id ASC
			INTO OUTFILE ' . \DB::getPdo()->quote($filename) . ' FIELDS TERMINATED BY "," ENCLOSED BY \'"\' LINES TERMINATED BY "\n"', $addresses->toArray());
	}

	public function getHashrateSum()
	{
		return $this->miners()->sum('hashrate');
	}

	public function getAverageHashrateSum()
	{
		return $this->miners()->sum('average_hashrate');
	}

	public function getDailyHashrate()
	{
		$miner_ids = $this->miners->pluck('id');
		return \DB::select('select sum(hashrate) hashrate, date from (select avg(hashrate) hashrate, DATE_FORMAT(created_at, "%Y-%m-%d") date from miner_stats where miner_id in (' . implode(', ', $miner_ids->count() ? $miner_ids->toArray() : [0]) . ') ums group by miner_id, date order by date) group by date');
	}

	public function getLatestHashrate()
	{
		$miner_ids = $this->miners->pluck('id');
		return \DB::select('select sum(hashrate) hashrate, date from (select avg(hashrate) hashrate, DATE_FORMAT(created_at, "%Y-%m-%d %H:00") date from miner_stats where created_at >= NOW() - INTERVAL 3 DAY and miner_id in (' . implode(', ', $miner_ids->count() ? $miner_ids->toArray() : [0]) . ') ums group by miner_id, date order by date) group by date');
	}
}
