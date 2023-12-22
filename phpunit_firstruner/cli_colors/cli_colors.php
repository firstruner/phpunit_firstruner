<?php
/*
 * This file is part of PHPUnit.
 *
 * (c) Firstruner <contact@firstruner.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Firstruner;

class cli_colors
{
      static function PrintColoredText($color, $text, bool $eol = true, bool $resetAfter = true)
      {
            echo $color, $text, cli_eol, cli_reset;// ($eol ? cli_eol : null), ($resetAfter ? cli_reset : null);
      }

      static function GetColoredText($color, $text, bool $eol = true, bool $resetAfter = true)
      {
            return $color . $text . cli_eol . cli_reset;// ($eol ? cli_eol : null), ($resetAfter ? cli_reset : null);
      }
}