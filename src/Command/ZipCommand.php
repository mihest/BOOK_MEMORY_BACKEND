<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use ZipArchive;

#[AsCommand(
    name: 'app:backup',
    description: 'Create a ZIP archive with MySQL dump, public folder and DB host IP',
)]
class ZipCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setHelp('This command backs up your MySQL database and public directory into one ZIP archive.')
            ->addArgument('public-dir', InputArgument::OPTIONAL, 'Path to the public directory', 'public')
            ->addOption('mysqldump-path', null, InputOption::VALUE_REQUIRED, 'Full path to mysqldump binary', 'mysqldump')
            ->addOption('db-host',      null, InputOption::VALUE_REQUIRED, 'Database host',     '127.0.0.1')
            ->addOption('db-user',      null, InputOption::VALUE_REQUIRED, 'Database user',     'root')
            ->addOption('db-pass',      null, InputOption::VALUE_REQUIRED, 'Database password', '')
            ->addOption('db-name',      null, InputOption::VALUE_REQUIRED, 'Database name (required)')
            ->addOption('output',       null, InputOption::VALUE_REQUIRED, 'Output ZIP file path', 'backup.zip')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $publicDir    = $input->getArgument('public-dir');
        $mysqldump    = $input->getOption('mysqldump-path');
        $dbHost       = $input->getOption('db-host');
        $dbUser       = $input->getOption('db-user');
        $dbPass       = $input->getOption('db-pass');
        $dbName       = $input->getOption('db-name');
        $outputFile   = $input->getOption('output');

        if (!$dbName) {
            $io->error('The --db-name option is required.');
            return Command::FAILURE;
        }

        $io->title('Starting backup');

        // Resolve DB host IP
        $dbHostIp = gethostbyname($dbHost);
        $io->text(sprintf('Resolved database host "%s" to %s', $dbHost, $dbHostIp));

        // Prepare temporary SQL dump file
        $tempSqlFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $dbName . '_' . time() . '.sql';
        $io->text(sprintf('Creating database dump to %s', $tempSqlFile));

        // Build and run mysqldump
        $cmd = array_filter([
            $mysqldump,
            '-h', $dbHost,
            '-u', $dbUser,
            $dbPass !== '' ? '-p' . $dbPass : null,
            $dbName,
        ]);

        $process = new Process($cmd);
        $process->setTimeout(null);
        $process->run();

        if (!$process->isSuccessful()) {
            $io->error('Database dump failed: ' . $process->getErrorOutput());
            return Command::FAILURE;
        }

        file_put_contents($tempSqlFile, $process->getOutput());

        // Create ZIP archive
        $zip = new ZipArchive();
        if ($zip->open($outputFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $io->error(sprintf('Cannot open "%s" for writing.', $outputFile));
            return Command::FAILURE;
        }

        // Add SQL dump and DB host IP
        $zip->addFile($tempSqlFile, basename($tempSqlFile));
        $zip->addFromString('db_host_ip.txt', $dbHostIp);

        // Add public directory recursively
        $publicPath = realpath($publicDir);
        if ($publicPath === false || !is_dir($publicPath)) {
            $io->error(sprintf('Public directory "%s" not found.', $publicDir));
            return Command::FAILURE;
        }
        $this->addDirectoryToZip($publicPath, $zip, $publicPath);

        // Finalize
        $zip->close();
        @unlink($tempSqlFile);

        $io->success(sprintf('Backup archive created: %s', $outputFile));
        return Command::SUCCESS;
    }

    private function addDirectoryToZip(string $dir, ZipArchive $zip, string $baseDir): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath     = $file->getRealPath();
                $relativePath = 'public/' . str_replace('\\', '/', substr($filePath, strlen($baseDir) + 1));
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
}
