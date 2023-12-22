<?php
/*
 * This file is part of PHPUnit.
 *
 * (c) Firstruner <contact@firstruner.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Firstruner\Commons\CLI\cli_colors;
use PHPUnit\TextUI\DefaultResultPrinter;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestResult;
use PHPUnit\Framework\Warning;
use PHPUnit\Runner\PhptTestCase;
use SebastianBergmann\Timer\ResourceUsageFormatter_Firstruner;
use SebastianBergmann\Timer\Timer;

define("result_Success", "☻");
define("result_Error", "►");
define("result_Fail", "♠");
define("result_Warn", "☼");
define("result_Incomplete", "♦");
define("result_Risky", "◘");
define("result_Skip", "◊");
define("result_Description", "░");

class ResultPrinter_firstruner extends DefaultResultPrinter
{
      /**
       * @var Timer
       */
      private $timer;

      /**
       * @var CurrentTest
       */
      private $currentTest = null;

      public function __construct($out = null, bool $verbose = false, string $colors = self::COLOR_DEFAULT, bool $debug = false, $numberOfColumns = 80, bool $reverse = false)
      {
            parent::__construct($out, $verbose, $colors, $debug, $numberOfColumns, $reverse);
            $this->timer = new Timer;

            $this->timer->start();
      }

      private function GetMethodName($classModel)
      {
            $reflection = new \ReflectionClass($classModel);

            $classname = $reflection->getProperty('className');
            $classname->setAccessible(true);
            $methodname = $reflection->getProperty('methodName');
            $methodname->setAccessible(true);

            $class = $classname->getValue($classModel);
            $instance = new $class();

            $titleAnnotation = $instance->GenMethodTitle($methodname->getValue($classModel));
            $descriptionAnnotation = $instance->GenMethodDescription($methodname->getValue($classModel));

            return
                  ((isset($titleAnnotation) && ($titleAnnotation != ""))
                        ? $titleAnnotation . " ("
                        : "") .
                  $classname->getValue($classModel) . "/" . $methodname->getValue($classModel) .
                  ((isset($titleAnnotation) && ($titleAnnotation != ""))
                        ? ")"
                        : "") .
                  ((isset($descriptionAnnotation) && ($descriptionAnnotation != ""))
                        ? PHP_EOL . "      " . result_Description . " " . $descriptionAnnotation
                        : "") .
                  PHP_EOL . "      → ";
      }

      private function getClass($class)
      {
            $reflection = new \ReflectionClass($class);
            $classname = $reflection->getProperty('className');
            $classname->setAccessible(true);
            $instance = $classname->getValue($class);

            return new $instance();
      }

      private function GetClassName($element)
      {
            return ($this->getClass($element))->genStaticMainTitle();
      }

      private function GetClassMemoryLimit($element)
      {
            return ($this->getClass($element))->getClassMemoryLimit();
      }
      
      private function GetClassTimeLimit($element)
      {
            return ($this->getClass($element))->getClassTimeLimit();
      }

      protected function printHeader(TestResult $result): void
      {
            $memoryLimit = $this->GetClassMemoryLimit($this->currentTest->providedTests[0]);
            $timeLimit = $this->GetClassTimeLimit($this->currentTest->providedTests[0]);

            if (count($result) > 0) {
                  $this->write(PHP_EOL . PHP_EOL .
                        (new ResourceUsageFormatter_Firstruner)->resourceUsage(
                              $this->timer->stop(),
                              $memoryLimit,
                              $timeLimit
                        )
                        . PHP_EOL . PHP_EOL);
            }
      }

      protected function writeProgress(string $progress): void
      {
            if ($this->debug) {
                  return;
            }

            $this->write($progress);
            $this->column++;
            $this->numTestsRun++;

            if ($this->column == $this->maxColumn || $this->numTestsRun == $this->numTests)
                  $this->lastestTest();
      }

      private function lastestTest()
      {
            echo PHP_EOL .
                  cli_colors::GetColoredText(
                        ($this->GetClassName($this->currentTest->providedTests[0]) == ""
                              ? cli_reset
                              : cli_unittest),
                        $this->GetClassName($this->currentTest->providedTests[0]) . '     ' .
                              $this->numTestsRun . ' / ' .
                              $this->numTests . ' (' .
                              floor(($this->numTestsRun / $this->numTests) * 100) .
                              '%)'
                  );
      }

      protected function printDefects(array $defects, string $type): void
      {
            $count = count($defects);

            if ($count == 0) return;

            $this->write("  ► " .
                  cli_colors::GetColoredText(
                        cli_unittest_memoryexceed,
                        "There " .
                              (($count == 1) ? "was " : "were ") .
                              $count .  " " .
                              $type .
                              (($count == 1) ? "" : "s")
                  ),
            );

            $i = 1;

            $this->defectListPrinted = true;
      }

      /**
       * A test ended.
       */
      public function endTest(Test $test, float $time): void
      {
            $this->currentTest = $test;

            if ($this->debug) {
                  $this->write(
                        sprintf(
                              "Test '%s' ended\n",
                              \PHPUnit\Util\Test::describeAsString($test),
                        ),
                  );
            }

            if (!$this->lastTestFailed)
                  $this->addSuccess($test);

            if ($test instanceof TestCase) {
                  $this->numAssertions += $test->getNumAssertions();
            } elseif ($test instanceof PhptTestCase) {
                  $this->numAssertions++;
            }

            $this->lastTestFailed = false;

            if ($test instanceof TestCase && !$test->hasExpectationOnOutput()) {
                  $this->write($test->getActualOutput());
            }
      }

      private function genResultIcon($icon)
      {
            return "   " . $icon . " ";
      }

      /**
       * A Success occurred.
       */
      public function addSuccess(Test $test): void
      {
            $this->writeProgress(
                  cli_colors::GetColoredText(
                        cli_unittest_success,
                        $this->genResultIcon(result_Success) .
                        $this->GetMethodName($test->providedTests[0]) . 'Success'
                  )
            );
      }

      /**
       * An error occurred.
       */
      public function addError(Test $test, Throwable $t, float $time): void
      {
            $this->writeProgressWithColor(
                  'fg-red, bold',
                  cli_colors::GetColoredText(
                        cli_unittest_error,
                        $this->genResultIcon(result_Error) .
                        $this->GetMethodName($test->providedTests[0]) . 'Error'
                  )
            );
            $this->lastTestFailed = true;
      }

      /**
       * A failure occurred.
       */
      public function addFailure(Test $test, AssertionFailedError $e, float $time): void
      {
            $this->writeProgressWithColor(
                  'bg-red, fg-white',
                  cli_colors::GetColoredText(
                        cli_unittest_fail,
                        $this->genResultIcon(result_Fail) .
                        $this->GetMethodName($test->providedTests[0]) . 'Fail'
                  )
            );
            $this->lastTestFailed = true;
      }

      /**
       * A warning occurred.
       */
      public function addWarning(Test $test, Warning $e, float $time): void
      {
            $this->writeProgressWithColor(
                  'fg-yellow, bold',
                  cli_colors::GetColoredText(
                        cli_unittest_warn,
                        $this->genResultIcon(result_Warn) .
                        $this->GetMethodName($test->providedTests[0]) . 'Warning'
                  )
            );
            $this->lastTestFailed = true;
      }

      /**
       * Incomplete test.
       */
      public function addIncompleteTest(Test $test, Throwable $t, float $time): void
      {
            $this->writeProgressWithColor(
                  'fg-yellow, bold',
                  cli_colors::GetColoredText(
                        cli_unittest_incomplete,
                        $this->genResultIcon(result_Incomplete) .
                        $this->GetMethodName($test->providedTests[0]) . 'Incomplete'
                  )
            );
            $this->lastTestFailed = true;
      }

      /**
       * Risky test.
       */
      public function addRiskyTest(Test $test, Throwable $t, float $time): void
      {
            $this->writeProgressWithColor(
                  'fg-yellow, bold',
                  cli_colors::GetColoredText(
                        cli_unittest_risk,
                        $this->genResultIcon(result_Risky) .
                        $this->GetMethodName($test->providedTests[0]) . 'Risky'
                  )
            );
            $this->lastTestFailed = true;
      }

      /**
       * Skipped test.
       */
      public function addSkippedTest(Test $test, Throwable $t, float $time): void
      {
            $this->writeProgressWithColor(
                  'fg-cyan, bold',
                  cli_colors::GetColoredText(
                        cli_unittest_skip,
                        $this->genResultIcon(result_Skip) .
                        $this->GetMethodName($test->providedTests[0]) . 'Skipped'
                  )
            );
            $this->lastTestFailed = true;
      }
}
