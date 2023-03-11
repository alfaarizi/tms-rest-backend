<?php

namespace app\tests\unit;

class DueSubmissionDigesterTest extends \Codeception\Test\Unit
{
    public function _fixtures()
    {
        return [
            'tasks' => [
                'class' => \app\tests\unit\fixtures\TaskFixture::class
            ],
            'subscriptions' => [
                'class' => \app\tests\unit\fixtures\SubscriptionFixture::class
            ]
        ];
    }

    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testDigestOncomingTaskDeadlinesWithMailSent()
    {
        $dueSubmissionDigester = new \app\components\DueSubmissionDigester(3, true);
        $dueSubmissionDigester->digestOncomingTaskDeadlines();

        $sentEmails = $this->tester->grabSentEmails();

        self::assertEquals(3, count($sentEmails), "3 emails are expected to be sent");

        $recipients = [];
        foreach ($sentEmails as $sentEmail) {
            $recipient = array_keys($sentEmail->to)[0];
            $this->tester->assertNotContains($recipient, $recipients, "A single email must be sent to each recipient");
            $recipients[] = $recipient;

            self::assertEquals(
                'Oncoming submission deadlines',
                $sentEmail->subject,
                "Subject must be the same for all mails"
            );
        }
    }

    public function testDigestOncomingTaskDeadlinesWithMailNotSent()
    {
        $dueSubmissionDigester = new \app\components\DueSubmissionDigester(3, false);

        $mailData = $dueSubmissionDigester->digestOncomingTaskDeadlines();

        self::assertEquals(0, count($this->tester->grabSentEmails()), "No mails should have sent");
        self::assertEquals(3, count($mailData), "For student has due task with oncoming deadline");

        $student = $mailData['STUD01'];
        self::assertEquals('STUD01', $student['user']->neptun, "array key must match student neptun code");
        self::assertEquals(12, count($student['data']), "STUD01 should receive 12 emails");
    }
}
