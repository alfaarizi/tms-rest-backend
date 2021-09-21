<?php

use app\rbac\InstructorRule;
use app\rbac\LecturerRule;
use yii\db\Migration;

/**
 * Populating RBAC roles and rules.
 */
class m210830_090447_init_rbac extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        // Create roles
        $student = $auth->createRole('student');
        $student->description = 'Student role';
        $auth->add($student);

        $faculty = $auth->createRole('faculty');
        $faculty->description = 'Faculty role';
        $auth->add($faculty);

        $admin = $auth->createRole('admin');
        $admin->description = 'Admin role';
        $auth->add($admin);

        // Create permissions for direct group and course management (admin usage).
        $manageGroup = $auth->createPermission('manageGroup');
        $manageGroup->description = 'Manage group';
        $auth->add($manageGroup);

        $manageCourse = $auth->createPermission('manageCourse');
        $manageCourse->description = 'Manage course';
        $auth->add($manageCourse);

        // Create permission for rule authorized group and course management.
        $instructorRule = new InstructorRule();
        $lecturerRule = new LecturerRule();
        $auth->add($instructorRule);
        $auth->add($lecturerRule);

        // Instructors can manage their own groups.
        $manageOwnGroup = $auth->createPermission('manageOwnGroup');
        $manageOwnGroup->description = 'Manage own group';
        $manageOwnGroup->ruleName = $instructorRule->name;
        $auth->add($manageOwnGroup);

        // Lecturers can manage their own courses.
        $manageOwnCourse = $auth->createPermission('manageOwnCourse');
        $manageOwnCourse->description = 'Manage own course';
        $manageOwnCourse->ruleName = $lecturerRule->name;
        $auth->add($manageOwnCourse);

        // Lecturers can manage all groups of their courses.
        $manageLecturedGroup = $auth->createPermission('manageLecturedGroup');
        $manageLecturedGroup->description = 'Manage lectured group';
        $manageLecturedGroup->ruleName = $lecturerRule->name;
        $auth->add($manageLecturedGroup);

        // Create permission hierarchy.
        $auth->addChild($admin, $manageGroup);
        $auth->addChild($admin, $manageCourse);
        $auth->addChild($faculty, $manageOwnGroup);
        $auth->addChild($faculty, $manageOwnCourse);
        $auth->addChild($faculty, $manageLecturedGroup);
        $auth->addChild($manageOwnGroup, $manageGroup);
        $auth->addChild($manageLecturedGroup, $manageGroup);
        $auth->addChild($manageOwnCourse, $manageCourse);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        $auth->removeAll();
    }
}
