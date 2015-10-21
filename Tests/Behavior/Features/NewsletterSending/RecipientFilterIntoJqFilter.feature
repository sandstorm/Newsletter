Feature: Recipient Filter

  Scenario: Recipient Filter converted to JQ Filter (Basic)
    When the recipient filter is "project == 'name'"
    Then the JQ filter should be 'select(.project == "name")'

  Scenario: Recipient Filter converted to JQ Filter (Double Quotes)
    When the recipient filter is 'project == "name"'
    Then the JQ filter should be 'select(.project == "name")'

  Scenario: Recipient Filter converted to JQ Filter (OR)
    When the recipient filter is 'project == "name" || project == "foo"'
    Then the JQ filter should be 'select(.project == "name" or .project == "foo")'

  Scenario: Recipient Filter converted to JQ Filter (AND)
    When the recipient filter is 'project == "name" && project == "foo"'
    Then the JQ filter should be 'select(.project == "name" and .project == "foo")'

  Scenario: Recipient Filter converted to JQ Filter (empty)
    When the recipient filter is ''
    Then the JQ filter should be 'select(true)'

  Scenario: Recipient Filter converted to JQ Filter (property exists)
    When the recipient filter is 'foo'
    Then the JQ filter should be 'select(.foo)'