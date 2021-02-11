<?php


namespace DragoonBoots\YamlFormatter\Command;


use DragoonBoots\YamlFormatter\AnchorBuilder\AnchorBuilderOptions;
use DragoonBoots\YamlFormatter\FileFormatter;
use DragoonBoots\YamlFormatter\Yaml\YamlDumperOptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * YAML Formatter console command
 */
class Format extends Command
{
    /**
     * @var SymfonyStyle
     */
    private $io;


    protected function configure()
    {
        $this->setName('format')
            ->setDescription('Format YAML files')
            ->addOption(
                'override',
                'o',
                InputOption::VALUE_NONE,
                'Override .yamlformatter.json options'
            )->addOption(
                'indent',
                null,
                InputOption::VALUE_REQUIRED,
                'Number of spaces to indent',
                2
            )->addOption(
                'no-multiline-literal',
                null,
                InputOption::VALUE_NONE,
                'Write string literals with multiple lines with embedded escaped newlines instead of as a multi-line literal'
            )->addOption(
                'no-null-tilde',
                null,
                InputOption::VALUE_NONE,
                'Write null values as "null"'
            )->addOption(
                'no-anchors',
                null,
                InputOption::VALUE_NONE,
                'Do not create reference anchors'
            )->addOption(
                'anchors-include',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Regular expression for YAML path to generate anchors for. Keys are separated by periods. Defaults to generating anchors for everything.'
            )->addOption(
                'anchors-exclude',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Regular expression for YAML path to not generate anchors for.'
            )->addArgument(
                'INPUT',
                InputArgument::REQUIRED,
                'Input file or directory'
            )->addArgument(
                'OUTPUT',
                InputArgument::OPTIONAL,
                'Output file or directory. Will overwrite when not specified.'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('OUTPUT')) {
            $input->setArgument('OUTPUT', $input->getArgument('INPUT'));
        }
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Validate input
        // Check nonsensical options
        $noAnchors = $input->getOption('no-anchors');
        $anchorsInclude = $input->getOption('anchors-include');
        $anchorsExclude = $input->getOption('anchors-exclude');
        if ($noAnchors && ($anchorsInclude || $anchorsExclude)) {
            $this->io->error('Cannot specify anchors to include/exclude when no anchors will be created.');

            return Command::FAILURE;
        }
        // Check regexes
        foreach (['anchors-include', 'anchors-exclude'] as $anchorPatternClass) {
            foreach ($input->getOption($anchorPatternClass) as $pattern) {
                // Don't care about the match, just that it's syntactically valid.
                if (preg_match($pattern, '') === false) {
                    $this->io->error('The regular expression "'.$pattern.'" is invalid: '.preg_last_error_msg());

                    return Command::FAILURE;
                }
            }
        }
        // Input directory requires an output directory
        $inputPath = $input->getArgument('INPUT');
        $outputPath = $input->getArgument('OUTPUT');
        if (is_dir($inputPath) && is_file($outputPath)) {
            $this->io->error('Input directory requires an output directory, file specified.');

            return Command::FAILURE;
        }

        // Set options
        $options = new YamlDumperOptions();
        $options->setIndentation($input->getOption('indent'))
            ->setMultiLineLiteral(!$input->getOption('no-multiline-literal'))
            ->setNullAsTilde(!$input->getOption('no-null-tilde'))
            ->setNoOptionsFile($input->getOption('override'));
        if ($noAnchors) {
            $options->setAnchors(null);
        } else {
            $anchorsOptions = new AnchorBuilderOptions($anchorsInclude, $anchorsExclude);
            $options->setAnchors($anchorsOptions);
        }

        // Format file(s)
        $fileFormatter = new FileFormatter($options);
        $progress = $this->io->createProgressBar();
        $fileFormatter->format(
            $inputPath,
            $outputPath,
            function (int $current, int $total, string $filename) use ($progress) {
                $progress->setMaxSteps($total);
                $progress->setProgress($current);
                $progress->setMessage($filename);
            }
        );

        return Command::SUCCESS;
    }

}
