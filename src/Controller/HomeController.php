<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\QaFormType;
use App\Util\VectorUtils;
use App\Entity\MetierBlog;
use App\Entity\MetierCity;
use App\Entity\MetierOrder;
use App\Entity\MetierState;
use App\Entity\EmployerJobs;
use App\Entity\MetierSkills;
use App\Entity\MetierCareers;
use App\Entity\MetierCountry;
use App\Entity\MetierJobType;
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
use App\Repository\EmployerCoursesRepository;
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

    #[Route('/test_shortlist/{job?}')]
    public function test_shortlist(Request $request, EntityManagerInterface $em, EmployerJobs $job = null)
    {
        $applications = $job->getJobApplications()->getValues();

        foreach ($applications as $application) {
            $aplication_shortlists = $application->getAutomaticShortlists()->getValues();
            foreach ($aplication_shortlists as $shotlist) {
                $em->remove($shotlist);
                $em->flush();
            }

            $user = $application->getJobSeeker();

            if ($user->getJobSeekerResume()->getExperience() < $job->getExperience()) {
                continue;
            }

            $educations = $user->getJobSeekerEducation()->map(function ($edu) {
                return $edu->getCourse();
            })->toArray();

            $educations = implode(", ", $educations);
            $job_education_titles = $job->getEducationTitles();

            $job_skills = $job->getRequiredSkill()->map(function ($skill) {
                return $skill->getName();
            })->toArray();
            $job_skills = implode(", ", $job_skills);

            $job_seeker_skills = $user->getJobSeekerResume()->getSkills()->map(function ($skill) {
                return $skill->getName();
            })->toArray();
            $job_seeker_skills = implode(", ", $job_seeker_skills);




            $prompt = "
                Given the following job education and skills requirement and cv's provided skills and education by candidate, provide a relevance score from 1 to 100 with no explanation. return a number response like 45 (numerical value).
    
                Job Education:
                $job_education_titles
                Job Skills:
                $job_skills
    
                Cv Education:
                $educations
    
                Cv Skills:
                $job_seeker_skills
                
                the focus should on the skills, and if they don't much with at least 5 skills, don't favor them.
                Response format:
                80
            ";

            $response = $this->openAIService->compareJobAndCV('gpt-4o-mini', $prompt);
            $response = $response['data'];

            if ($response >= 65) {
                $shortlist = new JobApplicationShortlist();
                $shortlist->setApplication($application);
                $shortlist->setScore($response);
                $em->persist($shortlist);
                $em->flush();
            }

            // dump($response);
        }

        // dd('dead');
    }

    // #[Route('/', name: 'app_home')]
    // public function index(
    //     Request $request,
    //     EmployerJobsRepository $job_repo,
    //     EntityManagerInterface $em,
    //     MetierAdsRepository $metierAdsRepository,
    // ): Response {

    //     // Check if user is logged in
    //     if ($this->getUser() && $this->isGranted('ROLE_JOBSEEKER')) {
    //         // Check if user has job alerts set up
    //         $user = $em->getRepository(User::class)->find($this->getUser());
    //         if ($user->getJobalerts()->isEmpty()) {
    //             // notyf()->warning('Your account may not have been restored.');
    //             // dd("yes");
    //             notyf()
    //                 ->duration(10000)
    //                 ->ripple(true)
    //                 ->addWarning(
    //                     'Reminder! <a href="' . $this->generateUrl('app_jobalert_set') . '" 
    //             style="color: black; text-decoration: underline;">Click here</a> 
    //             to choose and receive job alerts for new opportunities that match your preferences, along with interview updates via SMS and email.',
    //                     ['escapeMarkup' => false]
    //                 );
    //             // $notyf->using('notyf')->addFlash(
    //             //     'warning', // or 'error', 'success', 'info'
    //             //     'Please <a href="'.$this->generateUrl('app_jobalert_set').'" 
    //             //     style="color: white; text-decoration: underline;">create job alerts</a> 
    //             //     to get notified about new jobs matching your preferences.',
    //             //     ['escapeMarkup' => false]
    //             // );
    //         }
    //     }

    //     $now = new \DateTime();
    //     // $request->getSession()->remove('profile_notification_shown');
    //     $saved_jobs = $em->getRepository(JobSeekerSavedJob::class)
    //         ->createQueryBuilder('s')
    //         ->join('s.job', 'j')
    //         ->where('s.jobSeeker = :jobSeeker')
    //         ->andWhere('j.is_private IS NULL OR j.is_private = false')
    //         ->setParameter('jobSeeker', $this->getUser())
    //         ->select('j.id')
    //         ->getQuery()
    //         ->getResult();

    //     $saved_jobs = array_column($saved_jobs, 'id');

    //     $jobs = $job_repo->createQueryBuilder('j')
    //         ->andWhere('j.status = :status')
    //         ->setParameter('status', 'posted')
    //         ->andWhere('j.is_private IS NULL OR j.is_private = false')
    //         ->andWhere('j.application_closing_date >= :dateNow') // Filter based on expiry
    //         ->setParameter('dateNow', $now)
    //         ->setMaxResults(3)
    //         ->orderBy('j.id', 'DESC')
    //         ->getQuery()
    //         ->getResult();


    //     // get company ads
    //     // dd($metierAdsRepository->findActiveAds());


    //     return $this->render('home/index.html.twig', [
    //         'controller_name' => 'HomeController',
    //         'jobs' => $jobs,
    //         'tenders' => [],
    //         'saved_jobs' => $saved_jobs,
    //         'ads' => $metierAdsRepository->findActiveAds(),
    //     ]);
    // }


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

    #[Route('/process', name: 'app_home_process')]
    public function employerprocess(Request $request, EntityManagerInterface $em): Response
    {
        $directory = $this->getParameter('product_images_directory');
        // dd($directory);
        $files = scandir($directory);

        $docxFiles = array_filter($files, function ($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'docx';
        });

        $webpFiles = array_filter($files, function ($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'webp';
        });

        foreach ($docxFiles as $docxFile) {
            $baseName = pathinfo($docxFile, PATHINFO_FILENAME);
            $webpFile = $baseName . '.webp';

            if (in_array($webpFile, $webpFiles)) {
                $package = new MetierPackages();
                $package->setName($baseName);
                $package->setDescription($baseName);
                $package->setType('jobseeker');
                $package->setCost('4');
                $package->setStatus(true);
                $package->setCategory('product');
                $package->setFile($docxFile);
                $package->setThumbnail($webpFile);
                $package->setClass('resume');
                $package->setDuration(0);

                $em->persist($package);
            } else {
                dd("no files");
            }
        }


        $em->flush();
        dd("success");
        return $this->render('home/employer_landing.html.twig');
    }
    #[Route('/landing', name: 'app_home_landing')]
    public function employerLanding(Request $request): Response
    {
        return $this->render('home/employer_landing.html.twig');
    }
    #[Route('/blogs', name: 'app_home_blogs')]
    public function blogs(
        MetierBlogRepository $blogs,
    ): Response {
        $blogs = $blogs->findBy(["blog_type" => 0]);
        return $this->render('home/blogs.html.twig', compact('blogs'));
    }
    #[Route('/routef_message', name: 'app_home_access_denied')]
    public function routef_message(): Response
    {
        return $this->render('access-denied.html.twig');
    }
    #[Route('/read-post/{blog}', name: 'app_home_read-post')]
    public function read_post(
        MetierBlogRepository $blogs,
        EntityManagerInterface $em,
        MetierBlog $blog
    ): Response {

        $similar_blogs = $em->getRepository(MetierBlog::class)->findBy(['category' => $blog->getCategory()]);

        return $this->render('home/blog.html.twig', compact('blog', 'similar_blogs'));
    }
    #[Route('/about-us', name: 'app_home_about')]
    public function about(
        MetierBlogRepository $blogs,
    ): Response {
        $blogs = $blogs->findAll();
        return $this->render('home/about.html.twig', compact('blogs'));
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

    #[Route('/fetch_industries', name: 'app_fetch_industries')]
    public function fetch_industries(Request $request, EntityManagerInterface $em)
    {
        $industries = $em->getRepository(MetierJobIndustry::class)->createQueryBuilder('i')
            ->select('i.name, i.id')
            ->getQuery()
            ->getArrayResult();

        $industries = array_map(function ($option) {
            return ['value' => $option['id'], 'text' => $option['name']];
        }, $industries);

        return $this->json([$industries, 200]);
    }

    #[Route('/fetch_careers', name: 'app_fetch_careers')]
    public function fetch_careers(Request $request, EntityManagerInterface $em)
    {

        $careers = $em->getRepository(MetierCareers::class)->createQueryBuilder('i')
            ->select('i.name, i.id')
            ->where('i.name LIKE :name')
            ->setParameter('name', '%' . $request->get('search') . '%')
            ->orderBy('i.name', 'ASC')
            ->setMaxResults(80)
            ->getQuery()
            ->getArrayResult();

        $careers = array_map(function ($option) {
            return ['value' => $option['id'], 'text' => $option['name']];
        }, $careers);

        return $this->json([$careers, 200]);
    }

    #[Route('/fetch_cities', name: 'app_fetch_cities')]
    public function fetch_cities(Request $request, EntityManagerInterface $em)
    {
        $search = $request->get('searchTerm');
        // $state = $request->get('state');
        $state = '';
        $country = $request->get('country');

        $cities = $em->getRepository(MetierCity::class)
            ->createQueryBuilder('c')
            ->select('c.id as value', 'c.name as text')
            ->leftJoin('c.country', 'country')
            ->leftJoin('c.state', 'state');

        if ($country && $country != 'null') {
            $cities->andWhere('country.id = :country')->setParameter('country', $country);
        }

        if ($state && $state != 'null') {
            $cities->andWhere('state.name = :state')->setParameter('state', $state);
        }

        if ($search && $search != 'null') {
            $cities->andWhere('c.name LIKE :q')->setParameter('q', '%' . $search . '%');
        }

        if ($country && $country == 'null'):
            $cities->setMaxResults(1000);
        else:
            $cities->setMaxResults(80);
        endif;

        $cities = $cities->getQuery()->getArrayResult();

        $cities = array_map(function ($option) {
            return ['value' => $option['value'], 'text' => $option['text']];
        }, $cities);

        return $this->json([$cities, 200]);
    }

    #[Route('/fetch_states', name: 'app_fetch_states')]
    public function fetch_states(Request $request, EntityManagerInterface $em)
    {
        $search = $request->get('searchTerm');
        $country = $request->get('country');
        $city = $request->get('city');

        if ($city) {
            $states = [];
            $city = $em->getRepository(MetierCity::class)->findOneBy(['name' => $city]);
            $state = $city->getState();
            if ($state)
                array_push($states, ['value' => $state->getId(), 'text' => $state->getName()]);
            return $this->json([$states, 200]);
        }

        $states = $em->getRepository(MetierState::class)
            ->createQueryBuilder('s')
            ->select('s.id as value', 's.name as text')
            ->leftJoin('s.country', 'country');

        if ($country && $country != 'null') {
            $states->andWhere('country.id = :country')->setParameter('country', $country);
        }


        if ($search && $search != 'null') {
            $states->andWhere('s.name LIKE :q')->setParameter('q', '%' . $search . '%');
        }

        if ($country && $country == 'null'):
            $states->setMaxResults(1000);
        else:
            $states->setMaxResults(80);
        endif;

        $states = $states->getQuery()->getArrayResult();

        $states = array_map(function ($option) {
            return ['value' => $option['value'], 'text' => $option['text']];
        }, $states);

        return $this->json([$states, 200]);
    }

    #[Route('/fetch_skills', name: 'app_fetch_skills')]
    public function fetchOptions(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $searchTerm = $request->query->get('search', '');
        $careerId = $request->query->get('career', null);
        $limit = $request->query->getInt('limit', 80);

        if (!is_numeric($careerId) && $careerId) {
            $careerId = $em->getRepository(MetierCareers::class)->findOneBy(['name' => $careerId])?->getId();
        }

        $qb = $em->getRepository(MetierSkills::class)->createQueryBuilder('s')
            ->leftJoin('s.metierCareers', 'mc');

        // Select additional data to check if the career is linked to the skill
        $qb->addSelect('s.id', 's.name', '(CASE WHEN mc.id = :careerId THEN 0 ELSE 1 END) AS priority')
            ->setParameter('careerId', $careerId);

        $qb->where('s.custom = 0');

        if (!empty($searchTerm)) {
            $qb->andWhere('s.name LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        } else if ($careerId) {
            // If no search term but career is specified, fetch all skills associated with the career
            $qb->andWhere('mc.id = :careerId');
        }

        $qb->setMaxResults($limit);

        $options = $qb->getQuery()->getArrayResult();

        // Sort the results in PHP using the 'priority' field
        usort($options, function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        $formattedOptions = array_map(function ($option) {
            return ['value' => $option['id'], 'text' => $option['name']];
        }, $options);

        return new JsonResponse($formattedOptions);
    }

    #[Route('/contacts', name: 'app_home_contacts')]
    public function contacts(Request $request, EntityManagerInterface $em, RecaptchaValidator $recaptchaValidator): Response
    {
        $contact_form = new MetierContacts();
        $form = $this->createForm(ContactFormType::class, $contact_form);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $recaptchaResponse = $request->request->get('g-recaptcha-response');
                if (null == $recaptchaResponse)
                    return $this->redirectToRoute('app_home_contacts', ['form_message' => 'reCAPTCHA validation failed']);

                $remoteIp = $request->getClientIp();
                $verificationResult = $recaptchaValidator->verify($recaptchaResponse, $remoteIp);

                if (!$verificationResult) {
                    // Handle the error, reCAPTCHA validation failed
                    return $this->redirectToRoute('app_home_contacts', ['form_message' => 'reCAPTCHA validation failed']);
                }

                if ($this->getUser())
                    $contact_form->setUser($this->getUser());

                $em->persist($contact_form);
                $em->flush();
                return $this->redirectToRoute('app_home_contacts', ['form_message' => 'success']);
            } else {
                // $errors = $this->getFormErrors();
                $errors = $this->getFormErrors($form);
                return $this->redirectToRoute('app_home_contacts', ['form_message' => 'error']);
            }
        }

        return $this->render('home/contacts.html.twig', [
            'site_key' => $this->getParameter('recaptcha.site_key'),
            'form' => $form->createView(),
            'form_message' => $request->get('form_message'),
        ]);
    }
    #[Route('/cookies', name: 'app_home_cookies')]
    public function cookies(): Response
    {
        return $this->render('home/cookies.html.twig');
    }
    #[Route('/companies', name: 'app_home_company')]
    public function companies(
        MetierBlogRepository $blogs,
    ): Response {
        $blogs = $blogs->findAll();
        return $this->render('home/company.html.twig', compact('blogs'));
    }
    #[Route('/company/{detail}', name: 'app_home_single_company')]
    public function company(MetierBlogRepository $blogs, User $detail, EntityManagerInterface $em): Response
    {
        $blogs = $blogs->findAll();
        $detail = $detail->getEmployerDetails();

        $jobs = $em->getRepository(EmployerJobs::class)->findBy(['employer' => $detail->getEmployer()]);
        return $this->render('home/company_single.html.twig', compact('blogs', 'detail', 'jobs'));
    }
    #[Route('/resume', name: 'app_home_resume')]
    public function resume(
        MetierBlogRepository $blogs,
    ): Response {
        $blogs = $blogs->findAll();
        return $this->render('home/resume.html.twig', compact('blogs'));
    }
    #[Route('/resumes/{type}', name: 'app_resumes')]
    public function resumes(
        PaginatorInterface $paginator,
        Request $request,
        EntityManagerInterface $em,
        MetierPackagesRepository $packages_repo,
        string $type = "",
    ): Response {

        if (!$type) {
            $type = "resume";
        }
        $packages = $packages_repo->paginatePackagesPerTypeAndQuery(offset: $request->get('offset', 0), num_rows: $request->get('limit', 16), type: $type, query: $request->get('search'));

        // if ($type !== "") {
        //     $products = $packages->findBy(['status' => true, 'type' => "jobseeker", 'category' => "product", 'class' => $type],
        //     ['name' => 'ASC']
        // );
        //     $products = $paginator->paginate(
        //         $products,
        //         $request->query->get('page', 1)->getResults(),
        //         $request->get('limit') ?? 16
        //     );
        // } else {
        //     $products = $packages->findBy(['status' => true, 'type' => "jobseeker", 'category' => "product", 'class' => "resume"],
        //             ['name' => 'ASC']
        // );
        //     $type = "resume";
        //     $products = $paginator->paginate(
        //         $products,
        //         $request->query->get('page', 1),
        //         $request->get('limit') ?? 16
        //     );
        // }

        if ($request->isXmlHttpRequest()) {
            // return $this->render('home/package-cards.html.twig', [
            //     'packages' => $packages,
            //     'type' => $type,
            // ]);

            return new JsonResponse([
                'html' => $this->renderView('home/package-cards.html.twig', [
                    'packages' => $packages,
                    'type' => $type
                ]),
                'number_of_records' => count($packages),
            ]);
        }

        return $this->render('job_seeker/resumes.html.twig', [
            'packages' => $packages,
            'type' => $type,
        ]);
    }
    #[Route('/psychometric', name: 'app_home_psychometric')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function psychometric(
        MetierBlogRepository $blogs,
    ): Response {
        $blogs = $blogs->findAll();
        return $this->render('home/psychometric.html.twig', compact('blogs'));
    }
    #[Route('/personality_test', name: 'app_home_personality_test')]
    // #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function personality_test(
        MetierBlogRepository $blogs,
    ): Response {
        $blogs = $blogs->findAll();
        return $this->render('home/personality_test.html.twig', compact('blogs'));
    }
    #[Route('/qu-answer', name: 'app_home_qu_answer')]
    // #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function qu_answer(
        MetierBlogRepository $blogs,
        MetierAdsRepository $metierAdsRepository
    ): Response {

        $form = $this->createForm(QaFormType::class);
        $blogs = $blogs->findAll();
        $ads = $metierAdsRepository->findActiveAds();
        return $this->render('home/interview_questions_as.html.twig', compact('blogs', 'ads'));
    }

    #[Route('/services', name: 'app_home_services')]
    public function services(
        Request $request,
        EntityManagerInterface $em,
        MetierPackagesRepository $packages,
        MetierAdsRepository $metierAdsRepository,
        string $type = "",
    ): Response {
        $products = $packages->findBy(['status' => true, 'type' => "jobseeker", 'category' => "service"]);



        return $this->render('home/services.html.twig', [
            'packages' => $products,
            'type' => $type,
            'ads' => $metierAdsRepository->findActiveAds(),
        ]);
    }
    #[Route('/receipt/{order}', name: 'app_home_receipt', methods: ['GET'])]
    public function receipt(
        Request $request,
        EntityManagerInterface $em,
        $order,
        MetierOrderRepository $orders,
    ): Response {
        $order = $orders->findOneBy(["order_uid" => $order]);
        return $this->render('home/receipt.html.twig', [
            'order' => $order,
        ]);
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
    #[Route('/faq', name: 'app_home_faq')]
    public function faq(
        MetierBlogRepository $blogs,
        Request $request,
        EntityManagerInterface $em,
        RecaptchaValidator $recaptchaValidator
    ): Response {

        $contact_form = new MetierContacts();
        $form = $this->createForm(ContactFormType::class, $contact_form);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $recaptchaResponse = $request->request->get('g-recaptcha-response');
                $remoteIp = $request->getClientIp();
                $verificationResult = $recaptchaValidator->verify($recaptchaResponse, $remoteIp);

                if (!$verificationResult) {
                    // Handle the error, reCAPTCHA validation failed
                    return $this->redirectToRoute('app_home_faq', ['form_message' => 'reCAPTCHA validation failed']);
                }

                if ($this->getUser())
                    $contact_form->setUser($this->getUser());

                $em->persist($contact_form);
                $em->flush();
                return $this->redirectToRoute('app_home_faq', ['form_message' => 'success']);
            } else {
                // $errors = $this->getFormErrors();
                $errors = $this->getFormErrors($form);
                return $this->redirectToRoute('app_home_faq', ['form_message' => 'error']);
            }
        }
        return $this->render('home/faq_jobseeker.html.twig', [
            'site_key' => $this->getParameter('recaptcha.site_key'),
            'form' => $form->createView(),
            'form_message' => $request->get('form_message'),
        ]);
    }

    #[Route('/jobs', name: 'app_home_jobs')]
    public function jobs(Request $request, EmployerJobsRepository $job_repo, EntityManagerInterface $em, VectorUtils $vectorUtils): Response
    {

        $saved_jobs = $em->getRepository(JobSeekerSavedJob::class)
            ->createQueryBuilder('s')
            ->join('s.job', 'j')
            ->where('s.jobSeeker = :jobSeeker')
            ->setParameter('jobSeeker', $this->getUser())
            ->select('j.id')
            ->getQuery()
            ->getResult();

        $saved_jobs = array_column($saved_jobs, 'id');

        $now = new \DateTime();
        $job_title = $request->get('job_title');

        $job_category_id = $request->get('job_category');
        $title = $request->query->get('title', '');
        $jobCategoryId = $request->query->get('jobCategory', null);
        $country = $request->query->get('country', null);
        $offset = $request->query->getInt('offset', 0);
        $limit = $request->query->getInt('limit', 15);
        $immediate_hiring = $request->get('immediateHiring', null);
        $job_type = $request->get('jobType');
        $salary = $request->get('salary', null);
        $posted_date = $request->get('postedDate', null);
        $city = $request->get('city', null);
        $excludeJobId = $request->get('excludeJobId', null);

        $result = $job_repo->findByFilters(
            title: $title,
            country: $country,
            category: $jobCategoryId,
            offset: $offset,
            limit: $limit,
            immediate_hiring: $immediate_hiring,
            job_type: $job_type,
            experience: $request->get('experience', null),
            salary: $salary,
            education: $request->get('education', null),
            posted_date: $posted_date,
            city: $city,
            excludeJobId: $excludeJobId,
        );


        $jobs = $result['jobs'];
        $totalJobs = $result['total'];

        $remaining = $totalJobs - ($offset + $limit);
        $remaining = $remaining > 0 ? $remaining : 0;

        $countries = $em->getRepository(MetierCountry::class)->createQueryBuilder('c')->select('c.id', 'c.name')->getQuery()->getResult();
        $job_categories = $em->getRepository(MetierJobCategory::class)
            ->createQueryBuilder('category')
            ->select('category.id', 'category.name')
            ->orderBy('category.name', 'ASC') // Order by name in ascending order
            ->getQuery()
            ->getResult();

        $job_types = $em->getRepository(MetierJobType::class)->createQueryBuilder('c')
            ->select('c.id', 'c.name')
            ->getQuery()->getResult();

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'html' => $this->renderView('home/job-cards.html.twig', [
                    'jobs' => $jobs,
                    'saved_jobs' => $saved_jobs
                ]),
                'remaining' => $remaining
            ]);
        }

        $education_list = [
            'Secondary/ High School' => 1,
            'Diploma/Associate Degree' => 2,
            'Bachelor\'s Degree' => 3,
            'Master\'s Degree' => 4,
            'Doctorate/ PhD' => 5,
            'No Diploma Required' => 0,
        ];

        $experience_list = [
            'Entry-level (0-2 years)' => 0,
            'Intermediate or Mid-level (3-5 years)' => 1,
            'Senior-level (6-8 years)' => 2,
            'Managerial-level (9-12 years)' => 3,
            'Director-level (13-15 years)' => 4,
            'Executive-level (16+ years)' => 5,
        ];

        $salary_ranges = [
            [100, 500],
            [500, 1000],
            [1000, 2000],
            [2000, 3000],
            [3000, 4000],
            [4000, 5000],
            [6000, 7000],
            ['Over', 7000],
        ];


        return $this->render('home/jobs.html.twig', [
            'controller_name' => 'HomeController',
            'jobs' => $jobs,
            'tenders' => [],
            'countries' => $countries,
            'job_categories' => $job_categories,
            'remaining' => $remaining,
            'job_types' => $job_types,
            'education_list' => $education_list,
            'experience_list' => $experience_list,
            'saved_jobs' => $saved_jobs,
            'salary_ranges' => $salary_ranges
        ]);
    }

    #[Route('/fetch_citiess', name: 'app_home_fetch_cities')]
    public function fetchCities(Request $request, EntityManagerInterface $em)
    {

        $country = $request->get('country', null);
        $search = $request->get('searchTerm');

        $cities = $em->getRepository(MetierCity::class)
            ->createQueryBuilder('c')
            ->select('c.id as value', 'c.name as text')
            ->join('c.country', 'country');

        if ($search && $search != 'null') {
            $cities->where('c.name LIKE :q')->setParameter('q', '%' . $search . '%');
        }

        $cities
            ->setMaxResults(30);

        return $this->json($cities->getQuery()->getResult(), 200);
    }

    #[Route('/tenders', name: 'app_home_tenders')]
    public function tenders(Request $request, EmployerTenderRepository $tender_repo, EntityManagerInterface $em): Response
    {
        // dd('dead');
        // $tables = ['jobseeker', 'second'];
        // $results = $analyzer->analyzeTables($tables);
        // dd($results);
        $now = new \DateTime();
        $title = $request->get('title');
        $country = $request->get('country');
        $city = $request->get('city');
        $offset = $request->get('offset');
        $limit = $request->get('limit');

        $result = $tender_repo->findByFilters(
            title: $title,
            country: $country,
            offset: $offset,
            limit: $limit,
            city: $city,
        );

        $tenders = $result['tenders'];
        $totalJobs = $result['total'];

        $remaining = $totalJobs - ($offset + $limit);
        $remaining = $remaining > 0 ? $remaining : 0;

        if ($request->isXmlHttpRequest()) {

            return new JsonResponse([
                'html' => $this->renderView('home/tender-cards.html.twig', [
                    'tenders' => $tenders,
                ]),
                'remaining' => $remaining
            ]);
        }

        $countries = $em->getRepository(MetierCountry::class)->createQueryBuilder('c')->select('c.id', 'c.name')->getQuery()->getResult();


        return $this->render('home/tenders.html.twig', [
            'controller_name' => 'HomeController',
            // 'tenders' => $tenders,
            'countries' => $countries,
        ]);
    }

    #[Route('/courses', name: 'app_home_courses')]
    public function courses(Request $request, EmployerCoursesRepository $course_repo, EntityManagerInterface $em): Response
    {
        // dd('dead');
        // $tables = ['jobseeker', 'second'];
        // $results = $analyzer->analyzeTables($tables);
        // dd($results);
        $title = $request->get('title');
        $country = $request->get('country');
        $city = $request->get('city');
        $offset = $request->get('offset');
        $limit = $request->get('limit');

        $result = $course_repo->findByFilters(
            title: $title,
            country: $country,
            offset: $offset,
            limit: $limit,
            city: $city,
        );

        $courses = $result['courses'];
        $totalJobs = $result['total'];

        $remaining = $totalJobs - ($offset + $limit);
        $remaining = $remaining > 0 ? $remaining : 0;

        if ($request->isXmlHttpRequest()) {

            return new JsonResponse([
                'html' => $this->renderView('home/course-cards.html.twig', [
                    'courses' => $courses,
                ]),
                'remaining' => $remaining
            ]);
        }

        $countries = $em->getRepository(MetierCountry::class)->createQueryBuilder('c')->select('c.id', 'c.name')->getQuery()->getResult();


        return $this->render('home/courses.html.twig', [
            'controller_name' => 'HomeController',
            // 'tenders' => $tenders,
            'countries' => $countries,
        ]);
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

    #[Route('/home/contact_form', name: 'app_contact_form', methods: ['POST', 'GET'])]
    public function contact_form(Request $request, EntityManagerInterface $em)
    {
        $contact_form = new MetierContacts();
        $form = $this->createForm(ContactFormType::class, $contact_form);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                if ($this->getUser()) {
                    $contact_form->setUser($this->getUser());
                } else {
                    $contact_form->setEmail($form->get('email')->getData());
                }

                $em->persist($contact_form);
                $em->flush();

                return $this->json(['status' => 'success']);
            } else {
                $errors = $this->getFormErrors($form);
                return $this->json(['errors' => $errors]);
            }
        }

        return $this->render('home/contact_form.html.twig', [
            'form' => $form->createView(),
        ]);
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



#[Route('/{_locale}', name: 'app_home', requirements: ['_locale' => 'en|so'], defaults: ['_locale' => 'en'])]
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
    TranslationService $translationService
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
        return $this->redirectToRoute('app_scholarships_list');
    }

    // Get scholarship type
    $type = $scholarship->getType();

    // Filter regions based on scholarship type
    if ($type == 't') {
        // For Literacy scholarships, only show Maroodi Jeex and Togdheer
        $regions = $regionRepository->findBy([
            'name' => ['Maroodi Jeex', 'Togdheer']
        ]);
    } else {
        // For other scholarship types, show all regions
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
                'certificate_attachment',
                'willingness_declaration_attachment',
                'needs_statement_attachment',
                'other_documents_attachment'
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

                        // Generate unique filename
                        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
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
            $em->flush();

            $this->addFlash('success', 'Your application has been submitted successfully!');
            return $this->redirectToRoute('app_home');

        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    // Fetch all entities for dropdowns
    $regions = $regionRepository->findAll();
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
