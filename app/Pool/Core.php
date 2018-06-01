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

		if (!in_array($operation, ['livedata', 'fastdata'])) {
			try {
				$lock = new ExclusiveLock($operation == 'balance' ? 'core_call_balance' : 'core_call', 100);
				$lock->obtain();
			} catch (UnableToObtainLockException $ex) {
				throw new CoreCallException('Unable to obtain core lock.');
			}
		}

		$data = @file_get_contents($url . '?' . http_build_query($arguments), false, stream_context_create(['http' => ['timeout' => 350]]));
		if (!$data) {
			if (isset($lock))
				$lock->release();
			throw new CoreCallException('Unable to call openxdagpool-scripts core.');
		}

		if (isset($lock))
			$lock->release();
		return $data;
	}
}

class CoreException extends \Exception {}
class CoreCallException extends CoreException {}
