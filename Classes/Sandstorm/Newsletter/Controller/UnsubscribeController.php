<?php
namespace Sandstorm\Newsletter\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Sandstorm.Newsletter".  *
 *                                                                        *
 *                                                                        */

use GuzzleHttp\Client;
use Sandstorm\Newsletter\Domain\Model\Newsletter;
use Sandstorm\Newsletter\Domain\Model\ReceiverSource;
use Sandstorm\Newsletter\Domain\Model\UnsubscribeList;
use Sandstorm\Newsletter\Domain\Repository\ReceiverGroupRepository;
use Sandstorm\Newsletter\Domain\Repository\ReceiverSourceRepository;
use Sandstorm\Newsletter\Domain\Service\NewsletterSendingService;
use Sandstorm\Newsletter\Domain\Service\StyleInliningService;
use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Neos\Domain\Service\ContentDimensionPresetSourceInterface;
use TYPO3\Neos\Service\LinkingService;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

class UnsubscribeController extends ActionController {

	/**
	 * @Flow\InjectConfiguration("hmacUrlSecret")
	 * @var string
	 */
	protected $hmacUrlSecret;

	/**
	 * @param UnsubscribeList $unsubscribeList
	 * @param string $email
	 * @return string
	 */
	public function unsubscribeAction(UnsubscribeList $unsubscribeList, $email) {

		/* @var $httpRequest Request */
		$httpRequest = $this->request->getHttpRequest();
		$arguments = $httpRequest->getUri()->getArguments();

		if (!isset($arguments['hmac'])) {
			return '<h1>Error: HMAC needed to unsubscribe a person.</h1>';
		}

		$actualHmac = $arguments['hmac'];

		$uriWithoutHmac = str_replace('&hmac=' . $actualHmac, '', (string)$httpRequest->getUri());


		$expectedHmac = hash_hmac('sha1', $uriWithoutHmac, $this->hmacUrlSecret);

		if ($expectedHmac !== $actualHmac) {
			return '<h1>Error: Could not unsubscribe this email address.</h1>(Hash incorrect)';
		}

		$unsubscribeList->unsubscribeEmail($email);

		$this->view->assign('email', $email);
	}
}