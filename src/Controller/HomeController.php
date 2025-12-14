<?php
namespace App\Controller;
use App\Entity\User;
use App\Form\QaFormType;
use App\Util\VectorUtils;

use App\Form\ContactFormType;
use App\Entity\MetierContacts;
use App\Entity\MetierPackages;
use App\Service\OpenAIService;
use Flasher\Notyf\Prime\Notyf;
use App\Entity\EmployerDetails;
use App\Entity\KaabaApplication;
use App\Entity\JobSeekerSavedJob;
use App\Entity\MetierJobCategory;
use App\Entity\MetierJobIndustry;
use App\Service\ApplicationLogger;
use App\Service\RecaptchaValidator;
use App\Service\TranslationService;
use Flasher\Prime\FlasherInterface;
use App\Entity\KaabaApplicationStatus;
use App\Form\KaabaApplicationFormType;
use App\Entity\JobApplicationShortlist;
use App\Form\ProductsAutoCompleteField;
use App\Repository\MetierAdsRepository;
use Flasher\Notyf\Prime\NotyfInterface;
use App\Repository\MetierBlogRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\KaabaCourseRepository;
use App\Repository\KaabaGenderRepository;
use App\Repository\KaabaRegionRepository;
use App\Repository\MetierOrderRepository;
use Symfony\Component\Form\FormInterface;
use App\Repository\EmployerJobsRepository;
use App\Repository\KaabaDistrictRepository;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\EmployerTenderRepository;
use App\Repository\KaabaInstituteRepository;
use App\Repository\MetierPackagesRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\KaabaNationalityRepository;
use App\Repository\KaabaScholarshipRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\KaabaIdentityTypeRepository;
use App\Repository\KaabaQualificationRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File as HttpFoundationFile;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/')]
class HomeController extends AbstractController
{
    private $requestStack;
    private $parameterBag;
    private $openAIService;

    public function __construct(ParameterBagInterface $parameterBag, RequestStack $requestStack, OpenAIService $openAIService)
    {
        $this->requestStack = $requestStack;
        $this->openAIService = $openAIService;
    }

    
   


    #[Route('/api/session-lifetime', name: 'session_lifetime')]
    public function getSessionLifetime(): JsonResponse
    {
        $request = $this->requestStack->getCurrentRequest();
        $session = $request ? $request->getSession() : null;

        if ($session) {
            // Use PHP ini_get to retrieve session cookie lifetime
            $cookieLifetime = ini_get('session.cookie_lifetime'); // Lifetime in seconds
            return new JsonResponse(['cookie_lifetime' => (int) $cookieLifetime]);
        }

        return new JsonResponse(['error' => 'Session not available'], 500);
    }

    
    #[Route('/routef_message', name: 'app_home_access_denied')]
    public function routef_message(): Response
    {
        return $this->render('access-denied.html.twig');
    }
 
    #[Route('/terms', name: 'app_home_terms')]
    public function terms(): Response
    {
        return $this->render('home/terms.html.twig');
    }
    #[Route('/refundPolicy', name: 'app_home_refundPolicy')]
    public function refundPolicy(): Response
    {
        return $this->render('home/refund_policy.html.twig');
    }
    #[Route('/cookiesPolicy', name: 'app_home_cookiesPolicy')]
    public function cookiesPolicy(): Response
    {
        return $this->render('home/cookies_policy.html.twig');
    }
    #[Route('/privacy', name: 'app_home_privacy')]
    public function privacy(): Response
    {
        return $this->render('home/privacy.html.twig');
    }

  

   

   
   
    #[Route('/serveOnlyImage/{filename}', name: 'profile_serve_only_image')]
    public function serveOnlyImage(string $filename, string $type = null, Request $request): Response
    {
        // Check user authentication
        $imagePath = $this->getParameter('employer_profile_images_directory') . '/' . $filename;
        // Path to the image
        if ($type === "product") {
        }
        $imagePath = $this->getParameter('product_images_directory') . '/' . $filename;

        if (!file_exists($imagePath)) {
            throw $this->createNotFoundException('Image not found.');
        }

        try {
            $file = new HttpFoundationFile($imagePath);
        } catch (FileNotFoundException $e) {
            throw $this->createNotFoundException('Image not found.');
        }

        return $this->file($file);
    }
   

   

  

    private function getFormErrors(FormInterface $form)
    {
        $errors = [];

        // Global errors that do not belong to a specific field
        foreach ($form->getErrors() as $error) {
            $errors['global'][] = $error->getMessage();
        }

        // Field specific errors
        foreach ($form as $fieldName => $formField) {
            foreach ($formField->getErrors(true) as $error) {
                $errors[$fieldName][] = $error->getMessage();
            }
        }

        return $errors;
    }

  


    //  #[Route('/scholarships', name: 'app_scholarships')]
//     public function scholarships(KaabaScholarshipRepository $kaabaScholarshipRepository): Response
//     {
//         // Fetch only active scholarships
//         $scholarships = $kaabaScholarshipRepository->findBy(['status' => true], ['closing_date' => 'ASC']);

