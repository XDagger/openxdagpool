<?php

namespace App\Pool;

use App\Support\{ExclusiveLock, UnableToObtainLockException};

class Core
{
	public function call($operation, array $arguments = [])
	{
		$url = env('OPENXDAGPOOL_SCRIPTS_URL');
		if (!$url || (strpos($url, 'https://') !== 0 && strpos($url, 'http://') !== 0))
			throw new \InvalidArgumentException('.env setting OPENXDAGPOOL_SCRIPTS_URL invalid.');

		$arguments['operation'] = $operation;

		try {
			$lock = new ExclusiveLock('core_call', 100);
			$lock->obtain();
		} catch (UnableToObtainLockException $ex) {
			throw new CoreCallException('Unable to obtain core_call lock.');
		}

		$data = @file_get_contents($url . '?' . http_build_query($arguments), false, stream_context_create(['http' => ['timeout' => 350]]));
		if (!$data) {
			$lock->release();
			throw new CoreCallException('Unable to call openxdagpool-scripts core.');
		}

		$lock->release();
		return $data;
	}
}

class CoreException extends \Exception {}
class CoreCallException extends CoreException {}
