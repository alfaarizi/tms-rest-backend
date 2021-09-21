<?php

namespace app\commands;

use app\models\InstructorGroup;
use app\models\Semester;
use app\models\Course;
use app\models\Group;
use app\models\User;
use app\models\ExamQuestion;
use app\models\ExamQuestionSet;
use app\models\ExamAnswer;
use app\models\ExamTest;
use app\models\ExamTestInstance;
use yii\console\ExitCode;
use yii\helpers\Console;
use app\models\Subscription;

/**
 * Manages application setup.
 */
class SetupController extends BaseController
{
    /**
     * Seeds the database with initial data.
     *
     * @return int Error code.
     */
    public function actionSeed()
    {
        // Seed Semester
        if (Semester::find()->count()) {
            $this->stdout("Semester has already been seeded, skip." . PHP_EOL, Console::FG_YELLOW);
        } else {
            $month = intval(date('n'));

            // August -> December: fall semester
            if ($month >= 8) {
                $default_name = sprintf(
                    '%s/%s/1',
                    date('Y'),
                    date('y', strtotime('+1 year'))
                );
            } elseif ($month == 1) {
                // January: fall semester
                $default_name = sprintf(
                    '%s/%s/1',
                    date('Y', strtotime('-1 year')),
                    date('y')
                );
            } else {
                // February -> July: spring semester
                $default_name = sprintf(
                    '%s/%s/2',
                    date('Y', strtotime('-1 year')),
                    date('y')
                );
            }

            if ($this->interactive) {
                $name = Console::prompt("Initial semester:", [
                    'required' => true,
                    'default' => $default_name,
                    'pattern' => '|^\d{4}/\d{2}/[1,2]$|',
                    'error' => 'Invalid semester format.',
                ]);
            } else {
                $name = $default_name;
            }

            $semester = new Semester();
            $semester->name = $name;
            $semester->actual = true;
            if ($semester->save()) {
                $this->stdout("Successfully inserted initial semester '$name'." . PHP_EOL, Console::FG_GREEN);
            } else {
                $this->stdout("Failed to insert initial semester '$name'." . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        //Seed Admin
        $authManager = \Yii::$app->authManager;
        $numberOfAdmins = count($authManager->getUserIdsByRole('admin'));
        if ($numberOfAdmins >= 1) {
            $this->stdout("Admin has already been seeded, skip." . PHP_EOL, Console::FG_YELLOW);
        } elseif (!$this->interactive) {
            $this->stdout("Admin user cannot be seeded in non-interactive mode, skip." . PHP_EOL, Console::FG_YELLOW);
        } else {
            $neptun = Console::prompt("Administrator's neptun code:", [
                'required' => true,
                'pattern' => '/^\w{6}$/',
                'error' => 'Define a neptun code.',
            ]);

            $administrator = new User();
            $administrator->neptun = $neptun;
            if ($administrator->save()) {
                $authManager->assign($authManager->getRole('student'), $administrator->id);
                $authManager->assign($authManager->getRole('faculty'), $administrator->id);
                $authManager->assign($authManager->getRole('admin'), $administrator->id);
                $this->stdout("Successfully inserted administrator with neptun: '$administrator->neptun'." . PHP_EOL, Console::FG_GREEN);
            } else {
                $this->stdout("Failed to insert administrator." . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        // Seed Course
        if (Course::find()->count()) {
            $this->stdout("Course has already been seeded, skip." . PHP_EOL, Console::FG_YELLOW);
        } else {
            $default_name = 'Webes alkalmazások fejlesztése';
            $default_code = 'IP-08bWAFEG';

            if ($this->interactive) {
                $name = Console::prompt("Initial course name:", [
                    'required' => true,
                    'default' => $default_name,
                    'error' => 'Define a course name.',
                ]);

                if ($name == $default_name) {
                    $code = $default_code;
                } else {
                    $code = Console::prompt("Course code for '$name':", [
                        'required' => true,
                        'error' => 'Define a course code.',
                    ]);
                }
            } else {
                $name = $default_name;
                $code = $default_code;
            }

            $course = new Course();
            $course->name = $name;
            $course->code = $code;
            if ($course->save()) {
                $this->stdout("Successfully inserted initial course '$name'." . PHP_EOL, Console::FG_GREEN);
            } else {
                $this->stdout("Failed to insert initial course '$name'." . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }


        // Seed for exam module
        if (ExamTest::find()->count()) {
            $this->stdout("Exam module has already been seeded, skip." . PHP_EOL, Console::FG_YELLOW);
        } else {
            $answer = 0;
            if ($this->interactive) {
                $answer = Console::prompt("Seed for exam module? (1 = yes 0 = no):", [
                    'required' => true,
                    'default' => 0,
                    'pattern' => '|[0-1]|',
                    'error' => 'Please enter 1 for yes or 0 for no.',
                ]);
            }
            if ($answer) {
                if (User::find()->count()) {
                    $this->stdout("User has already been seeded, skip." . PHP_EOL, Console::FG_YELLOW);
                } else {
                    $user = new User();
                    $user->name = "John Doe";
                    $user->email = "email@address.johndoe";
                    $user->neptun = "JHNDOE";
                    if ($user->save()) {
                        $authManager = \Yii::$app->authManager;
                        $authManager->assign($authManager->getRole('student'), $user->id);
                        $authManager->assign($authManager->getRole('faculty'), $user->id);
                        $this->stdout("Successfully inserted initial user '$user->name'." . PHP_EOL, Console::FG_GREEN);
                    } else {
                        $this->stdout("Failed to insert initial user '$user->name'." . PHP_EOL, Console::FG_RED);
                        return ExitCode::UNSPECIFIED_ERROR;
                    }
                }

                if (Group::find()->count()) {
                    $this->stdout("Group has already been seeded, skip." . PHP_EOL, Console::FG_YELLOW);
                } else {
                    $transaction = \Yii::$app->db->beginTransaction();
                    $group = new Group();
                    $group->courseID = Course::find()->one()->id;
                    $group->semesterID = Semester::find()->one()->id;
                    $group->number = 1;

                    if ($group->save()) {
                        $this->stdout("Successfully inserted initial group." . PHP_EOL, Console::FG_GREEN);
                    } else {
                        $this->stdout("Failed to insert initial group." . PHP_EOL, Console::FG_RED);
                        return ExitCode::UNSPECIFIED_ERROR;
                    }

                    $instructorGroup = new InstructorGroup();
                    $instructorGroup->userID = User::find()->one()->id;
                    $instructorGroup->groupID = $group->id;

                    if ($instructorGroup->save()) {
                        $this->stdout("Successfully inserted initial group instructor permission." . PHP_EOL, Console::FG_GREEN);
                    } else {
                        $this->stdout("Failed to insert initial group instructor permission." . PHP_EOL, Console::FG_RED);
                        return ExitCode::UNSPECIFIED_ERROR;
                    }
                    $transaction->commit();
                }

                if (Subscription::find()->count()) {
                    $this->stdout("Subscription has already been seeded, skip." . PHP_EOL, Console::FG_YELLOW);
                } else {
                    $subscription = new Subscription();
                    $subscription->userID = User::find()->one()->id;
                    $subscription->groupID = Group::find()->one()->id;
                    $subscription->semesterID = Semester::find()->one()->id;
                    $subscription->isAccepted = 1;
                    if ($subscription->save()) {
                        $this->stdout("Successfully inserted initial student course subscription." . PHP_EOL, Console::FG_GREEN);
                    } else {
                        $this->stdout("Failed to insert initial student course subscription." . PHP_EOL, Console::FG_RED);
                        return ExitCode::UNSPECIFIED_ERROR;
                    }
                }


                if (ExamQuestionSet::find()->count()) {
                    $this->stdout("Question set has already been seeded, skip." . PHP_EOL, Console::FG_YELLOW);
                } else {
                    $questionSet = new ExamQuestionSet();
                    $questionSet->courseID = Course::find()->one()->id;
                    $questionSet->name = "Beugró kérdések";
                    if ($questionSet->save()) {
                        $this->stdout("Successfully inserted initial question set '$questionSet->name'." . PHP_EOL, Console::FG_GREEN);
                    } else {
                        $this->stdout("Failed to insert initial question set '$questionSet->name'." . PHP_EOL, Console::FG_RED);
                        return ExitCode::UNSPECIFIED_ERROR;
                    }

                    for ($i = 0; $i < 5; ++$i) {
                        $question = new ExamQuestion();
                        $question->text = "Kérdés " . ($i + 1);
                        $question->questionsetID = $questionSet->id;
                        $question->save();
                        for ($j = 0; $j < 5; ++$j) {
                            $answer = new ExamAnswer();
                            $answer->text = "Válasz " . ($j + 1);
                            $answer->correct = ($j == 1) ? 1 : 0;
                            $answer->questionID = $question->id;
                            $answer->save();
                        }
                    }
                }

                if (ExamTest::find()->count()) {
                    $this->stdout("Test has already been seeded, skip." . PHP_EOL, Console::FG_YELLOW);
                } else {
                    $test = new ExamTest();
                    $test->name = "Beugró";
                    $test->questionamount = ExamQuestion::find()->count();
                    $test->duration = 60;
                    $test->shuffled = 1;
                    $test->unique = 0;
                    $test->questionsetID = ExamQuestionSet::find()->one()->id;
                    $test->groupID = Group::find()->one()->id;
                    $test->availablefrom = date('Y-m-d H:i:s');
                    $test->availableuntil = date('Y-m-d H:i:s', strtotime('+1 day'));

                    $test2 = new ExamTest();
                    $test2->name = "ZH";
                    $test2->questionamount = ExamQuestion::find()->count();
                    $test2->duration = 60;
                    $test2->shuffled = 1;
                    $test2->unique = 0;
                    $test2->questionsetID = ExamQuestionSet::find()->one()->id;
                    $test2->groupID = Group::find()->one()->id;
                    $test2->availablefrom = date('Y-m-d H:i:s');
                    $test2->availableuntil = date('Y-m-d H:i:s', strtotime('+1 day'));

                    if (!$test->save() || !$test2->save()) {
                        $this->stdout("Failed to insert initial tests." . PHP_EOL, Console::FG_RED);
                        return ExitCode::UNSPECIFIED_ERROR;
                    } else {
                        $this->stdout("Successfully inserted initial tests." . PHP_EOL, Console::FG_GREEN);

                        $available = new ExamTestInstance();
                        $available->testID = $test->id;
                        $available->userID = User::find()->one()->id;
                        $available->submitted = 0;
                        $available->starttime = null;
                        $available->score = 0;
                        $available->save();
                        foreach (ExamQuestion::find()->all() as $question) {
                            $question->link('testInstances', $available);
                        }
                        $finished = new ExamTestInstance();
                        $finished->testID = $test2->id;
                        $finished->userID = User::find()->one()->id;
                        $finished->submitted = 1;
                        $finished->starttime = date('Y-m-d H:i:s', strtotime('-5 minute'));
                        $finished->finishtime = date('Y-m-d H:i:s');
                        $finished->score = $test->questionamount;
                        $finished->save();
                        foreach (ExamQuestion::find()->all() as $question) {
                            $question->link('testInstances', $finished);
                            $finished->link(
                                'answers',
                                ExamAnswer::find()->where(['questionID' => $question->id, 'correct' => 1])->one()
                            );
                        }
                    }
                }
            }
        }
        return ExitCode::OK;
    }
}
