<?php

namespace app\modules\instructor\controllers;

use app\components\plagiarism\AbstractPlagiarismFinder;
use app\models\MossPlagiarism;
use app\modules\instructor\resources\PlagiarismResource;
use Yii;
use yii\filters\Cors;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * This class provides access to downloaded plagiarism results.
 */
class PlagiarismResultController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);
        $behaviors['corsFilter'] = [
            'class' => Cors::class,
        ];
        return $behaviors;
    }

    /**
     * Renders the index file of a plagiarism result.
     * @param int $id The primary ID of the plagiarism check.
     * @param string $token The authorization token of the plagiarism check.
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/plagiarism-result",
     *     operationId="instructor::PlagiarismResultController::actionIndex",
     *     tags={"Instructor Plagiarism"},
     *     @OA\Parameter(
     *        name="id",
     *        in="query",
     *        required=true,
     *        description="The primary ID of the plagiarism check",
     *        @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(
     *        name="token",
     *        in="query",
     *        required=true,
     *        description="The authorization token of the plagiarism check",
     *        @OA\Schema(type="string"),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionIndex(int $id, string $token)
    {
        $plagiarism = $this->checkPlagiarism($id, $token);
        return $this->renderPlagiarismFile($id, $plagiarism->typeSpecificData->getMainFileName());
    }

    /**
     * Renders a plagiarism result frame.
     * @param int $id The primary ID of the plagiarism check.
     * @param string $token The authorization token of the plagiarism check.
     * @param int $number Ordinal number of the match.
     * @param string $side Which side to render (`top`, `0` or `1`).
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/plagiarism-result/frame",
     *     operationId="instructor::PlagiarismResultController::actionFrame",
     *     tags={"Instructor Plagiarism"},
     *     @OA\Parameter(
     *        name="id",
     *        in="query",
     *        required=true,
     *        description="The primary ID of the plagiarism check",
     *        @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(
     *        name="token",
     *        in="query",
     *        required=true,
     *        description="The authorization token of the plagiarism check",
     *        @OA\Schema(type="string"),
     *     ),
     *     @OA\Parameter(
     *        name="number",
     *        in="query",
     *        required=true,
     *        description="Ordinal number of the match",
     *        @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *        name="side",
     *        in="query",
     *        required=true,
     *        description="Which side to render",
     *        @OA\Schema(type="string", enum={"top", "0", "1"}),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionFrame(int $id, string $token, int $number, string $side)
    {
        switch ($side) {
            case 'top':
            case '0':
            case '1':
                $this->checkPlagiarism($id, $token, MossPlagiarism::ID);
                return $this->renderPlagiarismFile($id, "match$number-$side.html");
            default:
                throw new BadRequestHttpException("The 'side' parameter must be 'top', '0' or '1'");
        }
    }

    /**
     * Renders a plagiarism result match.
     * @param int $id The primary ID of the plagiarism check.
     * @param string $token The authorization token of the plagiarism check.
     * @param int $number Ordinal number of the match.
     * @throws NotFoundHttpException
     *
     * @OA\Get(
     *     path="/instructor/plagiarism-result/match",
     *     operationId="instructor::PlagiarismResultController::actionMatch",
     *     tags={"Instructor Plagiarism"},
     *     @OA\Parameter(
     *        name="id",
     *        in="query",
     *        required=true,
     *        description="The primary ID of the plagiarism check",
     *        @OA\Schema(ref="#/components/schemas/int_id")
     *     ),
     *     @OA\Parameter(
     *        name="token",
     *        in="query",
     *        required=true,
     *        description="The authorization token of the plagiarism check",
     *        @OA\Schema(type="string"),
     *     ),
     *     @OA\Parameter(
     *        name="number",
     *        in="query",
     *        required=true,
     *        description="Ordinal number of the match",
     *        @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="successful operation",
     *     ),
     *     @OA\Response(response=404, ref="#/components/responses/404"),
     *     @OA\Response(response=500, ref="#/components/responses/500"),
     * ),
     */
    public function actionMatch(int $id, string $token, int $number)
    {
        $this->checkPlagiarism($id, $token, MossPlagiarism::ID);

        return $this->renderPartial(
            '//plagiarismResult',
            [
                'id' => $id,
                'token' => $token,
                'number' => $number,
            ]
        );
    }

    /**
     * Render a file from a downloaded plagiarism check.
     * @param int $id ID of the plagiarism check to render.
     * @param string $path Relative path to render within the download directory.
     * @throws NotFoundHttpException If the file doesn’t exist or is not readable.
     */
    private function renderPlagiarismFile(int $id, string $path)
    {
        $content = AbstractPlagiarismFinder::getResultDirectory($id) . "/$path";
        if (!file_exists($content) || !is_readable($content)) {
            throw new NotFoundHttpException('The requested HTML file could not be found.');
        }
        $mime = FileHelper::getMimeType($content);
        $options = [
            'inline' => true,
            'mimeType' => strpos($mime, 'text/') === 0 ? "$mime; charset=UTF-8" : $mime,
        ];
        return Yii::$app->response->sendFile($content, null, $options);
    }

    /**
     * Find a plagiarism with the given IDs, and finish the request
     * handling immediately with a 404 status code if not found.
     * @param int $id
     * @param string $token
     * @param string|null $type Expected type of the plagiarism, or `null` to disable type check
     * @throws NotFoundHttpException if the plagiarism doesn’t exist, or the secondary ID doesn’t match.
     */
    private function checkPlagiarism(int $id, string $token, ?string $type = null): PlagiarismResource
    {
        $plagiarism = PlagiarismResource::findOne(['id' => $id, 'token' => $token]);
        if ($plagiarism !== null && ($type === null || $plagiarism->type === $type)) {
            return $plagiarism;
        } else {
            throw new NotFoundHttpException(Yii::t('app', 'The plagiarism check does not exist, or the authorization token is incorrect.'));
        }
    }
}
