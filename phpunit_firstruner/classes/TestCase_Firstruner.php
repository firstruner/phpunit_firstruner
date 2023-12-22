<?php
/*
 * This file is part of PHPUnit extension.
 *
 * (c) Firstruner <contact@firstruner.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\UnitTestAnnotation;
use Firstruner\Commons\CLI\cli_colors;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestResult;

class TestCase_Firstruner extends TestCase
{
      /**
       * @var null|list<ExecutionOrderDependency>
       */
      public $providedTests;

      // Méthode appelée une seule fois avant le début de la classe de test
      public static function setUpBeforeClass(): void
      {
            $maintitle = TestCase_Firstruner::genMainTitle(get_called_class());

            echo cli_colors::GetColoredText(
                  ($maintitle == ""
                        ? cli_reset
                        : cli_unittest),
                  $maintitle
            ) . PHP_EOL;

            parent::setUpBeforeClass();
      }

      private function searchAnnoInAnnots($annots)
      {
            foreach ($annots as $annot)
                  if ($annot instanceof UnitTestAnnotation)
                        return $annot;

            return null;
      }

      private function getClassUTAnnotations()
      {
            $annots = (new AnnotationReader())
                  ->getClassAnnotations(new ReflectionClass($this));

            return $this->searchAnnoInAnnots($annots);
      }

      private static function genMainTitle($class)
      {
            $annots = (new $class())->getClassUTAnnotations();
            return $annots == null ? "" : $annots->GenerateTitle();
      }

      private function getMethodAnnotations($methodName)
      {
            $prop = (new ReflectionClass($this))->getMethod($methodName);

            return (new AnnotationReader())->getMethodAnnotations(
                  $prop,
                  $this
            );
      }

      public function GenMethodTitle($methodName)
      {
            $annots = $this->getMethodAnnotations($methodName);
            return ($this->searchAnnoInAnnots($annots))->GenerateTitle();
      }

      public function GenMethodDescription($methodName)
      {
            $annots = $this->getMethodAnnotations($methodName);
            return ($this->searchAnnoInAnnots($annots))->GetDescription();
      }

      public function GetMethodTimeLimit($methodName)
      {
            $annots = $this->getMethodAnnotations($methodName);
            return ($this->searchAnnoInAnnots($annots))->executionTimeLimit;
      }

      public function getClassMemoryLimit()
      {
            $annots = $this->getClassUTAnnotations();
            return $annots == null ? 0 : $annots->memoryLimit;
      }

      public function getClassTimeLimit()
      {
            $annots = $this->getClassUTAnnotations();
            return $annots == null ? 0 : $annots->executionTimeLimit;
      }

      public function genStaticMainTitle()
      {
            return TestCase_Firstruner::genMainTitle($this);
      }

      public function run(TestResult $result = null): TestResult
      {
            //echo "Run-start";
            $this->setPreserveGlobalState(false);
            $result = parent::run($result);
            //echo "Run-end";
            return $result;
      }
}