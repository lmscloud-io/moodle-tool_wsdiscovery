@tool @tool_wsdiscovery
Feature: Basic tests for Web service discovery

  @javascript
  Scenario: Plugin tool_wsdiscovery appears in the list of installed additional plugins
    Given I log in as "admin"
    When I navigate to "Plugins > Plugins overview" in site administration
    And I follow "Additional plugins"
    Then I should see "Web service discovery"
    And I should see "tool_wsdiscovery"
