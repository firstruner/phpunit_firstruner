<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit extension.
 *
 * (c) Firstruner <contact@firstruner.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\Timer;

use Firstruner\Commons\CLI\cli_colors;

use function is_float;
use function memory_get_peak_usage;
use function microtime;
use function sprintf;

final class ResourceUsageFormatter_Firstruner
{
    /**
     * @psalm-var array<string,int>
     */
    private const SIZES = [
        'GB' => 1073741824,
        'MB' => 1048576,
        'KB' => 1024,
    ];

    public function resourceUsage(Duration $duration,
        int $limitAllowed = 0, int $timeAllowed = 0): string
    {
        $timeExecution = $duration->asSeconds();
        $memUsage = memory_get_peak_usage(true);
        $memExceed = ($limitAllowed > 0) && ($limitAllowed < $memUsage);
        $timeExceed = ($timeAllowed > 0) && ($timeAllowed < $timeExecution);

        return
            "╔════════════════════════════════════╗" . PHP_EOL .
            "║               RESUME               ║" . PHP_EOL .
            "╚════════════════════════════════════╝" . PHP_EOL .
            "  ► " . cli_colors::GetColoredText(
            ($memExceed || $timeExceed
                ? cli_unittest_memoryexceed
                : cli_reset),
            "Temps: {$duration->asString()}, Memoire: {$this->bytesToString($memUsage)}" .
            PHP_EOL .
            ($memExceed
                ? "     └ Memoire max attendue : " . $this->bytesToString($limitAllowed)
                : "") .
            ($timeExceed
                ? ($memExceed ? "; " : "") .
                 "     └ Temps d'exécution attendu : " .
                    (Duration::fromMicroseconds($timeAllowed * 1000000))
                        ->asString()
                : "")
            );
    }

    /**
     * @throws TimeSinceStartOfRequestNotAvailableException
     */
    public function resourceUsageSinceStartOfRequest(): string
    {
        if (!isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            throw new TimeSinceStartOfRequestNotAvailableException(
                'Cannot determine time at which the request started because $_SERVER[\'REQUEST_TIME_FLOAT\'] is not available'
            );
        }

        if (!is_float($_SERVER['REQUEST_TIME_FLOAT'])) {
            throw new TimeSinceStartOfRequestNotAvailableException(
                'Cannot determine time at which the request started because $_SERVER[\'REQUEST_TIME_FLOAT\'] is not of type float'
            );
        }

        return $this->resourceUsage(
            Duration::fromMicroseconds(
                (1000000 * (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']))
            )
        );
    }

    private function bytesToString(int $bytes): string
    {
        foreach (self::SIZES as $unit => $value) {
            if ($bytes >= $value) {
                return sprintf('%.2f %s', $bytes >= 1024 ? $bytes / $value : $bytes, $unit);
            }
        }

        // @codeCoverageIgnoreStart
        return $bytes . ' byte' . ($bytes !== 1 ? 's' : '');
        // @codeCoverageIgnoreEnd
    }
}
