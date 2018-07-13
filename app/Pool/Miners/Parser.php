<?php

namespace App\Pool\Miners;

use App\Pool\BaseParser;

class Parser extends BaseParser
{
	public function __construct(array $data)
	{
		parent::__construct($data);
		$this->data = $this->data['miners'] ?? [];
	}

	public function getNumberOfMiners()
	{
		return count($this->data);
	}

	public function getNumberOfActiveMiners()
	{
		$active = 0;

		$this->forEachMiner(function($miner) use (&$active) {
			if ($miner['status'] === 'active')
				$active++;
		});

		return $active;
	}

	public function getTotalUnpaidShares()
	{
		$total = 0;

		$this->forEachMiner(function($miner) use (&$total) {
			// count all shares, not just for active miners
			$total += $miner['unpaid_shares'];
		});

		return $total;
	}

	public function getMiner($address, &$version_gt_024 = null)
	{
		$miner = null;

		$this->forEachMiner(function($m) use ($address, &$miner, &$version_gt_024) {
			if (isset($m['hashrate']))
				$version_gt_024 = true;

			if ($m['address'] === $address) {
				if (!$miner) {
					$miner = Miner::fromArray($m);
				} else {
					$miner->addIpAndPort($m['ip_and_port']);
					$miner->addInOutBytes(implode('/', $m['in_out_bytes']));
					$miner->addUnpaidShares($m['unpaid_shares'])
					$miner->addName($m['name'] ?? '');

					if (isset($m['hashrate']))
						$miner->addHashrate($m['hashrate']);

					if ($miner->getStatus() !== 'active' && $m['status'] === 'active')
						$miner->setStatus($m['status']);
				}
			}
		});

		return $miner;
	}

	public function getMinersByHashrate($pool_hashrate)
	{
		$miners = [];
		$version_gt_024 = false;

		$this->forEachMiner(function($miner) use (&$miners, &$version_gt_024) {
			if ($miner['address'] == 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA')
				return;

			if (isset($miner['hashrate']))
				$version_gt_024 = true;

			if (!isset($miners[$miner['address']])) {
				$miners[$miner['address']] = Miner::fromArray($miner);
			} else {
				$miners[$miner['address']]->addIpAndPort($miner['ip_and_port']);
				$miners[$miner['address']]->addInOutBytes(implode('/', $miner['in_out_bytes']));
				$miners[$miner['address']]->addUnpaidShares($miner['unpaid_shares']);
				$miners[$miner['address']]->addName($miner['name'] ?? '');

				if (isset($miner['hashrate']))
					$miners[$miner['address']]->addHashrate($miner['hashrate']);

				if ($miners[$miner['address']]->getStatus() !== 'active' && $miner['status'] === 'active')
					$miners[$miner['address']]->setStatus($miner['status']);
			}
		});

		$total_unpaid_shares = $this->getTotalUnpaidShares();

		foreach ($miners as $address => $miner) {
			if ($miner->getStatus() == 'free') {
				unset($miners[$address]);
				continue;
			}

			if (!$version_gt_024) {
				$hashrate = 0;
				if ($total_unpaid_shares > 0)
					$hashrate = ($miner->getUnpaidShares() / $total_unpaid_shares) * $pool_hashrate;

				$miner->setHashrate($hashrate);
			}
		}

		uasort($miners, function ($a, $b) {
			if ($a->getHashrate() == $b->getHashrate())
				return 0;

			return $a->getHashrate() < $b->getHashrate() ? 1 : -1;
		});

		return $miners;
	}

	public function getMinersByIp($pool_hashrate)
	{
		$miners = [];
		$version_gt_024 = false;

		$this->forEachMiner(function($miner) use (&$miners, &$version_gt_024) {
			if ($miner['address'] == 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA')
				return;

			if (isset($miner['hashrate']))
				$version_gt_024 = true;

			list($ip, $port) = explode(':', $miner['ip_and_port']);

			if ($ip === '0.0.0.0' && $port === '0')
				return;

			if (!isset($miners[$ip]))
				$miners[$ip] = [];

			if (!isset($miners[$ip][$miner['address']])) {
				$miners[$ip][$miner['address']] = Miner::fromArray($miner);
			} else {
				$miners[$ip][$miner['address']]->addIpAndPort($miner['ip_and_port']);
				$miners[$ip][$miner['address']]->addInOutBytes(implode('/', $miner['in_out_bytes']));
				$miners[$ip][$miner['address']]->addUnpaidShares($miner['unpaid_shares']);
				$miners[$ip][$miner['address']]->addName($miner['name'] ?? '');

				if (isset($miner['hashrate']))
					$miners[$ip][$miner['address']]->addHashrate($miner['hashrate']);

				if ($miners[$ip][$miner['address']]->getStatus() !== 'active' && $miner['status'] === 'active')
					$miners[$ip][$miner['address']]->setStatus($miner['status']);
			}
		});

		foreach ($miners as $ip => $list) {
			$miners[$ip]['machines'] = $miners[$ip]['unpaid_shares'] = $miners[$ip]['hashrate'] = 0;
			$miners[$ip]['in_out_bytes'] = '0/0';
			foreach ($list as $address => $miner) {
				if ($miner->getStatus() == 'free') {
					unset($miners[$ip][$address]);
					continue;
				}

				$miners[$ip]['machines'] += $miner->getMachinesCount();
				$miners[$ip]['unpaid_shares'] += $miner->getUnpaidShares();

				if ($version_gt_024)
					$miners[$ip]['hashrate'] += $miner->getHashrate();

				$bytes = explode('/', $miners[$ip]['in_out_bytes']);
				$miner_bytes = explode('/', $miner->getInOutBytes());
				$miners[$ip]['in_out_bytes'] = ($bytes[0] + $miner_bytes[0]) . '/' . ($bytes[1] + $miner_bytes[1]);
			}

			if (count($miners[$ip]) == 4)
				unset($miners[$ip]);
		}

		if (!$version_gt_024) {
			$total_unpaid_shares = $this->getTotalUnpaidShares();

			foreach ($miners as $ip => $list) {
				$hashrate = 0;
				if ($total_unpaid_shares > 0)
					$hashrate = ($miners[$ip]['unpaid_shares'] / $total_unpaid_shares) * $pool_hashrate;

				$miners[$ip]['hashrate'] = $hashrate;
			}
		}

		uasort($miners, function ($a, $b) {
			if ($a['machines'] == $b['machines'])
				return 0;

			return $a['machines'] < $b['machines'] ? 1 : -1;
		});

		return $miners;
	}

	protected function forEachMiner(callable $callback)
	{
		foreach ($this->data as $miner)
			$callback($miner);
	}
}
