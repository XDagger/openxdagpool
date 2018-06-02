<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\CreateMiner;
use Auth;

use App\Pool\DataReader;
use App\Pool\Miners\Parser as MinersParser;

class MinersController extends Controller
{
	public function __construct()
	{
		$this->middleware('auth');
		$this->middleware('active');
		view()->share('activeTab', 'miners');
	}

	public function index()
	{
		return view('user.miners');
	}

	public function create(CreateMiner $request)
	{
		$user = Auth::user();
		$user->miners()->create([
			'address' => $request->input('address'),
			'note' => $request->input('note'),
		]);

		return redirect()->back()->with('success', 'Miner successfully added. Status, hashrate, unpaid shares and balance will update in 5 minutes.');
	}

	public function delete(Request $request)
	{
		$user = Auth::user();
		$miner = $user->miners()->where('address', $request->input('address'))->first();

		if (!$miner)
			return redirect()->back()->with('error', 'Miner not found.');

		$miner->stats()->delete();
		$miner->delete();

		return redirect()->back()->with('success', 'Miner successfully deleted.');
	}

	public function update(Request $request)
	{
		$user = Auth::user();
		$miner = $user->miners()->where('uuid', $request->input('uuid'))->first();

		if (!$miner)
			return redirect()->back()->with('error', 'Miner not found.');

		$miner->note = $request->input('note');
		$miner->save();

		return redirect()->back()->with('success', 'Miner successfully updated.');
	}

	public function alerts(Request $request, DataReader $reader)
	{
		$user = Auth::user();
		$alerts = (array) $request->input('alerts');
		$miners_parser = null;
		$uuids = array_keys($alerts);
		if (!$uuids) $uuids = ['0'];

		foreach ($user->miners()->whereIn('uuid', $uuids)->get() as $miner) {
			$miner->email_alerts = (boolean) $alerts[$miner->uuid];

			if ($miner->email_alerts) {
				$miners_parser = $miners_parser ?? new MinersParser($reader->getFastDataJson());
				$pool_miner = $miners_parser->getMiner($miner->address);

				$miner->seen_online = $pool_miner !== null;
				$miner->status = $pool_miner ? $pool_miner->getStatus() : 'offline';
			}

			$miner->save();
		}

		return redirect()->back()->with('success', 'Preferences updated successfully.');
	}
}
