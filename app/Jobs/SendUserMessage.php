<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;

use App\Mail\UserMessage;
use App\Users\User;

class SendUserMessage implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public $tries = 5;
	protected $user_id, $email_subject, $email_message;

	public function __construct($user_id, $subject, $message)
	{
		$this->user_id = $user_id;
		$this->email_subject = $subject;
		$this->email_message = $message;
	}

	public function handle()
	{
		$user = User::find($this->user_id);

		if (!$user)
			return;

		Mail::to($user->email, $user->nick)->send(new UserMessage($user, $this->email_subject, $this->email_message));
	}
}
