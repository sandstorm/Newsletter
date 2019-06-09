<?php

use Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;
use GuzzleHttp\Client;
use PHPUnit_Framework_Assert as Assert;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;
use TYPO3\Flow\Utility\Files;

/**
 * A trait with shared step definitions for common use by other contexts
 *
 * Note that this trait requires:
 * - $this->objectManager containing the Flow object manager
 */
trait NewsletterTrait {

	/**
	 * @var \Symfony\Component\Process\Process
	 */
	protected $redis;

	/**
	 * @var \Symfony\Component\Process\Process
	 */
	protected $mailer;

	/**
	 * @var \Sandstorm\Newsletter\Domain\Model\Newsletter
	 */
	protected $newsletter;

	/**
	 * @Given /^the test instance is running$/
	 */
	public function theTestInstanceIsRunning() {
		$killMailer = new \Symfony\Component\Process\Process("killall main; killall 'redis-server *:9999'; killall MailHog_darwin_amd64");
		$killMailer->run();

		$fakeMailServerCommand = $this->parameters['mailhogCommand'];
		$fakeMailServer = new \Symfony\Component\Process\Process("$fakeMailServerCommand &> /tmp/mailhog-out");
		$fakeMailServer->start();


		$mailerGoPath = $this->parameters['mailerGoPath'];
		$goCompiler = new \Symfony\Component\Process\Process("cd $mailerGoPath; go build");
		$returnCode = $goCompiler->run();
		if ($returnCode != 0) {
			throw new \Exception('Go Compilation Failed: ' . $goCompiler->getOutput() . $goCompiler->getErrorOutput());
		}

		$redisCommand = $this->parameters['redisCommand'];
		$redisConfig = __DIR__ . '/Fixtures/RedisConfig.conf';
		$this->redis = new \Symfony\Component\Process\Process("$redisCommand $redisConfig");
		$this->redis->start();


		$environment = array(
			'AUTH_TOKEN' => 'A_LONG_RANDOM_STRING',
			'REDIS_URL' => 'localhost:9999',
			'SMTP_URL' => 'localhost:1025'
		);
		$this->mailer = new \Symfony\Component\Process\Process($mailerGoPath . "/main &> /tmp/mailer-out", $mailerGoPath, $environment);
		$this->mailer->start();

		$this->newsletter = new \Sandstorm\Newsletter\Domain\Model\Newsletter();

	}

	/**
	 * @Given /^the recipient list is:$/
	 */
	public function theRecipientListIs(TableNode $table) {
		$content = '';

		$receiverGroup = new \Sandstorm\Newsletter\Domain\Model\ReceiverSource();

		foreach ($table->getHash() as $row) {
			$content .= json_encode($row) . "\n";
		}

		Files::createDirectoryRecursively(dirname($receiverGroup->getCacheFileName()));
		file_put_contents($receiverGroup->getCacheFileName(), $content);
		$this->newsletter->setReceiverGroup($receiverGroup);
	}

	/**
	 * @Given /^the newsletter ID is "([^"]*)"$/
	 */
	public function theNewsletterIdIs($newsletterId) {
		$this->newsletter->setIdentifier($newsletterId);
	}

	/**
	 * @Given /^the template is:$/
	 */
	public function theTemplateIs(PyStringNode $template) {
		$this->newsletter->setHtmlContent($template->getRaw());
	}

	/**
	 * @Given /^the subject is "([^"]*)"$/
	 */
	public function theSubjectIs($subject) {
		$this->newsletter->setSubject($subject);
	}

	/**
	 * @Given /^the recipient email is "([^"]*)"$/
	 */
	public function theRecipientEmailIs($email) {
		$this->newsletter->setReceiverEmailTemplate($email);
	}

	/**
	 * @Given /^the recipient name is "([^"]*)"$/
	 */
	public function theRecipientNameIs($name) {
		$this->newsletter->setReceiverNameTemplate($name);
	}

	/**
	 * @Given /^the sender email is "([^"]*)"$/
	 */
	public function theSenderEmailIs($email) {
		$this->newsletter->setSenderEmailTemplate($email);
	}

