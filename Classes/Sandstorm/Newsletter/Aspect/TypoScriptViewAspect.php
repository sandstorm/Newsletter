<?php
namespace Sandstorm\Newsletter\Aspect;

use Sandstorm\Newsletter\TypoScriptObjects\ReplacePlaceholdersInLiveImplementation;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Security\Context;
use TYPO3\Neos\View\TypoScriptView;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class TypoScriptViewAspect {

	/**
	 * @var ControllerContext
	 */
	protected $controllerContext;

	/**
	 * @Flow\InjectConfiguration("hmacUrlSecret")
	 * @var string
	 */
	protected $hmacUrlSecret;

	/**
	 * @Flow\Inject
	 * @var Context
	 */
	protected $securityContext;

	/**
	 * Log a message if a post is deleted
	 *
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @Flow\Before("method(TYPO3\Neos\View\TypoScriptView->setControllerContext())")
	 * @return void
	 */
	public function setControllerContext(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$this->controllerContext = $joinPoint->getMethodArgument('controllerContext');
	}

	/**
	 * Log a message if a post is deleted
	 *
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @Flow\Around("method(TYPO3\Neos\View\TypoScriptView->render())")
	 * @return void
	 */
	public function replacePlaceholdersIfNecessary(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$result = $joinPoint->getAdviceChain()->proceed($joinPoint);

		/* @var $typoScriptView TypoScriptView */
		$typoScriptView = $joinPoint->getProxy();
		$viewVariables = ObjectAccess::getProperty($typoScriptView, 'variables', TRUE);
		if (!isset($viewVariables['value']) || !$viewVariables['value']->getNodeType()->isOfType('Sandstorm.Newsletter:Newsletter')) {
			// No newsletter, so logic does not apply
			return $result;
		}

		/* @var $httpRequest Request */
		$httpRequest = $this->controllerContext->getRequest()->getHttpRequest();

		$arguments = $httpRequest->getUri()->getArguments();

		if (!isset($arguments['hmac'])) {
			if ($this->securityContext->isInitialized() && $this->securityContext->hasRole('TYPO3.Neos:Editor')) {
				// Logged into backend, so we don't need to do anything.
				return $result;
			} else {
				// No HMAC sent -- so we return the email INCLUDING placeholders (as per customer's request)
				return $result;
				//return '<h1>Error: HMAC not included in the link.</h1>';
			}
		}

		$actualHmac = $arguments['hmac'];

		$uriWithoutHmac = str_replace('&hmac=' . $actualHmac, '', (string)$httpRequest->getUri());

		$expectedHmac = hash_hmac('sha1', urldecode($uriWithoutHmac), $this->hmacUrlSecret);

		if ($expectedHmac !== $actualHmac) {
			return '<h1>Error: Wrong link clicked.</h1>Please contact your administrator for help';
		}

		$result = preg_replace_callback(ReplacePlaceholdersInLiveImplementation::PLACEHOLDER_REGEX, function($element) use($arguments) {
			return ObjectAccess::getPropertyPath($arguments, $element[1]);
		}, $result);

		return $result;
	}
}