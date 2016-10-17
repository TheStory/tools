<?php
/**
 * Copyright: STORY DESIGN Sp. z o.o.
 * Author: Yaroslav Shatkevich
 * Date: 17.10.2016
 * Time: 10:47
 */

require_once 'console.php';

class DumpFixer
{
    private $params;
    private $foundDatabase;

    public function fixDump()
    {
        Console::write('MySQL Dump fixer v1.0');
        Console::write('=====================');

        $this->transformDumpFile();
    }

    private function transformDumpFile()
    {
        $source = fopen($this->getDumpPath(), 'r');
        $destination = fopen($this->getDestinationPath(), 'w');

        while ($line = fgets($source)) {
            fwrite($destination, $line);

            $matches = [];
            if (preg_match_all('/^-- Host: .+ Database: (.+)/', $line, $matches)) {
                $this->foundDatabase = $matches[1][0];
            }

            if (trim($line) == '/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;') {
                if ($this->foundDatabase) {
                    fwrite($destination, PHP_EOL);
                    fwrite($destination, $this->prepareSql('CREATE SCHEMA `%s` DEFAULT CHARACTER SET utf8;'));
                    fwrite($destination, $this->prepareSql('USE `%s`;'));

                    $this->foundDatabase = null;
                }
            }
        }

        fclose($destination);
        fclose($source);
    }

    private function getDumpPath()
    {
        $dumpPath = $this->getCommandLineParam('input');

        if (!file_exists($dumpPath)) {
            throw new \Exception('Dump file not exists');
        }

        return $dumpPath;
    }

    private function getCommandLineParam($paramName)
    {
        if (!$this->params) {
            $this->params = getopt('', ['input:', 'output:']);
        }

        $param = $this->params[$paramName];

        if (!$param) {
            throw new \Exception(sprintf('--%s param is required', $paramName));
        }

        return $param;
    }

    private function getDestinationPath()
    {
        $path = $this->getCommandLineParam('output');

        return $path;
    }

    private function prepareSql($sql)
    {
        return sprintf($sql . PHP_EOL, $this->foundDatabase);
    }
}

$fixer = new DumpFixer();
$fixer->fixDump();
