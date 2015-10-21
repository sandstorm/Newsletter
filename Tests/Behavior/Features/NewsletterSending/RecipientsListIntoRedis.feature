Feature: A newsletter list is imported into Redis

  Background:
    Given the test instance is running

  Scenario: Add a comment to a node adds the comment in the user workspace
    Given the recipient list is:
      | email      | firstName | lastProject  | lastProjectId |
      | foo@bar.de | foo       | Some Project | someProject   |
    And the newsletter ID is "foo"
    And the subject is "News from {lastProject}"
    And the recipient email is "{email}"
    And the recipient name is "{firstName} (someCompany)"
    And the sender email is "newsletter-{lastProjectId}@sandstorm.de"
    And the sender name is "Sandstorm Newsletter for {firstName}"
    And the template is:
    """
      Hallo {firstName}
    """
    When I send the newsletter
    Then an E-Mail to "foo@bar.de" was sent with the following content:
      | Body contains | Hallo foo                                                            |
      | Content-Type  | text/html; charset=UTF-8                                             |
      | Subject       | News from Some Project                                               |
      | To            | "foo (someCompany)" <foo@bar.de>                                     |
      | From          | "Sandstorm Newsletter for foo" <newsletter-someProject@sandstorm.de> |
