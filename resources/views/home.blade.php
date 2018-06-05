@extends('layouts.app')

@section('title')
	Home
@endsection

@section('hero')
	<section class="hero is-primary home-hero">
		<div class="hero-body">
			<div class="container">
				<h1 class="title">
					{{ Setting::get('pool_name') }}
					@if ($pools)
						<div class="dropdown is-hoverable">
							<div class="dropdown-trigger">
								<button class="button" aria-haspopup="true" aria-controls="pool-selection">
									<span>{{ $current_pool_name }}</span>
									<span class="icon is-small">
										<i class="fa fa-angle-down" aria-hidden="true"></i>
									</span>
								</button>
							</div>

							<div class="dropdown-menu" id="pool-selection" role="menu">
								<div class="dropdown-content">
									@foreach ($pools as $pool)
										<a href="{{ $pool['url'] }}" class="dropdown-item{{ $pool['is_current_pool'] ? ' is-active' : '' }}">
											{{ $pool['name'] }}
										</a>
									@endforeach
								</div>
							</div>

							<span class="pool-selection-info tooltip is-tooltip-multiline" data-tooltip="Each pool has it's own database and user accounts. Register to use the pool to the fullest extent."><i class="fa fa-info-circle"></i></span>
						</div>
					@endif
				</h1>
				@if(Setting::get('pool_tagline') !== null)
					<h2 class="subtitle">
						@if (Setting::get('pool_tooltip') !== null)
							<span class="tooltip" data-tooltip="{{ Setting::get('pool_tooltip') }}">
								{{ Setting::get('pool_tagline') }}
							</span>
						@else
							{{ Setting::get('pool_tagline') }}
						@endif
					</h2>
				@endif
			</div>
		</div>
	</section>
@endsection

