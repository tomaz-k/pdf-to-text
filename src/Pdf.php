<?php

namespace Spatie\PdfToText;

use Spatie\PdfToText\Exceptions\CouldNotExtractText;
use Spatie\PdfToText\Exceptions\MalformedOption;
use Spatie\PdfToText\Exceptions\PdfNotFound;
use Symfony\Component\Process\Process;

class Pdf
{
    protected $pdf;

    protected $binPath;

    protected $options = [];

    public function __construct(string $binPath = null)
    {
        $this->binPath = $binPath ?? '/usr/bin/pdftotext';
    }

    public function setPdf(string $pdf) : self
    {
        if (!is_readable($pdf)) {
            throw new PdfNotFound(sprintf('could not find or read pdf `%s`', $pdf));
        }

        $this->pdf = $pdf;

        return $this;
    }

    public function setOptions(array $options) : self
    {
        $this->options = array_map([$this, 'formatOption'], $options);

        return $this;
    }

    protected function formatOption(string $content) : string
    {
        $content = trim($content);
        if ('-' === $content[0] ?? '') {
            return $content;
        }

        return '-'.$content;
    }

    public function text() : string
    {
        $arguments = $this->options;
        $arguments[] = escapeshellarg($this->pdf);
        $arguments[] = '-';

        $commandline = $this->binPath.' '.implode(' ', $arguments);
        $process = new Process($commandline);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new CouldNotExtractText($process);
        }

        return trim($process->getOutput(), " \t\n\r\0\x0B\x0C");
    }

    public static function getText(string $pdf, string $binPath = null, array $options = []) : string
    {
        return (new static($binPath))
            ->setOptions($options)
            ->setPdf($pdf)
            ->text()
        ;
    }
}
