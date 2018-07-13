@extends('layouts.app')

@section('title')
	Miners
@endsection

@section('hero')
	<section class="hero is-primary">
		<div class="hero-body">
			<div class="container">
				<h1 class="title">
					Miners
				</h1>
				<h2 class="subtitle">
					Manage your miners easily.
				</h2>
			</div>
		</div>
	</section>
@endsection

@section('content')
	<div class="miners-view">
		<div class="columns is-marginless is-centered">
			<div class="column is-7">
				<form action="{{ route('miners.alerts') }}" method="post">
					{{ csrf_field() }}
					<table class="table is-fullwidth miners-list">
						<thead>
							<tr>
								<th>Miner address</th>
								<th class="tooltip" data-tooltip="Status updates every 5 minutes.">Status</th>
								<th class="tooltip" data-tooltip="Estimated hashrate. Updates every 5 minutes.">Hashrate</th>
								<th class="tooltip" data-tooltip="Unpaid shares. Updates every 5 minutes.">Unpaid shares</th>
								<th class="tooltip is-tooltip-multiline" data-tooltip="Current address balance. Updates approximately every 5 minutes.">Balance</th>
								<th class="tooltip is-tooltip-multiline" data-tooltip="E-mail alerts when miner goes offline and back online.">Alerts</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							@forelse ($authUser->miners as $miner)
								<tr class="miner" data-uuid="{{ $miner->uuid }}" data-address="{{ $miner->address }}" data-note="{{ $miner->note }}">
									<td class="miner-address tooltip is-tooltip-multiline" data-tooltip="{{ $miner->note ? $miner->address . ' (' . $miner->note . ')' : $miner->address }}"><a href="#" class="update-miner">{{ $miner->short_address }}</a></td>
									<td class="miner-status api is-loading"></td>
									<td class="miner-average-hashrate api is-loading"></td>
									<td class="miner-unpaid-shares api is-loading"></td>
									<td class="miner-balance api is-loading is-tooltip-multiline"></td>
									<td>
										<input type="hidden" name="alerts[{{ $miner->uuid }}]" value="0">
										<input type="checkbox" name="alerts[{{ $miner->uuid }}]" value="1"{{ $miner->email_alerts ? ' checked' : '' }}>
									</td>
									<td>
										<a class="button is-success tooltip" href="{{ route('miners.payouts.graph', $miner->uuid) }}" data-tooltip="View payouts">
											<span class="icon"><i class="fa fa-money"></i></span>
										</a>

										<a class="button tooltip" href="{{ route('miners.hashrate.graph', [$miner->uuid, 'latest']) }}" data-tooltip="Hashrate history">
											<span class="icon"><i class="fa fa-bar-chart"></i></span>
										</a>

										<a class="button is-danger tooltip delete-miner" href="#" data-tooltip="Delete miner">
											<span class="icon"><i class="fa fa-trash-o"></i></span>
										</a>
									</td>
								</tr>
							@empty
								<tr>
									<td colspan="8">No miners.</td>
								</tr>
							@endforelse
						</tbody>
					</table>

					@if ($authUser->miners->count())
						<button type="submit" class="button is-pulled-right">
							<span class="icon"><i class="fa fa-floppy-o"></i></span>
							<span>Save alert preferences</span>
						</button>
					@endif

					<a class="button is-primary" id="addMiner">
						<span class="icon"><i class="fa fa-plus-square-o"></i></span>
						<span>Add miner</span>
					</a>
				</form>
				<hr>
				<p><span class="important">Note:</span> statuses, hash rates and unpaid shares update every 5 minutes.</p>
				<hr>
				<p><span class="important">Note:</span> hash rate calculation is purely informational, it does not represent 'what the pool sees', or your real mining speed. It is a statistical approximation, displayed for informational purposes only. The reading should start matching your real speed over a longer period of time (usually 6 hours). You are always mining at full speed reported by typing <code>stats</code> into your CPU miner console or by observing the speed that your GPU miner shows. Displayed hash rate does not affect payouts at all.</p>
				<hr>
				<p><span class="important">Note:</span> address balances, and payouts update approximately every 5 minutes. Like hash rate, this display is meant for a quick check on your miners to see how they are doing. Always check your real time time balance using the <code>balance</code> command in your wallet console if you need a precise value.</p>
			</div>
		</div>

		<div class="modal" id="addMinerModal">
			<div class="modal-background"></div>
			<div class="modal-card">
				<header class="modal-card-head">
					<p class="modal-card-title">Add miner</p>
					<a class="delete close-modal" aria-label="close" href="#"></a>
				</header>
				<form id="addMinerForm" method="post" action="{{ route('miners.create') }}">
					{{ csrf_field() }}
					<section class="modal-card-body">
						<p>You can find your miner address by typing <code>account</code> in wallet console.</p>

						<div class="column">
							<div class="field is-horizontal">
								<div class="field-label">
									<label class="label">Address</label>
								</div>

								<div class="field-body">
									<div class="field">
										<p class="control has-icons-left has-icons-right">
											<input class="input" type="text" id="address" name="address" maxlength="32" required>
											<span class="icon is-small is-left">
												<i class="fa fa-address-card-o"></i>
											</span>
										</p>
									</div>
								</div>
							</div>
						</div>

						<div class="column">
							<div class="field is-horizontal">
								<div class="field-label">
									<label class="label">Note</label>
								</div>

								<div class="field-body">
									<div class="field">
										<p class="control has-icons-left has-icons-right">
											<input class="input" type="text" id="note" name="note">
											<span class="icon is-small is-left">
												<i class="fa fa-sticky-note-o"></i>
											</span>
										</p>
									</div>
								</div>
							</div>
						</div>
					</section>
					<footer class="modal-card-foot">
						<button type="submit" class="button is-success">Save</button>
					</footer>
				</form>
			</div>
		</div>

		<div class="modal" id="deleteMinerModal">
			<div class="modal-background"></div>
			<div class="modal-card">
				<header class="modal-card-head">
					<p class="modal-card-title">Delete miner</p>
					<a class="delete close-modal" aria-label="close" href="#"></a>
				</header>
				<form id="deleteMinerForm" method="post" action="{{ route('miners.delete') }}">
					<input type="hidden" name="_method" value="delete">
					{{ csrf_field() }}
					<section class="modal-card-body">
						<p>Are you sure you want to delete this miner from your list? This will permanently delete miner's hashrate history.</p>

						<div class="column">
							<div class="field is-horizontal">
								<div class="field-label">
									<label class="label">Address</label>
								</div>

								<div class="field-body">
									<div class="field">
										<p class="control has-icons-left has-icons-right">
											<input class="input is-disabled" type="text" id="deleteMinerAddress" name="address" readonly>
											<span class="icon is-small is-left">
												<i class="fa fa-address-card-o"></i>
											</span>
										</p>
									</div>
								</div>
							</div>
						</div>

						<div class="column">
							<div class="field is-horizontal">
								<div class="field-label">
									<label class="label">Note</label>
								</div>

								<div class="field-body">
									<div class="field">
										<p class="control has-icons-left has-icons-right">
											<input class="input is-disabled" type="text" id="deleteMinerNote" name="note" readonly>
											<span class="icon is-small is-left">
												<i class="fa fa-sticky-note-o"></i>
											</span>
										</p>
									</div>
								</div>
							</div>
						</div>
					</section>
					<footer class="modal-card-foot">
						<button type="submit" class="button is-danger">Delete</button>
						<button type="button" class="button close-modal">Back</button>
					</footer>
				</form>
			</div>
		</div>

		<div class="modal" id="updateMinerModal">
			<div class="modal-background"></div>
			<div class="modal-card">
				<header class="modal-card-head">
					<p class="modal-card-title">Miner details</p>
					<a class="delete close-modal" aria-label="close" href="#"></a>
				</header>
				<form id="deleteMinerForm" method="post" action="{{ route('miners.update') }}">
					<input type="hidden" name="uuid" id="updateMinerUuid" value="">
					<input type="hidden" name="_method" value="put">
					{{ csrf_field() }}
					<section class="modal-card-body">
						<div class="column">
							<div class="field is-horizontal">
								<div class="field-label">
									<label class="label">Address</label>
								</div>

								<div class="field-body">
									<div class="field">
										<p class="control has-icons-left has-icons-right">
											<input class="input is-disabled" type="text" id="updateMinerAddress" name="address" readonly>
											<span class="icon is-small is-left">
												<i class="fa fa-address-card-o"></i>
											</span>
										</p>
									</div>
								</div>
							</div>
						</div>

						<div class="column">
							<div class="field is-horizontal">
								<div class="field-label">
									<label class="label">Note</label>
								</div>

								<div class="field-body">
									<div class="field">
										<p class="control has-icons-left has-icons-right">
											<input class="input" type="text" id="updateMinerNote" name="note">
											<span class="icon is-small is-left">
												<i class="fa fa-sticky-note-o"></i>
											</span>
										</p>
									</div>
								</div>
							</div>
						</div>

						<div class="column">
							<div class="field is-horizontal">
								<div class="field-label">
									<label class="label">Workers</label>
								</div>

								<div class="field-body">
									<div class="field">
										<textarea class="input is-disabled" id="updateMinerIpsAndPorts" name="ips_and_ports" rows="5" readonly></textarea>
									</div>
								</div>
							</div>
						</div>
					</section>
					<footer class="modal-card-foot">
						<button type="submit" class="button is-primary">Update note</button>
						<button type="button" class="button close-modal">Back</button>
					</footer>
				</form>
			</div>
		</div>
	</div>
@endsection

@section('scripts')
	<script>
		var minersView = new minersView();
	</script>
@endsection
