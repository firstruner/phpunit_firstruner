<?php

declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Firstruner <contact@firstruner.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace PHPUnit\TextUI;

use PHPUnit\TextUI\Command;
use PHPUnit\Framework\TestSuite_Firstruner;
use PHPUnit\TextUI\TestRunner_Firstruner;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 */
class Command_Firstruner extends Command
{
    /**
     * @throws Exception
     */
    public function run(array $argv, bool $exit = true): int
    {
        $this->handleArguments($argv);

        $runner = $this->createRunner_Firstruner();

        if ($this->arguments['test'] instanceof TestSuite_Firstruner) {
            $suite = $this->arguments['test'];
        } else {
            $suite =  $runner->getTest(
                $this->arguments['test'],
                $this->arguments['testSuffixes'],
            );
        }

        if ($this->arguments['listGroups']) {
            return $this->handleListGroups($suite, $exit);
        }

        if ($this->arguments['listSuites']) {
            return $this->handleListSuites($exit);
        }

        if ($this->arguments['listTests']) {
            return $this->handleListTests($suite, $exit);
        }

        if ($this->arguments['listTestsXml']) {
            return $this->handleListTestsXml($suite, $this->arguments['listTestsXml'], $exit);
        }

        unset($this->arguments['test'], $this->arguments['testFile']);

        try {
            $result = $runner->run($suite, $this->arguments, /*$this->warnings*/ [], $exit);
        } catch (Throwable $t) {
            print $t->getMessage() . PHP_EOL;
        }

        $return = TestRunner_Firstruner::FAILURE_EXIT;

        if (isset($result) && $result->wasSuccessful()) {
            $return = TestRunner_Firstruner::SUCCESS_EXIT;
        } elseif (!isset($result) || $result->errorCount() > 0) {
            $return = TestRunner_Firstruner::EXCEPTION_EXIT;
        }

        if ($exit) {
            exit($return);
        }

        return $return;
    }

     /**
     * Create a TestRunner, override in subclasses.
     */
    protected function createRunner_Firstruner(): TestRunner_Firstruner
    {
        return new TestRunner_Firstruner($this->arguments['loader']);
    }
}
