<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Firstruner <contact@firstruner.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace PHPUnit\Framework;

use const PHP_EOL;
use function array_keys;
use function array_map;
use function array_merge;
use function array_slice;
use function array_unique;
use function basename;
use function call_user_func;
use function class_exists;
use function count;
use function dirname;
use function get_declared_classes;
use function implode;
use function is_bool;
use function is_callable;
use function is_file;
use function is_object;
use function is_string;
use function method_exists;
use function preg_match;
use function preg_quote;
use function sprintf;
use function strpos;
use function substr;
use Iterator;
use IteratorAggregate;
use PHPUnit\Runner\BaseTestRunner;
use PHPUnit\Runner\Filter\Factory;
use PHPUnit\Runner\PhptTestCase;
use PHPUnit\Util\FileLoader;
use PHPUnit\Util\Reflection;
use PHPUnit\Util\Test as TestUtil;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Throwable;
use PHPUnit\Framework\TestSuite;

/**
 * @template-implements IteratorAggregate<int, Test>
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
class TestSuite_Firstruner extends TestSuite
{
    /**
    * @var null|list<ExecutionOrderDependency>
    */
    public $providedTests;

    /**
    * @var bool
    */
    private $beStrictAboutChangesToGlobalState;

    public function getProvidedTests()
    {
        return $this->providedTests;
    }

    /**
     * Constructs a new TestSuite.
     *
     *   - PHPUnit\Framework\TestSuite() constructs an empty TestSuite.
     *
     *   - PHPUnit\Framework\TestSuite(ReflectionClass) constructs a
     *     TestSuite from the given class.
     *
     *   - PHPUnit\Framework\TestSuite(ReflectionClass, String)
     *     constructs a TestSuite from the given class with the given
     *     name.
     *
     *   - PHPUnit\Framework\TestSuite(String) either constructs a
     *     TestSuite from the given class (if the passed string is the
     *     name of an existing class) or constructs an empty TestSuite
     *     with the given name.
     *
     * @param ReflectionClass|string $theClass
     *
     * @throws Exception
     */
    public function __construct(TestSuite $testSuite)//$theClass = '', string $name = '')
    {
        parent::__construct($testSuite->getName(), $testSuite->getName());
    }

    /**
     * Runs the tests and collects their result in a TestResult.
     *
     * @throws \PHPUnit\Framework\CodeCoverageException
     * @throws \SebastianBergmann\CodeCoverage\InvalidArgumentException
     * @throws \SebastianBergmann\CodeCoverage\UnintentionallyCoveredCodeException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @throws Warning
     */
    public function run(TestResult $result = null): TestResult
    {
        if ($result === null) {
            $result = $this->createResult();
        }

        if (count($this) === 0) {
            return $result;
        }

        /** @psalm-var class-string $className */
        $className   = $this->name;
        $hookMethods = TestUtil::getHookMethods($className);

        $result->startTestSuite($this);

        $test = null;

        if ($this->testCase && class_exists($this->name, false)) {
            try {
                foreach ($hookMethods['beforeClass'] as $beforeClassMethod) {
                    if (method_exists($this->name, $beforeClassMethod)) {
                        if ($missingRequirements = TestUtil::getMissingRequirements($this->name, $beforeClassMethod)) {
                            $this->markTestSuiteSkipped(implode(PHP_EOL, $missingRequirements));
                        }

                        call_user_func([$this->name, $beforeClassMethod]);
                    }
                }
            } catch (SkippedTestError|SkippedTestSuiteError $error) {
                foreach ($this->tests() as $test) {
                    $result->startTest($test);
                    $result->addFailure($test, $error, 0);
                    $result->endTest($test, 0);
                }

                $result->endTestSuite($this);

                return $result;
            } catch (Throwable $t) {
                $errorAdded = false;

                foreach ($this->tests() as $test) {
                    if ($result->shouldStop()) {
                        break;
                    }

                    $result->startTest($test);

                    if (!$errorAdded) {
                        $result->addError($test, $t, 0);

                        $errorAdded = true;
                    } else {
                        $result->addFailure(
                            $test,
                            new SkippedTestError('Test skipped because of an error in hook method'),
                            0,
                        );
                    }

                    $result->endTest($test, 0);
                }

                $result->endTestSuite($this);

                return $result;
            }
        }

        foreach ($this as $test) {
            if ($result->shouldStop()) {
                break;
            }

            if ($test instanceof TestCase || $test instanceof self) {
                $test->setBeStrictAboutChangesToGlobalState($this->beStrictAboutChangesToGlobalState);
                $test->setBackupGlobals($this->backupGlobals);
                $test->setBackupStaticAttributes($this->backupStaticAttributes);
                $test->setRunTestInSeparateProcess($this->runTestInSeparateProcess);
            }

            $test->run($result);
        }

        if ($this->testCase && class_exists($this->name, false)) {
            foreach ($hookMethods['afterClass'] as $afterClassMethod) {
                if (method_exists($this->name, $afterClassMethod)) {
                    try {
                        call_user_func([$this->name, $afterClassMethod]);
                    } catch (Throwable $t) {
                        $message = "Exception in {$this->name}::{$afterClassMethod}" . PHP_EOL . $t->getMessage();
                        $error   = new SyntheticError($message, 0, $t->getFile(), $t->getLine(), $t->getTrace());

                        $placeholderTest = clone $test;
                        $placeholderTest->setName($afterClassMethod);

                        $result->startTest($placeholderTest);
                        $result->addFailure($placeholderTest, $error, 0);
                        $result->endTest($placeholderTest, 0);
                    }
                }
            }
        }

        $result->endTestSuite($this);

        return $result;
    }

    /**
    * @param bool $beStrictAboutChangesToGlobalState
    */
    public function setBeStrictAboutChangesToGlobalState($beStrictAboutChangesToGlobalState): void
    {
        if (null === $this->beStrictAboutChangesToGlobalState && is_bool($beStrictAboutChangesToGlobalState)) {
            $this->beStrictAboutChangesToGlobalState = $beStrictAboutChangesToGlobalState;
        }
    }
}
