<?php

namespace App\Http\Controllers;

use App\FoundBlocks\FoundBlock;

class FoundBlocksController extends Controller
{
	public function index()
	{
		$found_blocks = FoundBlock::orderBy('found_at', 'desc')->limit(150)->get();

		return view('found-blocks.index', [
			'activeTab' => 'found-blocks',
			'blocks' => $found_blocks,
		]);
	}
}
