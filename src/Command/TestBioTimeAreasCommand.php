<?php 
// src/Command/TestBioTimeAreasCommand.php
namespace App\Command;

use App\Service\BioTimeService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:test-biotime-areas',
    description: 'Test BioTime Areas API connection and response format'
)]
class TestBioTimeAreasCommand extends Command
{
    protected static $defaultName = 'app:test-biotime-areas';
    
    private BioTimeService $bioTimeService;
    
    public function __construct(BioTimeService $bioTimeService)
    {
        $this->bioTimeService = $bioTimeService;
        parent::__construct();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Testing BioTime Areas API...');
        
        try {
            // Test authentication
            if (!$this->bioTimeService->authenticate()) {
                $output->writeln('<error>Authentication failed</error>');
                return Command::FAILURE;
            }
            
            $output->writeln('<info>✓ Authentication successful</info>');
            
            // Get areas
            $areas = $this->bioTimeService->getAllAreas();
            
            $output->writeln("\n<comment>Raw API Response:</comment>");
            $output->writeln(json_encode($areas, JSON_PRETTY_PRINT));
            
            $output->writeln("\n<comment>Response Structure:</comment>");
            $this->printStructure($areas, $output);
            
            // Try direct API call
            $output->writeln("\n<comment>Testing direct API call:</comment>");
            $directCall = $this->bioTimeService->request('GET', 'areas/');
            $output->writeln(json_encode($directCall, JSON_PRETTY_PRINT));
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            $output->writeln('<error>Trace: ' . $e->getTraceAsString() . '</error>');
            return Command::FAILURE;
        }
    }
    
    private function printStructure($data, OutputInterface $output, $indent = ''): void
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $output->writeln($indent . $key . ': ' . gettype($value));
                if (is_array($value) && count($value) > 0) {
                    $this->printStructure($value, $output, $indent . '  ');
                }
            }
        }
    }
}