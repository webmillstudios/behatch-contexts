<?php

namespace Sanpi\Behatch\Context;

use Behat\Behat\Context\Step;
use Behat\Gherkin\Node\TableNode;
use Behat\Gherkin\Node\PyStringNode;
use PHPUnit_Framework_ExpectationFailedException as AssertException;

class RestContext extends BaseContext
{
    /**
     * @Given /^I send a (GET|POST|PUT|DELETE|OPTION) request on "(?P<url>[^"]*)"$/
     */
    public function iSendARequestOn($method, $url)
    {
        $client = $this->getSession()->getDriver()->getClient();

        // intercept redirection
        $client->followRedirects(false);

        $client->request($method, $this->locatePath($url));
        $client->followRedirects(true);
    }

    /**
     * @Given /^I send a (GET|POST|PUT|DELETE|OPTION) request on "(?P<url>[^"]*)" with parameters:$/
     */
    public function iSendARequestOnWithParameters($method, $url, TableNode $datas)
    {
        $client = $this->getSession()->getDriver()->getClient();

        // intercept redirection
        $client->followRedirects(false);

        $parameters = array();
        foreach ($datas->getHash() as $row) {
            if (!isset($row['key']) || !isset($row['value'])) {
                throw new \Exception("You must provide a 'key' and 'value' column in your table node.");
            }
            $parameters[$row['key']] = $row['value'];
        }

        $client->request($method, $this->locatePath($url), $parameters);
        $client->followRedirects(true);
    }

    /**
     * @When /^I send a (GET|POST|PUT|DELETE|OPTION) request on "(?P<url>[^"]*)" with body:$/
     */
    public function iSendARequestOnWithBody($method, $url, PyStringNode $body)
    {
        $client = $this->getSession()->getDriver()->getClient();

        // intercept redirection
        $client->followRedirects(false);

        $client->request($method, $this->locatePath($url),
            array(), array(), array(), $body->getRaw());
        $client->followRedirects(true);
    }

    /**
     * @Then /^the response should be equal to:$/
     */
    public function theResponseShouldBeEqualTo(PyStringNode $expected)
    {
        $expected = str_replace('\\"', '"', $expected);
        $actual   = $this->getSession()->getPage()->getContent();

        try {
            assertEquals($expected, $actual);
        } catch (AssertException $e) {
            $message = sprintf('The string "%s" is not equal to the response of the current page', $expected);
            throw new \Behat\Mink\Exception\ExpectationException($message, $this->getSession(), $e);
        }
    }

    /**
     * @Then /^the header "(?P<name>[^"]*)" should be equal to "(?P<value>[^"]*)"$/
     */
    public function theHeaderShouldBeEqualTo($name, $value)
    {
        $header = $this->getSession()->getResponseHeaders();

        assertArrayHasKey($name, $header,
            sprintf('The header "%s" doesn\'t exist', $name)
        );
        assertEquals($expected, $header[$name],
            sprintf('The header "%s" is not equal to "%s"', $name, $value)
        );
    }

    /**
     * @Then /^the header "(?P<name>[^"]*)" should be contains "(?P<value>[^"]*)"$/
     */
    public function theHeaderShouldBeContains($name, $value)
    {
        $header = $this->getSession()->getResponseHeaders();

        assertArrayHasKey($name, $header,
            sprintf('The header "%s" doesn\'t exist', $name)
        );
        assertContains($expected, $header[$name],
            sprintf('The header "%s" is doesn\'t contain to "%s"', $name, $value)
        );
    }

    /**
     * @Then /^the header "(?P<name>[^"]*)" should not exist$/
     */
    public function theHeaderNotShouldExist($name)
    {
        $header = $this->getSession()->getResponseHeaders();

        assertArrayNotHasKey($name, $header,
            sprintf('The header "%s" exist', $name)
        );
    }

    /**
     * @Then /^I add "(?P<name>[^"]*)" header equal to "(?P<value>[^"]*)"$/
     */
    public function iAddHeaderEqualTo($name, $value)
    {
        $this->getSession()->getDriver()->getClient()->setServerParameter($name, $value);
    }

    /**
     * @Then /^the response should be encoded in "(?P<encoding>[^"]*)"$/
     */
    public function theResponeShouldBeEncodedIn($encoding)
    {
        $content = $this->getSession()->getPage()->getContent();
        if (!mb_check_encoding($content, $encoding)) {
            throw new \Exception("The response is not encoded in $encoding");
        }

        return array(
            new Step\Then('the header "Content-Type" should be contains "charset=' . $encoding . '"'),
        );
    }

    /**
     * @see Behat\MinkExtension\Context\MinkContext::locatePath()
     */
    protected function locatePath($path)
    {
        $startUrl = rtrim($this->getMinkParameter('base_url'), '/') . '/';

        return 0 !== strpos($path, 'http') ? $startUrl . ltrim($path, '/') : $path;
    }

}