	/**
	 * @Given /^the sender name is "([^"]*)"$/
	 */
	public function theSenderNameIs($name) {
		$this->newsletter->setSenderNameTemplate($name);
	}

	/**
	 * @When /^I send the newsletter$/
	 */
	public function iSendTheNewsletter() {
		$this->getNewsletterSendingService()->sendNewsletter($this->newsletter);
	}


	protected $recipientFilter;

	/**
	 * @When /^the recipient filter is "([^"]*)"$/
	 */
	public function theRecipientFilterIs($recipientFilter) {
		$this->recipientFilter = $recipientFilter;
	}

	/**
	 * @When /^the recipient filter is \'([^\']*)\'$/
	 */
	public function theRecipientFilterIs2($recipientFilter) {
		$this->theRecipientFilterIs($recipientFilter);
	}

	/**
	 * @Then /^the JQ filter should be '([^']*)'$/
	 */
	public function theJqFilterShouldBe($jqFilter) {
		$actual = $this->getReceiverGroupGenerationService()->convertFilterIntoJqExpression($this->recipientFilter);
		Assert::assertEquals($jqFilter, $actual, "Mismatch between expected and real JQ filter");
	}


	/**
	 * @Then /^an E-Mail to "([^"]*)" was sent with the following content:$/
	 */
	public function anEmailWasSent($recipient, TableNode $expectedEmailContent) {
		// TODO: DO NOT WAIT; BUT POLL API instead
		sleep(2);
		$client = new Client(array('base_url' => 'http://localhost:8025/api/v2/'));

		// Create a GET request using Relative to base URL
		// URL of the request: http://baseurl.com/api/v1/path?query=123&value=abc)
		$response = $client->get('messages');

		// The newest mail is on top; so we just need $mails[0]
		$mails = json_decode($response->getBody(true), true);

		$to = $mails['items'][0]['To'];
		$actualRecipient = $to[0]['Mailbox'] . '@' . $to[0]['Domain'];

		if ($actualRecipient !== $recipient) {
			throw new \Exception("The recipient name '$actualRecipient' does not match the expected one.");
		}


		foreach ($expectedEmailContent->getRowsHash() as $key => $expectedValue) {
			$isContainsQuery = preg_match('/contains/', $key);
			$key = trim(str_replace('contains', '', $key));

			$actualValue = null;
			switch ($key) {
				case 'Subject':
					$actualValue = $mails['items'][0]['Content']['Headers']['Subject'][0];
					break;
				case 'From':
					$actualValue = $mails['items'][0]['Content']['Headers']['From'][0];
					break;
				case 'To':
					$actualValue = $mails['items'][0]['Content']['Headers']['To'][0];
					break;
				case 'Content-Type':
					$actualValue = $mails['items'][0]['Content']['Headers']['Content-Type'][0];
					Break;
				case 'Body':
					$actualValue = $mails['items'][0]['Content']['Body'];
					Break;
				default:
					throw new Exception("The key $key is not supported.");
			}

			if ($isContainsQuery) {
				if (strpos($actualValue, $expectedValue) === false) {
					throw new \Exception("The field $key does not contain the expected value; actual contents: $actualValue");
				}
			} else {
				// Exact Match
				if (trim($actualValue) !== trim($expectedValue)) {
					throw new \Exception("The field $key does is not equal to the expected value; actual contents: $actualValue");
				}
			}
		}
	}


	/**
	 * @return \Sandstorm\Newsletter\Domain\Service\NewsletterSendingService
	 */
	protected function getNewsletterSendingService() {
		return $this->getObjectManager()->get('Sandstorm\Newsletter\Domain\Service\NewsletterSendingService');
	}

	/**
	 * @return \Sandstorm\Newsletter\Domain\Service\ReceiverGroupGenerationService
	 */
	protected function getReceiverGroupGenerationService() {
		return $this->getObjectManager()->get('Sandstorm\Newsletter\Domain\Service\ReceiverGroupGenerationService');
	}
}
