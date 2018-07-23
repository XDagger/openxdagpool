<?php

namespace App\Pool;

class DataReader
{
	public function getLiveDataJson()
	{
		return $this->json(@file_get_contents(storage_path('work_livedata.json')));
	}

	public function getFastDataJson()
	{
		return $this->json(@file_get_contents(storage_path('work_fastdata.json')));
	}

	public function getLiveDataHumanReadable()
	{
		return @file_get_contents(storage_path('livedata.txt'));
	}

	public function getFastDataHumanReadable()
	{
		return @file_get_contents(storage_path('fastdata.txt'));
	}

	protected function json($data)
	{
		$data = @json_decode($data, true);
		if ($data === null)
			throw new DataReaderException('Invalid json.');

		return $data;
	}
}

class DataReaderException extends \Exception {}
