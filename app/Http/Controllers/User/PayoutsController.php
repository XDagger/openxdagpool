<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use Auth, Excel, Setting;
use Carbon\Carbon;

class PayoutsController extends Controller
{
	public function __construct()
	{
		$this->middleware('auth');
		$this->middleware('active');
	}

	public function userPayoutsGraph()
	{
		return view('user.payouts.user-payouts-graph', [
			'graph_data' => $this->getGraphData(Auth::user()->getDailyPayouts(), $sum),
			'payouts_sum' => $sum,
			'activeTab' => 'payouts',
		]);
	}

	public function userPayoutsListing(Request $request)
	{
		return view('user.payouts.user-payouts-listing', [
			'payouts' => Auth::user()->getPayoutsListing($request->input('page')),
			'payouts_sum' => Auth::user()->getPayoutsSum(),
			'activeTab' => 'payouts',
		]);
	}

	public function exportUserPayoutsGraph()
	{
		$user = Auth::user();
		return $this->exportPayoutsGraph($user->getDailyPayouts(), 'user', $user->display_nick);
	}

	public function exportUserPayoutsListing()
	{
		$user = Auth::user();

		if ($user->getPayoutsCount() > 10000)
			return $this->exportPayoutsCsv($user, $user->getPayoutsSum(), 'user', $user->display_nick);

		return $this->exportPayoutsXlsx($user->getPayoutsListingNonPaged(), 'user', $user->display_nick);
	}

	public function minerPayoutsGraph($uuid)
	{
		if (($miner = Auth::user()->miners()->where('uuid', $uuid)->first()) === null)
			return redirect()->back()->with('error', 'Miner not found.');

		return view('user.payouts.miner-payouts-graph', [
			'miner' => $miner,
			'graph_data' => $this->getGraphData($miner->getDailyPayouts(), $sum),
			'payouts_sum' => $sum,
			'activeTab' => 'miners',
		]);
	}

	public function minerPayoutsListing($uuid, Request $request)
	{
		if (($miner = Auth::user()->miners()->where('uuid', $uuid)->first()) === null)
			return redirect()->back()->with('error', 'Miner not found.');

		return view('user.payouts.miner-payouts-listing', [
			'miner' => $miner,
			'payouts' => $miner->getPayoutsListing($request->input('page')),
			'payouts_sum' => $miner->payouts()->sum('amount'),
			'activeTab' => 'miners',
		]);
	}

	public function exportMinerPayoutsGraph($uuid)
	{
		if (($miner = Auth::user()->miners()->where('uuid', $uuid)->first()) === null)
			return redirect()->back()->with('error', 'Miner not found.');

		return $this->exportPayoutsGraph($miner->getDailyPayouts(), 'address', $miner->address);
	}

	public function exportMinerPayoutsListing($uuid)
	{
		if (($miner = Auth::user()->miners()->where('uuid', $uuid)->first()) === null)
			return redirect()->back()->with('error', 'Miner not found.');

		if ($miner->payouts()->count() > 10000)
			return $this->exportPayoutsCsv($miner, $miner->payouts()->sum('amount'), 'address', $miner->address);

		return $this->exportPayoutsXlsx($miner->payouts, 'address', $miner->address);
	}

	protected function exportPayoutsCsv($model, $sum, $for_label, $for)
	{
		$download_name = $this->sanitizeFileName(Setting::get('pool_name') . ' - payouts listing for ' . $for_label . ' ' . $for . ' ' . rand() . '.csv');
		$filename = public_path('payouts/' . $download_name);

		try {
			$model->exportPayoutsToCsv($filename);
		} catch (\Illuminate\Database\QueryException $ex) {
			return redirect()->back()->with('error', 'Unable to export your payouts, please try again later.');
		}

		$file = @fopen($filename, 'a');
		if (!$file) return redirect()->back()->with('error', 'Unable to export your payouts, please try again later.');

		fputcsv($file, ['', '', '', '']);
		fputcsv($file, [ucfirst($for_label) . ':', $for, 'Total:', sprintf('%.09f', $sum)]);

		return response()->download($filename)->deleteFileAfterSend(true);
	}

	protected function exportPayoutsXlsx($payouts, $for_label, $for)
	{
		$export = [
			[ucfirst($for_label) . ':', $for, '', ''],
			['', '', '', ''],
			['Date and time', 'Sender', 'Recipient', 'Amount']
		];

		$total = 0;
		foreach ($payouts as $payout) {
			$export[] = [$payout->made_at->format('Y-m-d H:i:s'), $payout->sender, $payout->recipient, $payout->amount];
			$total += $payout->amount;
		}

		$export[] = ['', '', '', ''];
		$export[] = ['', '', 'Total:', sprintf('%.09f', $total)];

		return Excel::create($this->sanitizeFileName(Setting::get('pool_name') . ' - payouts listing for ' . $for_label . ' ' . $for . ' ' . rand()), function($excel) use ($export) {
			$excel->sheet('Payouts listing', function($sheet) use ($export) {
				$sheet->fromArray($export, null, 'A1', false, false);
			});
		})->download('xlsx');
	}

	protected function exportPayoutsGraph($days, $for_label, $for)
	{
		$export = [
			[ucfirst($for_label) . ':', $for],
			['', ''],
			['Date', 'Amount']
		];

		$total = 0;
		foreach ($days as $day) {
			$export[] = [$day->date, sprintf('%.09f', $day->total)];
			$total += $day->total;
		}

		$export[] = ['', ''];
		$export[] = ['Total:', sprintf('%.09f', $total)];

		return Excel::create($this->sanitizeFileName(Setting::get('pool_name') . ' - daily payouts for ' . $for_label . ' ' . $for . ' ' . rand()), function($excel) use ($export) {
			$excel->sheet('Daily payouts', function($sheet) use ($export) {
				$sheet->fromArray($export, null, 'A1', false, false);
			});
		})->download('xlsx');
	}

	protected function getGraphData($days, &$sum)
	{
		$graph = ['x' => [], 'Payout' => []];
		$sum = 0;

		$last_date = null;

		foreach ($days as $day) {
			$date = Carbon::parse($day->date);

			if ($last_date) {
				while ($last_date->addDays(1) < $date) {
					$graph['x'][] = $last_date->format('Y-m-d');
					$graph['Payout'][] = 0;
				}
			}

			$last_date = Carbon::parse($day->date);
			$graph['x'][] = $day->date;
			$graph['Payout'][] = sprintf('%.09f', $day->total);
			$sum += $day->total;
		}

		return json_encode($graph);
	}

	protected function sanitizeFilename($filename)
	{
		$chars = ['/', '<', '>', ':', '"', '\\', '|', '?', '*'];
		for ($i = 0; $i < 32; $i++)
			$chars[] = chr($i);

		return str_replace($chars, '_', $filename);
	}
}
