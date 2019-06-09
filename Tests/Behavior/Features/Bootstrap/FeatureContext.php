<?php

use Behat\MinkExtension\Context\MinkContext;
use TYPO3\TYPO3CR\Tests\Behavior\Features\Bootstrap\NodeOperationsTrait;

require_once(__DIR__ . '/../../../../../Flowpack.Behat/Tests/Behat/FlowContext.php');
require_once(__DIR__ . '/../../../../../TYPO3.TYPO3CR/Tests/Behavior/Features/Bootstrap/NodeOperationsTrait.php');
require_once(__DIR__ . '/NewsletterTrait.php');

/**
 * Features context
 */
class FeatureContext extends MinkContext {

	use NodeOperationsTrait;
	use NewsletterTrait;

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	protected $isolated = false;

	protected $parameters;

	/**
	 * Initializes the context
	 *
	 * @param array $parameters Context parameters (configured through behat.yml)
	 */
	public function __construct(array $parameters) {
		$this->parameters = $parameters;
		$this->useContext('flow', new \Flowpack\Behat\Tests\Behat\FlowContext($parameters));
		$this->objectManager = $this->getSubcontext('flow')->getObjectManager();
		$this->environment = $this->objectManager->get('TYPO3\Flow\Utility\Environment');
		$this->nodeAuthorizationService = $this->objectManager->get('TYPO3\TYPO3CR\Service\AuthorizationService');
	}

	/**
	 * @return \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected function getObjectManager() {
		return $this->objectManager;
	}
}