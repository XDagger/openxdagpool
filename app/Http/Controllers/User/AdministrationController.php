<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Users\User;
use App\Pool\{DataReader, Formatter};
use App\Pool\Miners\Parser as MinersParser;
use App\Pool\Statistics\Parser as StatsParser;
use App\Pool\State\Parser as StateParser;
use App\Http\Requests\{UpdateUser, SaveSettings, SendMassEmail};
use App\Jobs\SendUserMessage;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Setting;

class AdministrationController extends Controller
{
	public function __construct()
	{
		$this->middleware('auth');
		$this->middleware('active');
		$this->middleware('admin');
		view()->share('activeTab', 'admin');
	}

	public function users(Formatter $format)
	{
		return view('user.admin.users', [
			'format' => $format,
			'users' => User::orderBy('active')->orderBy('administrator', 'desc')->orderBy('nick')->orderBy('id')->paginate(25),
			'section' => 'users',
		]);
	}

	public function editUser($id)
	{
		if (!($user = User::find($id)))
			return redirect()->back()->with('error', 'User not found.');

		return view('user.admin.edit-user', [
			'user' => $user,
			'section' => 'users',
		]);
	}

	public function updateUser(UpdateUser $request, $id)
	{
		$user = User::findOrFail($id);

		$user->nick = $request->input('nick');
		$user->email = $request->input('email');
		$user->anonymous_profile = (boolean) $request->input('anonymous_profile');
		$user->exclude_from_leaderboard = (boolean) $request->input('exclude_from_leaderboard');
		$user->active = (boolean) $request->input('active');
		$user->administrator = (boolean) $request->input('administrator');

		if ($request->input('password'))
			$user->password = bcrypt($request->input('password'));

		$user->save();

		return redirect()->route('user.admin.users')->with('success', 'User successfully updated.');
	}

	public function poolSettings()
	{
		return view('user.admin.settings', [
			'section' => 'settings',
			'coefficient' => Setting::get('reference_miner_coefficient'),
		]);
	}

	public function savePoolSettings(SaveSettings $request)
	{
		Setting::set('pool_created_at', $request->input('pool_created_at'));
		Setting::set('other_pools', $request->input('other_pools'));
		Setting::set('pool_name', $request->input('pool_name'));
		Setting::set('header_background_color', $request->input('header_background_color'));
		Setting::set('pool_tagline', $request->input('pool_tagline'));
		Setting::set('pool_tooltip', $request->input('pool_tooltip'));

		Setting::set('pool_domain', $request->input('pool_domain'));
		Setting::set('pool_port', $request->input('pool_port'));
		Setting::set('website_domain', $request->input('website_domain'));

		Setting::set('contact_email', $request->input('contact_email'));
		Setting::set('important_message_html', $request->input('important_message_html'));
		Setting::set('important_message_until', $request->input('important_message_until'));
		Setting::set('pool_news_html', $request->input('pool_news_html'));

		Setting::set('reference_miner_address', $request->input('reference_miner_address'));
		Setting::set('reference_miner_hashrate', $request->input('reference_miner_hashrate'));

		Setting::save();

		return redirect()->back()->with('success', 'Settings successfuly updated.');
	}

	public function massEmail()
	{
		return view('user.admin.mass-email', [
			'section' => 'mass-email',
		]);
	}

	public function sendMassEmail(SendMassEmail $request)
	{
		if ($request->input('active'))
			$users = User::where('active', true)->whereHas('miners', function ($query) {
				$query->where('status', 'active');
			})->get();
		else
			$users = User::where('active', true)->get();

		foreach ($users as $user)
			SendUserMessage::dispatch($user->id, $request->input('subject'), $request->input('content'));

		return redirect()->back()->with('success', 'E-mail successfully sent to ' . count($users) . ' users.');
	}

	public function minersByIp(Request $request, DataReader $reader, Formatter $format)
	{
		$miners_parser = new MinersParser($reader->getFastDataJson());
		$stats_parser = new StatsParser($reader->getLiveDataJson());
		$ips = new Collection($miners_parser->getMinersByIp($stats_parser->getPoolHashrate()));
		$page = $request->input('page', 1);
		$ips = new LengthAwarePaginator($ips->forPage($page, 20), count($ips), 20, $page, ['path' => route('user.admin.miners-by-ip')]);

		return view('user.admin.miners-by-ip', [
			'section' => 'miners-by-ip',
			'ips' => $ips,
			'format' => $format,
		]);
	}

	public function minersByHashrate(Request $request, DataReader $reader, Formatter $format)
	{
		$miners_parser = new MinersParser($reader->getFastDataJson());
		$stats_parser = new StatsParser($reader->getLiveDataJson());
		$miners = new Collection($miners_parser->getMinersByHashrate($stats_parser->getPoolHashrate()));
		$page = $request->input('page', 1);
		$miners = new LengthAwarePaginator($miners->forPage($page, 20), count($miners), 20, $page, ['path' => route('user.admin.miners-by-hashrate')]);

		return view('user.admin.miners-by-hashrate', [
			'section' => 'miners-by-hashrate',
			'miners' => $miners,
			'format' => $format,
		]);
	}

	public function poolInformation(DataReader $reader)
	{
		$state_parser = new StateParser($reader->getLiveDataJson());

		return view('user.admin.pool-information', [
			'section' => 'pool-information',
			'state_normal' => $state_parser->isNormalPoolState(),
			'livedata' => $reader->getLiveDataHumanReadable(),
			'fastdata' => $reader->getFastDataHumanReadable(),
		]);
	}
}
