<?php

namespace App\DataFixtures;

use App\Entity\EmployerJobs;
use App\Entity\MetierAppSetting;
use App\Entity\MetierCareers;
use App\Entity\MetierGender;
use App\Entity\MetierJobCategory;
use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setName("admin");
        $user->setEmail("admin@systesa.com");
        $user->setUsername("admin@systesa.com");
        $user->settype("super admin");
        $user->setStatus(true);
        $user->setRoles(["ROLE_SUPER_ADMIN"]);
        $user->setVerified(true);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'alhamdulilah'));


        $new_app = new MetierAppSetting();
        $new_app->setName("Metier Quest");
        $new_app->setAddress("Burj Khalifa, Dubai");
        $new_app->setWhatsapp("34243423");
        $new_app->setPhone("34243423");
        $new_app->setEmail("info@metierquest.com");
        $new_app->setLogo("logo-dark.png");

        $genders = ['Male', 'Female'];
        foreach($genders as $gender)
        {
            $new_gender = new MetierGender();
            $new_gender->setName($gender);
        }
        
        $manager->persist($new_app);
        $manager->persist($user);
        $manager->flush();

        $employer = new User();
        $employer->setName('employer test');
        $employer->setEmail('emp@gmail.com');
        $employer->setRoles(['ROLE_EMPLOYER']);
        $employer->setPassword('pass');
        $employer->setUsername('employer');
        $employer->setStatus(1);
        $employer->setType('employer');
        $manager->persist($employer);
        $manager->flush();

        for($i = 0; $i <= 30; $i++)
        {
            
            $job = new EmployerJobs();
            $job->setEmployer($employer);
            $job->setJobCategory($manager->getRepository(MetierJobCategory::class)->find(1));
            $job->setJobDescription('Job description goes here, Job description goes here, Job description goes here, Job description goes here, Job description goes here, Job description goes here, ');
            $job->setJobtitle($manager->getRepository(MetierCareers::class)->find(1));
            $job->setMaximumPay(5000);
            $job->setMinimumPay(3000);
            $job->setApplicationClosingDate(new \DateTime('+10 days'));
            $job->setOperation('job');
            $manager->persist($job);
            $manager->flush();
        }

    }
}
