<?php

use Behat\Behat\Event\FeatureEvent;
use Behat\Behat\Event\ScenarioEvent;
use Behat\Behat\Event\StepEvent;
use Behat\Behat\Formatter\FormatterInterface;
use Symfony\Component\Translation\Translator;

/**
 * Class TeamCityFormatter
 * @package tests\features\formatter
 */
class TeamCityFormatter implements FormatterInterface
{
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
        $events = array(
            'afterStep',
            'beforeStep',
            'beforeFeature', 'afterFeature',
            'beforeScenario', 'afterScenario',
//            'beforeSuite', 'afterSuite',
//            'beforeScenario',
//            'beforeBackground', 'afterBackground',
//            'beforeOutline', 'afterOutline',
//            'beforeOutlineExample', 'afterOutlineExample',
        );

        return array_combine($events, $events);
    }

    /**
     * @param StepEvent $event
     */
    public function beforeStep(StepEvent $event)
    {
        $this->printEvent('message', [
            'text'=>$event->getStep()->getText()
        ]);
    }

    /**
     * @param StepEvent $event
     */
    public function afterStep(StepEvent $event)
    {
        $params = [
            'name'=>$event->getStep()->getParent()->getTitle(),
        ];
        if($event->getResult() == StepEvent::FAILED) {
            $this->printEvent('testFailed', $params);
        } elseif ($event->getResult() == StepEvent::PENDING) {
            $this->printEvent('testIgnored', $params);
        } elseif ($event->getResult() == StepEvent::SKIPPED) {
            $this->printEvent('testIgnored', $params);
        } elseif ($event->getResult() == StepEvent::UNDEFINED) {
            $this->printEvent('testFailed', $params);
        }
    }

    /**
     * @param FeatureEvent $event
     */
    public function beforeFeature(FeatureEvent $event)
    {
        $fileName = $event->getFeature()->getFile();
        $this->printEvent("testSuiteStarted", [
            "name"=>$event->getFeature()->getTitle(),
            "locationHint"=>"file://$fileName",
        ]);
    }

    /**
     * @param FeatureEvent $event
     */
    public function afterFeature(FeatureEvent $event)
    {
        $fileName = $event->getFeature()->getFile();
        $this->printEvent("testSuiteFinished", [
            "name"=>$event->getFeature()->getTitle(),
            "locationHint"=>"file://$fileName",
        ]);
    }

    /**
     * @param ScenarioEvent $event
     */
    public function beforeScenario(ScenarioEvent $event)
    {
        $fileName = $event->getScenario()->getFile();
        $this->printEvent("testStarted", [
            "name"=>$event->getScenario()->getTitle(),
            "locationHint"=>"file://$fileName",
            "captureStandardOutput"=>"true"
        ]);
    }

    /**
     * @param ScenarioEvent $event
     */
    public function afterScenario(ScenarioEvent $event)
    {
        $fileName = $event->getScenario()->getFile();
        $this->printEvent("testFinished", [
            "name"=>$event->getScenario()->getTitle(),
            "locationHint"=>"file://$fileName",
        ]);
    }

    /**
     * Set formatter translator.
     *
     * @param Translator $translator
     */
    public function setTranslator(Translator $translator)
    {
        // ignore
    }

    /**
     * Checks if current formatter has parameter.
     *
     * @param string $name
     *
     * @return Boolean
     */
    public function hasParameter($name)
    {
        return false;
    }

    /**
     * Sets formatter parameter.
     *
     * @param string $name
     * @param mixed $value
     */
    public function setParameter($name, $value)
    {
        // ignore
    }

    /**
     * Returns parameter value.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getParameter($name)
    {
        return null;
    }

    /**
     * @param $eventName
     * @param array $params
     */
    public static function printEvent($eventName, $params = array())
    {
        self::printText("\n##teamcity[$eventName");
        foreach ($params as $key => $value) {
            self::printText(" $key='$value'");
        }
        self::printText("]\n");
    }

    /**
     * @param $text
     */
    public static function printText($text)
    {
        file_put_contents('php://stderr', $text);
    }
}
