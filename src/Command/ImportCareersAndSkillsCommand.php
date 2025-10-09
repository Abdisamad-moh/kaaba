<?php

namespace App\Command;

use App\Entity\MetierCareers;
use App\Entity\MetierSkills;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;


class ImportCareersAndSkillsCommand extends Command
{
    protected static $defaultName = 'app:import-careers-skills';

    private EntityManagerInterface $entityManager;
    private KernelInterface $kernel;

    public function __construct(EntityManagerInterface $entityManager, KernelInterface $kernel)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->kernel = $kernel;
    }

    protected function configure()

    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Imports careers and skills from an Excel file')
            ->setHelp('This command allows you to import careers and their associated skills from an Excel file into the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Get the root directory of the project
        $projectDir = $this->kernel->getProjectDir();

        // Set the file path to the Excel file located in public/assets
        $filePath = $projectDir . '/public/assets/Careers_Skills_File.xlsx';

        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        $careerRepo = $this->entityManager->getRepository(MetierCareers::class);

        // Fetch all existing careers from the database
        $existingCareers = $careerRepo->findAll();
        $existingCareersIndexed = [];
        foreach ($existingCareers as $career) {
            $existingCareersIndexed[$career->getName()] = $career;
        }

        foreach ($worksheet->getRowIterator() as $row) {
            $careerName = $worksheet->getCell("A" . $row->getRowIndex())->getValue();
            $skillName = $worksheet->getCell("B" . $row->getRowIndex())->getValue();

            // Skip if careerName or skillName is null or empty
            if (empty($careerName) || empty($skillName)) {
                continue;
            }

            // Check if the career exists in the database
            if (isset($existingCareersIndexed[$careerName])) {
                $career = $existingCareersIndexed[$careerName];

                // Create a new MetierSkills entity for the skill
                $skill = new MetierSkills();
                $skill->setName($skillName);
                $skill->setCareer($career);
                $skill->setCareerName($careerName);

                $career->addSkill($skill); // Add the skill to the career entity (important)
                $this->entityManager->persist($skill);
            }
        }

        // Flush all changes to the database
        $this->entityManager->flush();

        $output->writeln('Careers and skills have been successfully imported.');

        return Command::SUCCESS;
    }
    
}