<?php
/**
 * Feature Context.
 *
 * @package    WordPress
 * @author     David Perez <david@close.technology>
 * @copyright  2024 CLOSE
 */

namespace CLOSE\FormsCRM\Behat_Utils;

defined( 'ABSPATH' ) || exit;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends MinkContext implements Context {
    /**
     * @Given I am on the homepage
     */
    public function iAmOnTheHomepage()
    {
        $this->visitPath('/');
    }

    /**
     * @Then I should see :text
     */
    public function iShouldSee($text)
    {
        $this->assertPageContainsText($text);
    }
}

