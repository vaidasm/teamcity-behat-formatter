<?php

use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\Output\Printer\ConsoleOutputPrinter;
use Behat\Behat\Tester\Result\ExecutedStepResult;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Output\Printer\OutputPrinter;

/**
 * Class TeamCityFormatter
 * @package tests\features\formatter
 */
class TeamCityFormatter implements Formatter
{
    /**
     * @var array
     */
    private $parameters;

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return array(
            'tester.feature_tested.before'=>'onBeforeFeatureTested',
            'tester.feature_tested.after'=>'onAfterFeatureTested',
            'tester.scenario_tested.before'=>'onBeforeScenarioTested',
            'tester.scenario_tested.after'=>'onAfterScenarioTested',
            'tester.step_tested.before'=>'onBeforeStepTested',
            'tester.step_tested.after'=>'onAfterStepTested',
        );
    }

    /**
     * Returns formatter name.
     *
     * @return string
     */
    public function getName()
    {
        return "teamcity";
    }

    /**
     * Returns formatter description.
     *
     * @return string
     */
    public function getDescription()
    {
        return "Formatter for teamcity";
    }

    /**
     * Returns formatter output printer.
     *
     * @return OutputPrinter
     */
    public function getOutputPrinter()
    {
        return new ConsoleOutputPrinter();
    }

    /**
     * Sets formatter parameter.
     *
     * @param string $name
     * @param mixed $value
     */
    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    /**
     * Returns parameter name.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getParameter($name)
    {
        return $this->parameters[$name];
    }

    public function onBeforeFeatureTested(BeforeFeatureTested $event)
    {
        $this->printEvent("testSuiteStarted", ['name'=>$event->getFeature()->getTitle()]);
    }

    public function onAfterFeatureTested(BeforeFeatureTested $event)
    {
        $this->printEvent("testSuiteFinished", ['name'=>$event->getFeature()->getTitle()]);
    }

    public function onBeforeScenarioTested(BeforeScenarioTested $event)
    {
        $this->printEvent("testStarted", ['name'=>$event->getScenario()->getTitle()]);
    }

    public function onAfterScenarioTested(AfterScenarioTested $event)
    {
        if(!$event->getTestResult()->isPassed()) {
            $this->printEvent("testFailed", ['name'=>$event->getScenario()->getTitle()]);
        }
        $this->printEvent("testFinished", ['name'=>$event->getScenario()->getTitle()]);
    }

    public function onAfterStepTested(AfterStepTested $event)
    {
        $result = $event->getTestResult();

        if($result instanceof ExecutedStepResult) {
            $exception = $result->getException();
            if($exception) {
                $this->printEvent("testStdErr", ['name'=> $exception->getFile(), "out"=> $exception->getMessage()]);
            }
        }

        $this->printEvent("testFinished", ['name'=>$event->getStep()->getText()]);
    }

    /**
     * @param $eventName
     * @param array $params
     */
    public function printEvent($eventName, $params = array())
    {
        self::printText("##teamcity[$eventName");
        foreach ($params as $key => $value) {
            self::printText(" $key='".str_replace("'", "\"", $value)."'");
        }
        self::printText("]\n");
    }

    /**
     * @param $text
     */
    public function printText($text)
    {
        file_put_contents('php://stderr', $text);
    }
}