    //         return $this->render('home/scholarships.html.twig', [
//             'scholarships' => $scholarships,
//         ]);
//     }


    // #[Route('/', name: 'app_home')]
    // public function index(KaabaScholarshipRepository $repo): Response
    // {
    //     $scholarships = $repo->findBy(['status' => true], ['closing_date' => 'ASC']);

    //     return $this->render('home/index.html.twig', [
    //         'scholarships' => $scholarships,
    //     ]);
    // }

// #[Route('/{_locale}', name: 'app_home', requirements: ['_locale' => 'en|so'], defaults: ['_locale' => 'en'])]
// public function SomaliIndex(KaabaScholarshipRepository $repo, Request $request): Response
// {
//     $scholarships = $repo->findBy(['status' => true], ['closing_date' => 'ASC']);
// $session = $this->requestStack->getSession();
//       $s =  $session->get('app_language', 'en');
//     $template = $request->getLocale() === 'so' 
//         ? 'home/index_so.html.twig' 
//         : 'home/index.html.twig';

//     return $this->render($template, [
//         'scholarships' => $scholarships,
//         'lang' => $s,
//     ]);
// }



#[Route('/{_locale}', name: 'app_home', requirements: ['_locale' => 'en|so'], defaults: ['_locale' => 'so'])]
public function SomaliIndex(KaabaScholarshipRepository $repo, Request $request, TranslationService $translationService): Response
{
    $scholarships = $repo->findBy(['status' => true], ['closing_date' => 'ASC']);
    
    // Set the language in session based on the request locale
    $translationService->setLanguage($request->getLocale());
    
    // Get the current language from session
    $currentLang = $translationService->getCurrentLanguage();
    
    $template = $currentLang === 'so' 
        ? 'home/index_so.html.twig' 
        : 'home/index.html.twig';

    return $this->render($template, [
        'scholarships' => $scholarships,
        'lang' => $currentLang,
    ]);
}
#[Route('/scholarship/apply/{uuid}', name: 'app_scholarship_application')]
public function scholarshipApplication(
    Request $request,
    string $uuid,
    KaabaScholarshipRepository $scholarshipRepository,
    KaabaRegionRepository $regionRepository,
    KaabaDistrictRepository $districtRepository,
    KaabaGenderRepository $genderRepository,
    KaabaNationalityRepository $nationalityRepository,
    KaabaInstituteRepository $instituteRepository,
    KaabaQualificationRepository $qualificationRepository,
    KaabaCourseRepository $courseRepository,
    EntityManagerInterface $em,
    KaabaIdentityTypeRepository $identityTypeRepository,
    TranslationService $translationService,
  ApplicationLogger $applicationLogger,
): Response {
    $session = $this->requestStack->getSession();
    $currentLang = $session->get('app_language', 'en');

    // Find scholarship by UUID
    $scholarship = $scholarshipRepository->findOneBy(['uuid' => $uuid]);

  
    if (!$scholarship) {
        throw $this->createNotFoundException('Scholarship not found.');
    }

    // Check if scholarship is active and not expired
    if (!$scholarship->isStatus() || $scholarship->getClosingDate() < new \DateTime()) {
        $this->addFlash('error', 'This scholarship is no longer available for applications.');
        return $this->redirectToRoute('app_home');
    }

    // Get scholarship type
    $type = $scholarship->getType();

    // Filter regions based on scholarship type
   if ($type === 't') {
    // For type 't', only show Maroodi Jeex and Togdheer
    $regions = $regionRepository->createQueryBuilder('r')
        ->where('r.name IN (:names)')
        ->setParameter('names', ['Maroodi Jeex', 'Togdheer'])
        ->orderBy('r.name', 'ASC')
        ->getQuery()
        ->getResult();
} else {
    $regions = $regionRepository->findAll();
}


    $application = new KaabaApplication();
    $application->setScholarship($scholarship);
    
    // Get institutes that belong to this scholarship
    $scholarshipInstitutes = $scholarship->getInstitutes();

    // Create form with filtered institutes
    $form = $this->createForm(KaabaApplicationFormType::class, $application, [
 'regions' => $regions, // Pass filtered regions to form
        'institutes' => $scholarshipInstitutes // Pass filtered institutes to form
    ]);
    
    // Get scholarship type
    $type = $scholarship->getType();

    // Remove literacy_level field if scholarship type is 'l'
    if ($type == 'l') {
        $form->remove('secondary_region');
        $form->remove('secondary_school');
        $form->remove('secondary_graduation_year');
        $form->remove('secondary_grade');
        $form->remove('highest_qualification');
        $form->remove('highest_qualification_detail');
        $form->remove('institution_name');
        $form->remove('location');
        $form->remove('start_year');
        $form->remove('end_year');
        $form->remove('qualification');
        $form->remove('minimum_grade');
        $form->remove('enrollment_course');
        $form->remove('enrollment_school');
        $form->remove('institute');
        $form->remove('course');
    }
    if ($type == 't' || $type == 'h') {
        $form->remove('literacy_level');
        $form->remove('numeracy_level');
        $form->remove('recent_education');
        $form->remove('literacy_numeracy_qualification');
    }

    // Set default status (Applied)
    $appliedStatus = $em->getRepository(KaabaApplicationStatus::class)->find(1);
    if ($appliedStatus) {
        $application->setStatus($appliedStatus);
    }

    // Handle AJAX requests
    if ($request->isXmlHttpRequest()) {
        $action = $request->query->get('action');
        
        if ($action === 'get_districts') {
            $regionId = $request->query->get('region_id');
            $region = $regionRepository->find($regionId);

            if (!$region) {
                return new JsonResponse([]);
            }

            $districts = $districtRepository->findBy(['region' => $region]);
            $districtArray = [];

            foreach ($districts as $district) {
                $districtArray[] = [
                    'id' => $district->getId(),
                    'name' => $district->getName()
                ];
            }

            return new JsonResponse($districtArray);
        }
        
        // Handle AJAX request for courses by institute
        if ($action === 'get_courses') {
            $instituteId = $request->query->get('institute_id');
            $institute = $instituteRepository->find($instituteId);

            if (!$institute) {
                return new JsonResponse([]);
            }

            $courses = $courseRepository->findBy(['institute' => $institute]);
            $courseArray = [];

            foreach ($courses as $course) {
                $courseArray[] = [
                    'id' => $course->getId(),
                    'name' => $course->getName()
                ];
            }

            return new JsonResponse($courseArray);
        }
    }

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        try {
            // Handle file uploads
            $fileFields = [
                'identity_attachment',
                'certificate_attachment'
               
            ];

         foreach ($fileFields as $field) {
    if ($form->has($field)) {
        $file = $form->get($field)->getData();
        
        if ($file) {
            // Validate file type and size
            $allowedMimeTypes = [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/jpg',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];
            
            $fileMimeType = $file->getMimeType();
            
            if (!in_array($fileMimeType, $allowedMimeTypes)) {
                $this->addFlash('error', 'Invalid file type for ' . $field . '. Allowed types: PDF, JPG, PNG, DOC, DOCX.');
                return $this->redirectToRoute('app_scholarship_application', ['uuid' => $uuid]);
            }
            
            if ($file->getSize() > 20 * 1024 * 1024) { // 20MB limit
                $this->addFlash('error', 'File too large for ' . $field . '. Maximum size is 20MB.');
                return $this->redirectToRoute('app_scholarship_application', ['uuid' => $uuid]);
            }

            // Generate unique filename with fallback for transliterator
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            
            // Use transliterator if available, otherwise use simple sanitization
            if (function_exists('transliterator_transliterate')) {
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
            } else {
                // Fallback sanitization
                $safeFilename = preg_replace('/[^A-Za-z0-9_-]/', '', $originalFilename);
                $safeFilename = strtolower($safeFilename);
            }
            
            $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

            // Move file to uploads directory
            $file->move(
                $this->getParameter('application_attachments'),
                $newFilename
            );

            // Set filename in entity
            $setter = 'set' . str_replace('_', '', ucwords($field, '_'));
            $application->$setter($newFilename);
        }
    } 
}


            $em->persist($application);

    
            // ✅ LOG APPLICATION CREATION
            $applicationLogger->log(
                $application,
                'applied',
                null, // No additional note needed
                null  // No user since applicant is submitting
            );
            $em->flush();

            $this->addFlash('success', 'Your application has been submitted successfully!');
            return $this->redirectToRoute('app_home');

        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    // Fetch all entities for dropdowns
   // $regions = $regionRepository->findAll();
    $genders = $genderRepository->findAll();
    $nationalities = $nationalityRepository->findAll();
    $institutes = $scholarshipInstitutes; // Use the filtered institutes
    $qualifications = $qualificationRepository->findAll();
    $courses = $courseRepository->findAll();
    $identityTypes = $identityTypeRepository->findAll();

    return $this->render('home/application.html.twig', [
        'scholarship' => $scholarship,
        'form' => $form->createView(),
        'regions' => $regions,
        'genders' => $genders,
        'nationalities' => $nationalities,
        'institutes' => $institutes,
        'qualifications' => $qualifications,
        'courses' => $courses,
        'identityTypes' => $identityTypes,
        'site_key' => $this->getParameter('recaptcha.site_key'),
        'translations' => $translationService->getAllTranslations(),
        'trans' => $translationService,
        'lang' => $currentLang,
        'type' => $type,
    ]);
}

#[Route('/change-language/{lang}', name: 'app_change_language')]
    public function changeLanguage(string $lang, TranslationService $translationService, Request $request): JsonResponse
    {
        $allowedLanguages = ['en', 'so'];
        
        if (!in_array($lang, $allowedLanguages)) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid language']);
        }

        $translationService->setLanguage($lang);

        return new JsonResponse([
            'success' => true,
            'language' => $lang,
            'message' => 'Language changed successfully'
        ]);
    }

}
