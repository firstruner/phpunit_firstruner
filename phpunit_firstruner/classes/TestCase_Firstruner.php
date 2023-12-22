<?php

use Doctrine\Common\Annotations\AnnotationReader;
use Firstruner\cli_colors;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestResult;
use Doctrine\Common\Annotations\UnitTestAnnotation;

class TestCase_Firstruner extends TestCase
{
      /**
       * @var null|list<ExecutionOrderDependency>
       */
      public $providedTests;

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

      public function getClassMemoryLimit()
      {
            $annots = $this->getClassUTAnnotations();
            return $annots == null ? 0 : $annots->memoryLimit;
      }

      private static function genMainTitle($class)
      {
            $annots = (new $class())->getClassUTAnnotations();
            return $annots == null ? "" : $annots->GenerateTitle();
      }

      public function genStaticMainTitle()
      {
            return TestCase_Firstruner::genMainTitle($this);
      }

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

      public function run(TestResult $result = null): TestResult
      {
            $this->setPreserveGlobalState(false);
            return parent::run($result);
      }
}
