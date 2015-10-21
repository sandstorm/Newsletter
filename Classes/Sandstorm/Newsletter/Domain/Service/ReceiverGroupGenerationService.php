<?php
namespace Sandstorm\Newsletter\Domain\Service;

use Sandstorm\Newsletter\Domain\Model\ReceiverGroup;
use Sandstorm\Newsletter\Domain\Model\ReceiverSource;
use Sandstorm\Newsletter\Domain\Service\Internal\FilterExpressionContext;
use Sandstorm\Newsletter\Domain\Service\Internal\FilterExpressionEvaluator;
use Symfony\Component\Process\Process;
use TYPO3\Eel\Context;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Utility\Files;
use TYPO3\Neos\Domain\Service\ContentDimensionPresetSourceInterface;

/**
 * @Flow\Scope("singleton")
 */
class ReceiverGroupGenerationService {

	/**
	 * @var string
	 * @Flow\InjectConfiguration("receiverGroupCache")
	 */
	protected $receiverGroupCache;

	/**
	 * @Flow\Inject
	 * @var SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @Flow\Inject
	 * @var FilterExpressionEvaluator
	 */
	protected $filterExpressionEvaluator;

	/**
	 * @Flow\Inject
	 * @var ContentDimensionPresetSourceInterface
	 */
	protected $contentDimensionPresetSource;

	/**
	 * @param ReceiverGroup $receiverGroup
	 */
	public function generate(ReceiverGroup $receiverGroup) {
		Files::createDirectoryRecursively($this->receiverGroupCache);

		$presets = $this->contentDimensionPresetSource->getAllPresets();
		$languages = array_keys($presets['language']['presets']);

		if (count($languages) == 0) {
			$this->processReceiverGroup($receiverGroup);
		} else {
			foreach ($languages as $language) {
				$this->processReceiverGroup($receiverGroup, $language);
			}
		}
	}

	/**
	 * @param string $filter
	 */
	public function convertFilterIntoJqExpression($filter) {
		if (strlen(trim($filter)) === 0) {
			return 'select(true)';
		}
		return $this->filterExpressionEvaluator->evaluate($filter, new Context(array()));
	}

	private function processReceiverGroup(ReceiverGroup $receiverGroup, $language = NULL) {
		$cacheFileName = $receiverGroup->getCacheFileName($language);
		$jqProcess = escapeshellarg(
			'cat ' . escapeshellcmd($receiverGroup->getReceiverSource()->getSourceFileName())
			. ' | jq -c ' . escapeshellarg($this->convertFilterIntoJqExpression($receiverGroup->getFilter($language)))
			. ' > ' . escapeshellcmd($cacheFileName)
			. ' ; wc -l < ' . escapeshellcmd($cacheFileName) . ' > ' . escapeshellcmd($cacheFileName . '.lines')
		);

		$finalProcess = 'nohup /bin/bash -c ' . $jqProcess . ' &';

		$this->systemLogger->log('Starting process: ' . $finalProcess);
		$proc = new Process($finalProcess);
		$proc->start();
	}
}