<?php

declare (strict_types=1);

namespace Rector\ChangesReporting\Output;

use Rector\ChangesReporting\Contract\Output\OutputFormatterInterface;
use Rector\Core\ValueObject\Configuration;
use Rector\Core\ValueObject\ProcessResult;
use Rector\Core\ValueObject\Reporting\FileDiff;
use RectorPrefix202212\Symfony\Component\Console\Style\SymfonyStyle;

final class CheckstyleOutputFormatter implements OutputFormatterInterface
{
    /**
     * @var string
     */
    public const NAME = 'checkstyle';

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    public function __construct(SymfonyStyle $symfonyStyle)
    {
        $this->symfonyStyle = $symfonyStyle;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function report(ProcessResult $processResult, Configuration $configuration): void
    {
        $this->symfonyStyle->writeln('<?xml version="1.0" encoding="UTF-8"?>');
        $this->symfonyStyle->writeln('<checkstyle>');

        foreach ($processResult->getFileDiffs() as $fileDiff) {
            $this->writeFileErrors($fileDiff);
        }

        $this->writeNonFileErrors($processResult);
        $this->symfonyStyle->writeln('</checkstyle>');
    }

    private function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function writeFileErrors(FileDiff $fileDiff): void
    {
        $this->symfonyStyle->writeln(sprintf('<file name="%s">', $this->escape($fileDiff->getRelativeFilePath())));
        foreach ($fileDiff->getRectorChanges() as $rectorChange) {
            $message = $rectorChange->getRectorDefinitionsDescription() . ' (Reported by: ' . $rectorChange->getRectorClass() . ')';
            $message = $this->escape($message);

            $error = sprintf(
                '  <error line="%d" column="1" severity="error" message="%s" />',
                $this->escape((string) $rectorChange->getLine()),
                $message
            );
            $this->symfonyStyle->writeln($error);
        }

        $this->symfonyStyle->writeln('</file>');
    }

    private function writeNonFileErrors(ProcessResult $processResult): void
    {
        if ($processResult->getErrors() !== []) {
            $this->symfonyStyle->writeln('<file>');

            foreach ($processResult->getErrors() as $error) {
                $escapedMessage = $this->escape($error->getMessage());

                $this->symfonyStyle->writeln(
                    sprintf('    <error severity="error" message="%s" />', $escapedMessage)
                );
            }

            $this->symfonyStyle->writeln('</file>');
        }
    }
}