@section('content')
	<div class="home-view">
		<div class="columns is-marginless is-centered">
			<div class="column is-7">
				@if ($message)
					<div class="notification is-info">
						<button class="delete"></button>
						{!! $message !!}
					</div>
				@endif

				@if (isset($authUser) && $authUser->isAdministrator())
					<div class="notification is-danger" id="adminPoolStateAlert">
						<button class="delete"></button>
						Warning, pool daemon state abnormal. This is admin-only notification.<br>
						<span id="poolVersion"></span><br>
						<span id="poolState"></span>
					</div>
				@endif

				<div class="notification" id="balanceResult">
					<button class="delete"></button>
					<span></span>
					<p><a href="#">View payouts</a></p>
				</div>

				<nav class="card">
					<header class="card-header">
						<div class="tabs stat-tabs">
							<ul>
								<li class="is-active" data-target=".pool-stats"><a>Pool statistics</a></li>
								<li data-target=".pool-blocks"><a>Found blocks</a></li>
								<li data-target=".network-stats"><a>Network statistics</a></li>
								@if (!Auth::guest())
									<li data-target=".user-stats"><a>{{ Auth::user()->display_nick }}'s statistics</a></li>
								@endif
							</ul>
						</div>
					</header>

					<div class="card-content stats">
						<nav class="level is-mobile pool-stats">
							<div class="level-item has-text-centered tooltip" data-tooltip="Past 4 hours hashrate. Click for details.">
								<div>
									<p class="heading">Hashrate</p>
									<p class="title">
										<a href="{{ route('stats') }}" class="stat api is-loading" data-stat="pool_hashrate"></a>
									</p>
								</div>
							</div>
							<div class="level-item has-text-centered tooltip" data-tooltip="Currently active miners. Click for details.">
								<div>
									<p class="heading">Miners</p>
									<p class="title">
										<a href="{{ route('stats') }}" class="stat api is-loading" data-stat="miners"></a>
									</p>
								</div>
							</div>
							<div class="level-item has-text-centered stat-tooltip is-tooltip-multiline" data-stat="config">
								<div>
									<p class="heading">Fees</p>
									<p class="title stat api is-loading" data-stat="fees"></p>
								</div>
							</div>
							<div class="level-item has-text-centered stat-tooltip is-tooltip-multiline" data-stat="uptime_exact">
								<div>
									<p class="heading">Uptime</p>
									<p class="title stat api is-loading" data-stat="uptime"></p>
								</div>
							</div>
						</nav>
						<nav class="level is-mobile pool-blocks inactive-tab-stats">
							<div class="level-item has-text-centered tooltip is-tooltip-multiline" data-tooltip="Updates every 5 minutes. Click for details.">
								<div>
									<p class="heading">Last month</p>
									<p class="title">
										<a href="{{ route('stats') }}" class="stat api is-loading" data-stat="blocks_last_month"></a>
									</p>
								</div>
							</div>
							<div class="level-item has-text-centered tooltip is-tooltip-multiline" data-tooltip="Updates every 5 minutes. Click for details.">
								<div>
									<p class="heading">Last week</p>
									<p class="title">
										<a href="{{ route('stats') }}" class="stat api is-loading" data-stat="blocks_last_week"></a>
									</p>
								</div>
							</div>
							<div class="level-item has-text-centered tooltip is-tooltip-multiline" data-tooltip="Updates every 5 minutes. Click for details.">
								<div>
									<p class="heading">Last day</p>
									<p class="title">
										<a href="{{ route('stats') }}" class="stat api is-loading" data-stat="blocks_last_day"></a>
									</p>
								</div>
							</div>
							<div class="level-item has-text-centered tooltip is-tooltip-multiline" data-tooltip="Updates every 5 minutes. Approximate value based on last 20 blocks found. Click for a listing of latest found blocks.">
								<div>
									<p class="heading">Block every</p>
									<p class="title">
										<a href="{{ route('found-blocks') }}" class="stat api is-loading" data-stat="block_found_every"></a>
									</p>
								</div>
							</div>
						</nav>
						<nav class="level is-mobile network-stats inactive-tab-stats">
							<div class="level-item has-text-centered tooltip" data-tooltip="Past 4 hours hashrate. Click for details.">
								<div>
									<p class="heading">Hashrate</p>
									<p class="title">
										<a href="{{ route('stats') }}" class="stat api is-loading" data-stat="network_hashrate"></a>
									</p>
								</div>
							</div>
							<div class="level-item has-text-centered tooltip" data-tooltip="Number of known blocks">
								<div>
									<p class="heading">Blocks</p>
									<p class="title stat api is-loading" data-stat="blocks"></p>
								</div>
							</div>
							<div class="level-item has-text-centered stat-tooltip" data-stat="supply" data-stat-prefix="Coin supply: ">
								<div>
									<p class="heading">Main blocks</p>
									<p class="title stat api is-loading" data-stat="main_blocks"></p>
								</div>
							</div>
							<div class="level-item has-text-centered stat-tooltip" data-stat="difficulty_exact">
								<div>
									<p class="heading">Difficulty</p>
									<p class="title stat api is-loading" data-stat="difficulty"></p>
								</div>
							</div>
						</nav>
						@if (!Auth::guest())
							<nav class="level is-mobile user-stats inactive-tab-stats">
								<div class="level-item has-text-centered tooltip" data-tooltip="Your estimated hashrate. Click for details.">
									<div>
										<p class="heading">Hashrate</p>
										<p class="title">
											<a href="{{ route('miners') }}" class="stat api is-loading" data-stat="user_hashrate"></a>
										</p>
									</div>
								</div>
								<div class="level-item has-text-centered tooltip" data-tooltip="Your active miners (machines). Click for details.">
									<div>
										<p class="heading">Miners</p>
										<p class="title">
											<a href="{{ route('miners') }}" class="stat api is-loading" data-stat="user_miners"></a>
										</p>
									</div>
								</div>
								<div class="level-item has-text-centered stat-tooltip" data-stat="user_earnings" data-stat-prefix="Earnings: ">
									<div>
										<p class="heading">Coins</p>
										<p class="title">
											<a href="{{ route('miners') }}" class="stat api is-loading" data-stat="user_balance"></a>
										</p>
									</div>
								</div>
								<div class="level-item has-text-centered tooltip is-tooltip-multiline" data-tooltip="Out of all pool users with registered miners, this is how your hashrate compares to them.">
									<div>
										<p class="heading">Rank</p>
										<p class="title">
											<a href="{{ route('leaderboard') }}" class="stat api is-loading" data-stat="user_rank"></a>
										</p>
									</div>
								</div>
							</nav>
						@endif
					</div>
				</nav>
			</div>
		</div>

		<div class="columns is-marginless is-centered">
			<div class="column is-7">
				<nav class="card">
					<header class="card-header">
						<p class="card-header-title">
							Wallet balance and payouts
						</p>
					</header>

					<div class="card-content">
						<div class="content">
							<form action="#" method="post" id="balanceCheckForm">
								<div class="field has-addons is-horizontal">
									<div class="control is-expanded">
										<input class="input is-fullwidth" type="text" name="address" placeholder="Wallet address" maxlength="32" required>
									</div>
									<div class="control">
										<button class="button tooltip is-tooltip-multiline" data-tooltip="Balances and payouts update every 5 minutes." type="submit">
											Show
										</button>
									</div>
								</div>
							</form>
						</div>
					</div>
				</nav>
			</div>
		</div>

		<div class="columns is-marginless is-centered">
			<div class="column is-7">
				<nav class="card">
					<header class="card-header">
						<p class="card-header-title">
							Mining information
						</p>
					</header>

					<div class="card-content">
						<p>Windows GPU (<a href="{{ route('pages', 'setup/windows-gpu') }}">detailed instructions</a>):</p>
						<pre class="oneline">
							<span class="parameter">C:\DaggerGpuMiner</span>\DaggerGpuMiner.exe -G -a <span class="parameter">wallet_address</span> -p {{ Setting::get('pool_domain') }}:{{ Setting::get('pool_port') }} -t 0 -v 2 -opencl-platform <span class="parameter">platform_id</span> -opencl-devices <span class="parameter">device_nums</span>
						</pre>
						<p class="offset">Replace <span class="parameter">C:\DaggerGpuMiner</span> with full path to your xdag miner installation folder.</p>
						<p>Replace <span class="parameter">wallet_address</span> with your wallet address.</p>
						<p>Replace <span class="parameter">platform_id</span> with <code>0</code>, <code>1</code> or <code>2</code>. Try with <code>0</code> first, as this is the most common platform id.</p>
						<p>Replace <span class="parameter">device_nums</span> with <code>0</code> or <code>0 1 2 3</code> or similar based on number of GPUs you have. Always count up from <code>0</code>.</p>
						<p><span class="important">Note:</span> if you are using NVIDIA GPUs, make sure you add <code>-nvidia-fix</code> at the end of the command line to prevent high system CPU usage and increase your hashrate.</p>

						<hr>

						<p>Windows CPU (<a href="{{ route('pages', 'setup/windows-cpu') }}">detailed instructions</a>):</p>
						<pre class="oneline">
							<span class="parameter">C:\xdag</span>\xdag.exe -d -m <span class="parameter">4</span> {{ Setting::get('pool_domain') }}:{{ Setting::get('pool_port') }}
						</pre>
						<p class="offset">Replace <span class="parameter">C:\xdag</span> with full path to your xdag installation folder.</p>
						<p>Replace <span class="parameter">4</span> with number of mining threads, for dedicated mining machines, set this to number of CPU threads.</p>

						<hr>

						<p>Unix GPU (<a href="{{ route('pages', 'setup/unix-gpu') }}">detailed instructions</a>):</p>
						<pre class="oneline">
							./xdag-gpu -G -a <span class="parameter">wallet_address</span> -p {{ Setting::get('pool_domain') }}:{{ Setting::get('pool_port') }} -t 0 -v 2 -opencl-platform <span class="parameter">platform_id</span> -opencl-devices <span class="parameter">device_nums</span>
						</pre>
						<p class="offset">Replace <span class="parameter">wallet_address</span> with your wallet address.</p>
						<p>Replace <span class="parameter">platform_id</span> with <code>0</code>, <code>1</code> or <code>2</code>. Try with <code>0</code> first, as this is the most common platform id.</p>
						<p>Replace <span class="parameter">device_nums</span> with <code>0</code> or <code>0 1 2 3</code> or similar based on number of GPUs you have. Always count up from <code>0</code>.</p>
						<p><span class="important">Note:</span> if you are using NVIDIA GPUs, make sure you add <code>-nvidia-fix</code> at the end of the command line to prevent high system CPU usage and increase your hashrate.</p>

						<hr>
						<p>Unix CPU (<a href="{{ route('pages', 'setup/unix-cpu') }}">detailed instructions</a>):</p>
						<pre class="oneline">
							./xdag -d -m <span class="parameter">4</span> {{ Setting::get('pool_domain') }}:{{ Setting::get('pool_port') }}
						</pre>
						<p class="offset">Replace <span class="parameter">4</span> with number of mining threads, for dedicated mining machines, set this to number of CPU threads.</p>
					</div>
				</nav>
			</div>
		</div>

		<div class="columns is-marginless is-centered">
			<div class="column is-7">
				<nav class="card">
					<header class="card-header">
						<p class="card-header-title">
							Optional registration
						</p>
					</header>

					<div class="card-content">
						<div class="content">
							Register if you want to easily keep track of your miners, their hashrates, balances, payouts and receive email notifications should your miner go offline.
						</div>
					</div>
				</nav>
			</div>
		</div>

		<div class="columns is-marginless is-centered">
			<div class="column is-7">
				<nav class="card">
					<header class="card-header">
						<p class="card-header-title">
							Pool news
						</p>
					</header>

					<div class="card-content">
						<div class="content">
							{!! Setting::get('pool_news_html') !!}
						</div>
					</div>
				</nav>
			</div>
		</div>
	</div>
@endsection

@section('scripts')
	<script>
		var homeView = new homeView();
	</script>
@endsection
