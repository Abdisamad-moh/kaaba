<?php
    namespace App\Command;

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;
    use Symfony\Component\Console\Input\InputArgument;
    use Symfony\Component\Console\Style\SymfonyStyle;
    use Doctrine\ORM\EntityManagerInterface;
    use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
    use App\Entity\MetierCareers;
    
    class ImportCareersCommand extends Command
    {
        protected static $defaultName = 'app:import-careers';
    
        private $entityManager;
    
        public function __construct(EntityManagerInterface $entityManager)
        {
            parent::__construct();
            $this->entityManager = $entityManager;
        }
    
        protected function configure()
        {
            $this
                ->setDescription('Imports career titles from an Excel file')
                ->addArgument('excelFile', InputArgument::REQUIRED, 'Path to the Excel file')
                ->setName('app:import-careers'); // Set a non-empty name here
        }
    
        protected function execute(InputInterface $input, OutputInterface $output): int
        {
            $io = new SymfonyStyle($input, $output);
            $excelFile = $input->getArgument('excelFile');
    
            try {
                $reader = new Xlsx();
                $spreadsheet = $reader->load($excelFile);
                $worksheet = $spreadsheet->getActiveSheet();
    
                $highestRow = $worksheet->getHighestRow();
    
                for ($row = 2; $row <= $highestRow; $row++) {
                    $name = $worksheet->getCell("A$row")->getValue();
    
                    $existingCareer = $this->entityManager->getRepository(MetierCareers::class)->findOneBy(['name' => $name]);
    
                    if (!$existingCareer) {
                        $career = new MetierCareers();
                        $career->setName($name);
    
                        $this->entityManager->persist($career);
                        $this->entityManager->flush();
    
                        $io->success("Created new MetierCareers: {$name}");
                    } else {
                        $io->info("MetierCareers already exists: {$name}");
                    }
                }
    
                $io->success('Import completed successfully!');
            } catch (\Exception $e) {
                $io->error('An error occurred during import: ' . $e->getMessage());
            }
    
            return 0; // Return 0 to indicate success
        }
    }
?>