<?php

namespace App\Http\Controllers;

class PublicStatusController extends Controller
{
	public function json()
	{
		return response(file_get_contents(storage_path('livedata.json')), 200)->header('Content-Type', 'application/json');
	}

	public function humanReadable()
	{
		return response(file_get_contents(storage_path('livedata.txt')), 200)->header('Content-Type', 'text/plain');
	}
}
