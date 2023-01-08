<?php

namespace app\components\plagiarism;

use InvalidArgumentException;

class JPlagOptions
{
    private const LANGUAGE_EXTENSIONS = [
        'cpp' => ['c', 'cc', 'cpp', 'cxx', 'c++', 'h', 'hpp', 'hxx'],
        'csharp' => ['cs'],
        'emf' => ['ecore'],
        'go' => ['go'],
        'java' => ['java'],
        'kotlin' => ['kt'],
        'python3' => ['py'],
        'rlang' => ['r'],
        'rust' => ['rs'],
        'scala' => ['scala', 'sc'],
        'scheme' => ['scm', 'ss'],
        'swift' => ['swift'],
        'text' => ['txt', 'asc', 'tex'],
    ];

    /**
     * Get languages by extensions.
     * @return string[][] An associative array ([ext => [lang1, lang2, ...]])
     */
    public static function getExtensionsLanguages(): array
    {
        $extensions_languages = [];
        foreach (self::LANGUAGE_EXTENSIONS as $lang => $extensions) {
            foreach ($extensions as $ext) {
                @$extensions_languages[$ext][] = $lang;
            }
        }
        return $extensions_languages;
    }

    // These variable names correspond to command line flags. Do not change them!
    private array $new;
    private array $old;
    private string $l;
    private string $bc;
    private int $t;
    private int $n;
    private string $r;
    private string $s;
    private string $p;
    private string $x;
    private float $m;

    /** Root directories with submissions to check for plagiarism */
    public function setRootDirectories(string ...$values)
    {
        $this->new = $values;
    }

    /** Root directories with prior submissions to compare against */
    public function setOldRootDirectories(string ...$values)
    {
        $this->old = $values;
    }

    /** Select the language to parse the submissions */
    public function setLanguage(string $value)
    {
        if (array_key_exists($value, self::LANGUAGE_EXTENSIONS)) {
            $this->l = $value;
        } else {
            throw new InvalidArgumentException("Unsupported language '$value'");
        }
    }

    /** Path of the directory containing the base code (common framework used in all submissions) */
    public function setBaseCode(string $value)
    {
        $this->bc = $value;
    }

    /**
     * Tunes the comparison sensitivity by adjusting the minimum token required to be counted
     * as a matching section. A smaller <n> increases the sensitivity but might lead to more
     * false-positives
     */
    public function setMinTokenMatch(int $value)
    {
        $this->t = $value;
    }

    /**
     * The maximum number of comparisons that will be shown in the generated report,
     * if set to -1 all comparisons will be shown
     */
    public function setShownComparisons(int $value)
    {
        $this->n = $value;
    }

    /** Name of the directory in which the comparison results will be stored */
    public function setResultDirectory(string $value)
    {
        $this->r = $value;
    }

    /** Look in directories \<root-dir>\/*\/\<dir> for programs */
    public function setSubdirectoryName(string $value)
    {
        $this->s = $value;
    }

    /** List of all filename suffixes that are included */
    public function setSuffixes(string ...$values)
    {
        $this->p = implode(',', $values);
    }

    /** All files named in this file will be ignored in the comparison (line-separated list) */
    public function setExcludeFile(string $value)
    {
        $this->x = $value;
    }

    /** Comparison similarity threshold [0.0-1.0]: All comparisons above this threshold will be saved. */
    public function setSimilarityThreshold(float $value)
    {
        if ($value >= 0 && $value <= 1) {
            $this->m = $value;
        } else {
            throw new InvalidArgumentException("\$value must be between 0 and 1 (inclusive), got $value");
        }
    }

    public function __toString(): string
    {
        $params = '';
        foreach (get_object_vars($this) as $name => $value) {
            $escaped = implode(' ', array_map('escapeshellarg', (array)$value));
            $params .= " -$name $escaped";
        }
        return $params;
    }
}
