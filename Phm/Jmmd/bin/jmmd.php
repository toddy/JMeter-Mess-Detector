<?php

use Phm\Jmmd\JMeter\Normalizer\Normalizer;

use Phm\Jmmd\Filter\UrlBlackListFilter;

use Phm\Jmmd\Rule\NotFoundStatusCode;

use Phm\Jmmd\Filter\UrlWhiteListFilter;

use Phm\Jmmd\Filter\DuplicateFilter;

use Symfony\Component\Console\Input\InputOption;

use Phm\Jmmd\Report\CsvFormat;

use Phm\Jmmd\Rule\ErrorStatusCode;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatter;

use Phm\Jmmd\Jmmd;
use Phm\Jmmd\JMeter\JMeterReport;
use Phm\Jmmd\Rule\ElapsedTimeRule;
use Phm\Jmmd\Report\TextReport;

include_once __DIR__ . "/autoload.php";

$console = new Application();
$console->register("analyze")
    ->setDefinition(
        array(new InputArgument('inputFileName', InputArgument::REQUIRED, 'JMeter report file'),
              new InputArgument('outputFileName', InputArgument::REQUIRED, 'xUnit output file'),
        		  new InputOption('maxElapsedTime', null, InputArgument::OPTIONAL, 'Max elapsed time', '200'),
        		))->setDescription("Analyzing a JMeter log file.")
    ->setHelp("Analyzing a JMeter log file.")
    ->setCode(function (InputInterface $input, OutputInterface $output)
    {
      runAnalyzer($input, $output);
    });

$console->run();

function runAnalyzer(InputInterface $input, OutputInterface $output)
{
  $output->writeln("Analyzing " . $input->getArgument('inputFileName'));

  $JMeterReport = JMeterReport::createByJtlFile($input->getArgument('inputFileName'));

  $jmmd = new Jmmd();

  $jmmd->addRule(new ElapsedTimeRule($input->getOption('maxElapsedTime')));
  $jmmd->addRule(new ErrorStatusCode());
  $jmmd->addRule(new NotFoundStatusCode());
  
  $jmmd->addFilter(new DuplicateFilter());  
  
  $whiteListFilter = new UrlWhiteListFilter();
  $whiteListFilter->addRegEx("#\/\d+\/[^\/]+\.html(\?.+)?$#");
  $whiteListFilter->addRegEx("#http://image.gala.de/$#");
  $whiteListFilter->addRegEx("#_\d+\.html(\?.+)?$#");
  $whiteListFilter->addRegEx("#^(\/syndication\/mobile\_feed\.php|\/video\/bc_feed.php|\/rss\/(gala_rss|beauty)\.html)(\?.+)?$#" );
  $jmmd->addFilter($whiteListFilter);
  
  $blackListFilter = new UrlBlackListFilter();
  $blackListFilter->addRegEx('#archiveid#');
  $blackListFilter->addRegEx('#Uebersicht.html#');
  $jmmd->addFilter($blackListFilter);
  
  $normalizer = new Normalizer();
  $normalizedJMeterReport = $normalizer->getNormalizedReport($JMeterReport);
  unset( $JMeterReport);
  
  $violations = $jmmd->detect($normalizedJMeterReport);

  $textReport = new CsvFormat();

  file_put_contents($input->getArgument('outputFileName'), $textReport->createReport($violations));

  if (count($violations) > 0)
  {
  	$violationCount = 0;
  	foreach($violations as $violations) {
  		$violationCount += count( $violations );
  	}
  	$output->writeln("<error>".$violationCount." violations found.</error>");
    exit(1);
  }
  else
  {
  	$output->writeln("<info>No violations found.</info>");
  	exit(0);
  }
}