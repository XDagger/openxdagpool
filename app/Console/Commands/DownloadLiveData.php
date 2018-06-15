<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Pool\Core;
use App\Support\{ExclusiveLock, UnableToObtainLockException};

class DownloadLiveData extends Command
{
	protected $signature = 'data:live';
	protected $description = 'Downloads live data from the openxdagpool-scripts endpoint.';

	protected $core;

	public function handle()
	{
		$this->core = new Core;

		$lock = new ExclusiveLock('download_data', 100);
		$lock->obtain();
		$this->downloadFiles();
		$lock->release();

		$self = explode('\\', get_class($this));
		$this->info(array_pop($self) . ' completed successfully.');
	}

	protected function downloadFiles()
	{
		$this->downloadFile('livedata', [], 'livedata.json', true);
		$this->downloadFile('livedata', ['human_readable' => true], 'livedata.txt');
	}

	protected function downloadFile($operation, array $arguments, $save_as, $expect_json = false)
	{
		try {
			$data = $this->core->call($operation, $arguments);
		} catch (CoreCallException $ex) {
			return false;
		}

		if (!$data)
			throw new DownloadDataException('Unable to download data from openxdagpool-scripts core.');

		if ($expect_json && !@json_decode($data, true))
			throw new DownloadDataException('JSON returned by openxdagpool-scripts core is invalid.');

		if (@file_put_contents(storage_path($save_as), $data) === false)
			throw new DownloadDataException('Unable to save data into file ' . $save_as);

		return $data;
	}
}

class DownloadDataException extends \Exception {}
