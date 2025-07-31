<?php

namespace app\components;

use app\models\StructuralRequirement;

/**
 *  This class checks the validity of submission uploads according
 *  to their structural requirements.
 */
class StructuralRequirementChecker
{
    /**
     * A helper method that checks the structural requirements on the given paths.
     * @param StructuralRequirement[] $structuralRequirements The structural requirements to check against.
     * @param string[] $paths The paths to validate.
     * @return array{
     *  includeErrors: string[],
     *  excludeErrors: string[],
     * }
     */
    public static function validatePaths(array $structuralRequirements, array $paths): array
    {
        $includeErrors = [];
        $excludeErrors = [];

        foreach ($structuralRequirements as $req) {
            if ($req->type === StructuralRequirement::SUBMISSION_INCLUDES) {
                if (!self::checkInclusionRequirement($req, $paths)) {
                    $includeErrors[] = $req->errorMessage ?:
                        \Yii::t('app', 'The uploaded solution does not match the required regular expression: ') . $req->regexExpression;
                }
            } elseif ($req->type === StructuralRequirement::SUBMISSION_EXCLUDES) {
                if (!self::checkExclusionRequirement($req, $paths)) {
                    $excludeErrors[] = $req->errorMessage ?:
                        \Yii::t('app', 'The uploaded solution matches the prohibited regular expression: ') . $req->regexExpression;
                }
            }
        }

        return [
            'includeErrors' => $includeErrors,
            'excludeErrors' => $excludeErrors,
        ];
    }

    /**
     * Checks if the given paths satisfy the inclusion requirement.
     * @param StructuralRequirement $req The structural requirement to check.
     * @param string[] $paths Paths to check against the requirement.
     * @return bool True if the requirement is satisfied, false otherwise.
     */
    private static function checkInclusionRequirement(StructuralRequirement $req, array $paths): bool
    {
        $escapedRegexExpression = str_replace("#", "\\#", $req->regexExpression);

        foreach ($paths as $path) {
            if (preg_match('#' . $escapedRegexExpression . '#', $path)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if the given paths satisfy the exclusion requirement.
     * @param StructuralRequirement $req The structural requirement to check.
     * @param string[] $paths Paths to check against the requirement.
     * @return bool True if the requirement is satisfied, false otherwise.
     */
    private static function checkExclusionRequirement(StructuralRequirement $req, array $paths): bool
    {
        $escapedRegexExpression = str_replace("#", "\\#", $req->regexExpression);

        foreach ($paths as $path) {
            if (preg_match('#' . $escapedRegexExpression . '#', $path)) {
                return false;
            }
        }
        return true;
    }
}
