<?php
/*
 * This file is part of PHPUnit.
 *
 * (c) Firstruner <contact@firstruner.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Common\Annotations;

/**
 * @Annotation
 * @Target("ALL")
 */
class UnitTestAnnotation
{
      /** @Required */
      public string $name = "";

      /** @Required */
      public string $item = "";

      /** @Required */
      public string $element = "";

      public string $description = "";

      public int $memoryLimit = 0;

      public function __construct(array $values)
      {
            foreach ($values as $key => $value)
                  $this->$key = $value;
      }

      public function GenerateTitle()
      {
            return "-- {$this->name} : {$this->item}/{$this->element} --";
      }

      public function GetDescription()
      {
            return $this->description;
      }
}