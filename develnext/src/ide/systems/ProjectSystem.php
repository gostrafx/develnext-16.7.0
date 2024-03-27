<?php
namespace ide\systems;

use ide\forms\MainForm;
use ide\forms\MessageBoxForm;
use ide\forms\OpenProjectForm;
use ide\Ide;
use ide\Logger;
use ide\project\AbstractProjectTemplate;
use ide\project\InvalidProjectFormatException;
use ide\project\Project;
use ide\project\ProjectConsoleOutput;
use ide\project\ProjectImporter;
use ide\ui\Notifications;
use ide\utils\FileUtils;
use php\gui\UXApplication;
use php\gui\UXDialog;
use php\gui\UXDirectoryChooser;
use php\io\File;
use php\io\IOException;
use php\lang\System;
use php\lang\Thread;
use php\lib\fs;
use php\lib\Items;
use php\lib\Str;
use script\TimerScript;
use timer\AccurateTimer;

/**
 * Class ProjectSystem
 * @package ide\systems
 */
class ProjectSystem
{
    protected static function clear()
    {
        //WatcherSystem::clear();
        //WatcherSystem::clearListeners();
        Ide::get()->unregisterCommands();
    }

    /**
     * Compile full project.
     *
     * @param string $env
     * @param ProjectConsoleOutput $consoleOutput
     * @param string $hintCommand
     * @param callable $callback
     */
    static function compileAll($env, ProjectConsoleOutput $consoleOutput, $hintCommand, callable $callback)
    {
        $project = Ide::project();

        if (!$project) {
            return;
        }

        $th = new Thread(function () use ($project, $consoleOutput, $callback, $env, $hintCommand) {
            try {
                $project->preCompile($env, function ($log) use ($consoleOutput) {
                    uiLater(function () use ($consoleOutput, $log) {
                        $consoleOutput->addConsoleLine($log, 'gray');
                    });
                });

                $project->compile($env, function ($log) use ($consoleOutput) {
                    uiLater(function () use ($consoleOutput, $log) {
                        $consoleOutput->addConsoleLine($log, 'blue');
                    });
                });

                uiLater(function () use ($consoleOutput, $project, $hintCommand) {
                    $consoleOutput->addConsoleLine('> ' . $hintCommand, 'green');
                    $consoleOutput->addConsoleLine('   --> ' . $project->getRootDir() . ' ..', 'gray');
                });

                uiLater(function () use ($callback) {
                    $callback(true);
                });
            } catch (\Throwable $e) {
                uiLater(function () use ($consoleOutput, $e, $callback) {
                    $file = Ide::project() ? Ide::project()->getAbsoluteFile($e->getFile())->getRelativePath() : $e->getFile();

                    $consoleOutput->addConsoleLine("[ERROR] Cannot build project");
                    $consoleOutput->addConsoleLine("  -> {$file}");
                    $consoleOutput->addConsoleLine("  -> {$e->getMessage()}, on line {$e->getLine()}", 'red');

                    $callback(false);
                });
            }
        });
        $th->setName("ProjectSystem.compileAll #" . str::random());

        $th->start();
    }


    static public function checkDirectory($path)
    {
        Logger::debug("Check directory: $path");

        if (File::of($path)->find()) {
            $path = File::of($path);

            $msg = new MessageBoxForm("Folder '$path' must be empty for the project, do you want to clear it to continue?", [
                'Yes, clear and continue',
                'No, choose another',
                'Cancel'
            ]);

            if ($msg->showDialog()) {
                switch ($msg->getResultIndex()) {
                    case 0:
                        FileUtils::deleteDirectory($path);
                        break;
                    case 1:
                        $dialog = new UXDirectoryChooser();
                        $dialog->initialDirectory = $path;

                        if ($file = $dialog->showDialog()) {
                            return $file;
                        } else {
                            return null;
                        }

                        break;
                    case 2:
                        return null;
                }
            }
        }

        return $path;
    }

