<?php

/**
 * @package Erp\SourceCode
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Erp\SourceCode;

use RuntimeException;

/**
 * Class PhpUnitTextCoverageParser
 * @example
 * $parser = new PhpUnitTextCoverageParser('tmp/coverage.txt');
 * $parser->parse();
 * dump($parser->getSummary());
 * dump($parser->getDatas());
 */
class PhpUnitTextCoverageParser
{
    private const TEMP_FILE_PATH = 'tmp/coverage.txt';

    /**
     * @var string
     */
    private $file;

    /**
     * @var array
     */
    private $summary = [];

    /**
     * @var array
     */
    private $datas   = [];


    /**
     * PhpUnitTextCoverageParser constructor.
     *
     * @param string $file
     * @param false  $isResource
     *
     * @throws RuntimeException
     */
    public function __construct(string $file, bool $isResource = false)
    {
        if ($isResource) {
            $this->file = $file;
        } else {
            file_put_contents(self::TEMP_FILE_PATH, $file);
            $this->file = self::TEMP_FILE_PATH;
        }

        if (!file_exists($this->file)) {
            throw new RuntimeException('File does not exists ' . $this->file);
        }
    }

    /**
     * @return false|resource
     */
    private function getResource()
    {
        return fopen($this->file, 'r');
    }


    public function parse(): void
    {
        $resource = $this->getResource();
        $summary  = true;
        while ($line = fgets($resource)) {
            if ($line === PHP_EOL) {
                continue;
            }
            $_text = trim($line);

            if ($summary) {
                if (!str_starts_with($_text, "Ox")) {
                    $this->summary[] = $_text;
                    continue;
                }
                $summary = false;
            }

            $this->datas[] = $_text;
        }
        fclose($resource);
    }

    public function getSummary(): array
    {
        $summary = [
            'Report'  => $this->summary[1] ?? null,
            'Classes' => $this->summary[4] ?? null,
            'Methods' => $this->summary[5] ?? null,
            'Lines'   => $this->summary[6] ?? null,
        ];

        foreach ($summary as $key => $value) {
            $summary[$key] = trim(str_replace($key . ':', '', $value));
        }

        return $summary;
    }

    public function getDatas(): array
    {
        $datas    = [];
        $last_key = null;
        foreach ($this->datas as $value) {
            if ($last_key) {
                $datas[$last_key] = $value;
                $last_key         = null;
                continue;
            }
            $last_key = $value;
        }

        foreach ($datas as $class => $value) {
            $value         = $this->format($value);
            $datas[$class] = [
                'Methods' => $value['methods'],
                'Lines'   => $value['lines'],
            ];
        }

        return $datas;
    }

    private function format(string $str): array
    {
        $re = '/^([^\)]+\))\s*([^\)]+\))/';
        preg_match($re, $str, $matches, PREG_OFFSET_CAPTURE, 0);

        return [
            'methods' => $this->sanitize($matches[1][0], 'Methods:'),
            'lines'   => $this->sanitize($matches[2][0], 'Lines:'),
        ];
    }

    private function sanitize(string $str, string $replace): string
    {
        return trim(str_replace([$replace, ' ', '%('], ['', '', '% ('], $str));
    }
}
