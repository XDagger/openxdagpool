@extends('layouts.admin')

@section('title')
	Mass e-mail
@endsection

@section('hero')
	<section class="hero is-primary">
		<div class="hero-body">
			<div class="container">
				<h1 class="title">
					Mass e-mail
				</h1>
				<h2 class="subtitle">
					Send important notifications to registered pool users
				</h2>
			</div>
		</div>
	</section>
@endsection

@section('adminContent')
	<nav class="card">
		<header class="card-header">
			<p class="card-header-title">
				Mass e-mail
			</p>
		</header>

		<div class="card-content">
			<form method="POST" action="{{ route('user.admin.mass-email.send') }}">

				{{ csrf_field() }}

				<div class="field is-horizontal">
					<div class="field-label"></div>
					<div class="field-body">
						<label class="checkbox tooltip is-tooltip-multiline" data-tooltip="When checked, your e-mail will be sent only to users with at least one active miner.">
							<input type="hidden" name="active" value="0">
							<input type="checkbox" name="active" value="1"{{ old('active', 1) ? ' checked' : '' }}>
							e-mail only active users
						</label>
					</div>
				</div>

				<div class="field is-horizontal">
					<div class="field-label">
						<label class="label">Address contains</label>
					</div>

					<div class="field-body">
						<div class="field tooltip is-tooltip-multiline" data-tooltip="Send e-mail only to user's e-mail addresses that contain given phrase, for example 'domain.com'.">
							<p class="control">
								<input class="input" type="text" id="contains" name="contains" value="{{ old('contains') }}">
							</p>

							@if ($errors->has('contains'))
								<p class="help is-danger">
									{{ $errors->first('contains') }}
								</p>
							@endif
						</div>
					</div>
				</div>

				<div class="field is-horizontal">
					<div class="field-label">
						<label class="label">Address doesn't contain</label>
					</div>

					<div class="field-body">
						<div class="field tooltip is-tooltip-multiline" data-tooltip="Send e-mail only to user's e-mail addresses that DO NOT contain given phrase, for example 'foobar.com'.">
							<p class="control">
								<input class="input" type="text" id="except" name="except" value="{{ old('except') }}">
							</p>

							@if ($errors->has('except'))
								<p class="help is-danger">
									{{ $errors->first('except') }}
								</p>
							@endif
						</div>
					</div>
				</div>

				<div class="field is-horizontal">
					<div class="field-label">
						<label class="label">E-mails / hour</label>
					</div>

					<div class="field-body">
						<div class="field tooltip is-tooltip-multiline" data-tooltip="Send at most this number of e-mails per hour. E-mails that are over limit will be sent in next hours.">
							<p class="control">
								<input class="input" type="number" min="0" max="5000" step="1" id="emails_per_hour" name="emails_per_hour" value="{{ old('emails_per_hour') }}">
							</p>

							@if ($errors->has('emails_per_hour'))
								<p class="help is-danger">
									{{ $errors->first('emails_per_hour') }}
								</p>
							@endif
						</div>
					</div>
				</div>

				<div class="field is-horizontal">
					<div class="field-label">
						<label class="label">E-mail subject</label>
					</div>

					<div class="field-body">
						<div class="field">
							<p class="control">
								<input class="input" type="text" id="subject" name="subject" value="{{ old('subject') }}">
							</p>

							@if ($errors->has('subject'))
								<p class="help is-danger">
									{{ $errors->first('subject') }}
								</p>
							@endif
						</div>
					</div>
				</div>

				<div class="field is-horizontal">
					<div class="field-label">
						<label class="label">E-mail content</label>
					</div>

					<div class="field-body">
						<div class="field tooltip is-tooltip-multiline" data-tooltip="Content of your message. No HTML is allowed.">
							<p class="control">
								<textarea class="textarea" rows="4" name="content">{{ old('content') }}</textarea>
							</p>

							@if ($errors->has('content'))
								<p class="help is-danger">
									{{ $errors->first('content') }}
								</p>
							@endif
						</div>
					</div>
				</div>

				<div class="field is-horizontal">
					<div class="field-label"></div>

					<div class="field-body">
						<div class="field is-grouped">
							<div class="control">
								<button type="submit" class="button is-primary">Send</button>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
	</nav>
@endsection
