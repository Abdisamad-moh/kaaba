<?php

namespace App\Command;

use Symfony\Component\Mime\Email;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;

#[AsCommand(
    name: 'app:send-test-email',
    description: 'Add a short description for your command',
)]
class SendTestEmailCommand extends Command
{

    private $mailer;
    public function __construct(MailerInterface $mailer)
    {
        parent::__construct();

        $this->mailer = $mailer;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Send a test email')
            ->addArgument('to', InputArgument::REQUIRED, 'The recipient email address');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $to = $input->getArgument('to'); 

        $email = (new Email())
            ->from('metier.systesa.test@gmail.com')
            ->to($to)
            ->subject('Here goes the subject')
            ->text('The email content')
            ->html('<p style="color: red"> here goes a paragraph</p>');
            
        $this->mailer->send($email);

        $output->writeln('Test email sent to ' . $to);

        return Command::SUCCESS;
    }
}
