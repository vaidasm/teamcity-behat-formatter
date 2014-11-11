<?php namespace Behat\TeamCity;

use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
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
     * @var
     */
    private $name;

    /**
     * @param string $name formatter name
     */
    function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            'tester.feature_tested.before'=>'onBeforeFeatureTested',
            'tester.feature_tested.after'=>'onAfterFeatureTested',
            'tester.scenario_tested.before'=>'onBeforeScenarioTested',
            'tester.scenario_tested.after'=>'onAfterScenarioTested',
            'tester.step_tested.after'=>'onAfterStepTested',
        ];
    }

    /**
     * Returns formatter name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
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

    /**
     * @param BeforeFeatureTested $event
     */
    public function onBeforeFeatureTested(BeforeFeatureTested $event)
    {
        $this->printEvent("testSuiteStarted", ['name'=>$event->getFeature()->getTitle()]);
    }

    /**
     * @param AfterFeatureTested $event
     */
    public function onAfterFeatureTested(AfterFeatureTested $event)
    {
        $this->printEvent("testSuiteFinished", ['name'=>$event->getFeature()->getTitle()]);
    }

    /**
     * @param BeforeScenarioTested $event
     */
    public function onBeforeScenarioTested(BeforeScenarioTested $event)
    {
        $this->printEvent("testStarted", ['name'=>$event->getScenario()->getTitle()]);
    }

    /**
     * @param AfterScenarioTested $event
     */
    public function onAfterScenarioTested(AfterScenarioTested $event)
    {
        if(!$event->getTestResult()->isPassed()) {
            $this->printEvent("testFailed", ['name'=>$event->getScenario()->getTitle()]);
        }
        $this->printEvent("testFinished", ['name'=>$event->getScenario()->getTitle()]);
    }

    /**
     * @param AfterStepTested $event
     */
    public function onAfterStepTested(AfterStepTested $event)
    {
        $result = $event->getTestResult();

        if($result instanceof ExecutedStepResult) {
            $exception = $result->getException();
            if($exception) {
                $this->printEvent("testStdErr", ['name'=> $exception->getFile(), "out"=> $exception->getMessage()]);
            }
        }

        $this->printEvent("testStdOut", ['name'=>$event->getStep()->getText()]);
    }

    /**
     * @param $eventName
     * @param array $params
     */
    public function printEvent($eventName, $params = [])
    {
        $this->printText("##teamcity[$eventName");
        foreach ($params as $key => $value) {
            $this->printText(" $key='".str_replace("'", "\"", $value)."'");
        }
        $this->printText("]\n");
    }

    /**
     * @param $text
     */
    public function printText($text)
    {
        file_put_contents('php://stderr', $text);
    }
}