    static function import($file, $projectDir = null, $newName = null, callable $afterOpen = null)
    {
        Logger::info("Start import project: file = $file, projectDir = $projectDir");

        Ide::get()->getMainForm()->showPreloader('Unpacking the archive ...');

        ProjectSystem::close();

        if (!$projectDir) {
            $projectDir = FileUtils::stripExtension($file);
        }

        if (!($projectDir = self::checkDirectory($projectDir))) {
            Ide::get()->getMainForm()->hidePreloader();
            ProjectSystem::closeWithWelcome();
            return;
        }

        FileUtils::deleteDirectory($projectDir);

        $importer = new ProjectImporter($file);

        try {
            $importer->extract($projectDir);

            $files = File::of($projectDir)->findFiles(function (File $dir, $name) {
                return (Str::endsWith($name, '.dnproject'));
            });

            if (!$files) {
                UXDialog::show('Project file not found in archive', 'ERROR');
                return;
            }

            $file = File::of(Items::first($files));

            if ($newName) {
                $files = [File::of($file->getParent() . "/$newName.dnproject")];

                $file->renameTo(Items::first($files));
            }

            AccurateTimer::executeAfter(1000, function () use ($projectDir, $files, $file, $afterOpen) {
                ProjectSystem::open($projectDir . "/" . Items::first($files)->getName(), true, false);

                Ide::get()->getMainForm()->toast("The project was successfully imported from the archive", 3000);

                Logger::info("Finish importing project.");

                if ($afterOpen) {
                    $afterOpen();
                }
            });
        } catch (IOException $e) {
            self::close(false);
            Ide::get()->getMainForm()->hidePreloader();
            Notifications::error("Project opening error", "Perhaps the project folder is not accessible or there is no disk space");
        }
    }

    /**
     * @param AbstractProjectTemplate $template
     * @param string $path
     * @param string $package
     */
    static function create(AbstractProjectTemplate $template, $path, $package = 'app')
    {
        static::clear();
        $parent = File::of($path)->getParent();

        if (!($parent = self::checkDirectory($parent))) {
            FileSystem::open('~welcome');
            return;
        }

        $path = $parent . "/" . File::of($parent)->getName();

        try {
            $project = Project::createForFile($path);
            $project->setTemplate($template);
            $project->setPackageName($package);

            $template->makeProject($project);
            Ide::get()->setOpenedProject($project);

            $project->create();
            $project->recover();
            $project->open();

            Ide::get()->trigger('openProject', [$project]);

            static::save();
        } catch (\Exception $e) {
            Logger::exception("Unable to create project", $e);
            ProjectSystem::close(false);
            Ide::get()->getMainForm()->hidePreloader();
            Notifications::error("Project creation error", "The project folder may not be accessible or there is no disk space");
        }
    }

