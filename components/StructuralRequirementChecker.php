<?php

namespace app\components;

use app\models\StructuralRequirements;

/**
 *  This class checks the validity of submission uploads according
 *  to their structural requirements.
 */
class StructuralRequirementChecker
{
    /**
     * A helper method that checks the structural requirements of the given paths
     * @param StructuralRequirements[] $structuralRequirements
     * @param string[] $paths
     * @return array{
     *  failedExcludedPaths: string[],
     *  failedIncludedRequirements: string[],
     * }
     */
    public static function validatePaths(array $structuralRequirements, array $paths)
    {
        $failedIncludedRequirements = array_filter($structuralRequirements, function ($structuralRequirement) {
            return $structuralRequirement->type === StructuralRequirements::SUBMISSION_INCLUDES;
        });
        $failedIncludedRequirements = array_map(function ($structuralRequirement) {
            return $structuralRequirement->regexExpression;
        }, $failedIncludedRequirements);
        $failedExcludedPaths = [];
        foreach ($paths as $path) {
            $result = self::checkStructuralRequirements($structuralRequirements, $path, $failedIncludedRequirements);
            $failedIncludedRequirements = $result['failedIncludedRequirements'];
            if (count($result['failedExcludedPaths']) > 0) {
                $failedExcludedPaths = array_merge($failedExcludedPaths, $result['failedExcludedPaths']);
            }
        }

        return [
            'failedExcludedPaths' => $failedExcludedPaths,
            'failedIncludedRequirements' => $failedIncludedRequirements
        ];
    }

    /**
     * @param StructuralRequirements[] $structuralRequirements
     * @param string $path
     * @param string[] $failedIncludedRequirements
     * @return array{
     *  failedExcludedPaths: string[],
     *  failedIncludedRequirements: string[],
     * }
     */
    private static function checkStructuralRequirements(
        array $structuralRequirements,
        string $path,
        array $failedIncludedRequirements
    ): array {
        $failedExcludedPaths = [];
        foreach ($structuralRequirements as $structuralRequirement) {
            if (
                $structuralRequirement->type === StructuralRequirements::SUBMISSION_INCLUDES &&
                preg_match('/' . $structuralRequirement->regexExpression . '/', $path)
            ) {
                $failedIncludedRequirements = array_diff($failedIncludedRequirements, [$structuralRequirement->regexExpression]);
            } else if (preg_match('/' . $structuralRequirement->regexExpression . '/', $path)) {
                $failedExcludedPaths[] = $path;
            }
        }
        return [
            'failedExcludedPaths' => $failedExcludedPaths,
            'failedIncludedRequirements' => $failedIncludedRequirements
        ];
    }
}
