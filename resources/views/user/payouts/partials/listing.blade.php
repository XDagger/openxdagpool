<table class="table is-fullwidth">
	<thead>
		<tr>
			<th>Date and time</th>
			<th>Sender</th>
			<th>Recipient</th>
			<th>Amount</th>
		</tr>
	</thead>
	<tbody>
		@forelse ($payouts as $payout)
			<tr>
				<td class="tooltip" data-tooltip="{{ $payout->precise_made_at->format('Y-m-d H:i:s.u') }}">{{ $payout->made_at->format('Y-m-d H:i:s') }}</td>
				<td class="tooltip" data-tooltip="{{ $payout->sender }}">{{ $payout->short_sender }}</td>
				<td class="tooltip" data-tooltip="{{ $payout->recipient }}">{{ $payout->short_recipient }}</td>
				<td>{{ number_format($payout->amount, 9, '.', ',') }} XDAG</td>
			</tr>
		@empty
			<tr>
				<td colspan="4">No payouts yet, check back later! ;-)</td>
			</tr>
		@endforelse
	</tbody>
	@if ($payouts->count())
		<tfoot>
			<tr>
				<th colspan="3">TOTAL</th>
				<th>{{ number_format($payouts->sum('amount'), 9, '.', ',') }} XDAG</th>
			</tr>
		</tfoot>
	@endif
</table>