<?php

/**
 * @var \omnilight\scheduling\Schedule $schedule
 */

$schedulingPeriods = Yii::$app->params['scheduling']['periods'];
$schedulingDates = Yii::$app->params['scheduling']['dates'];
$schedulingParams = Yii::$app->params['scheduling']['params'];

// Commands to be scheduled
$schedule->command('notification/digest-instructors')
         ->dailyAt($schedulingDates['nDigestInstructors'])
         ->withoutOverlapping();
$schedule->command('notification/digest-oncoming-task-deadlines')
         ->dailyAt($schedulingDates['nDigestOncomingTaskDeadlines'])
         ->withoutOverlapping();

// $schedule->command('canvas/synchronize')->everyNMinutes($schedulingPeriods['canvasSynchronize'])->withoutOverlapping();
$schedule->command('canvas/synchronize-prioritized' . ' ' . $schedulingParams['canvasSynchronizePrioritizedNumber'])
         ->everyNMinutes($schedulingPeriods['canvasSynchronizePrioritized'])
         ->withoutOverlapping();

$schedule->command('evaluator/check' . ' ' . $schedulingParams['evaluatorCheckTasksNumber'])
         ->everyNMinutes($schedulingPeriods['evaluatorCheck'])
         ->withoutOverlapping();

$schedule->command('code-compass/clear-cached-images')
         ->everyNMinutes(intval($schedulingPeriods['ccClearCachedImages']) * 1440) // days * 1440 = minutes
         ->withoutOverlapping();
$schedule->command('code-compass/start-waiting-container')
         ->everyNMinutes($schedulingPeriods['ccStartWaitingContainer'])
         ->withoutOverlapping();
$schedule->command('code-compass/stop-expired-containers')
         ->everyNMinutes($schedulingPeriods['ccStopExpiredContainers'])
         ->withoutOverlapping();

$schedule->command('web-app/shut-down-expired-executions')
         ->everyNMinutes($schedulingPeriods['waShutDownExpiredExecutions'])
         ->withoutOverlapping();

$schedule->command('system/clear-expired-access-tokens')
         ->everyNMinutes($schedulingPeriods['systemClearExpiredAccessTokens'] * 1440) // days * 1440 = minutes
         ->withoutOverlapping();
