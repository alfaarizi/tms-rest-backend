<?php

use yii\db\Migration;

/**
 * Creates the table for storing evaluator templates.
 */
class m241102_010415_create_evaluator_templates_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%evaluator_templates}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(40)->notNull(),
            'enabled' => $this->boolean()->notNull(),
            'courseID' => $this->integer(), //empty for now, but will be a foreign key to courses table for non-global templates
            'os' => "ENUM('linux','windows') NOT NULL DEFAULT 'linux'",
            'image' => $this->string()->notNull(),
            'autoTest' => $this->boolean()->notNull(),
            'appType' => "ENUM('Console','Web') NOT NULL DEFAULT 'Console'",
            'port' => $this->integer(),
            'compileInstructions' => $this->text()->notNull(),
            'runInstructions' => $this->text()->notNull(),
            'staticCodeAnalysis' => $this->boolean()->notNull(),
            'staticCodeAnalyzerTool' => $this->string(),
            'codeCheckerCompileInstructions' => $this->string(1000),
            'staticCodeAnalyzerInstructions' => $this->string(1000),
            'codeCheckerToggles' => $this->string(1000),
            'codeCheckerSkipFile' => $this->string(1000),
        ]);
        $this->createIndex('enabled', '{{%evaluator_templates}}', ['enabled']);
        $this->createIndex('courseID', '{{%evaluator_templates}}', ['courseID']);
        $this->addForeignKey(
            '{{%evaluator_templates_ibfk_1}}',
            '{{%evaluator_templates}}',
            ['courseID'],
            '{{%courses}}',
            ['id'],
            'CASCADE',
            'CASCADE'
        );

        // Inserting default evaluator templates
        $this->insertLinuxGccTemplate();
        $this->insertLinuxGppTemplate();
        $this->insertLinuxQt5Template();
        $this->insertLinuxDotnetTemplate();
        $this->insertWindowsDotnetTemplate();
        $this->insertWindowsDotnetMauiTemplate();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%evaluator_templates}}');
    }

    private function insertLinuxGccTemplate()
    {
        $this->insert('{{%evaluator_templates}}', [
            'name' => 'Linux / gcc',
            'os' => 'linux',
            'image' => 'tmselte/evaluator:cpp-ubuntu-24.04',
            'autoTest' => true,
            'appType' => 'Console',
            'compileInstructions' => <<<'EOD'
                # Remove spaces from directory and file names
                find -name "* *" -type d | rename 's/ /_/g'
                find -name "* *" -type f | rename 's/ /_/g'
                # Build the program
                CFLAGS="-std=c11 -pedantic -W -Wall -Wextra"
                gcc $CFLAGS $(find . -type f -iname "*.c") -o program.out
                EOD,
            'runInstructions' => './program.out "$@"',
            'staticCodeAnalysis' => true,
            'staticCodeAnalyzerTool' => 'codechecker',
            'codeCheckerCompileInstructions' => <<<'EOD'
                # Remove spaces from directory and file names
                find -name "* *" -type d | rename 's/ /_/g'
                find -name "* *" -type f | rename 's/ /_/g'
                # Build the program
                CFLAGS="-std=c11 -pedantic -W -Wall -Wextra"
                gcc $CFLAGS $(find . -type f -iname "*.c") -o program.out
                EOD,
            'staticCodeAnalyzerInstructions' => null,
            'codeCheckerToggles' => '',
            'codeCheckerSkipFile' => '-/usr/*',
            'enabled' => true,
            'courseId' => null
        ]);
    }

    private function insertLinuxGppTemplate()
    {
        $this->insert('{{%evaluator_templates}}', [
            'name' => 'Linux / g++',
            'os' => 'linux',
            'image' => 'tmselte/evaluator:cpp-ubuntu-24.04',
            'autoTest' => true,
            'appType' => 'Console',
            'compileInstructions' => <<<'EOD'
                # Remove spaces from directory and file names
                find -name "* *" -type d | rename 's/ /_/g'
                find -name "* *" -type f | rename 's/ /_/g'
                # Build the program
                CFLAGS="-std=c++14 -pedantic -Wall -I ./include"
                g++ $CFLAGS $(find . -type f -iname "*.cpp") -o program.out
                EOD,
            'runInstructions' => './program.out "$@"',
            'staticCodeAnalysis' => true,
            'staticCodeAnalyzerTool' => 'codechecker',
            'codeCheckerCompileInstructions' => <<<'EOD'
                # Remove spaces from directory and file names
                find -name "* *" -type d | rename 's/ /_/g'
                find -name "* *" -type f | rename 's/ /_/g'
                # Build the program
                CFLAGS="-std=c++14 -pedantic -Wall -I ./include"
                g++ $CFLAGS $(find . -type f -iname "*.cpp") -o program.out
                EOD,
            'staticCodeAnalyzerInstructions' => null,
            'codeCheckerToggles' => '',
            'codeCheckerSkipFile' => '-/usr/*',
            'enabled' => true,
            'courseId' => null
        ]);
    }

    private function insertLinuxQt5Template()
    {
        $this->insert('{{%evaluator_templates}}', [
            'name' => 'Linux / Qt5',
            'os' => 'linux',
            'image' => 'tmselte/evaluator:qt5-ubuntu-20.04',
            'autoTest' => true,
            'appType' => 'Console',
            'compileInstructions' => <<<'EOD'
                /build.sh
                # Built-in script that looks for Qt projects (Qt Creator, CMake) and build them.
                EOD,
            'runInstructions' => '',
            'staticCodeAnalysis' => true,
            'staticCodeAnalyzerTool' => 'codechecker',
            'codeCheckerCompileInstructions' => <<<'EOD'
                /build.sh
                # Built-in script that looks for Qt projects (Qt Creator, CMake) and build them.
                EOD,
            'staticCodeAnalyzerInstructions' => null,
            'codeCheckerToggles' => '',
            'codeCheckerSkipFile' => <<<'EOD'
                -/usr/*
                -*/moc*
                -*/qrc*
                EOD,
            'enabled' => true,
            'courseId' => null
        ]);
    }

    private function insertLinuxDotnetTemplate()
    {
        $this->insert('{{%evaluator_templates}}', [
            'name' => 'Linux / .NET',
            'os' => 'linux',
            'image' => 'tmselte/evaluator:dotnet-8.0',
            'autoTest' => true,
            'appType' => 'Console',
            'compileInstructions' => <<<'EOD'
                /build.sh
                # Built-in script that looks for .NET Core projects (.sln files) and build them.
                EOD,
            'runInstructions' => <<<'EOD'
                /execute.sh "$@"
                # Built-in script that looks for executable .NET Core projects and runs the first one.
                EOD,
            'staticCodeAnalysis' => true,
            'staticCodeAnalyzerTool' => 'roslynator',
            'staticCodeAnalyzerInstructions' => <<<'EOD'
                set -e
                IFS=$'\n'
                counter=$(find . -iname "*.csproj" | wc -l)
                  if [ $counter -eq 0 ]; then
                  echo "No Visual Studio projects found." 1>&2
                exit 1
                fi
                diagnostics=("--supported-diagnostics")
                if [ -f /test/test_files/diagnostics.txt ]; then
                  readarray  -t -O "${#diagnostics[@]}" diagnostics < <(grep -v "^#" /test/test_files/diagnostics.txt)
                else
                  readarray  -t -O "${#diagnostics[@]}" diagnostics < <(curl --fail --silent --show-error https://gitlab.com/tms-elte/backend-core/-/snippets/2518152/raw/main/diagnostics.txt | grep -v "^#")
                fi
                /build.sh >/dev/null
                roslynator analyze $(find . -name "*.csproj") \
                  --output roslynator.xml \
                  --severity-level hidden \
                  --analyzer-assemblies $ANALYZERS_DIR \
                  --ignore-analyzer-references \
                  --report-suppressed-diagnostics \
                "${diagnostics[@]}"
                roslynatorExitCode=$?
                if [ -f roslynator.xml ]; then
                  exit 1
                fi
                exit $roslynatorExitCode
                EOD,
            'codeCheckerCompileInstructions' => null,
            'codeCheckerToggles' => '',
            'codeCheckerSkipFile' => '',
            'enabled' => true,
            'courseId' => null
        ]);
    }

    private function insertWindowsDotnetTemplate()
    {
        $this->insert('{{%evaluator_templates}}', [
            'name' => 'Windows / .NET',
            'os' => 'windows',
            'image' => 'tmselte/evaluator:dotnet-8.0',
            'autoTest' => true,
            'appType' => 'Console',
            'compileInstructions' => <<<'EOD'
                C:\build.ps1
                # Built-in script that looks for .NET Core projects (.sln files) and build them.
                EOD,
            'runInstructions' => <<<'EOD'
                C:\execute.ps1 $args
                # Built-in script that looks for executable .NET Core projects and runs the first one.
                EOD,
            'staticCodeAnalysis' => true,
            'staticCodeAnalyzerTool' => 'roslynator',
            'codeCheckerCompileInstructions' => null,
            'staticCodeAnalyzerInstructions' => <<<'EOD'
                C:\analyze.ps1
                # Built-in script that performs default analysis (with Roslynator) and optionally architectural analysis.
                # Add -Arch MV or -Arch MVVM to perform architectural analysis for MV/MVVM architecture.
                EOD,
            'codeCheckerToggles' => '',
            'codeCheckerSkipFile' => '',
            'enabled' => true,
            'courseId' => null
        ]);
    }

    private function insertWindowsDotnetMauiTemplate()
    {
        $this->insert('{{%evaluator_templates}}', [
            'name' => 'Windows / .NET + MAUI',
            'os' => 'windows',
            'image' => 'tmselte/evaluator:maui-8.0-windows',
            'autoTest' => true,
            'appType' => 'Console',
            'compileInstructions' => <<<'EOD'
                C:\build.ps1
                # Built-in script that looks for .NET Core projects (.sln files) and build them
                EOD,
            'runInstructions' => '',
            'staticCodeAnalysis' => true,
            'staticCodeAnalyzerTool' => 'roslynator',
            'codeCheckerCompileInstructions' => null,
            'staticCodeAnalyzerInstructions' => <<<'EOD'
                C:\analyze.ps1
                # Built-in script that performs default analysis (with Roslynator) and optionally architectural analysis.
                # Add -Arch MV or -Arch MVVM to perform architectural analysis for MV/MVVM architecture.
                EOD,
            'codeCheckerToggles' => '',
            'codeCheckerSkipFile' => '',
            'enabled' => true,
            'courseId' => null
        ]);
    }
}
