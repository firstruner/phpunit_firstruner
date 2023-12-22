<?php

require_once("vendor/autoload.php");

use Doctrine\Common\Annotations\UnitTestAnnotation;

/**
 * @UnitTestAnnotation(
 * name="Annotation",
 * description="Test des annotations",
 * item="Annotation",
 * element="Class",
 * memoryLimit=8000000,
 * executionTimeLimit=1)
 */
class AnnotationTest extends TestCase_Firstruner
{
      /**
       * @UnitTestAnnotation(name="Test0", description="Test en essai", item="Bar", element="Method1", )
       */
      public function test_MethodOne()
      {
            $this->assertEquals(3, 1 + 2);
      }

      /**
       * @UnitTestAnnotation(name="Test1", description="Test aussi en essai", item="Fizz", element="MethodBis", executionTimeLimit=1)
       */
      public function test_MethodBis()
      {
            $this->assertEquals(3, 2 + 2);
      }
}