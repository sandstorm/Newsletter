<?php
/**
 * Created by IntelliJ IDEA.
 * User: sebastian
 * Date: 27.05.15
 * Time: 12:59
 */

namespace Sandstorm\Newsletter\Domain\Model;

use Sandstorm\Newsletter\Domain\Service\ReceiverGroupGenerationService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * Wraps a "ReceiverGroup" and reduces it to only the first entry (used for sending test emails)
 */
class SinglePersonReceiverGroup extends ReceiverGroup {

	/**
	 * @var ReceiverGroup
	 */
	protected $parentReceiverGroup;

	public function __construct(ReceiverGroup $receiverGroup) {
		$this->parentReceiverGroup = $receiverGroup;
	}

	public function getReceiverSource() {
		return $this->parentReceiverGroup->getReceiverSource();
	}

	public function getUnsubscribeList() {
		return $this->parentReceiverGroup->getUnsubscribeList();
	}

	public function getName() {
		return $this->parentReceiverGroup->getName();
	}

	public function getFilter($language = NULL) {
		return $this->parentReceiverGroup->getFilter($language);
	}

	public function getCacheFileName($language = NULL) {
		$parentCacheFileName = $this->parentReceiverGroup->getCacheFileName($language);
		$singleCacheFileName = $parentCacheFileName . '_single';

		$file = fopen($parentCacheFileName, 'r');
		$singleLineContents = fgets($file);
		fclose($file);
		file_put_contents($singleCacheFileName, $singleLineContents);

		return $singleCacheFileName;
	}

	public function getCacheFiles() {
		return $this->parentReceiverGroup->getCacheFiles();
	}

	public function getFullLabel() {
		return $this->parentReceiverGroup->getFullLabel();
	}


}