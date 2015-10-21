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
 * Class ReceiverSource
 * @package Sandstorm\Newsletter\Domain\Model
 * @Flow\Entity
 *
 */
class ReceiverGroup {

	/**
	 * @var ReceiverSource
	 * @ORM\ManyToOne(inversedBy="receiverGroups")
	 */
	protected $receiverSource;

	/**
	 * @var UnsubscribeList
	 * @ORM\ManyToOne
	 */
	protected $unsubscribeList;

	/**
	 * @var string
	 */
	protected $name;


	/**
	 * @var string
	 */
	protected $filter;

	/**
	 * @var string
	 * @Flow\InjectConfiguration("receiverGroupCache")
	 * @Flow\Transient
	 */
	protected $receiverGroupCache;


	/**
	 * @Flow\Inject
	 * @var PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var ReceiverGroupGenerationService
	 * @Flow\Inject
	 */
	protected $receiverGroupGenerationService;

	/**
	 * @return ReceiverSource
	 */
	public function getReceiverSource() {
		return $this->receiverSource;
	}

	/**
	 * @param ReceiverSource $receiverSource
	 * @internal
	 */
	public function setReceiverSource($receiverSource) {
		$this->receiverSource = $receiverSource;
	}

	/**
	 * @return UnsubscribeList
	 */
	public function getUnsubscribeList() {
		return $this->unsubscribeList;
	}

	/**
	 * @param UnsubscribeList $unsubscribeList
	 */
	public function setUnsubscribeList($unsubscribeList) {
		$this->unsubscribeList = $unsubscribeList;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = $name;
	}


	/**
	 * @return string
	 */
	public function getFilter($language = NULL) {
		$filter = $this->filter;

		$dimensionFilter = $this->receiverSource->getDimensionFilter();
		if (isset($dimensionFilter[$language]) && strlen($dimensionFilter[$language])) {
			if (strlen(trim($filter)) > 0) {
				$filter .= ' && ';
			}

			$filter .= $dimensionFilter[$language];
		}
		return $filter;
	}


	/**
	 * @param string $filter
	 */
	public function setFilter($filter) {
		$this->filter = $filter;
	}

	/**
	 * @param string $language
	 * @return string
	 */
	public function getCacheFileName($language = NULL) {
		$fileName = $this->receiverGroupCache . '/' . $this->persistenceManager->getIdentifierByObject($this);
		if ($language) {
			$fileName .= '_' . $language;
		}
		return $fileName;
	}

	public function getNumberOfReceivers($language = NULL) {
		$lineFileName = $this->getCacheFileName($language) . '.lines';
		if (!file_exists($lineFileName)) {
			return NULL;
		}
		else {
			return (int)trim(file_get_contents($lineFileName));
		}
	}


	public function getCacheFiles() {
		$cacheFile = $this->getCacheFileName();
		if (file_exists($cacheFile)) {
			return 'Cache File found';
		} else {
			$directory = dirname($cacheFile);
			$a = $directory . '/' . basename($cacheFile) . '_';
			$result = glob($a . '*');

			$r = array();
			foreach ($result as $res) {
				if (strpos($res, '_single') !== FALSE || strpos($res, '.lines') !== FALSE) {
					continue;
				}
				$r[] = substr($res, strlen($a));
			}
			return implode(', ', $r);
		}
		return $cacheFile;
	}

	public function updateCacheFile() {
		$this->receiverGroupGenerationService->generate($this);
	}

	public function getFullLabel() {
		return $this->receiverSource->getName() . ' - ' . $this->name;
	}

	/**
	 * Get a specialized "Receiver Group" which only contains the first recipient (for sending a single test-email)
	 *
	 * @return SinglePersonReceiverGroup
	 */
	public function singlePersonReceiverGroup() {
		return new SinglePersonReceiverGroup($this);
	}


}