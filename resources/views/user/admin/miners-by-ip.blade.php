@extends('layouts.admin')

@section('title')
	Miners by IP
@endsection

@section('hero')
	<section class="hero is-primary">
		<div class="hero-body">
			<div class="container">
				<h1 class="title">
					Miners by IP
				</h1>
				<h2 class="subtitle">
					All pool miners grouped by IP address
				</h2>
			</div>
		</div>
	</section>
@endsection

@section('adminContent')
	<table class="miners-grouped table is-fullwidth">
		<thead>
			<tr>
				<th>IP address</th>
				<th class="tooltip is-tooltip-multiline" data-tooltip="Miners connected from this IP.">Miners</th>
				<th class="tooltip is-tooltip-multiline" data-tooltip="Current estimated hashrate. The value is not averaged or corrected by any means.">Hashrate</th>
				<th class="tooltip is-tooltip-multiline" data-tooltip="Registered users connected from this IP.">Users</th>
			</tr>
		</thead>
		<tbody>
			@foreach ($ips as $ip => $data)
				<tr>
					<td><a href="#" class="ip-address-details" data-unpaid-shares="{{ $data['unpaid_shares'] }}" data-in-out-bytes="{{ $data['in_out_bytes'] }}">{{ $ip }}</a></td>
					<td class="tooltip is-tooltip-multiline is-tooltip-right ip-miners" data-tooltip="@foreach ($data as $key => $miner)
						@if ($key == 'machines' || $key == 'unpaid_shares' || $key == 'in_out_bytes' || $key == 'hashrate')
							@continue
						@endif
						{{ $miner->getAddress() }}:
						{{ $miner->getIpsAndNames() }}
					@endforeach">{{ $data['machines'] }}</td>
					<td>
						{{ $format->hashrate($data['hashrate']) }}
					</td>
					<td>
						@php($users = [])
						@foreach ($data as $key => $miner)
							@if ($key == 'machines' || $key == 'unpaid_shares' || $key == 'in_out_bytes' || $key == 'hashrate')
								@continue
							@endif
							@php($users = array_merge($users, $miner->getUsers()))
						@endforeach
						@forelse ($users as $user)
							<a href="{{ route('user.admin.edit-user', $user->id) }}">{{ $user->nick }}</a>@if (!$loop->last), @endif
						@empty
							-
						@endforelse
					</td>
				</tr>
			@endforeach
		</tbody>
	</table>

	{{ $ips->links() }}

	<div class="modal" id="ipAddressDetailsModal">
		<div class="modal-background"></div>
		<div class="modal-card">
			<header class="modal-card-head">
				<p class="modal-card-title">IP address details</p>
				<a class="delete close-modal" aria-label="close" href="#"></a>
			</header>
			<section class="modal-card-body">
				<div class="column">
					<div class="field is-horizontal">
						<div class="field-label">
							<label class="label">IP address</label>
						</div>

						<div class="field-body">
							<div class="field">
								<p class="control has-icons-left has-icons-right">
									<input class="input is-disabled" type="text" name="ip_address" readonly>
									<span class="icon is-small is-left">
										<i class="fa fa-server"></i>
									</span>
								</p>
							</div>
						</div>
					</div>
				</div>

				<div class="column">
					<div class="field is-horizontal">
						<div class="field-label">
							<label class="label">Unpaid shares</label>
						</div>

						<div class="field-body">
							<div class="field">
								<p class="control has-icons-left has-icons-right">
									<input class="input is-disabled" type="text" name="unpaid_shares" readonly>
									<span class="icon is-small is-left">
										<i class="fa fa-money"></i>
									</span>
								</p>
							</div>
						</div>
					</div>
				</div>

				<div class="column">
					<div class="field is-horizontal">
						<div class="field-label">
							<label class="label">In / out bytes</label>
						</div>

						<div class="field-body">
							<div class="field">
								<p class="control has-icons-left has-icons-right">
									<input class="input is-disabled" type="text" name="in_out_bytes" readonly>
									<span class="icon is-small is-left">
										<i class="fa fa-exchange"></i>
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
								<textarea class="textarea is-disabled" name="miners" rows="8" readonly></textarea>
							</div>
						</div>
					</div>
				</div>
			</section>
			<footer class="modal-card-foot">
				<button type="button" class="button close-modal">Close</button>
			</footer>
		</div>
	</div>
@endsection

@section('scripts')
	<script>
		var adminMinersByIpView = new adminMinersByIpView();
	</script>
@endsection