    /**
     * @param string $fileName
     * @param bool $showDialogAlreadyOpened
     * @param bool $showMigrationDialog
     */
    static function open($fileName, $showDialogAlreadyOpened = true, $showMigrationDialog = true)
    {
        Logger::info("Start opening project: $fileName");

        try {
            Ide::get()->getMainForm()->showPreloader('Opening a project ...');

            static::clear();
            static::close(false);

            $file = File::of($fileName);

            try {
                $project = new Project($file->getParent(), fs::nameNoExt($file));

                if ($project->isOpenedInOtherIde()) {
                    if ($showDialogAlreadyOpened) {
                        $msg = new MessageBoxForm('This project is already open in another instance of the environment!', ['ОК, open another project', 'Exit']);
                        $msg->showDialog();

                        if ($msg->getResultIndex() == 0) {
                            uiLater(function () {
                                $dialog = new OpenProjectForm();
                                $dialog->showDialog();
                            });
                        }
                    }

                    FileSystem::open('~welcome');
                    Ide::get()->getMainForm()->hidePreloader();
                    return;
                }

                $prVersion = $project->getConfig()->getIdeVersion();

                if (!Ide::get()->isSameVersionIgnorePatch($prVersion) && $project->getConfig()->getIdeVersionHash() > Ide::get()->getVersionHash()) {
                    $msg = new MessageBoxForm("Project '{$project->getName()}' created in a newer version of DevelNext ($prVersion), you want to open it?", [
                        'Нет', 'Да, открыть проект'
                    ]);

                    $msg->makeWarning();
                    $msg->showWarningDialog();

                    if ($msg->getResultIndex() == 0) {
                        FileSystem::open('~welcome');
                        Ide::get()->getMainForm()->hidePreloader();
                        return;
                    }
                }

                if ($showMigrationDialog) {
                    if ($project->getConfig()->getTemplate()) {
                        if ($project->getConfig()->getTemplate()->isProjectWillMigrate($project)) {
                            $msg = new MessageBoxForm("Проект '{$project->getName()}' will be converted to a new format that is not supported by previous versions, continue?", [
                                'Open project', 'Cancel'
                            ]);
                            $msg->showWarningDialog();

                            if ($msg->getResultIndex() == 1) {
                                FileSystem::open('~welcome');
                                Ide::get()->getMainForm()->hidePreloader();

                                uiLater(function () {
                                    $dialog = new OpenProjectForm();
                                    $dialog->showDialog();
                                });
                                return;
                            }
                        }
                    }
                }

                Ide::get()->setOpenedProject($project);

                $project->load();
                $project->recover();

                if (!FileSystem::getOpened()) {
                    FileSystem::open($project->getMainProjectFile());
                }

                $project->open();

                Ide::get()->getMainForm()->hidePreloader();

                Ide::get()->trigger('openProject', [$project]);

                Logger::info("Finish opening project.");
                return $project;
            } catch (IOException $e) {
                ProjectSystem::close(false);
                Ide::get()->getMainForm()->hidePreloader();

                Logger::exception("Unable to open project", $e);
                Notifications::error("Project opening error", "Perhaps the project folder is not accessible or there is no disk space");
            }
        } catch (InvalidProjectFormatException $e) {
            Ide::get()->getMainForm()->hidePreloader();
            ProjectSystem::closeWithWelcome(false);
            Notifications::error('Damaged project', 'Project"' . fs::nameNoExt($fileName) . '" can\'t be opened, it\'s corrupted, or it\'s created in a new version of DevelNext.');
        }

        return null;
    }

    /**
     * ...
     */
    static function saveOnlyRequired()
    {
        if ($editor = FileSystem::getSelectedEditor()) {
            $editor->save();
        }
    }

    /**
     * @throws \Exception
     */
    static function save()
    {
        $project = Ide::get()->getOpenedProject();

        if (!$project) {
            throw new \Exception("Project is not opened");
        }

        $project->save();
    }

    static function closeWithWelcome($save = true)
    {
        self::close($save);
        FileSystem::open('~welcome');
    }

    /**
     * Закрывает проект с открытми файлами проекта.
     * @param bool $saveAll
     */
    static function close($saveAll = true)
    {
        $project = Ide::get()->getOpenedProject();

        ProjectSystem::saveOnlyRequired();

        if ($project) {
            Ide::get()->trigger('closeProject', [$project]);
        }

        if ($project) {
            $project->close(true);
        }

        foreach (FileSystem::getOpened() as $hash => $info) {
            //if ($project && $project->isContainsFile($info['file'])) {
            FileSystem::close($info['file'], $saveAll);
            //}
        }

        FileSystem::closeAllTabs();

        Cache::clear();

        static::clear();


        Ide::get()->setOpenedProject(null);

        if ($project) {
            Ide::get()->trigger('afterCloseProject', [$project]);

            $project->free();
        }

        FileSystem::clearCache();
        System::gc();
    }
}