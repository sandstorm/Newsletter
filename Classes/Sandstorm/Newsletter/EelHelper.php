<?php
/**
 * Created by IntelliJ IDEA.
 * User: sebastian
 * Date: 05.05.15
 * Time: 15:16
 */

namespace Sandstorm\Newsletter;


use Sandstorm\Newsletter\Domain\Model\ReceiverGroup;
use Sandstorm\Newsletter\Domain\Model\ReceiverSource;
use Sandstorm\Newsletter\Domain\Repository\ReceiverGroupRepository;
use Sandstorm\Newsletter\Domain\Repository\ReceiverSourceRepository;
use TYPO3\Eel\ProtectedContextAwareInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Neos\Domain\Service\ContentDimensionPresetSourceInterface;

/**
 * @Flow\Scope("singleton")
 */
class EelHelper implements ProtectedContextAwareInterface {

	/**
	 * @Flow\Inject
	 * @var ReceiverGroupRepository
	 */
	protected $receiverGroupRepository;

	/**
	 * @Flow\Inject
	 * @var ContentDimensionPresetSourceInterface
	 */
	protected $contentDimensionPresetSource;



	public function findRandomRecordForGroup($identifier, $dimensions) {
		$preset = $this->contentDimensionPresetSource->findPresetByDimensionValues('language', $dimensions['language']);
		$currentLanguageDimension = $preset['identifier'];
		if (!$identifier) {
			return array();
		}
		$receiverGroup = $this->receiverGroupRepository->findByIdentifier($identifier);
		/* @var $receiverGroup ReceiverGroup */

		if (!$receiverGroup) {
			return array();
		}
		if (!file_exists($receiverGroup->getCacheFileName($currentLanguageDimension))) {
			return array();
		}

		$file = fopen($receiverGroup->getCacheFileName($currentLanguageDimension), 'r');
		$contents = fgets($file);
		fclose($file);
		return json_decode($contents, TRUE);
	}

	public function findNumberOfRecipientsForGroup($identifier) {

		if (!$identifier) {
			return array();
		}
		$receiverGroup = $this->receiverGroupRepository->findByIdentifier($identifier);
		/* @var $receiverGroup ReceiverGroup */

		if (!$receiverGroup) {
			return array();
		}

		$presets = $this->contentDimensionPresetSource->getAllPresets();

		$result = array();
		if (!isset($presets['language']['presets'])) {
			$result['all'] = $receiverGroup->getNumberOfReceivers(NULL);
		} else {
			foreach ($presets['language']['presets'] as $languageKey => $languageConfiguration) {
				$result[$languageKey] = $receiverGroup->getNumberOfReceivers($languageKey);
			}
		}

		return $result;
	}

	/**
	 * @param string $methodName
	 * @return boolean
	 */
	public function allowsCallOfMethod($methodName) {
		return TRUE;
	}
}
