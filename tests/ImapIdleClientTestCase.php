<?php
abstract class ImapIdleClientTestCase extends \PHPUnit_Framework_TestCase
{
    protected $unused = null;

    /**
     *
     * @param PHPUnit_Framework_MockObject_Builder_InvocationMocker $target
     * @return PHPUnit_Framework_MockObject_Stub_ReturnCallback
     */
    protected function verifyAfter($target)
    {
        $matcher = $target->getMatcher();
        return $this->returnCallback(function () use ($matcher) {
            $matcher->verify();
        });
    }

    protected function verifyConsecutiveCalls(array $constraints)
    {
        return new TestCase_ConsecutiveCallsMap($constraints);
    }

    protected function arrayHasKeyValuePair($key, $value, $ignoreCaseOfKey = false) {
        return $this->callback(function ($p) use ($key, $value, $ignoreCaseOfKey) {
            if ($ignoreCaseOfKey) {
                $p = array_change_key_case($p, CASE_LOWER);
                $key = strtolower($key);
            }
            if (!array_key_exists($key, $p)) {
                return false;
            }

            if ($value instanceof \PHPUnit_Framework_Constraint) {
                return $value->evaluate($p[$key], '', true);
            }
            return ($p[$key] == $value);
        });
    }
}

/**
 * @author Akihiro Yamanoi <akihiro.yamanoi@gmail.com>
 */
class TestCase_ConsecutiveCallsMap extends \PHPUnit_Framework_MockObject_Stub_ConsecutiveCalls
{
    protected $constraints;

    public function __construct($constraints)
    {
        $this->constraints = array();

        $stack = array();
        foreach ($constraints as $pairs) {
            $stub = null;

            if (!is_array($pairs)) {
                $pairs = array($pairs);
            }

            if (end($pairs) instanceof \PHPUnit_Framework_MockObject_Stub) {
                $stub = array_pop($pairs);
            }
            $stack[] = $stub;
            $this->constraints[] = $pairs;
        }
        parent::__construct($stack);
    }

    public function invoke(\PHPUnit_Framework_MockObject_Invocation $invocation)
    {
        if (!count($this->constraints)) {
            return null;
        }

        $expected = array_shift($this->constraints);
        $matcher = new \PHPUnit_Framework_MockObject_Matcher_Parameters($expected);
        $matcher->matches($invocation);

        return parent::invoke($invocation);
    }
}
