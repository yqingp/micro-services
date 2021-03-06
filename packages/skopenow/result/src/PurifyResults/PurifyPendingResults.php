<?php

namespace Skopenow\Search\Libraries;

class PurifyPendingResults
{

	protected $rules;

	protected $priority = 0;

	public function __construct(\Iterator $rules)
	{
		$this->rules = $rules;
	}

	public function setRules(\Iterator $rules)
	{
		$this->rules = $rules;
	}

	public function purify(\Iterator $results)
	{
		$results = $this->setPriorities($results);
		$results = $this->getPurifiedResults($results, $this->priority);

		return $results;
	}

	protected function getPurifiedResults(\Iterator $results, int $priority)
	{
		$purifiedResults = new \ArrayIterator();
		while ($results->valid()) {
			$result = $results->current();
			if ($result['priority'] == $priority) {
				$purifiedResults->append(unserialize($result['resultData']));
			}
			$results->next();
		}
		return $purifiedResults;
	}

	protected function setPriorities(\Iterator $results)
	{
		$sortedResults = new \ArrayIterator();
		while ($results->valid()) {
			$result = $results->current();
			$result['priority'] = $this->getResultPriority($result);
			$sortedResults->append($result);
			$results->next();
		}

		return $sortedResults;
	}

	protected function getResultPriority(array $result): int
	{
		while ($this->rules->valid()) {
			$rule = $this->rules->key();
			$priority = $this->rules->current();
			$resultData = @unserialize($result['resultData']);
			$flags = $this->getFlags($resultData->getMatchStatus());
			$resultData->setFlags($flags);

			if($flags&$rule == $rule) {
				if($priority != 0 && $priority<$this->priority) $this->priority = $priority;
				elseif($this->priority == 0)	$this->priority = $priority;
				return $priority;
			}

			$this->rules->next();
		}
		return 0;
	}

	protected function getFlags(array $matchingData)
	{
		$scoringService = loadService('scoring');
		$flags = $scoringService->getFlags($matchingData);

		return $flags;
	} 
}