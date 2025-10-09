<?php 
namespace App\Service;

use Carbon\Carbon;
use App\Entity\User;
use Twig\TwigFilter;
use App\Entity\MetierChat;
use App\Model\JobStatusEnum;
use Twig\Extension\AbstractExtension;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\File\File as HttpFoundationFile;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CarbonExtension extends AbstractExtension
{
    private $em;
    private Security $security;
    private ParameterBagInterface $parameterBag;
    public function __construct(EntityManagerInterface $em, Security $security, ParameterBagInterface $parameterBag){
        $this->em = $em;
        $this->security = $security;
        $this->parameterBag = $parameterBag;
    }
    public function getFilters()
    {
        return [
            new TwigFilter('diffForHumans', [$this, 'diffForHumans']),
            new TwigFilter('numberToString', [$this, 'numberToString']),
            new TwigFilter('educationNumberToString', [$this, 'educationNumberToString']),
            new TwigFilter('packageType', [$this, 'packageType']),
            new TwigFilter('emailMask', [$this, 'emailMask']),
            new TwigFilter('lastChat', [$this, 'lastChat']),
            new TwigFilter('serveImage', [$this, 'serveImage']),
            new TwigFilter('types', [$this, 'types']),
            new TwigFilter('encodeStatus', [$this, 'encodeStatus']),
            new TwigFilter('shortenHtml', [$this, 'shortenHtml']),
        ];
    }
    public function shortenHtml($html, $length = 220)
    {
        // Strip tags and shorten
        $text = strip_tags($html);
        if (strlen($text) <= $length) {
            return $html; // Return original if shorter than desired length
        }

        // Shorten the text and preserve HTML
        $shortened = mb_substr($text, 0, $length);
        $lastSpace = strrpos($shortened, ' '); // Find last space
        if ($lastSpace !== false) {
            $shortened = mb_substr($shortened, 0, $lastSpace); // Cut at last space
        }

        return $shortened . '...'; // Add ellipsis
    }
    public function encodeStatus(string $status): string
    {
        $statusEnum = JobStatusEnum::tryFrom($status);

        if ($statusEnum) {
            return $statusEnum->getEncoded();
        }

        throw new \InvalidArgumentException("Invalid job status: {$status}");
    }
    public function diffForHumans($date)
    {
        // dd(Carbon::instance($date)->diffForHumans());
        if ($date instanceof \DateTime) {
            return Carbon::instance($date)->diffForHumans();
        }

        return Carbon::parse($date)->diffForHumans();
    }
    public function types($number)
    {
        // dd(Carbon::instance($date)->diffForHumans());
        if ($number = 0) {
            return "";
        }else if($number == 1) {
            return "Role available";
        }else if($number > 1) {    
            return "Roles available";
         }

        
    }

    public function emailMask(string $email): string
    {
        $parts = explode('@', $email);
        $localPart = $parts[0];
        $domainPart = $parts[1];
        $maskedLocalPart = str_repeat('*', max(0, strlen($localPart) - 4)) . substr($localPart, -4);
        return $maskedLocalPart . '@' . $domainPart;
    }

    public function numberToString($number){
        $experiences = [
            0  => 'Entry-level (0-2 years)', 
            1  =>  'Intermediate or Mid-level (3-5 years)', 
            2  => 'Senior-level (6-8 years)', 
            3  => 'Managerial-level (9-12 years)', 
            4  => 'Director-level (13-15 years)',
            5  => 'Executive-level (16+ years)'
        ];
        if (isset($experiences[$number])) {
            $experienceString = $experiences[$number];
            return $experienceString;
          } else {
            return "N/A";
          }
    }
    public function educationNumberToString($number){
        $experiences = [
            0  => 'Domestic/Manual Worker', 
            1  =>  'Secondary/ High School', 
            2  => 'Diploma/Associate Degree', 
            3  => 'Bachelor\'s Degree', 
            4  => 'Master\'s Degree',
            5  => 'Doctorate/ PhD'
        ];
        if (isset($experiences[$number])) {
            $experienceString = $experiences[$number];
            return $experienceString;
          } else {
            return "N/A";
          }
    }
    public function packageType($number){
        
        if ($number == 6) {
            return "/6 Months";
          } else if($number == 12){
            return "/Annual";
          }else{
            return "/mo";
          }
    }
    public function lastChat(User $currentUser, User $clientUser){
        $chat = $this->em->getRepository(MetierChat::class)->findLastChatBetweenUsers($currentUser, $clientUser);
        return $chat;
    }

    public function serveImage(string $filename, string $type)
    {
        // Check user authentication
        // if (!$this->security->isGranted('ROLE_ADMIN') OR !$this->security->isGranted('ROLE_JOBSEEKER')) {
        //     throw new AccessDeniedException('You do not have access to this resource.');
        // }

        

        // Path to the image
        $imagePath = $this->parameterBag->get('employer_profile_images_directory') . '/' . $filename;
        // dd($imagePath);
        if (!file_exists($imagePath)) {
            dump('Image not found.');
        }

        try {
            $file = new HttpFoundationFile($imagePath);
        } catch (FileNotFoundException $e) {
            dump('Image not found.');
        }

        // return $this->file($file);
        // return new BinaryFileResponse($imagePath, 200, [], true, ResponseHeaderBag::DISPOSITION_INLINE);
        return $imagePath;
    }
}
