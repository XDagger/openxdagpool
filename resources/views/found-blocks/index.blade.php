@extends('layouts.app')

@section('title')
	Found blocks
@endsection

@section('hero')
	<section class="hero is-primary">
		<div class="hero-body">
			<div class="container">
				<h1 class="title">
					Found blocks
				</h1>
				<h2 class="subtitle">
					Last 150 blocks mined by this pool
				</h2>
			</div>
		</div>
	</section>
@endsection

@section('content')
	<div class="columns is-marginless is-centered">
		<div class="column is-9">
			<nav class="card">
				<header class="card-header">
					<p class="card-header-title">
						Found blocks
					</p>
				</header>

				<div class="card-content">
					<div class="content">
						<p><span class="important">Note:</span> This list updates approximately every 5 minutes. Times are listed in UTC. Blocks are paid out immediately when they are ready.</p>

						<table class="table is-fullwidth">
							<thead>
								<tr>
									<th class="tooltip" data-tooltip="Block was found at this date and time.">Found at</th>
									<th class="tooltip" data-tooltip="Found block's hash.">Hash</th>
									<th class="tooltip" data-tooltip="Total payout given to miners and community fund.">Payout</th>
									<th class="tooltip" data-tooltip="Pool's fee for this block.">Fee</th>
								</tr>
							</thead>
							<tbody>
								@forelse ($blocks as $block)
									<tr>
										<td>{{ $block->found_at->format('Y-m-d H:i:s') }}.{{ sprintf('%03d', $block->found_at_milliseconds) }}</td>
										<td>
											<a href="https://explorer.xdag.io/block/{{ $block->hash }}" target="_blank" class="tooltip" data-tooltip="Open in block explorer">
												{{ $block->short_hash }}
											</a>
										</td>
										<td>{{ number_format($block->payout, 2, '.', ',') }} XDAG</td>
										<td>{{ number_format($block->fee, 2, '.', ',') }} XDAG</td>
									</tr>
								@empty
									<tr>
										<td colspan="4">No found blocks yet, please come back later! ;-)</td>
									</tr>
								@endforelse
							</tbody>
						</table>
					</div>
				</div>
			</nav>
		</div>
	</div>
@endsection
