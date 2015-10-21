<?php
/**
 * Created by IntelliJ IDEA.
 * User: sebastian
 * Date: 05.05.15
 * Time: 14:22
 */

namespace Sandstorm\Newsletter;

use Sandstorm\Newsletter\Domain\Repository\ReceiverGroupRepository;
use TYPO3\Flow\Annotations as Flow;

use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Neos\Service\DataSource\AbstractDataSource;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

class ReceiverGroupsDataSource extends AbstractDataSource {

	static protected $identifier = 'receiverGroups';

	/**
	 * @Flow\Inject
	 * @var ReceiverGroupRepository
	 */
	protected $receiverGroupRepository;

	/**
	 * @Flow\Inject
	 * @var PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Get data
	 *
	 * @param NodeInterface $node The node that is currently edited (optional)
	 * @param array $arguments Additional arguments (key / value)
	 * @return mixed JSON serializable data
	 * @api
	 */
	public function getData(NodeInterface $node = NULL, array $arguments) {
		$result = array();
		$result[''] = array(
			'label' => 'Please select a receiver group'
		);

		foreach ($this->receiverGroupRepository->findAll() as $receiverGroup) {
			/* @var $receiverGroup \Sandstorm\Newsletter\Domain\Model\ReceiverGroup */
			$result[$this->persistenceManager->getIdentifierByObject($receiverGroup)] = array(
				'label' => $receiverGroup->getFullLabel()
			);
		}
		return $result;
	}
}