<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ZipArchive;

class DeployPackage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy-package';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a ZIP package of the application excluding specific files and directories.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating deployment package...');

        $this->call('config:clear');
        $this->call('cache:clear');
        $this->call('route:clear');
        $this->call('view:clear');
        $this->call('optimize:clear');

        $this->info('Caches cleared and optimization reset.');

        $this->info('Compiling assets...');

        $npmCommand = ['npm', 'run', 'build'];
        $process = new \Symfony\Component\Process\Process($npmCommand);
        $process->setWorkingDirectory(base_path());

        try {
            $process->mustRun();
            $this->info('Assets compiled successfully.');
        } catch (\Symfony\Component\Process\Exception\ProcessFailedException $exception) {
            $this->error('Failed to compile assets: ' . $exception->getMessage());
            return Command::FAILURE;
        }

        $this->info('Creating deployment package...');

        $timestamp = now()->format('YmdHis');
        $zipFileName = base_path("{$timestamp}.zip");
        $excludePaths = ['.git', 'node_modules', '.env', 'storage','.DS_Store'];

        $zip = new ZipArchive();

        if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("Unable to create ZIP file: $zipFileName");
            return Command::FAILURE;
        }

        $this->addFilesToZip(base_path(), $zip, $excludePaths);
        

        $zip->close();

        $this->info("Deployment package created: $zipFileName");

        return Command::SUCCESS;
    }

    /**
     * Recursively add files to the ZIP archive.
     *
     * @param string     $path
     * @param ZipArchive $zip
     * @param array      $excludePaths
     */
    private function addFilesToZip($path, ZipArchive $zip, $excludePaths)
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getPathname());

            if ($this->shouldExclude($relativePath, $excludePaths)) {
                continue;
            }

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($file->getPathname(), $relativePath);
            }
        }
    }

    /**
     * Determine if a file or directory should be excluded.
     *
     * @param string $path
     * @param array  $excludePaths
     * @return bool
     */
    private function shouldExclude($path, $excludePaths)
    {
        foreach ($excludePaths as $exclude) {
            if (str_starts_with($path, $exclude) || str_contains($path, DIRECTORY_SEPARATOR . $exclude)) {
                return true;
            }
        }
        return false;
    }
}
