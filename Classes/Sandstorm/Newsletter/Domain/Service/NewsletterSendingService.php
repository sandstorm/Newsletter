<?php
namespace Sandstorm\Newsletter\Domain\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Sandstorm\Newsletter\Domain\Model\Newsletter;
use Sandstorm\Newsletter\TypoScriptObjects\ReplacePlaceholdersInLiveImplementation;
use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class NewsletterSendingService {


	/**
	 * @Flow\InjectConfiguration("sendingApiUri")
	 * @var string
	 */
	protected $sendingApiUri;

	/**
	 * @Flow\InjectConfiguration("auth_token")
	 * @var string
	 */
	protected $authToken;

	/**
	 * @Flow\InjectConfiguration("hmacUrlSecret")
	 * @var string
	 */
	protected $hmacUrlSecret;


	/**
	 * @param Newsletter $newsletter
	 */
	public function sendNewsletter(Newsletter $newsletter, $languageKey = NULL) {
		$client = new Client();

		try {


			$bodyTemplate = $this->convertTemplatesToGoTemplates($newsletter->getHtmlContent());
			$bodyTemplate = str_replace('href="SHOWINBROWSER"', '{{ .showInBrowser }}', $bodyTemplate);
			$bodyTemplate = str_replace('href="UNSUBSCRIBE"', '{{ .unsubscribeUri }}', $bodyTemplate);
			$placeholdersInBodyTemplate = $this->foundPlaceholdersInLastProcessedTemplate;

			$senderEmailTemplate = $this->convertTemplatesToGoTemplates($newsletter->getSenderEmailTemplate() ?: 'newsletter@yourdomain.com');

			$replyToEmailTemplate = $senderEmailTemplate;
			if ($newsletter->getReplyToEmailTemplate()) {
				$replyToEmailTemplate = $this->convertTemplatesToGoTemplates($newsletter->getReplyToEmailTemplate());
			}


			$request = array(
				'RecipientsList' => $newsletter->getReceiverGroup()->getCacheFileName($languageKey),
				'Templates' => array(
					'SubjectTemplate' => $this->convertTemplatesToGoTemplates($newsletter->getSubject()),
					'BodyTemplate' => $bodyTemplate,

					'ReceiverEmailTemplate' => $this->convertTemplatesToGoTemplates($newsletter->getReceiverEmailTemplate() ?: '{email}'),
					'ReceiverNameTemplate' => $this->convertTemplatesToGoTemplates($newsletter->getReceiverNameTemplate() ?: '{firstName} {lastName}'),

					'SenderEmailTemplate' => $senderEmailTemplate,
					'SenderNameTemplate' => $this->convertTemplatesToGoTemplates($newsletter->getSenderNameTemplate() ?: 'Newsletter'),

					'ReplyToEmailTemplate' => $replyToEmailTemplate,
					'LinkTemplates' => array(
						'unsubscribeUri' => array(
							'EncryptionKey' => $this->hmacUrlSecret,
							'BaseLink' => $newsletter->getUnsubscribeLink(),

							// TODO: this the only place where the "email" parameter is currently hardcoded. at all other places, it is configurable.
							'Parameters' =>  ['email'],
						),
						'showInBrowser' => array(
							'EncryptionKey' => $this->hmacUrlSecret,
							'BaseLink' => $newsletter->getNewsletterLink(),
							'Parameters' => $placeholdersInBodyTemplate,
						)
					)
				)
			);

			$blacklistFileName = $newsletter->getReceiverGroup()->getUnsubscribeList()->getUnsubscribeFileName();
			if (file_exists($blacklistFileName)) {
				$request['Blacklist'] = $blacklistFileName;
			}


			$response = $client->post(rtrim($this->sendingApiUri, '/') . '/' . $this->authToken . '/newsletter/' . $newsletter->getIdentifier() . '/send', array(
				'body' => json_encode($request))
			);
		}  catch (RequestException $e) {
			$errorMessage = 'There was an error communicating with the newsletter API -- HTTP Status Code: ' . $e->getCode();
			if ($e->hasResponse()) {
				$errorMessage .= "\nResponse Body:\n" . $e->getResponse()->getBody()->__toString();
			}
			throw new \Exception($errorMessage, 1431428827, $e);
		}
	}

	protected $foundPlaceholdersInLastProcessedTemplate;

	private function convertTemplatesToGoTemplates($htmlTemplate) {

		$elements = array();
		$result = preg_replace_callback(ReplacePlaceholdersInLiveImplementation::PLACEHOLDER_REGEX, function($element) use(&$elements) {
			$elements[] = $element[1];
			return '{{.' . $element[1] . '}}';
		}, $htmlTemplate);

		$this->foundPlaceholdersInLastProcessedTemplate = $elements;

		return $result;
	}

	/**
	 * @param array $newsletterIdentifiers
	 * @return array
	 */
	public function getStatus(array $newsletterIdentifiers) {
		$client = new Client();

		try {
			$response = $client->get(rtrim($this->sendingApiUri, '/') . '/' . $this->authToken . '/newsletter/status?jobIds=' . implode(',', $newsletterIdentifiers));
		}  catch (RequestException $e) {
			$errorMessage = 'There was an error communicating with the newsletter API -- HTTP Status Code: ' . $e->getCode();
			if ($e->hasResponse()) {
				$errorMessage .= "\nResponse Body:\n" . $e->getResponse()->getBody()->__toString();
			}

			if (strpos('connection refused', $errorMessage) !== -1) {
				return array('error' => 'Connection to the newsletter sending backend could not be established.');
			}
			throw new \Exception($errorMessage, 1431428827, $e);
		}
		return json_decode($response->getBody()->__toString(), TRUE);
	}

	/**
	 * @return array
	 */
	public function getConfiguration() {
		$client = new Client();

		try {
			$response = $client->get(rtrim($this->sendingApiUri, '/') . '/' . $this->authToken . '/newsletter/serverConfiguration');
		}  catch (RequestException $e) {
			$errorMessage = 'There was an error communicating with the newsletter API -- HTTP Status Code: ' . $e->getCode();
			if ($e->hasResponse()) {
				$errorMessage .= "\nResponse Body:\n" . $e->getResponse()->getBody()->__toString();
			}

			if (strpos('connection refused', $errorMessage) !== -1) {
				return array('error' => 'Connection to the newsletter sending backend could not be established.');
			}
			throw new \Exception($errorMessage, 1431428827, $e);
		}
		return json_decode($response->getBody()->__toString(), TRUE);
	}

	public function cancel(array $newsletterIdentifiers) {
		$client = new Client();

		$responses = array();
		foreach ($newsletterIdentifiers as $newsletterIdentifier) {
			try {
				$response = $client->delete(rtrim($this->sendingApiUri, '/') . '/' . $this->authToken . '/newsletter/' . $newsletterIdentifier . '/abortAndRemove');
				$responses[$newsletterIdentifier] = json_decode($response->getBody()->__toString(), TRUE);
			}  catch (RequestException $e) {
				$errorMessage = 'There was an error communicating with the newsletter API -- HTTP Status Code: ' . $e->getCode();
				if ($e->hasResponse()) {
					$errorMessage .= "\nResponse Body:\n" . $e->getResponse()->getBody()->__toString();
				}

				if (strpos('connection refused', $errorMessage) !== -1) {
					$responses[$newsletterIdentifier] = array('error' => 'Connection to the newsletter sending backend could not be established.');
				} else {
					$responses[$newsletterIdentifier] = array('error' => $errorMessage);
				}
			}
		}

		return $responses;
	}

	/**
	 * @param array $newsletterIdentifiers
	 * @return string errorFileName
	 */
	public function getFailures(array $newsletterIdentifiers) {

		$client = new Client();

		try {
			$targetFile = sys_get_temp_dir() . '/' . uniqid('newsletter-failures') . '.csv';
			$response = $client->get(rtrim($this->sendingApiUri, '/') . '/' . $this->authToken . '/newsletter/sendingFailures', array(
				'query' => array(
					'jobIds' => implode(',', $newsletterIdentifiers),
					'targetFile' => $targetFile
				)
			));
			json_decode($response->getBody()->__toString(), TRUE);

			return $targetFile;
		}  catch (RequestException $e) {
			$errorMessage = 'There was an error communicating with the newsletter API -- HTTP Status Code: ' . $e->getCode();
			if ($e->hasResponse()) {
				$errorMessage .= "\nResponse Body:\n" . $e->getResponse()->getBody()->__toString();
			}

			if (strpos('connection refused', $errorMessage) !== -1) {
				throw new \Exception('Connection to the newsletter sending backend could not be established.', $e);
			} else {
				throw $e;
			}
		}

	}
}