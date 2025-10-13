<?php

namespace App\Controller;

use App\Entity\EmployerCourses;
use App\Entity\EmployerDetails;
use App\Entity\EmployerJobQuestion;
use App\Entity\EmployerJobQuestionAnswer;
use App\Entity\EmployerJobs;
use App\Entity\EmployerStaff;
use App\Entity\EmployerTender;
use App\Entity\JobApplication;
use App\Entity\JobApplicationInterview;
use App\Entity\JobApplicationInterviewRound;
use App\Entity\JobApplicationShortlist;
use App\Entity\JobHiring;
use App\Entity\JobOffering;
use App\Entity\JobSeekerRecommendedJobs;
use App\Entity\MetierAds;
use App\Entity\MetierBlockedUser;
use App\Entity\MetierCareers;
use App\Entity\MetierChat;
use App\Entity\MetierContacts;
use App\Entity\MetierEmailTemps;
use App\Entity\MetierInquiry;
use App\Entity\MetierOrder;
use App\Entity\MetierOrderPayment;
use App\Entity\MetierPackages;
use App\Entity\MetierPlanUsed;
use App\Entity\MetierProfileView;
use App\Entity\MetierSkills;
use App\Entity\User;
use App\Event\SendEmailEvent;
use App\Form\AdsFormType;
use App\Form\ChangePasswordType;
use App\Form\ContactFormType;
use App\Form\CountryAutoCompleteField;
use App\Form\CourseFormType;
use App\Form\CvForm;
use App\Form\EmployerDetailsFormType;
use App\Form\EmployerStaffType;
use App\Form\InterviewFormType;
use App\Form\JobFormType;
use App\Form\JobFormTypeShort;
use App\Form\JobHiringFormType;
use App\Form\JobOfferFormType;
use App\Form\JobQuestionType;
use App\Form\MetierInquiryFormType;
use App\Form\RoundFormType;
use App\Form\TenderFormType;
use App\Model\JobStatusEnum;
use App\Repository\EmployerCoursesRepository;
use App\Repository\EmployerDetailsRepository;
use App\Repository\EmployerJobsRepository;
use App\Repository\EmployerStaffRepository;
use App\Repository\EmployerTenderRepository;
use App\Repository\JobApplicationInterviewRepository;
use App\Repository\JobApplicationRepository;
use App\Repository\JobHiringRepository;
use App\Repository\JobOfferingRepository;
use App\Repository\JobSeekerJobAlertRepository;
use App\Repository\JobSeekerRecommendedJobsRepository;
use App\Repository\MetierAdsRepository;
use App\Repository\MetierChatRepository;
use App\Repository\MetierEmailTempsRepository;
use App\Repository\MetierNotificationRepository;
use App\Repository\MetierPackagesRepository;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use App\Service\MailService;
use App\Service\NotificationService;
use App\Service\OrderService;
use App\Service\PaymentService;
use App\Service\RecaptchaValidator;
use App\Service\SmsAPIService;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Stripe\Checkout\Session;
use Stripe\StripeClient;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File as HttpFoundationFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Form\FormInterface;

#[Route('/account')]
class EmployerController extends AbstractController
{
    private UrlGeneratorInterface $generator;
    private $serializer;
    private $params;
    private $stripeGateway;
    private $em;
    private $eventDispatcher;
    private $orderService;
    public function __construct(
        $stripSK,
        EntityManagerInterface $em,
        ParameterBagInterface $params,
        private HttpClientInterface $client,
        UrlGeneratorInterface $generator,
        SerializerInterface $serializer,
        EventDispatcherInterface $eventDispatcher,
        OrderService $orderService,
    ) {
        $this->generator = $generator;
        $this->serializer = $serializer;
        $this->params = $params;
        $this->em = $em;
        $this->stripeGateway = new StripeClient($stripSK);
        $this->eventDispatcher = $eventDispatcher;
        $this->orderService = $orderService;
    }

    #[Route('/', name: 'app_employer')]
    public function index(ChartBuilderInterface $chartBuilder, EmployerCoursesRepository $courses, EmployerTenderRepository $tenders, EmployerJobsRepository $jobs, JobApplicationRepository $applications, string $type = null, JobApplicationInterviewRepository $jobApplicationInterviewRepository): Response
    {
        $applicants = count($applications->findBy(["employer" => $this->getUser()]));
        $posted = count($jobs->totalPosted(["employer" => $this->getUser()]));
        $shortlisted = count($applications->findBy(["employer" => $this->getUser(), "status" => "shortlisted"]));
        $interviews = count($jobApplicationInterviewRepository->findBy(["employer" => $this->getUser()]));
        $offers = count($applications->findBy(["employer" => $this->getUser(), "status" => "sent offer"]));
        $hired = count($applications->findBy(["employer" => $this->getUser(), "status" => "hired"]));

        $candidates = $applications->findBy(["employer" => $this->getUser(), "status" => "shortlisted", "type" => "self"]);

        $apps = $applications->findMatchingJobApplications($this->getUser());

        $automaticShortlistings = [];
        foreach ($apps as $filteredResult) {
            $jobApplication = $applications->find($filteredResult['id']);

            if ($jobApplication) {
                // Attach the extra fields (matchScore, matchScorePercentage, etc.) to the JobApplication entity
                $automaticShortlistings[] = [
                    'jobApplication' => $jobApplication,
                    'matchScore' => $filteredResult['matchScore'],
                    'matchScorePercentage' => $filteredResult['matchScorePercentage'],
                    'matchedItems' => $filteredResult['matchedItems'],
                ];
            }
        }

        $rejected = $applications->findBy(["employer" => $this->getUser(), "status" => "rejected"]);
        $courses = $courses->findBy(["employer" => $this->getUser()]);
        $tenders = $tenders->findBy(["employer" => $this->getUser()]);
        $mcandidates = $applications->findBy(["employer" => $this->getUser(), "status" => "shortlisted", "type" => "manual"]);
        $jobs = $jobs->filter($this->getUser(), $type);

        $data = $applications->getCandidateComparisonLastSixMonths($this->getUser());

        // Use the injected ChartBuilderInterface
        $months = [];
        $counts = [];

        foreach ($data as $item) {
            $months[] = $item['month'];
            $counts[] = $item['count'] ?? 0;
        }

        $chart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels' => $months,
            'datasets' => [
                [
                    'label' => 'Number of Candidates',
                    'backgroundColor' => '#51006a',
                    'borderColor' => '#51006a',
                    'borderWidth' => 1,
                    'data' => $counts,
                ],
            ],
        ]);

        $chart->setOptions([
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ]);

        // Get counts for the current employer
        $roundData = $this->getRoundCounts();

        // Chart Colors
        $backgroundColors = [
            'rgba(255, 99, 132, 0.2)',
            'rgba(54, 162, 235, 0.2)',
            'rgba(255, 206, 86, 0.2)',
        ];

        $borderColors = [
            'rgba(255, 99, 132, 1)',
            'rgba(54, 162, 235, 1)',
            'rgba(255, 206, 86, 1)',
        ];

        // Create chart
        $interviewChart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $interviewChart->setData([
            'labels' => $roundData['labels'],
            'datasets' => [
                [
                    'label' => 'Candidate Rounds',
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $borderColors,
                    'borderWidth' => 1,
                    'data' => $roundData['values'],
                ],
            ],
        ]);

        $interviewChart->setOptions([]);

        $activePlan = $this->getActiveOrder($this->getUser());
        return $this->render('employer/index.html.twig', [
            'controller_name' => 'EmployerController',
            'posted' => $posted,
            'mcandidates' => $mcandidates,
            'applicants' => $applicants,
            'shortlisted' => $shortlisted,
            'interviews' => $interviews,
            'offers' => $offers,
            'hired' => $hired,
            'jobs' => $jobs,
            'candidates' => $automaticShortlistings,
            'applicantchart' => $chart,
            'interviewchart' => $interviewChart,
            'activeplan' => $activePlan,
            'courses' => $courses,
            'tenders' => $tenders,
            'rejected' => $rejected,
        ]);
    }
    public function getRoundCounts(): array
    {
        // Predefined rounds and labels
        $rounds = [1, 2, 3];
        $labels = ['1st round', '2nd round', '3rd round'];
        $counts = array_fill_keys($rounds, 0); // Initialize counts to 0

        // Get the current employer (logged-in user)
        $currentEmployer = $this->getUser();

        // Query to count interviews by round for the current employer
        $results = $this->em->createQueryBuilder()
            ->select('i.rounds AS round, COUNT(i.id) AS roundCount')
            ->from(JobApplicationInterview::class, 'i')
            ->where('i.rounds IN (:rounds)')
            ->andWhere('i.employer = :employer')
            ->setParameter('rounds', $rounds)
            ->setParameter('employer', $currentEmployer)
            ->groupBy('i.rounds')
            ->getQuery()
            ->getResult();

        // Populate counts based on query results
        foreach ($results as $result) {
            $counts[$result['round']] = $result['roundCount'];
        }

        // Return labels and values
        return [
            'labels' => $labels,
            'values' => array_values($counts),
        ];
    }

    #[Route('/jobs/{type?}', name: 'app_employer_jobs')]
    public function jobs(
        EmployerJobsRepository $employerJobsRepository,
        Request $request,
        string $type = null,
        MetierAdsRepository $metierAdsRepository
    ): Response {
        $user = $this->getUser();
        $type = $request->query->get('type', $type); // type from query or route

        $qb = $employerJobsRepository->createQueryBuilder('j')
            ->where('j.employer = :user')
            ->andWhere('j.status != :deletedStatus')
            ->setParameter('user', $user)
            ->setParameter('deletedStatus', 'deleted')
            ->orderBy('j.is_repost', 'DESC'); // sort with repost first

        if ($type) {
            $qb->andWhere('j.status = :type')
                ->setParameter('type', $type);
        }

        $jobs = $qb->getQuery()->getResult();
        $ads = $metierAdsRepository->findActiveAds();

        return $this->render('employer/jobs.html.twig', compact('jobs', 'type', 'ads'));
    }


    #[Route('/candidates/{type}', name: 'app_employer_candidates', defaults: ['type' => null])]
    public function candidates(JobApplicationRepository $applicationss, string $type = null): Response
    {

        $applications = $applicationss->filter($type, $this->getUser());

        return $this->render('employer/candidates.html.twig', [
            'applications' => $applications,
            'type' => $type,
        ]);
    }
    #[Route('/recommended_candidates', name: 'app_employer_recommended_candidates')]
    public function recommended_candidates(Request $request, JobSeekerRecommendedJobsRepository $recommends, EntityManagerInterface $em): Response
    {

        if (!$this->getActiveOrder($this->getUser())) {
            sweetalert()->warning("Unfortunately, you cannot view recommended candidates because you don't have an active plan.");
            return $this->redirectToRoute('app_employer_jobs');
        }

        $now = new DateTime();
        $monthAgo = (clone $now)->sub(new \DateInterval('P1M'));

        $employer_jobs = $em->getRepository(EmployerJobs::class)->createQueryBuilder('j');
        $employer_jobs
            ->where('j.status != :status')
            ->setParameter('status', 'deleted')
            // ->andWhere('j.application_closing_date >= :dateNow') // Filter based on expiry
            // ->setParameter('dateNow', new DateTime())
            // ->andWhere('j.createdAt > :monthAgo')
            // ->setParameter('monthAgo', $monthAgo)
            ->andWhere('j.employer = :employer')
            ->setParameter('employer', $this->getUser());
        $employer_jobs = $employer_jobs->getQuery()->getResult();

        $locals_only = false;
        $full_form = $request->get('form');
        if ($full_form && $full_form['jobs']) {
            $locals_only = $em->getRepository(EmployerJobs::class)->find($full_form['jobs'])?->isLocalsOnly();
        }

        $form = $this->createFormBuilder()
            ->add('jobs', EntityType::class, [
                'class' => EmployerJobs::class,
                'choices' => $employer_jobs,
                'choice_label' => function (EmployerJobs $job) {
                    return $job->getId() . ' - ' . $job->getTitle() . '(' . $job->getStatus() . ')';
                },
                'choice_attr' => function (EmployerJobs $job) {
                    // Set data attribute to control country field
                    return ['data-is-locals-only' => $job->isLocalsOnly() ? 'true' : 'false'];
                },
                'placeholder' => 'Select Job',
                'attr' => [
                    'class' => 'form-select job_input',
                    'data-action' => 'change->recommended-candidates#jobChanged',
                ],
                // 'autocomplete' => true
            ])
            ->add('minSkill', ChoiceType::class, [
                'choices' => [
                    1 => 1,
                    2 => 2,
                    3 => 3,
                    4 => 4,
                    5 => 5,
                    6 => 6,
                ],
                'placeholder' => 'Select Minimum Skill Match',
                'attr' => [
                    'class' => 'form-select',
                ],
                'required' => false,
            ])
            ->add('relocate', ChoiceType::class, [
                'choices' => [
                    'Yes' => 1,
                    'No' => 0,
                ],
                'required' => false,
                'placeholder' => 'All',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('country', CountryAutoCompleteField::class, [
                'attr' => [
                    'class' => 'form-select country_input',
                    'disabled' => $locals_only,
                ],
                'required' => false,

            ])
            ->add('city', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control city_input',
                    'placeholder' => 'Select City',
                ],
            ]);

        $form->setMethod('GET');
        $form = $form->getForm();
        $form->handleRequest($request);

        $candidates = [];
        $job = null;

        $minSkill = 3;
        try {
            $minSkill = $form->get('minSkill')->getData();
        } catch (\Exception $e) {
            $minSkill = 3;
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $candidates = $em->getRepository(EmployerJobs::class)->findRecommendedCandidates($form->get('jobs')->getData(), $minSkill, $form->get('relocate')->getData(), $form->get('country')->getData(), $form->get('city')->getData());
            $job = $form->get('jobs')->getData();
        }

        $recommended = $recommends->filter($this->getUser());
        // $em->getRepository(EmployerJobs::class)->findRecommendedCandidates($this->getUser()->get);

        return $this->render('employer/recommended_candidates.html.twig', [
            'recommends' => $recommended,
            'employer_jobs' => $employer_jobs,
            'candidates' => $candidates,
            'form' => $form,
            'job' => $job,
        ]);
    }
    #[Route('/notifications', name: 'app_employer_notifications_list')]
    public function notifications(MetierNotificationRepository $notificationRepository): Response
    {

        $nots = $notificationRepository->findLatestByUser($this->getUser());
        return $this->render('employer/notifications.html.twig', [
            'nots' => $nots,
        ]);
    }
    #[Route('/messages', name: 'app_employer_messages')]
    public function messages(JobApplicationRepository $applications, MetierChatRepository $chats): Response
    {
        return $this->render('employer/messages.html.twig');
    }
    #[Route('/manualCandidates', name: 'app_employer_manualCandidates')]
    public function manualCandidates(JobApplicationRepository $applications): Response
    {
        $type = "yes";
        return $this->render('employer/candidates.html.twig', [
            'applications' => $applications->findBy(['employer' => $this->getUser(), 'type' => 'manual', 'status' => 'shortlisted']),
            'type' => $type,
        ]);
    }
    #[Route('/automaticShortlisted', name: 'app_employer_autoshortlisted')]
    public function automaticShortlisted(Request $request, JobApplicationRepository $applicationss, EntityManagerInterface $em): Response
    {
        // $apps = $applicationss->findMatchingJobApplications($this->getUser());
        // dd($shortlist);
        // $applications = [];
        // foreach ($apps as $filteredResult) {
        //     $jobApplication = $applicationss->find($filteredResult['id']);

        //     if ($jobApplication) {
        //         // Attach the extra fields (matchScore, matchScorePercentage, etc.) to the JobApplication entity
        //         $applications[] = [
        //             'jobApplication' => $jobApplication,
        //             'matchScore' => $filteredResult['matchScore'],
        //             'matchScorePercentage' => $filteredResult['matchScorePercentage'],
        //             'matchedItems' => $filteredResult['matchedItems'],
        //         ];
        //     }
        // }

        $now = new DateTime();
        $monthAgo = (clone $now)->sub(new \DateInterval('P1M'));

        $employer_jobs = $em->getRepository(EmployerJobs::class)->createQueryBuilder('j');
        $employer_jobs->where('j.status = :status')
            ->setParameter('status', 'posted')
            ->andWhere('j.application_closing_date >= :dateNow') // Filter based on expiry
            ->setParameter('dateNow', new DateTime())
            ->andWhere('j.createdAt > :monthAgo')
            ->setParameter('monthAgo', $monthAgo)
            ->andWhere('j.employer = :employer')
            ->setParameter('employer', $this->getUser());
        $employer_jobs = $employer_jobs->getQuery()->getResult();

        $form = $this->createFormBuilder()
            ->add('score', NumberType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Please enter the required scores to filter top candidates below. For example: 70',
                ],
                'required' => false,
            ])
            ->add('jobs', EntityType::class, [
                'class' => EmployerJobs::class,
                'choice_label' => function (EmployerJobs $job) {
                    return $job->getId() . ' - ' . $job->getTitle();
                },
                'query_builder' => function (EmployerJobsRepository $jobRepo) use ($monthAgo) {
                    return $jobRepo->createQueryBuilder('j')
                        ->where('j.employer = :employer')
                        ->setParameter('employer', $this->getUser())
                        ->andWhere('j.status = :status')
                        ->setParameter('status', 'posted')
                        ->andWhere('j.application_closing_date >= :dateNow') // Filter based on expiry
                        ->setParameter('dateNow', new DateTime())
                        ->andWhere('j.createdAt > :monthAgo')
                        ->setParameter('monthAgo', $monthAgo);
                },

                'placeholder' => 'Please select the job ID/Title to filter for matching candidates.',
                'attr' => [
                    'class' => 'form-select job_input',
                    // 'data-action' => 'change->recommended-candidates#jobChanged'
                ],
                'required' => false,
                // 'autocomplete' => true
            ])
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ]);

        $form->setMethod('GET');

        $form = $form->getForm();
        $form->handleRequest($request);
        $score = $form->get('score')->getData();
        $job = $form->get('jobs')->getData();

        // Pass the applications to Twig
        $shortlist = $this->em->getRepository(JobApplicationShortlist::class)->findEmployerShortlist(user: $this->getUser(), score: $score, job: $job);

        return $this->render('employer/automaticShortlists.html.twig', [
            'shortlist' => $shortlist,
            'form' => $form->createView(),
        ]);
    }
    #[Route('/cvCenter', name: 'app_employer_cvCenter')]
    public function cvCenter(
        JobApplicationRepository $applications,
        Request $request,
        PaginatorInterface $paginator,
        UserRepository $jobseekers
    ): Response {

        if (!$this->getActiveOrder($this->getUser())) {
            sweetalert()->warning("Unfortunately, you cannot access the CV database because you don't have an active plan.");
            return $this->redirectToRoute('app_employer_jobs');
        }

        $form = $this->createForm(CvForm::class, null, [
            'method' => 'GET',
        ]);
        $form->handleRequest($request);

        $cvs = [];

        // dd($em->getRepository(BudgetEntry::class)->findBy($criteria));
        // $form = $this->createForm(PurchaseFormType::class,$order);
        //     $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() || $request->query->get('page')) {
            $data = $form->getData();

            $experience = $data['experience'] ?? null;
            $education = $data['education'] ?? null;
            $city = $data['city'] ?? null;
            $country = $data['country'] ?? null;
            $state = $data['states'] ?? null;
            $jobtitle = $data['jobtitle'] ?? null;
            $careerStatus = $data['careerStatus'] ?? null;

            $fields = [$experience, $education, $city, $country, $state, $jobtitle, $careerStatus];

            if (array_filter($fields)) {
                $datatable = $jobseekers->searchJobseekers($jobtitle, $country, $state, $city, $experience, $education, $careerStatus);
               $page  = $request->query->getInt('page', 1);
    $limit = $request->query->getInt('limit', 50); // default 50, matches your select dropdown

    $cvs = $paginator->paginate(
        $datatable, // can be QueryBuilder or array
        $page,
        $limit
    );
            }
        }

        return $this->render('employer/cvs.html.twig', compact('cvs', 'form'));
    }
    #[Route('/edit_interview/{interview}', name: 'app_employer_edit_interview')]
    public function edit_interview(
        Request $request,
        JobApplicationInterview $interview,
        EntityManagerInterface $em,
        NotificationService $notificationService
    ): Response {

        if (!$interview) {
            dd("Sorry, no interview found");
        }
        $form = $this->createForm(InterviewFormType::class, $interview);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($interview);

            $applicant = $interview->getApplicant();
            if ($applicant) {
                $notification = $notificationService->createNotification(
                    type: "success",
                    message: "Your interview with " . $interview->getEmployer()->getName() . " has been updated check reports page for more information about the interview",
                    user: $applicant,
                    routeName: "",
                    routeParams: []
                );
                $em->persist($notification);
            }
            $em->flush();
            sweetalert()->success("Successfully updated the interview information");
        }
        return $this->render('employer/edit_interview.html.twig', compact('interview', 'form'));
    }
    #[Route('/addAdd/{ad}', name: 'app_employer_request_ad_temp', defaults: ['ad' => null], methods: ['POST', 'GET'])]
    public function addAdd(
        Request $request,
        EntityManagerInterface $em,
        MetierAds $ad = null,
        MetierAdsRepository $packages,
        FileUploader $fileUploader,
    ): Response {

        if (!$ad) {
            $ad = new MetierAds();
            $ad->setRequestedBy($this->getUser());
            $ad->setClient($this->getUser()->getName());
            $ad->setStatus(false);
        }

        // Create the form and pass the ad entity
        $form = $this->createForm(AdsFormType::class, $ad);
        $form->remove('status');
        $form->remove('client');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Handle file upload only if there's a new image uploaded
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                // Process the uploaded file
                $originalFilename = $fileUploader->upload($imageFile, $this->getParameter('employer_profile_images_directory'));
                $ad->setImage($originalFilename);
            }

            // Save the entity
            $em->persist($ad);
            $em->flush();

            $this->addFlash('success', 'Successfully created/updated the ad');
        }

        $packages = $packages->findAll();
        return $this->render('employer/ad.html.twig', [
            'form' => $form->createView(),
            'ad' => $ad,
        ]);
    }
    #[Route('/shortlistCandidate/{candidate}', methods: ["POST", "GET"], name: 'app_employer_shortlist_application', defaults: ['candidate'], options: ['expose' => true])]
    public function shortlistCandidate(
        JobApplication $candidate,
        RequestStack $requestStack,
        Request $request,
        EntityManagerInterface $em,
        NotificationService $notificationService
    ): Response {

        $status = "shortlisted";
        // $new_quot = new PurchaseQuotation();
        // $new_quot->setPo($order);
        // $orderItems = $order->getPurchaseOrderItems();
        $referer = $requestStack->getCurrentRequest()->headers->get('referer');

        // Get the action URL dynamically
        $actionUrl = $this->generateUrl(
            'app_employer_shortlist_application',
            [
                'candidate' => $candidate->getId(),
            ]
        );

        $form = $this->createFormBuilder(null, ['action' => $actionUrl]);
        $form->add('note', TextareaType::class, []);

        $form = $form->getForm();

        $form->handleRequest($request);

        // dd($em->getRepository(BudgetEntry::class)->findBy($criteria));
        // $form = $this->createForm(PurchaseFormType::class,$order);
        //     $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $candidate->setStatus($status);

            $messages = "Successfully Shortlisted Candidate";
            $notification = $notificationService->createNotification(
                type: "danger",
                message: "Congratulations! You've been shortlisted for the position. Stay tuned for the next steps in the process.",
                user: $candidate->getJobSeeker(),
                routeName: "",
                routeParams: []
            );
            $em->persist($notification);
            $em->persist($candidate);
            $em->flush();

            sweetalert()->success($messages);
            // return $this->redirectToRoute('app_procurement_edit_po', [
            return new RedirectResponse($referer);
            //     'order' => $approval->getPo()->getId(),
            // ]);

        }

        $type = "shortlisted";
        $msg = ["msg" => "Are you sure you want shortlist this candidate?", "class" => "alert alert-success"];

        return $this->render('employer/form.html.twig', [
            'form' => $form,
            'type' => $type,
            'candidate' => $candidate,
            'msg' => $msg,
        ]);
    }
    #[Route('/messageCandidate/{candidate}', methods: ["POST", "GET"], name: 'app_employer_message_candidate', defaults: ['candidate'], options: ['expose' => true])]
    public function messageCandidate(
        User $candidate,
        RequestStack $requestStack,
        Request $request,
        EntityManagerInterface $em
    ): Response {

        $status = "shortlisted";
        // $new_quot = new PurchaseQuotation();
        // $new_quot->setPo($order);
        // $orderItems = $order->getPurchaseOrderItems();
        $referer = $requestStack->getCurrentRequest()->headers->get('referer');
        // Get the action URL dynamically
        $actionUrl = $this->generateUrl(
            'app_employer_message_candidate',
            [
                'candidate' => $candidate->getId(),
            ]
        );
        $form = $this->createFormBuilder(null, ['action' => $actionUrl]);
        $form->add('message', TextareaType::class, []);
        $form = $form->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $message = $form->get("message")->getData();
            $new_msg = new MetierChat();
            $new_msg->setSender($this->getUser());
            $new_msg->setReceiver($candidate);
            $new_msg->setContent($message);
            $new_msg->setSeen(false);
            $new_msg->setDate(new DateTime("now"));
            $messages = "Message sent successfully";
            $em->persist($new_msg);
            $em->flush();

            sweetalert()->success($messages);
            // return $this->redirectToRoute('app_procurement_edit_po', [
            return new RedirectResponse($referer);
            //     'order' => $approval->getPo()->getId(),
            // ]);

        }

        $type = "message";
        $msg = ["msg" => "Are you sure you want shortlist this candidate?", "class" => "alert alert-success"];

        return $this->render('employer/form.html.twig', [
            'form' => $form,
            'type' => $type,
            'candidate' => $candidate,
            'msg' => $msg,
        ]);
    }
    #[Route('/rejectCandidate/{candidate}', methods: ["POST", "GET"], name: 'app_employer_reject_candidate', defaults: ['candidate'], options: ['expose' => true])]
    public function rejectCandidate(
        JobApplication $candidate,
        RequestStack $requestStack,
        Request $request,
        EntityManagerInterface $em,
        NotificationService $notificationService
    ): Response {

        $status = "rejected";

        // $new_quot = new PurchaseQuotation();
        // $new_quot->setPo($order);
        // $orderItems = $order->getPurchaseOrderItems();
        $referer = $requestStack->getCurrentRequest()->headers->get('referer');
        // Get the action URL dynamically
        $actionUrl = $this->generateUrl(
            'app_employer_reject_candidate',
            [
                'candidate' => $candidate->getId(),
            ]
        );
        $form = $this->createFormBuilder(null, ['action' => $actionUrl]);
        $form->add('message', TextareaType::class, []);
        $form = $form->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $message = $form->get('message')->getData();
            $candidate->setStatus("rejected");
            $candidate->setRejectionNote($message);

            $notification = $notificationService->createNotification(
                type: "danger",
                message: "We regret to inform you that your application was not successful. We wish you the best in your job search.",
                user: $candidate->getJobSeeker(),
                routeName: "",
                routeParams: []
            );
            $em->persist($notification);
            $em->persist($candidate);
            $em->flush();

            $temps = $this->em->getRepository(MetierEmailTemps::class);
            $template = $temps->findOneBy(["action" => "jobseeker_job_application_rejected"]) ?? new MetierEmailTemps();

            if ($candidate->getJob()) {
                $title = $candidate->getJob()->getTitle();
            } else {
                $title = "";
            }
            $title =
                $d = [
                    "name" => $candidate->getJobSeeker()->getName(),
                    "email" => $candidate->getJobSeeker()->getEmail(),
                    "type" => $template->getType(),
                    "content" => $template->getContent(),
                    "subject" => "Application Status - Métier Quest",
                    "header" => $template->getHeader(),
                    "cat" => "",
                    "extra" => "",
                    "otp" => "",
                    "employer" => $candidate->getEmployer()->getName(),
                    "interview_date" => "",
                    "platform" => "",
                    "job_title" => $title,
                    "link" => "",
                    "job_id" => "",
                    "closing_date" => "",
                    "interview_time" => "",
                ];
            $event = new SendEmailEvent($d);
            $this->eventDispatcher->dispatch($event, SendEmailEvent::class);

            sweetalert()->success("The job application has been updated successfully");
            // return $this->redirectToRoute('app_procurement_edit_po', [
            return new RedirectResponse($referer);
            //     'order' => $approval->getPo()->getId(),
            // ]);

        }

        $type = "rejected";
        $msg = ["msg" => "Are you sure you want reject this candidate?", "class" => "alert alert-danger"];

        return $this->render('employer/form.html.twig', [
            'form' => $form,
            'type' => $type,
            'candidate' => $candidate,
            'msg' => $msg,
        ]);
    }
    #[Route('/cancelPlan/{plan}', methods: ["POST", "GET"], name: 'app_employer_cancel_plan', defaults: ['order'], options: ['expose' => true])]
    public function cancelPlan(
        MetierOrder $plan,
        RequestStack $requestStack,
        Request $request,
        EntityManagerInterface $em
    ): Response {

        // $new_quot = new PurchaseQuotation();
        // $new_quot->setPo($order);
        // $orderItems = $order->getPurchaseOrderItems();
        $referer = $requestStack->getCurrentRequest()->headers->get('referer');

        $plan->setCanceled(true);
        $em->persist($plan);
        $em->flush();

        sweetalert()->success("Successfully updated");
        return new RedirectResponse($referer);
    }
    #[Route('/manualShortListCandidate/{candidate}', methods: ["POST", "GET"], name: 'app_employer_shortlist_manual_candidate', defaults: ['candidate'], options: ['expose' => true])]
    public function manualShortListCandidate(
        User $candidate,
        RequestStack $requestStack,
        Request $request,
        EntityManagerInterface $em,
        NotificationService $notificationService
    ): Response {

        $status = "shortlisted";
        // $new_quot = new PurchaseQuotation();
        // $new_quot->setPo($order);
        // $orderItems = $order->getPurchaseOrderItems();
        $referer = $requestStack->getCurrentRequest()->headers->get('referer');

        // Get the action URL dynamically
        $actionUrl = $this->generateUrl(
            'app_employer_shortlist_manual_candidate',
            [
                'candidate' => $candidate->getId(),
            ]
        );

        $form = $this->createFormBuilder(null, ['action' => $actionUrl]);
        $form->add('note', HiddenType::class, []);

        $form = $form->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $new_application = new JobApplication();
            $new_application->setStatus($status);
            $new_application->setType("manual");
            $new_application->setJobSeeker($candidate);
            $new_application->setEmployer($this->getUser());
            // $candidate->setStatus($status);
            $messages = "Successfully Shortlisted Candidate";
            $em->persist($new_application);

            $notification = $notificationService->createNotification(
                type: "danger",
                message: "Congratulations! You've been shortlisted for the position. Stay tuned for the next steps in the process.",
                user: $new_application->getJobSeeker(),
                routeName: "",
                routeParams: []
            );
            $em->persist($notification);
            $em->flush();
            sweetalert()->success($messages);
            return $this->redirectToRoute('app_employer_manualCandidates');
            // return new RedirectResponse($referer);
            //     'order' => $approval->getPo()->getId(),
            // ]);

        }

        $type = "manual shortlisted";
        $msg = ["msg" => "If you are certain that you want to shortlist this candidate, please click the submit button", "class" => "alert alert-success"];

        return $this->render('employer/form.html.twig', [
            'form' => $form,
            'type' => $type,
            'candidate' => $candidate,
            'msg' => $msg,
        ]);
    }

    #[Route('/scheduleInterview/{candidate}', methods: ["POST", "GET"], name: 'app_employer_schedule_interview', defaults: ['candidate'], options: ['expose' => true])]
    public function scheduleInterview(
        JobApplication $candidate,
        RequestStack $requestStack,
        Request $request,
        EntityManagerInterface $em,
        SmsAPIService $smsAPIService
    ): Response {

        $status = "interview scheduled";
        $referer = $requestStack->getCurrentRequest()->headers->get('referer');

        $new_interview = new JobApplicationInterview();
        $new_interview->setEmployer($this->getUser());

        // Get the action URL dynamically
        $actionUrl = $this->generateUrl(
            'app_employer_schedule_interview',
            [
                'candidate' => $candidate->getId(),
            ]
        );

        // dd($new_interview);
        $form = $this->createForm(InterviewFormType::class, $new_interview, ['action' => $actionUrl]);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {

            if ($form->isValid()) {
                $candidate->setStatus($status);

                $which_round = $form->get("which_round")->getData();
                $new_round = new JobApplicationInterviewRound();
                $new_round->setInterview($new_interview);
                $new_round->setStatus($status);
                $new_interview->setStatus($status);
                $new_interview->setApplication($candidate);
                $new_interview->setApplicant($candidate->getJobSeeker());
                $new_round->setRound($which_round);
                $candidate->setStatus($status);

                $messages = "Successfully scheduled interview";
                $em->persist($candidate);
                $em->persist($new_round);
                $em->persist($new_interview);
                $em->flush();

                $temps = $this->em->getRepository(MetierEmailTemps::class);
                $template = $temps->findOneBy(["action" => "jobseeker_interview_scheduled"]) ?? new MetierEmailTemps();

                $platform = "";
                if ($new_interview->getType() == "in person") {
                    $platform = $new_interview->getLocation() . "," . $new_interview->getCity()->getName() ?? "" . ", " . $new_interview->getCountry()->getName() ?? "";
                } else {
                    $platform = $new_interview->getMeetingLink();
                }

                $d = [
                    "name" => $candidate->getJobSeeker()->getName(),
                    "email" => $candidate->getJobSeeker()->getEmail(),
                    "type" => $template->getType(),
                    "content" => $template->getContent(),
                    "subject" => $template->getSubject(),
                    "header" => $template->getHeader(),
                    "cat" => "",
                    "extra" => "",
                    "otp" => "",
                    "employer" => $candidate->getEmployer()->getName(),
                    "interview_date" => $new_interview->getDate()->format("Y-m-d"),
                    "platform" => $platform,
                    "note" => $new_interview->getNotes(),
                    "job_title" => "",
                    "link" => "",
                    "job_id" => "",
                    "closing_date" => "",
                    "interview_time" => $new_interview->getDate()->format("H:i:s"),
                ];

                if ($candidate->getJobSeeker()->getJobalerts()) {
                    $alert = $candidate->getJobSeeker()->getJobalerts()[0];
                    $phone = $alert->getPhone();
                    $job_info = $candidate->getJob();
                    $title = "Interview";
                    if ($job_info) {
                        $title = $job_info->getTitle();
                    }

                    if ($phone) {

                        $smsAPIService->send($phone, 'interview', [
                            'company' => $candidate->getEmployer()->getName(),
                            'title' => $title,
                        ]);
                        // dd("yes");
                    }
                }


                $event = new SendEmailEvent($d);
                $this->eventDispatcher->dispatch($event, SendEmailEvent::class);
                sweetalert()->success($messages);

                // return $this->redirectToRoute('app_procurement_edit_po', [
                return new RedirectResponse($referer);
                //     'order' => $approval->getPo()->getId(),
                // ]);
            } else {
                $messages = "There was an error processing your request. Please try again later";
                sweetalert()->success($messages);
                // return $this->redirectToRoute('app_procurement_edit_po', [
                return new RedirectResponse($referer);
            }
        }

        $type = "interview scheduled";
        $msg = ["msg" => "Are you sure you want shortlist this candidate?", "class" => "alert alert-success"];

        return $this->render('employer/form.html.twig', [
            'form' => $form,
            'type' => $type,
            'candidate' => $candidate,
            'msg' => $msg,
        ]);
    }
    #[Route('/serveFiles/{filename}', name: 'employer_serve_image')]
    public function serveFiles(string $filename, string $type = null, Request $request): Response
    {
        // Check user authentication
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $imagePath = $this->getParameter('product_images_directory') . '/' . $filename;
        // Path to the image
        // if ($type === "product") {
        //     $imagePath = $this->getParameter('product_images_directory') . '/' . $filename;
        // }
        // if ($type === "sreport") {
        //     $imagePath = $this->getParameter('ps_report_directory') . '/' . $filename;
        // }

        // if (!file_exists($imagePath)) {
        //     throw $this->createNotFoundException('Image not found.');
        // }

        try {
            $file = new HttpFoundationFile($imagePath);
        } catch (FileNotFoundException $e) {
            throw $this->createNotFoundException('Image not found.');
        }

        return $this->file($file);
    }
    #[Route('/interviewResult/{candidate}', methods: ["POST", "GET"], name: 'app_employer_interview_result', defaults: ['candidate'], options: ['expose' => true])]
    public function interviewResult(
        JobApplication $candidate,
        RequestStack $requestStack,
        Request $request,
        EntityManagerInterface $em,
        NotificationService $notificationService
    ): Response {

        $status = "interview result";
        $jobseeker = $candidate->getJobSeeker();
        $referer = $requestStack->getCurrentRequest()->headers->get('referer');

        //get the last interview
        $interview = $candidate->lastInterview();

        $temps = $this->em->getRepository(MetierEmailTemps::class);

        $round = null;

        if ($interview->noneCompleteRound()) {
            $round = $interview->noneCompleteRound();
            if ($round->getStatus() == "next round") {
                $round = new JobApplicationInterviewRound();
                $round->setInterview($interview);
                $round->setStatus("interview scheduled");
            }
        }
        $actionUrl = $this->generateUrl(
            'app_employer_interview_result',
            [
                'candidate' => $candidate->getId(),
            ]
        );

        $roundForm = $this->createForm(RoundFormType::class, $round, ['action' => $actionUrl]);
        $roundForm->handleRequest($request);
        if ($roundForm->isSubmitted()) {

            if ($roundForm->isValid()) {
                $st = $roundForm->get("status")->getData();
                $comments = $roundForm->get("comments")->getData();
                // check the status
                $checkStatus = $this->checkStatus(["rejected", "selected", "next round"], $st);
                if ($checkStatus) {
                    $interview->setStatus($st);
                    $new_round = new JobApplicationInterviewRound();
                    $new_round->setInterview($interview);
                    $round_number = $interview->noneCompleteRound();
                    if ($round_number) {
                        $round_number = $interview->noneCompleteRound()->getRound();
                    } else {
                        $round_number = 0;
                    }
                    if ($st === "rejected") {
                        $interview->setStatus($st);
                        $candidate->setStatus($st);
                        $new_round->setStatus($st);
                        $new_round->setComments($comments);
                        $new_round->setRound($round_number + 1);
                        $candidate->setStatus("rejected");

                        $notification = $notificationService->createNotification(
                            type: "danger",
                            message: "We regret to inform you that your application was not successful. We wish you the best in your job search.",
                            user: $candidate->getJobSeeker(),
                            routeName: "",
                            routeParams: []
                        );
                        $em->persist($notification);

                        $em->persist($new_round);

                        $template = $temps->findOneBy(["action" => "jobseeker_job_application_rejected"]) ?? new MetierEmailTemps();

                        if ($candidate->getJob()) {
                            $candidate->getJob()->getTitle();
                        } else {
                            $jobTitle = "";
                        }

                        $d = [
                            "name" => $candidate->getJobSeeker()->getName(),
                            "email" => $candidate->getJobSeeker()->getEmail(),
                            "type" => $template->getType(),
                            "content" => $template->getContent(),
                            "subject" => "Application Status - Métier Quest",
                            "header" => $template->getHeader(),
                            "cat" => "",
                            "extra" => "",
                            "otp" => "",
                            "employer" => $candidate->getEmployer()->getName(),
                            "interview_date" => "",
                            "platform" => "",
                            "job_title" => $jobTitle,
                            "link" => "",
                            "job_id" => "",
                            "closing_date" => "",
                            "interview_time" => "",
                        ];
                        $event = new SendEmailEvent($d);
                        $this->eventDispatcher->dispatch($event, SendEmailEvent::class);
                    } elseif ($st === "selected") {

                        // dd("selected");
                        $notification = $notificationService->createNotification(
                            type: "success",
                            message: "Congratulations! You've been selected for the next round of the interview process. Stay tuned for further details.",
                            user: $candidate->getJobSeeker(),
                            routeName: "",
                            routeParams: []
                        );
                        $em->persist($notification);
                        $interview->setStatus($st);
                        $candidate->setStatus($st);
                        $new_round->setStatus($st);
                        $new_round->setComments($comments);
                        $new_round->setRound($round_number + 1);

                        $em->persist($new_round);

                        //     $template = $temps->findOneBy(["action" => "jobseeker_offer_made"]) ?? new MetierEmailTemps();

                        //     if($candidate->getJob()){
                        //         $candidate->getJob()->getTitle();
                        //    }else{
                        //        $jobTitle = "";
                        //    }

                        //     $d = [
                        //         "name" => $candidate->getJobSeeker()->getName(),
                        //         "email" => $candidate->getJobSeeker()->getEmail(),
                        //         "type" => $template->getType(),
                        //         "content" => $template->getContent(),
                        //         "subject" => "Application Status - Métier Quest",
                        //         "header" => $template->getHeader(),
                        //         "cat" => "",
                        //         "extra" => "",
                        //         "otp" => "",
                        //         "employer" => $candidate->getEmployer()->getName(),
                        //         "interview_date" => "",
                        //         "platform" => "",
                        //         "job_title" =>$jobTitle,
                        //         "link" => "",
                        //         "job_id" => "",
                        //         "closing_date" => "",
                        //         "interview_time" => "",
                        //     ];
                        //     $event = new SendEmailEvent($d);
                        //     $this->eventDispatcher->dispatch($event, SendEmailEvent::class);
                    } elseif ($st === "next round") {

                        $new_round->setStatus($st);
                        $new_round->setComments($comments);
                        $new_round->setRound($round_number + 1);

                        $template = $temps->findOneBy(["action" => "jobseeker_interview_scheduled_round_2"]) ?? new MetierEmailTemps();

                        $platform = "";
                        if ($interview->getType() == "in person") {
                            $platform = $interview->getLocation() . "," . $interview->getCity()->getName() ?? "" . ", " . $interview->getCountry()->getName() ?? "";
                        } else {
                            $platform = $interview->getMeetingLink();
                        }
                        if ($round->getLocation() !== "") {
                            $platform = $round->getLocation();
                        }

                        $time = $interview->getDate()->format("d-m-Y H:i:s");
                        if ($round->getDate()) {
                            $time = $round->getDate()->format("d-m-Y H:i:s");
                        }

                        $d = [
                            "name" => $candidate->getJobSeeker()->getName(),
                            "email" => $candidate->getJobSeeker()->getEmail(),
                            "type" => $template->getType(),
                            "content" => $template->getContent(),
                            "subject" => "Interview Scheduled round " . $round_number + 1,
                            "header" => $template->getHeader(),
                            "cat" => "",
                            "extra" => "",
                            "otp" => "",
                            "employer" => $candidate->getEmployer()->getName(),
                            "interview_date" => $round->getComments(),
                            "note" => $round->getComments(),
                            "platform" => $platform,
                            "job_title" => "",
                            "link" => "",
                            "job_id" => "",
                            "closing_date" => "",
                            "interview_time" => $time,
                        ];
                        $event = new SendEmailEvent($d);
                        $this->eventDispatcher->dispatch($event, SendEmailEvent::class);

                        $em->persist($new_round);
                    }
                    if ($round->getId()) {
                        $round->setStatus($st);
                        $em->persist($round);
                    }
                    // dd("halkan wuuu yimid");

                    $em->persist($candidate);

                    $em->flush();

                    $messages = "Interview was updated successfully!";
                    sweetalert()->success($messages);
                    // return $this->redirectToRoute('app_procurement_edit_po', [
                    return new RedirectResponse($referer);
                }
            } else {
                $messages = "There was an error processing your request. Please try again later";
                sweetalert()->success($messages);
                // return $this->redirectToRoute('app_procurement_edit_po', [
                return new RedirectResponse($referer);
            }
        }

        $type = "interview result";
        $msg = ["msg" => "Are you sure you want shortlist this candidate?", "class" => "alert alert-success"];

        return $this->render('employer/form.html.twig', [
            'type' => $type,
            'round' => $round,
            'candidate' => $candidate,
            'msg' => $msg,
            'jobseeker' => $jobseeker,
            'roundForm' => $roundForm,
        ]);
    }
    #[Route('/resume/{candidate}', methods: ["POST", "GET"], name: 'app_employer_resume', defaults: ['candidate'], options: ['expose' => true])]
    public function resume(
        User $candidate,
        RequestStack $requestStack,
        Request $request,
        EntityManagerInterface $em
    ): Response {

        $jobseeker = $candidate;

        // Get the action URL dynamically
        $actionUrl = $this->generateUrl(
            'app_employer_schedule_interview',
            [
                'candidate' => $candidate->getId(),
            ]
        );

        // update profile viewed
        $new_profile_view = new MetierProfileView();
        $new_profile_view->setCreatedAt(new \DateTime());
        $new_profile_view->setEmployer($this->getUser());
        $new_profile_view->setJobseeker($candidate);

        $em->persist($new_profile_view);
        $em->flush();

        $type = "resume";
        $msg = ["msg" => "Are you sure you want shortlist this candidate?", "class" => "alert alert-success"];

        return $this->render('employer/form.html.twig', [
            'type' => $type,
            'candidate' => $candidate,
            'msg' => $msg,
            'jobseeker' => $jobseeker,
        ]);
    }

    #[Route('/hireOffer/{candidate}', methods: ["POST", "GET"], name: 'app_employer_send_offer', defaults: ['candidate'], options: ['expose' => true])]
    public function hireOffer(
        JobApplication $candidate,
        RequestStack $requestStack,
        Request $request,
        EntityManagerInterface $em,
        FileUploader $fileUploader,
        JobOfferingRepository $joboffers,
        JobHiringRepository $hirings,
        NotificationService $notificationService
    ): Response {

        $jobseeker = $candidate->getJobSeeker();
        $joboffer_status = "sent offer";
        $hiring_status = "hired";

        $referer = $requestStack->getCurrentRequest()->headers->get('referer');

        $getoffers = $joboffers->findOneBy(["application" => $candidate]);

        if ($getoffers) {
            $jobOffer = $getoffers;
        } else {
            $jobOffer = new JobOffering();
            $jobOffer->setApplication($candidate);
            $jobOffer->setEmployer($candidate->getEmployer());
            $jobOffer->setJobseeker($candidate->getJobSeeker());
            $jobOffer->setStatus($joboffer_status);
        }
        // dd($getoffers);

        // Get the action URL dynamically
        $actionUrl = $this->generateUrl(
            'app_employer_send_offer',
            [
                'candidate' => $candidate->getId(),
            ]
        );

        $jobOfferForm = $this->createForm(JobOfferFormType::class, $jobOffer, ['action' => $actionUrl]);
        $jobOfferForm->handleRequest($request);
        if ($jobOfferForm->isSubmitted()) {
            if ($jobOfferForm->isValid()) {

                $offer_letter = $jobOfferForm->get('offer_letter')->getData();
                if ($offer_letter) {
                    $originalFilename = $fileUploader->upload($offer_letter, $this->getParameter('joboffer_directory'));
                    $jobOffer->setOfferLetter($originalFilename);
                }
                $messages = "Successfully sent the offer";
                $candidate->setStatus($joboffer_status);

                $temps = $this->em->getRepository(MetierEmailTemps::class);
                $template = $temps->findOneBy(["action" => "jobseeker_offer_made"]) ?? new MetierEmailTemps();

                if ($candidate->getJob()) {
                    $jobTitle = $candidate->getJob()->getTitle();
                } else {
                    $jobTitle = "#";
                }

                $d = [
                    "name" => $candidate->getJobSeeker()->getName(),
                    "email" => $candidate->getJobSeeker()->getEmail(),
                    "type" => $template->getType(),
                    "content" => $template->getContent(),
                    "subject" => "Job Offer - Métier Quest",
                    "header" => $template->getHeader(),
                    "cat" => $jobOffer->getSalary(),
                    "extra" => $jobOffer->getProbationPeriod(),
                    "otp" => "",
                    "employer" => $candidate->getEmployer()->getName(),
                    "interview_date" => "",
                    "platform" => "",
                    "job_title" => $jobTitle,
                    "link" => "",
                    "job_id" => "",
                    "closing_date" => $jobOffer->getJoiningDate()->format("d-m-Y"),
                    "interview_time" => "",
                ];
                $event = new SendEmailEvent($d);
                $this->eventDispatcher->dispatch($event, SendEmailEvent::class);
                // dump($jobOffer);
                // dd("offering made");
                $notification = $notificationService->createNotification(
                    type: "success",
                    message: "Great news! You've received a job offer. Check your email for the offer details and next steps.",
                    user: $candidate->getJobSeeker(),
                    routeName: "",
                    routeParams: []
                );
                $em->persist($candidate);
                $em->persist($jobOffer);
                $em->persist($notification);
                $em->flush();

                sweetalert()->success($messages);
                // return $this->redirectToRoute('app_procurement_edit_po', [
                return new RedirectResponse($referer);
                //     'order' => $approval->getPo()->getId(),
                // ]);
            } else {
                $messages = "There was an error processing your request. Please try again later";
                sweetalert()->warning($messages);
                // return $this->redirectToRoute('app_procurement_edit_po', [
                return new RedirectResponse($referer);
            }
        }

        $hiring_candidate = $hirings->findOneBy(["application" => $candidate]);
        if ($hiring_candidate) {
            $hiring = $hiring_candidate;
        } else {
            $hiring = new JobHiring();
            $hiring->setApplication($candidate);
            $hiring->setEmploye($candidate->getEmployer());
            $hiring->setJobseeker($candidate->getJobSeeker());
            $hiring->setStatus($hiring_status);
        }

        $jobHiringForm = $this->createForm(JobHiringFormType::class, $hiring, ['action' => $actionUrl]);
        $jobHiringForm->handleRequest($request);
        if ($jobHiringForm->isSubmitted()) {
            if ($jobHiringForm->isValid()) {

                $messages = "Successfully Hired the Candidate";
                $candidate->setStatus($hiring_status);

                $em->persist($candidate);
                $em->persist($hiring);

                $temps = $this->em->getRepository(MetierEmailTemps::class);
                $template = $temps->findOneBy(["action" => "jobseeker_job_application_hired"]) ?? new MetierEmailTemps();
                if ($candidate->getJob()) {
                    $jobTitle = $candidate->getJob()->getTitle();
                } else {
                    $jobTitle = "#";
                }
                //    dd($hiring->getSalaryPackage());
                $d = [
                    "name" => $candidate->getJobSeeker()->getName(),
                    "email" => $candidate->getJobSeeker()->getEmail(),
                    "type" => $template->getType(),
                    "content" => $template->getContent(),
                    "subject" => "Job Offer - Métier Quest",
                    "header" => $template->getHeader(),
                    "cat" => "",
                    "extra" => "",
                    "otp" => "",
                    "start_date" => $hiring->getJoiningDate()->format("d-m-Y"),
                    "probation_period" => $hiring->getProbationPeriod(),
                    "salary" => $hiring->getSalaryPackage(),
                    "employer" => $candidate->getEmployer()->getName(),
                    "interview_date" => "",
                    "platform" => "",
                    "job_title" => $jobTitle,
                    "link" => "",
                    "job_id" => "",
                    "closing_date" => "",
                    "interview_time" => "",
                ];
                $event = new SendEmailEvent($d);
                $this->eventDispatcher->dispatch($event, SendEmailEvent::class);

                // dd("hired");

                $notification = $notificationService->createNotification(
                    type: "success",
                    message: "Congratulations! You've been hired. Welcome to the team! Expect further instructions in your inbox soon.",
                    user: $candidate->getJobSeeker(),
                    routeName: "",
                    routeParams: []
                );
                $em->persist($notification);
                $em->flush();
                sweetalert()->success($messages);
                // return $this->redirectToRoute('app_procurement_edit_po', [
                return new RedirectResponse($referer);
                //     'order' => $approval->getPo()->getId(),
                // ]);
            } else {
                $messages = "There was an error processing your request. Please try again later";
                sweetalert()->warning($messages);
                // return $this->redirectToRoute('app_procurement_edit_po', [
                return new RedirectResponse($referer);
            }
        }

        $type = "send offer";
        $msg = ["msg" => "Are you sure you want shortlist this candidate?", "class" => "alert alert-success"];

        return $this->render('employer/form.html.twig', [
            'jobOfferForm' => $jobOfferForm,
            'hiringForm' => $jobHiringForm,
            'type' => $type,
            'candidate' => $candidate,
            'msg' => $msg,
            'jobseeker' => $jobseeker,
            'offer' => $jobOffer,
            'hiring' => $hiring,
        ]);
    }

    #[Route('/find_resume', name: 'app_employer_find_resume')]
    public function find_resume(EmployerJobsRepository $jobs): Response
    {

        return $this->render('employer/find_resume.html.twig', [
            'jobs' => $jobs->findBy(['employer' => $this->getUser(), 'operation' => 'job']),
        ]);
    }
    #[Route('/interviews', name: 'app_employer_interviews')]
    public function interviews(
        JobApplicationInterviewRepository $interviews,
        Request $request,
        EmployerJobsRepository $employerJobsRepository
    ): Response {
        // Get the logged-in user
        $user = $this->getUser();

        // Get the search parameters from the request
        $jobId = $request->query->get('job');
        $fromDate = $request->query->get('from_date');
        $toDate = $request->query->get('to_date');
        // $status = $request->query->get('status');

        // Convert empty jobId to null
        $jobId = $jobId === "" ? null : (int) $jobId;

        $jobs = $employerJobsRepository->createQueryBuilder('j')
            ->where('j.employer = :user')
            ->andWhere('j.status != :deletedStatus')
            ->setParameter('user', $user)
            ->setParameter('deletedStatus', 'deleted')
            ->getQuery()
            ->getResult();

        // Fetch filtered interviews
        $interviews = $interviews->findByFilters($user, $jobId, $fromDate, $toDate, null);

        return $this->render('employer/interviews.html.twig', [
            'interviews' => $interviews,
            'jobs' => $jobs,
        ]);
    }

    #[Route('/tenders/{type}', name: 'app_employer_tenders', defaults: ['type' => null])]
    public function tenders(EmployerTenderRepository $tenders, string $type = null): Response
    {
        $c = $tenders->filter($type, $this->getUser());
        // dd($type);
        // dd($c);
        return $this->render('employer/tenders.html.twig', [
            'tenders' => $c,
            'type' => $type,
        ]);
    }
    #[Route('/addTender/{tender}', methods: ["POST", "GET"], name: 'app_employer_add_tender', defaults: ['tender' => null], options: ['expose' => true])]
    public function addTender(
        RequestStack $requestStack,
        Request $request,
        EmployerTender $tender = null,
        EntityManagerInterface $em,
        FileUploader $fileUploader,
        SluggerInterface $slugger
    ): Response {

        // $new_quot = new PurchaseQuotation();
        // $new_quot->setPo($order);
        // $orderItems = $order->getPurchaseOrderItems();
        $referer = $requestStack->getCurrentRequest()->headers->get('referer');

        if (!$tender) {
            $tender = new EmployerTender();
            $tender->setStatus("posted");
            $tender->setEmployer($this->getUser());
            $tender->setPostingDate(new DateTime("now"));
        }

        // Get the action URL dynamically
        $form = $this->createForm(TenderFormType::class, $tender);
        $form->handleRequest($request);
        // dd($em->getRepository(BudgetEntry::class)->findBy($criteria));
        // $form = $this->createForm(PurchaseFormType::class,$order);
        //     $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $attachment = $form->get('attachment')->getData();
                if ($attachment) {
                    // $originalFilename = $fileUploader->upload($attachment, $this->getParameter('joboffer_directory'));
                    $originalFilename = pathinfo($attachment->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $fileName = $safeFilename . '-' . uniqid() . '.' . $attachment->guessExtension();
                    $tender->setAttachment($fileName);
                    try {
                        $attachment->move(
                            $this->getParameter('logo_directory'),
                            $fileName
                        );
                    } catch (FileException $e) {
                        // Handle the exception
                    }
                }

                $plan = $this->orderService->getActiveSubscriptionOrder($this->getUser(), 'tender');

                if ($plan) {
                    $planUsed = new MetierPlanUsed();
                    $planUsed->setPlan($plan->getPlan());
                    $planUsed->setSubscription($plan);
                    $planUsed->setType('tender');
                    $planUsed->setDate(new DateTime());
                    $planUsed->setBalance(1);
                    $em->persist($planUsed);
                }
                $em->persist($tender);
                $messages = "Successfully posted new tender";
                $em->flush();
                sweetalert()->success($messages);
                // return $this->redirectToRoute('app_procurement_edit_po', [
                return new RedirectResponse($referer);
                //     'order' => $approval->getPo()->getId(),
                // ]);
            } else {
                $messages = "Please upload only PDF files";
                $em->flush();
                sweetalert()->warning($messages);
                // return $this->redirectToRoute('app_procurement_edit_po', [
                return new RedirectResponse($referer);
            }
        }

        $type = "post tender";
        // $msg = ["msg" => "Are you sure you want shortlist this candidate?", "class" => "alert alert-success"];

        return $this->render('employer/form.html.twig', [
            'form' => $form,
            'type' => $type,
            'tender' => $tender,
            'msg' => "",
        ]);
    }
    #[Route('/view_tender/{tender}', methods: ["POST", "GET"], name: 'app_employer_view_tender', defaults: ['tender' => null], options: ['expose' => true])]
    public function view_tender(
        RequestStack $requestStack,
        Request $request,
        EmployerTender $tender,
        EntityManagerInterface $em,
        FileUploader $fileUploader,
        SluggerInterface $slugger
    ): Response {


        if (!$tender) {
            dd("No tender Available");
        }

        $type = "view tender";
        // $msg = ["msg" => "Are you sure you want shortlist this candidate?", "class" => "alert alert-success"];

        return $this->render('employer/form.html.twig', [
            'type' => $type,
            'tender' => $tender,
            'msg' => "",
        ]);
    }
    #[Route('/courseApplications/{course}', methods: ["POST", "GET"], name: 'app_employer_course_applications', defaults: ['course'], options: ['expose' => true])]
    public function courseApplications(
        RequestStack $requestStack,
        Request $request,
        EmployerCourses $course,
        EntityManagerInterface $em,
        FileUploader $fileUploader,
        SluggerInterface $slugger
    ): Response {

        // $new_quot = new PurchaseQuotation();
        // $new_quot->setPo($order);
        // $orderItems = $order->getPurchaseOrderItems();
        $referer = $requestStack->getCurrentRequest()->headers->get('referer');

        if (!$course) {
            dd("sorry, not course found");
        }

        $type = "post course";
        // $msg = ["msg" => "Are you sure you want shortlist this candidate?", "class" => "alert alert-success"];

        return $this->render('employer/course_applications.html.twig', [
            'course' => $course,
            'msg' => "",
        ]);
    }
    #[Route('/tenderApplications/{tender}', methods: ["POST", "GET"], name: 'app_employer_tender_applications', defaults: ['tender'], options: ['expose' => true])]
    public function tenderApplications(
        RequestStack $requestStack,
        Request $request,
        EmployerTender $tender = null,
        EntityManagerInterface $em,
        FileUploader $fileUploader,
        SluggerInterface $slugger
    ): Response {

        // $new_quot = new PurchaseQuotation();
        // $new_quot->setPo($order);
        // $orderItems = $order->getPurchaseOrderItems();
        $referer = $requestStack->getCurrentRequest()->headers->get('referer');

        if (!$tender) {
            dd("sorry, not tender found");
        }

        $type = "post tender";
        // $msg = ["msg" => "Are you sure you want shortlist this candidate?", "class" => "alert alert-success"];

        return $this->render('employer/tender_applications.html.twig', [
            'tender' => $tender,
            'msg' => "",
        ]);
    }
    #[Route('/addCourse/{course}', methods: ["POST", "GET"], name: 'app_employer_add_course', defaults: ['course' => null], options: ['expose' => true])]
    public function addCourse(
        RequestStack $requestStack,
        Request $request,
        EmployerCourses $course = null,
        EntityManagerInterface $em,
        FileUploader $fileUploader,
        SluggerInterface $slugger
    ): Response {

        $referer = $requestStack->getCurrentRequest()->headers->get('referer');

        if ($this->orderService->getActiveSubscriptionOrder($this->getUser(), 'course')) {
            // dump($this->orderService->getActiveSubscriptionOrder($this->getUser(), 'tender'));
            // dd("yes");
        } else {
            sweetalert()->warning("Unfortunately, you cannot post a course because you don't have an active plan or You have reached your course post limit. Please upgrade your plan.");
            return $this->redirectToRoute('app_employer_courses');
        }
        if (!$course) {
            $course = new Employercourses();
            $course->setStatus("posted");
            $course->setEmployer($this->getUser());
        }

        // Get the action URL dynamically
        $form = $this->createForm(CourseFormType::class, $course);
        $form->handleRequest($request);
        // dd($em->getRepository(BudgetEntry::class)->findBy($criteria));
        // $form = $this->createForm(PurchaseFormType::class,$order);
        //     $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $attachment = $form->get('attachment')->getData();
                if ($attachment) {
                    // $originalFilename = $fileUploader->upload($attachment, $this->getParameter('joboffer_directory'));
                    $originalFilename = pathinfo($attachment->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $fileName = $safeFilename . '-' . uniqid() . '.' . $attachment->guessExtension();
                    $course->setAttachment($fileName);
                    try {
                        $attachment->move(
                            $this->getParameter('logo_directory'),
                            $fileName
                        );
                    } catch (FileException $e) {
                        // Handle the exception
                    }
                } else {
                    $em->persist($course);
                    $messages = "Please uplaod only PDF files";
                    $em->flush();
                    sweetalert()->warning($messages);
                    // return $this->redirectToRoute('app_procurement_edit_po', [
                    return new RedirectResponse($referer);
                    //     'order' => $approval->getPo()->getId(),
                    // ]);
                }
            }

            $plan = $this->orderService->getActiveSubscriptionOrder($this->getUser(), 'course');

            if ($plan) {
                $planUsed = new MetierPlanUsed();
                $planUsed->setPlan($plan->getPlan());
                $planUsed->setSubscription($plan);
                $planUsed->setType('course');
                $planUsed->setDate(new DateTime());
                $planUsed->setBalance(1);
                $em->persist($planUsed);
            }
            $messages = "Please upload only PDF files";
            $em->flush();
            sweetalert()->warning($messages);
            // return $this->redirectToRoute('app_procurement_edit_po', [
            return new RedirectResponse($referer);
        }

        $type = "post course";
        // $msg = ["msg" => "Are you sure you want shortlist this candidate?", "class" => "alert alert-success"];

        return $this->render('employer/form.html.twig', [
            'form' => $form,
            'type' => $type,
            'course' => $course,
            'msg' => "",
        ]);
    }
    #[Route('/courses/{type}', name: 'app_employer_courses', defaults: ['type' => null])]
    public function courses(EmployerCoursesRepository $courses, string $type = null): Response
    {
        $c = $courses->filter($type, $this->getUser());
        // dd($type);
        // dd($c);
        return $this->render('employer/courses.html.twig', [
            'courses' => $c,
            'type' => $type,
        ]);
    }

    // #[Route('/settings', name: 'app_employer_settings')]
    // public function settings(
    //     FileUploader $fileUploader,
    //     MetierPackagesRepository $packages,
    //     UserPasswordHasherInterface $passwordHasher,
    //     Request $request,
    //     EntityManagerInterface $em,
    //     UserRepository $users,
    //     EmployerDetailsRepository $employerDetails,
    // ): Response {
    //     $user = $this->getUser();
    //     $currentUser = $users->find($this->getUser());
    //     $form = $this->createForm(ChangePasswordType::class);
    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $oldPassword = $form->get('oldPassword')->getData();
    //         $newPassword = $form->get('newPassword')->getData();

    //         if (!$passwordHasher->isPasswordValid($currentUser, $oldPassword)) {
    //             sweetalert()->error("Old password is incorrect.");
    //             // return $this->redirectToRoute('user_change_password');
    //         }

    //         if ($oldPassword === $newPassword) {
    //             sweetalert()->error("New password cannot be the same as the old password.");
    //             // return $this->redirectToRoute('user_change_password');
    //         }

    //         $encodedPassword = $passwordHasher->hashPassword($currentUser, $newPassword);
    //         $currentUser = $em->getRepository(User::class)->find($this->getUser());
    //         $currentUser->setPassword($encodedPassword);
    //         $em->persist($user);
    //         $em->flush();

    //         // $this->addFlash('success', 'Password changed successfully.');
    //         // Logout the user and redirect to login page
    //         return $this->redirectToRoute('app_logout');
    //     }

    //     $logoForm = $this->createFormBuilder()->add('logo', FileType::class, [
    //         'label' => 'Exam Image (JPG, JPEG, PNG, Webp file)',
    //         'mapped' => false,
    //         'required' => true,
    //         'constraints' => [
    //             new File([
    //                 'maxSize' => '1024k',
    //                 'mimeTypes' => [
    //                     'image/jpeg',
    //                     'image/png',
    //                     'image/jpg',
    //                     'image/webp',
    //                 ],
    //                 'mimeTypesMessage' => 'Please upload a valid image file (JPG, JPEG, PNG, WEBP).',
    //             ]),
    //         ],
    //     ])->getForm();
    //     $logoForm->handleRequest($request);
    //     if ($logoForm->isSubmitted() && $logoForm->isValid()) {
    //         $imageFile = $logoForm->get('logo')->getData();

    //         if ($imageFile) {
    //             try {
    //                 $employerDetails = $employerDetails->findOneBy(['employer' => $this->getUser()]);
    //                 if ($employerDetails) {
    //                     $existingImage = $employerDetails->getLogo();
    //                     if ($existingImage) {
    //                         // Remove the existing image
    //                         $existingImagePath = $fileUploader->getTargetDirectory() . '/' . $existingImage;
    //                         if (file_exists($existingImagePath)) {
    //                             unlink($existingImagePath);
    //                         }
    //                     }
    //                     $fileName = $fileUploader->uploadImage($imageFile, $this->getParameter('employer_profile_images_directory'));

    //                     $employerDetails->setLogo($fileName);
    //                     $em->persist($employerDetails);
    //                 } else {
    //                     $new_employer_details = new EmployerDetails();
    //                     $new_employer_details->setEmployer($this->getUser());

    //                     $fileName = $fileUploader->uploadImage($imageFile, $this->getParameter('employer_profile_images_directory'));

    //                     $new_employer_details->setLogo($fileName);
    //                     $em->persist($new_employer_details);
    //                 }
    //                 $em->flush();
    //             } catch (\Exception $e) {
    //                 $this->addFlash('error', $e->getMessage());
    //                 // return $this->redirectToRoute('exam_upload');
    //             }
    //         }
    //     } else if ($logoForm->isSubmitted() && !$logoForm->isValid()) {
    //         $errors = $logoForm->getErrors(true, true);
    //         $errors_list = [];
    //         foreach ($errors as $error) {
    //             array_push($errors_list, $error->getMessage());
    //         }
    //         $message = implode(", ", $errors_list);
    //         sweetalert()->error($message);
    //         return $this->redirectToRoute('app_employer_company_settings');
    //     }

    //     $userDetails = $currentUser->getEmployerDetails();
    //     if (!$userDetails) {
    //         $userDetails = new EmployerDetails();
    //         $userDetails->setEmployer($currentUser);
    //     }
    //     $detailsForm = $this->createForm(EmployerDetailsFormType::class, $userDetails)
    //         ->add('name', TextType::class, [
    //             'mapped' => false,
    //             'required' => true,
    //             'data' => $currentUser->getName(),
    //             'attr' => [
    //                 'class' => 'form-control',
    //             ],
    //             'label' => 'Company Name',
    //         ]);

    //     $detailsForm->handleRequest($request);

    //     if ($detailsForm->isSubmitted() && $detailsForm->isValid()) {
    //         $em->persist($userDetails);
    //         $currentUser->setName($detailsForm->get('name')->getData());
    //         $em->persist($currentUser);
    //         $em->flush();
    //     }

    //     // deactivation / activation form
    //     $statusForm = $this->createFormBuilder($currentUser)
    //         ->add('status', ChoiceType::class, [
    //             'required' => true,
    //             'choices' => [
    //                 'Status' => null,
    //                 'Active' => true,
    //                 'Disabled' => false,
    //             ],

    //             'attr' => ['class' => 'form-control'],
    //         ])->getForm();
    //     $statusForm->handleRequest($request);
    //     if ($statusForm->isSubmitted() && $statusForm->isValid()) {
    //         $currentUser->setStatus($statusForm->get('status')->getData());
    //         $em->persist($currentUser);
    //         $em->flush();
    //         sweetalert()->success("Updated account successfully");
    //     }

    //     $activePlan = $this->getActiveOrder($this->getUser());

    //     $services = $packages->findBy(['status' => true, 'type' => "employer", "category" => "service"]);

    //     return $this->render('employer/settings.html.twig', [
    //         'backages' => $packages->findBy(['status' => true, 'type' => "employer", "category" => "subscription"]),
    //         'activeplan' => $activePlan,
    //         'employer' => $this->getUser(),
    //         'form' => $form,
    //         'logoForm' => $logoForm,
    //         'userDetails' => $detailsForm,
    //         'statusForm' => $statusForm,
    //         'services' => $services,
    //     ]);
    // }
    #[Route('/settingsBillingH', name: 'app_employer_settings_billing_history')]
    public function settingsBillingH(
        FileUploader $fileUploader,
        MetierPackagesRepository $packages,
        UserPasswordHasherInterface $passwordHasher,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $users,
        EmployerDetailsRepository $employerDetails,
    ): Response {
        $user = $this->getUser();
        $currentUser = $users->find($this->getUser());
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $oldPassword = $form->get('oldPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();

            if (!$passwordHasher->isPasswordValid($currentUser, $oldPassword)) {
                sweetalert()->error("Old password is incorrect.");
                // return $this->redirectToRoute('user_change_password');
            }

            if ($oldPassword === $newPassword) {
                sweetalert()->error("New password cannot be the same as the old password.");
                // return $this->redirectToRoute('user_change_password');
            }

            $encodedPassword = $passwordHasher->hashPassword($currentUser, $newPassword);
            $currentUser = $em->getRepository(User::class)->find($this->getUser());
            $currentUser->setPassword($encodedPassword);
            $em->persist($user);
            $em->flush();

            // $this->addFlash('success', 'Password changed successfully.');
            // Logout the user and redirect to login page
            return $this->redirectToRoute('app_logout');
        }

        $logoForm = $this->createFormBuilder()->add('logo', FileType::class, [
            'label' => 'Exam Image (JPG, JPEG, PNG, Webp file)',
            'mapped' => false,
            'required' => true,
            'constraints' => [
                new File([
                    'maxSize' => '1024k',
                    'mimeTypes' => [
                        'image/jpeg',
                        'image/png',
                        'image/jpg',
                        'image/webp',
                    ],
                    'mimeTypesMessage' => 'Please upload a valid image file (JPG, JPEG, PNG, WEBP).',
                ]),
            ],
        ])->getForm();
        $logoForm->handleRequest($request);
        if ($logoForm->isSubmitted() && $logoForm->isValid()) {
            $imageFile = $logoForm->get('logo')->getData();

            if ($imageFile) {
                try {
                    $employerDetails = $employerDetails->findOneBy(['employer' => $this->getUser()]);
                    if ($employerDetails) {
                        $existingImage = $employerDetails->getLogo();
                        if ($existingImage) {
                            // Remove the existing image
                            $existingImagePath = $fileUploader->getTargetDirectory() . '/' . $existingImage;
                            if (file_exists($existingImagePath)) {
                                unlink($existingImagePath);
                            }
                        }
                        $fileName = $fileUploader->uploadImage($imageFile, $this->getParameter('employer_profile_images_directory'));

                        $employerDetails->setLogo($fileName);
                        $em->persist($employerDetails);
                    } else {
                        $new_employer_details = new EmployerDetails();
                        $new_employer_details->setEmployer($this->getUser());

                        $fileName = $fileUploader->uploadImage($imageFile, $this->getParameter('employer_profile_images_directory'));

                        $new_employer_details->setLogo($fileName);
                        $em->persist($new_employer_details);
                    }
                    $em->flush();
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    // return $this->redirectToRoute('exam_upload');
                }
            }
        } else if ($logoForm->isSubmitted() && !$logoForm->isValid()) {
            $errors = $logoForm->getErrors(true, true);
            $errors_list = [];
            foreach ($errors as $error) {
                array_push($errors_list, $error->getMessage());
            }
            $message = implode(", ", $errors_list);
            sweetalert()->error($message);
            return $this->redirectToRoute('app_employer_company_settings');
        }

        $userDetails = $currentUser->getEmployerDetails();
        if (!$userDetails) {
            $userDetails = new EmployerDetails();
            $userDetails->setEmployer($currentUser);
        }
        $detailsForm = $this->createForm(EmployerDetailsFormType::class, $userDetails)
            ->add('name', TextType::class, [
                'mapped' => false,
                'required' => true,
                'data' => $currentUser->getName(),
                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'Company Name',
            ]);

        $detailsForm->handleRequest($request);

        if ($detailsForm->isSubmitted() && $detailsForm->isValid()) {
            $em->persist($userDetails);
            $currentUser->setName($detailsForm->get('name')->getData());
            $em->persist($currentUser);
            $em->flush();
        }

        // deactivation / activation form
        $statusForm = $this->createFormBuilder($currentUser)
            ->add('status', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'Status' => null,
                    'Active' => true,
                    'Disabled' => false,
                ],

                'attr' => ['class' => 'form-control'],
            ])->getForm();
        $statusForm->handleRequest($request);
        if ($statusForm->isSubmitted() && $statusForm->isValid()) {
            $currentUser->setStatus($statusForm->get('status')->getData());
            $em->persist($currentUser);
            $em->flush();
            sweetalert()->success("Updated account successfully");
        }

        $activePlan = $this->getActiveOrder($this->getUser());

        $services = $packages->findBy(['status' => true, 'type' => "employer", "category" => "service"]);

        return $this->render('employer/settings_billing_history.html.twig', [
            'backages' => $packages->findBy(['status' => true, 'type' => "employer", "category" => "subscription"]),
            'activeplan' => $activePlan,
            'employer' => $this->getUser(),
            'form' => $form,
            'logoForm' => $logoForm,
            'userDetails' => $detailsForm,
            'statusForm' => $statusForm,
            'services' => $services,
        ]);
    }
    #[Route('/companySettings', name: 'app_employer_company_settings')]
    public function companySettings(
        FileUploader $fileUploader,
        MetierPackagesRepository $packages,
        UserPasswordHasherInterface $passwordHasher,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $users,
        EmployerDetailsRepository $employerDetails,
    ): Response {
        $user = $this->getUser();
        $currentUser = $users->find($this->getUser());

        $logoForm = $this->createFormBuilder()->add('logo', FileType::class, [
            'label' => 'Exam Image (JPG, JPEG, PNG, Webp file)',
            'mapped' => false,
            'required' => true,
            'constraints' => [
                new File([
                    'maxSize' => '1024k',
                    'mimeTypes' => [
                        'image/jpeg',
                        'image/png',
                        'image/jpg',
                        'image/webp',
                    ],
                    'mimeTypesMessage' => 'Please upload a valid image file (JPG, JPEG, PNG, WEBP).',
                ]),
            ],
        ])->getForm();
        $logoForm->handleRequest($request);
        if ($logoForm->isSubmitted() && $logoForm->isValid()) {
            $imageFile = $logoForm->get('logo')->getData();

            if ($imageFile) {
                try {
                    $employerDetails = $employerDetails->findOneBy(['employer' => $this->getUser()]);
                    if ($employerDetails) {
                        $existingImage = $employerDetails->getLogo();
                        if ($existingImage) {
                            // Remove the existing image
                            $existingImagePath = $fileUploader->getTargetDirectory() . '/' . $existingImage;
                            if (file_exists($existingImagePath)) {
                                unlink($existingImagePath);
                            }
                        }
                        $fileName = $fileUploader->uploadImage($imageFile, $this->getParameter('employer_profile_images_directory'));

                        $employerDetails->setLogo($fileName);
                        $em->persist($employerDetails);
                    } else {
                        $new_employer_details = new EmployerDetails();
                        $new_employer_details->setEmployer($this->getUser());

                        $fileName = $fileUploader->uploadImage($imageFile, $this->getParameter('employer_profile_images_directory'));

                        $new_employer_details->setLogo($fileName);
                        $em->persist($new_employer_details);
                    }
                    $em->flush();
                } catch (\Exception $e) {
                    $this->addFlash('error', $e->getMessage());
                    // return $this->redirectToRoute('exam_upload');
                }
            }
        } else if ($logoForm->isSubmitted() && !$logoForm->isValid()) {
            $errors = $logoForm->getErrors(true, true);
            $errors_list = [];
            foreach ($errors as $error) {
                array_push($errors_list, $error->getMessage());
            }
            $message = implode(", ", $errors_list);
            sweetalert()->error($message);
            return $this->redirectToRoute('app_employer_company_settings');
        }

        $userDetails = $currentUser->getEmployerDetails();
        if (!$userDetails) {
            $userDetails = new EmployerDetails();
            $userDetails->setEmployer($currentUser);
        }
        $detailsForm = $this->createForm(EmployerDetailsFormType::class, $userDetails)
            ->add('name', TextType::class, [
                'mapped' => false,
                'required' => true,
                'data' => $currentUser->getName(),
                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'Company Name',
            ]);

        $detailsForm->handleRequest($request);

        if ($detailsForm->isSubmitted() && $detailsForm->isValid()) {
            $em->persist($userDetails);
            $currentUser->setName($detailsForm->get('name')->getData());
            $em->persist($currentUser);
            $em->flush();
        }

        // deactivation / activation form
        $statusForm = $this->createFormBuilder($currentUser)
            ->add('status', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'Status' => null,
                    'Active' => true,
                    'Disabled' => false,
                ],

                'attr' => ['class' => 'form-control'],
            ])->getForm();
        $statusForm->handleRequest($request);
        if ($statusForm->isSubmitted() && $statusForm->isValid()) {
            $currentUser->setStatus($statusForm->get('status')->getData());
            $em->persist($currentUser);
            $em->flush();
            sweetalert()->success("Updated account successfully");
        }

        $activePlan = $this->getActiveOrder($this->getUser());

        $services = $packages->findBy(['status' => true, 'type' => "employer", "category" => "service"]);

        return $this->render('employer/company_settings.html.twig', [
            'employer' => $this->getUser(),
            'logoForm' => $logoForm,
            'userDetails' => $detailsForm,
            'statusForm' => $statusForm,
            'services' => $services,
        ]);
    }
    #[Route('/settings', name: 'app_employer_account_settings')]
    public function accountSettings(
        FileUploader $fileUploader,
        MetierPackagesRepository $packages,
        UserPasswordHasherInterface $passwordHasher,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $users,
        EmployerDetailsRepository $employerDetails,
    ): Response {
        $user = $this->getUser();
        $currentUser = $users->find($this->getUser());
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $oldPassword = $form->get('oldPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();

            if (!$passwordHasher->isPasswordValid($currentUser, $oldPassword)) {
                sweetalert()->error("Old password is incorrect.");
                // return $this->redirectToRoute('user_change_password');
            }

            if ($oldPassword === $newPassword) {
                sweetalert()->error("New password cannot be the same as the old password.");
                // return $this->redirectToRoute('user_change_password');
            }

            $encodedPassword = $passwordHasher->hashPassword($currentUser, $newPassword);
            $currentUser = $em->getRepository(User::class)->find($this->getUser());
            $currentUser->setPassword($encodedPassword);
            $em->persist($user);
            $em->flush();

            // $this->addFlash('success', 'Password changed successfully.');
            // Logout the user and redirect to login page
            return $this->redirectToRoute('app_logout');
        }

        // deactivation / activation form
        $statusForm = $this->createFormBuilder($currentUser)
            ->add('status', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'Status' => null,
                    'Active' => true,
                    'Disabled' => false,
                ],

                'attr' => ['class' => 'form-control'],
            ])->getForm();
        $statusForm->handleRequest($request);
        if ($statusForm->isSubmitted() && $statusForm->isValid()) {
            $currentUser->setStatus($statusForm->get('status')->getData());
            $em->persist($currentUser);
            $em->flush();
            sweetalert()->success("Updated account successfully");
        }

        $activePlan = $this->getActiveOrder($this->getUser());

        $services = $packages->findBy(['status' => true, 'type' => "employer", "category" => "service"]);

        return $this->render('employer/account_settings.html.twig', [
            'employer' => $this->getUser(),
            'form' => $form,
            'statusForm' => $statusForm,
        ]);
    }
    #[Route('/profile-image/{filename}', name: 'profile_image')]
    public function serveImage(string $filename, Request $request): Response
    {
        // Check user authentication
        // $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Path to the image
        $imagePath = $this->getParameter('employer_profile_images_directory') . '/' . $filename;
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

    #[Route('/postJob/{job}', name: 'app_employer_post_job', defaults: ['job' => null], methods: ['GET', 'POST'])]
    public function postJob(
        Request $request,
        EmployerJobsRepository $jobs,
        EmployerJobs $job = null,
        EmployerDetailsRepository $details,
        JobSeekerJobAlertRepository $alerts,
        SmsAPIService $smsAPIService,
        EntityManagerInterface $em

    ): Response {

        // dd($this->getActiveOrder($this->getUser()));
        if ($this->orderService->getActiveSubscriptionOrder($this->getUser(), 'job')) {
            // dump($this->orderService->getActiveSubscriptionOrder($this->getUser(), 'tender'));
            // dd("yes");
        } else {
            sweetalert()->warning("Unfortunately, you cannot post a job because you don't have an active plan or You have reached your job post limit. Please upgrade your plan.");
            return $this->redirectToRoute('app_employer_jobs');
        }

        // check if an active plan
        // if (! $this->getActiveOrder($this->getUser())) {
        //     sweetalert()->warning("Unfortunately, you cannot post a job because you don't have an active plan.");
        //     return $this->redirectToRoute('app_employer_jobs');
        // }

        // dump($jobs->findMatchingJobSeekers($job));
        // dd("yes");
        // check if the employer details are full
        $emp_details = $details->findOneBy(['employer' => $this->getUser()]);

        if ($emp_details) {
        } else {

            sweetalert()->warning("In order to be able to post and access other features, please click Settings and complete all required fields.");
            return $this->redirectToRoute('app_employer_company_settings');
        }

        // Check if all required fields are filled
        if (
            // empty($emp_details->getHeading()) ||
            // empty($emp_details->getDescription()) ||
            empty($emp_details->getLogo()) ||
            empty($emp_details->getIndustry()) ||
            empty($emp_details->getCountry()) ||
            empty($emp_details->getCity()) ||
            empty($emp_details->getAddress()) ||
            empty($emp_details->getPhone())
        ) {
            sweetalert()->error("In order to be able to post and access other features, please click Settings and complete all required fields.");
            return $this->redirectToRoute('app_employer_company_settings');
        }

        if (!$job) {
            $job = new EmployerJobs();
            $job->setStatus("draft");
            $job->setApplicationClosingDate((new DateTime())->modify('+30 days'));
        }


        // dd("hh");
        $job->setOperation('job');
        $form = $this->createForm(JobFormType::class, $job);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // dd($form->getData());
            // dd($job->getApplicationClosingDate());
            $career = $form->get('title')->getData();

            if (is_numeric($career)) {
                $found_career = $em->getRepository(MetierCareers::class)->find($career);
                $career = $found_career->getName();

                $job->setJobtitle($found_career);
            } else {
                $career_with_name = $em->getRepository(MetierCareers::class)->findOneBy(['name' => $career]);
                $job->setJobtitle($career_with_name);
            }

            $required_skills = explode('|', $form->get('required_skill')->getData());

            $skills_objects = [];
            foreach ($required_skills as $skill) {
                if (is_numeric($skill)) {
                    $skill = $em->getRepository(MetierSkills::class)->find($skill);
                    if ($skill) {
                        $skills_objects[] = $skill;
                    }
                } else {
                    $skill_obj = new MetierSkills();
                    $skill_obj->setName($skill);
                    $skill_obj->setCustom(true);
                    $em->persist($skill_obj);
                    $skills_objects[] = $skill_obj;
                }
            }

            $em->flush();

            $job->getRequiredSkill()->clear();

            foreach ($skills_objects as $skill) {
                $job->addRequiredSkill($skill);
            }

            $preferred_skills = explode('|', $form->get('preferred_skill')->getData());

            $skills_objects = [];
            foreach ($preferred_skills as $skill) {
                if (is_numeric($skill)) {
                    $skill = $em->getRepository(MetierSkills::class)->find($skill);
                    if ($skill) {
                        $skills_objects[] = $skill;
                    }
                } else {
                    $skill_obj = new MetierSkills();
                    $skill_obj->setName($skill);
                    $skill_obj->setCustom(true);
                    $em->persist($skill_obj);
                    $skills_objects[] = $skill_obj;
                }
            }

            $em->flush();

            $job->getPreferredSkill()->clear();

            foreach ($skills_objects as $skill) {
                $job->addPreferredSkill($skill);
            }

            $job->setTitle($career);
            $job->setEmployer($this->getUser());

            $plan = $this->orderService->getActiveSubscriptionOrder($this->getUser(), 'job');

            if ($plan) {
                $planUsed = new MetierPlanUsed();
                $planUsed->setPlan($plan->getPlan());
                $planUsed->setSubscription($plan);
                $planUsed->setType('job');
                $planUsed->setDate(new DateTime());
                $planUsed->setBalance(1);
                $em->persist($planUsed);
            }

            $job_category = $job->getJobCategory();
            $recommends = $alerts->findJobSeekersByMatchingJobCategory($job_category);

            if ($job->getStatus() === "posted" && $job->getId()) {
                if ($job->getIsPrivate()) {
                    $internals = $job->getEmployer()?->getEmployerStaff();
                    if ($internals) {
                        foreach ($internals as $staff) {
                            $jobseeker = $staff->getJobseeker();
                            if ($jobseeker) {
                                $jobalerts = $jobseeker->getJobalerts();
                                $alert = $jobalerts[0] ?? null;
                                if ($alert && $alert->getPhone()) {
                                    $smsAPIService->send($alert->getPhone(), 'internal_job', [
                                        'company' => $job->getEmployer()?->getName() ?? 'Unknown Company',
                                        'title' => $job->getTitle() ?? "Missing Title",
                                    ]);
                                }
                            }
                        }
                    }
                } else {
                    foreach ($recommends as $recommending) {
                        if ($recommending && $recommending->getPhone()) {
                            $smsAPIService->send($recommending->getPhone(), 'job_alert', [
                                'company' => $job->getEmployer()?->getName() ?? 'Unknown Company',
                                'title' => $job->getTitle() ?? 'Missing Title',
                            ]);
                        }
                    }
                }
            }


            $em->persist($job);
            $em->flush();
            // Return a successful response (e.g., 200 OK) for Turbo to update the pages
            // return $this->json(['success' => true]);
            if ($job->getStatus() === "draft") {
                return $this->redirectToRoute('app_employer_review_job', [
                    'job' => $job->getId(),
                ]);
            }

            return $this->redirectToRoute('app_employer_jobs');
        }

        $question = new EmployerJobQuestion();
        $questionForm = $this->createForm(JobQuestionType::class, $question);
        $questionForm->handleRequest($request);

        $required_skills = $job->getRequiredSkill()?->getValues() ?? [];
        $required_skills = array_map(function ($skill) {
            return ['value' => $skill->getId(), 'text' => $skill->getName()];
        }, $required_skills);

        $preferred_skills = $job->getPreferredSkill()?->getValues() ?? [];
        $preferred_skills = array_map(function ($skill) {
            return ['value' => $skill->getId(), 'text' => $skill->getName()];
        }, $preferred_skills);

        $career_skills = $job->getJobtitle()?->getSkills()?->getValues() ?? [];
        // dd($career_skills);
        $career_skills = array_map(function ($skill) {
            return ['value' => $skill->getId(), 'text' => $skill->getName()];
        }, $career_skills);

        return $this->render('employer/new_job_post.html.twig', compact('form', 'question', 'questionForm', 'job', 'required_skills', 'preferred_skills', 'career_skills'));
    }
    #[Route('/blockUser/{blocked}', name: 'app_employer_block_user', methods: ['GET', 'POST'])]
    public function blockUser(Request $request, User $blocked, EmployerDetailsRepository $details, EntityManagerInterface $em): Response
    {

        // check if the employer details are full

        $blockedby = $this->getUser();

        if (!$blocked) {
            sweetalert()->warning("Please choose the user to block.");
        }
        if (!$blockedby) {

            sweetalert()->warning("Please choose the user to block.");
        }
        $job = new MetierBlockedUser();
        $job->setBlockedBy($blockedby);
        $job->setBlockedUser($blocked);
        sweetalert()->success("User has blocked successfully, which means the user is no longer getting the messages you sent");
        $em->persist($job);
        $em->flush();
        // dd("hh");

        return $this->redirectToRoute('app_employer_messages');
    }
    #[Route('/employerLanding', name: 'app_employer_landing', methods: ['GET', 'POST'])]
    public function employerLanding(Request $request, EntityManagerInterface $em): Response
    {

        return $this->render('home/employer_landing.html.twig');
    }
    #[Route('/jobReview/{job}', name: 'app_employer_review_job', methods: ['GET', 'POST'])]
    public function jobReview(
        Request $request,
        EmployerJobs $job,
        EntityManagerInterface $em,
        MetierEmailTempsRepository $temps,
    ): Response {
        // if ($job->getStatus() !== "draft") {
        //     return false;
        // }
        // dd($job);

        foreach ($job->getEmployerJobQuestions() as $question) {
            $question->addEmployerJobQuestionAnswer(new EmployerJobQuestionAnswer());
        }
        // dd("hh");
        // $job->setOperation('job');
        $form = $this->createForm(JobFormTypeShort::class, $job);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // dd($form->getData());

            $job->setEmployer($this->getUser());
            $em->persist($job);
            $em->flush();



            // Return a successful response (e.g., 200 OK) for Turbo to update the pages
            // return $this->json(['success' => true]);
            $this->addFlash('success', 'Your job has been posted successfully.');
            return $this->redirectToRoute('app_employer_jobs');
        }
        $question = new EmployerJobQuestion();
        $questionForm = $this->createForm(JobQuestionType::class, $question);
        $questionForm->handleRequest($request);
        return $this->render('employer/new_job_post_review.html.twig', compact('form', 'question', 'questionForm', 'job'));
    }
    #[Route('/jobQuestionnaire/{job}', name: 'app_employer_jobe_questionnaire', methods: ['GET', 'POST'])]
    public function jobQuestionnaire(Request $request, EmployerJobs $job, EntityManagerInterface $em): Response
    {

        foreach ($job->getEmployerJobQuestions() as $question) {
            $question->addEmployerJobQuestionAnswer(new EmployerJobQuestionAnswer());
        }
        // dd("hh");
        // $job->setOperation('job');
        $form = $this->createForm(JobFormTypeShort::class, $job);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // dd($form->getData());

            $job->setEmployer($this->getUser());
            $em->persist($job);
            $em->flush();
            // Return a successful response (e.g., 200 OK) for Turbo to update the pages
            // return $this->json(['success' => true]);
            return $this->redirectToRoute('app_employer_jobs');
        }
        $question = new EmployerJobQuestion();
        $questionForm = $this->createForm(JobQuestionType::class, $question);
        $questionForm->handleRequest($request);
        return $this->render('employer/new_job_post_q.html.twig', compact('form', 'question', 'questionForm', 'job'));
    }

    #[Route('/checkOutStripe/{package}', name: 'app_employer_checkout_package', methods: ['GET', 'POST'])]
    public function checkOutStripe(
        $stripSK,
        Request $request,
        MetierPackages $package,
        EntityManagerInterface $em,
        PaymentService $paymentService
    ): RedirectResponse {

        if (!$package) {
            dd("Package is missing");
        }

        // Calculate the tax
        $tax = $paymentService->calculateTax($package->getCost());

        // Calculate total amount including tax
        $totalAmount = ($package->getCost() + $tax) * 100; // Stripe expects the amount in cents

        // Ensure the total amount is cast to an integer (to avoid floating point issues)
        $totalAmount = (int) round($totalAmount);

        $products = [];
        // Prepare products array for Stripe
        $products[] = [
            'price_data' => [
                'currency' => 'usd',
                'unit_amount' => $totalAmount, // Total amount in cents, as an integer
                'product_data' => [
                    'name' => "Employer " . $package->getName() . " Package",
                ],
            ],
            'quantity' => 1,
        ];

        $successBaseUrl = $this->generator->generate("app_employer_stripe_success", [
            'package' => $package->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $successUrl = $successBaseUrl . '?id_sessions={CHECKOUT_SESSION_ID}';

        \Stripe\Stripe::setApiKey($stripSK);

        $checkout_session = Session::create([
            'customer_email' => $this->getUser()->getEmail(),
            'payment_method_types' => ['card'],
            'line_items' => [
                $products,
            ],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $this->generator->generate("app_employer_stripe_error", [
                'package' => $package->getId(),

            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'automatic_tax' => [
                'enabled' => true,
            ],
        ]);

        return new RedirectResponse($checkout_session->url);
    }

    #[Route('/stripOnSuccess/{package}', name: 'app_employer_stripe_success', methods: ['GET', 'POST'])]
    public function stripOnSuccess(
        $stripSK,
        Request $request,
        MetierPackages $package,
        EntityManagerInterface $em,
        MetierEmailTempsRepository $temps,
        MailService $mailService,
    ): Response {
        $session_id = $request->query->get('id_sessions');
        $customer_data = $this->stripeGateway->checkout->sessions->retrieve(
            $session_id,
            []
        );

        $total_amount = $customer_data['amount_total'];

        // dump($customer_data);

        // dd("");

        // create order information
        $order = new MetierOrder();
        $order->setCustomer($this->getUser());
        $order->setAmount($total_amount / 100);
        $order->setPlan($package);
        $order->setPaymentStatus("paid");
        $order->setOrderDate(new DateTime("now"));
        $order->setCustomerType($package->getCategory());
        $order->setCategory("employer");
        if ($package->getCategory() === "subscription") {
            $order->setValidityPeriod($package->getDuration());
        }
        $order->setOrderUid(uniqid('ord_' . $order->getId(), true));

        $new_payment = new MetierOrderPayment();
        $new_payment->setReceivedFrom($this->getUser());

        $new_payment->setPaymentCategory("stripe");
        $new_payment->setPaymentDate(new DateTime("now"));
        $new_payment->setPurchase($order);
        $new_payment->setAmount($total_amount);
        $em->persist($new_payment);
        $em->persist($order);

        $user = $this->getUser();

        if ($package->getType() == "employer" && $package->getCategory() == "service" && $package->getClass() == "description") {

            // dd("waa psychometric");
            $template = $temps->findOneBy(["action" => "employed_description_service_sale"]);

            $d = [
                "name" => $order->getCustomer()->getName(),
                "email" => $order->getCustomer()->getEmail(),
                "type" => $template->getType(),
                "content" => $template->getContent(),
                "subject" => $template->getSubject(),
                "header" => $template->getHeader(),
                "cat" => "",
                "extra" => "",
                "otp" => "",
                "employer" => $order->getCustomer()->getName(),
                "interview_date" => "",
                "platform" => "",
                "job_title" => "",
                "link" => "",
                "job_id" => "",
                "closing_date" => "",
                "interview_time" => "",
            ];
            $event = new SendEmailEvent($d);
            $this->eventDispatcher->dispatch($event, SendEmailEvent::class);
        } elseif ($package->getType() == "employer" && $package->getCategory() == "service" && $package->getClass() == "description_offer") {
            $template = $temps->findOneBy(["action" => "employed_description_offer_letter_service_sale"]);

            $d = [
                "name" => $order->getCustomer()->getName(),
                "email" => $order->getCustomer()->getEmail(),
                "type" => $template->getType(),
                "content" => $template->getContent(),
                "subject" => $template->getSubject(),
                "header" => $template->getHeader(),
                "cat" => "",
                "extra" => "",
                "otp" => "",
                "employer" => $order->getCustomer()->getName(),
                "interview_date" => "",
                "platform" => "",
                "job_title" => "",
                "link" => "",
                "job_id" => "",
                "closing_date" => "",
                "interview_time" => "",
            ];
            $event = new SendEmailEvent($d);
            $this->eventDispatcher->dispatch($event, SendEmailEvent::class);
        } elseif ($package->getType() == "employer" && $package->getCategory() == "subscription") {
            $template = $temps->findOneBy(["action" => "employer_new_subscription_plan"]);

            $d = [
                "name" => $order->getCustomer()->getName(),
                "email" => $order->getCustomer()->getEmail(),
                "type" => $template->getType(),
                "content" => $template->getContent(),
                "subject" => $template->getSubject(),
                "header" => $template->getHeader(),
                "cat" => "",
                "extra" => "",
                "otp" => "",
                "employer" => $order->getCustomer()->getName(),
                "interview_date" => "",
                "platform" => "",
                "job_title" => "",
                "link" => "",
                "job_id" => "",
                "closing_date" => $order->getValidTo()->format("Y-m-d H:i:s"),
                "interview_time" => "",
            ];
            $event = new SendEmailEvent($d);
            $this->eventDispatcher->dispatch($event, SendEmailEvent::class);
        }
        $em->flush();

        if ($order && $new_payment) {
            return $this->redirectToRoute('app_employer_purchase_receipt', [
                'order' => $order->getId(),
            ]);
        }
        // Use the Stripe API to retrieve the payment intent details
        // $stripe = new \Stripe\Stripe($stripSK);

        // halkan ku qor logic-gii back-end-ka
        return $this->render('employer/stripe_success.html.twig', compact('package'));
    }
    #[Route('/stripOnError/{package}', name: 'app_employer_stripe_error', methods: ['GET', 'POST'])]
    public function stripOnError(Request $request, MetierPackages $package, EntityManagerInterface $em): Response
    {

        // halkan ku qor logic-gii back-end-ka
        return $this->render('employer/stripe_error.html.twig', compact('package'));
    }

    public function checkStatus(array $statuses, $status_value): bool
    {
        if (in_array($status_value, $statuses)) {
            return true;
        } else {
            return false;
        }
    }
    #[Route('/testtest', name: 'app_test_sms', methods: ['GET', 'POST'])]
    public function testsms(SmsAPIService $smsAPIService): JsonResponse
    {
        // now send the 
        $info = $smsAPIService->send("654097602", 'job_alert', [
            'company' => "Warsame Cali Cabdi",
            'title' => "Geeljire",
        ]);

        // Get cached token
        // Return JSON response
        return new JsonResponse([
            'status' => 'success',

        ]);
        //     $ch = curl_init('https://somteloss.com/externalresource/SMS/sendsms');
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // $response = curl_exec($ch);
        // $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // curl_close($ch);

        // return new JsonResponse([
        //     'curl_test' => $httpCode,
        //     'curl_response' => $response
        // ]);
    }
    #[Route('/changeJobStatus/{job}/{encodedStatus}/{re}', name: 'app_employer_job_change_status', defaults: ["re" => null], methods: ['GET', 'POST'])]
    public function changeJobStatus(
        Request $request,
        EmployerJobs $job,
        string $re = null,
        string $encodedStatus,
        EntityManagerInterface $em,
        MetierEmailTempsRepository $temps,
        SluggerInterface $slugger,
        JobApplicationRepository $applications,
        JobSeekerJobAlertRepository $alerts,
        SmsAPIService $smsAPIService
    ): Response {

        // Decode the status
        $statusEnum = JobStatusEnum::fromEncoded($encodedStatus);

        if ($statusEnum === null) {
            dd("The status is unknown");
        }

        $status = $statusEnum->value;
        $job_category = $job->getJobCategory();
        $recommends = $alerts->findJobSeekersByMatchingJobCategory($job_category);

        // dd($status);

        if ($statusEnum === JobStatusEnum::POSTED && $re !== "repost") {
            // now get those who got same category




            // dd($recommends);

            // dd("yes now posting");
            $job->setStatus($status);
            $currentDate = new \DateTime();

            $template = $temps->findOneBy(["action" => "employer_job_post"]);

            if (in_array($statusEnum, JobStatusEnum::cases())) {
                $job->setStatus($status);
            } else {
                dd("The status is unknown");
            }

            $em->persist($job);
            $em->flush();


            if (!$template) {
                return $this->redirectToRoute('app_employer_jobs');
            }

            $d = [
                "name" => $job->getEmployer()->getName(),
                "email" => $job->getEmployer()->getEmail(),
                "type" => $template->getType(),
                "content" => $template->getContent(),
                "subject" => $template->getSubject(),
                "header" => $template->getHeader(),
                "cat" => "",
                "extra" => "",
                "otp" => "",
                "employer" => $job->getEmployer()->getName(),
                "interview_date" => "",
                "platform" => "",
                "job_title" => $job->getTitle(),
                "link" => "",
                "job_id" => "#" . $job->getId(),
                "closing_date" => $job->getApplicationClosingDate()->format("Y-m-d H:i:s"),
                "interview_time" => "",
            ];
            $event = new SendEmailEvent($d);
            $this->eventDispatcher->dispatch($event, SendEmailEvent::class);

            if ($job->getIsPrivate()) {
                $internals = $job->getEmployer()?->getEmployerStaff();
                if ($internals) {
                    foreach ($internals as $staff) {
                        $jobseeker = $staff->getJobseeker();
                        if ($jobseeker) {
                            $jobalerts = $jobseeker->getJobalerts();
                            $alert = $jobalerts[0] ?? null;
                            if ($alert && $alert->getPhone()) {
                                $smsAPIService->send($alert->getPhone(), 'internal_job', [
                                    'company' => $job->getEmployer()?->getName() ?? 'Unknown Company',
                                    'title' => $job->getTitle() ?? "Missing Title",
                                ]);
                            }
                        }
                    }
                }
            } else {
                foreach ($recommends as $recommending) {
                    if ($recommending && $recommending->getPhone()) {
                        $smsAPIService->send($recommending->getPhone(), 'job_alert', [
                            'company' => $job->getEmployer()?->getName() ?? 'Unknown Company',
                            'title' => $job->getTitle() ?? 'Missing Title',
                        ]);
                    }
                }
            }
            // hello from employer controller 
        } elseif ($statusEnum === JobStatusEnum::POSTED && $re === "repost") {

            // TODO: check repost functionality
            $job->setStatus('posted');
            $job->setCreatedAt(new DateTime("now"));
            $job->setRepost(true);
            $em->persist($job);
            $em->flush();

            if ($job->getIsPrivate()) {
                $internals = $job->getEmployer()?->getEmployerStaff();
                if ($internals) {
                    foreach ($internals as $staff) {
                        $jobseeker = $staff->getJobseeker();
                        if ($jobseeker) {
                            $jobalerts = $jobseeker->getJobalerts();
                            $alert = $jobalerts[0] ?? null;
                            if ($alert && $alert->getPhone()) {
                                $smsAPIService->send($alert->getPhone(), 'internal_job', [
                                    'company' => $job->getEmployer()?->getName() ?? 'Unknown Company',
                                    'title' => $job->getTitle() ?? "Missing Title",
                                ]);
                            }
                        }
                    }
                }
            } else {
                foreach ($recommends as $recommending) {
                    if ($recommending && $recommending->getPhone()) {
                        $smsAPIService->send($recommending->getPhone(), 'job_alert', [
                            'company' => $job->getEmployer()?->getName() ?? 'Unknown Company',
                            'title' => $job->getTitle() ?? 'Missing Title',
                        ]);
                    }
                }
            }

        } elseif ($statusEnum === JobStatusEnum::CLOSED) {

            // now get those who got same category
            $job_category = $job->getJobCategory();

            $recommends = $alerts->findJobSeekersByMatchingJobCategory($job_category);

            // dump($recommends);

            // dd("yes now posting");
            $job->setStatus($status);
            $currentDate = new \DateTime();
            if ($job->getApplicationClosingDate() < $currentDate) {
                sweetalert()->warning('Please extend the application closing date first, by editing the job');
                return $this->redirectToRoute('app_employer_jobs');
            }

            $template = $temps->findOneBy(["action" => "jobseeker_application_closed"]);
            //get all the applicants of this job
            $applicants = $applications->findBy(['employer' => $this->getUser(), 'job' => $job->getId()]);

            if (in_array($statusEnum, JobStatusEnum::cases())) {
                $job->setStatus($status);
            } else {
                dd("The status is unknown");
            }

            foreach ($applicants as $applicant) {
                if (!$template) {
                    return $this->redirectToRoute('app_employer_jobs');
                }

                $d = [
                    "name" => $applicant->getJobseeker()->getName(),
                    "email" => $applicant->getJobseeker()->getEmail(),
                    "type" => $template->getType(),
                    "content" => $template->getContent(),
                    "subject" => $template->getSubject(),
                    "header" => $template->getHeader(),
                    "cat" => "",
                    "extra" => "",
                    "otp" => "",
                    "employer" => $job->getEmployer()->getName(),
                    "interview_date" => "",
                    "platform" => "",
                    "job_title" => $job->getTitle(),
                    "link" => "",
                    "job_id" => "#" . $job->getId(),
                    "closing_date" => "",
                    "interview_time" => "",
                ];
                $event = new SendEmailEvent($d);
                $this->eventDispatcher->dispatch($event, SendEmailEvent::class);
            }

            $em->persist($job);
            $em->flush();

            foreach ($recommends as $recommending) {



                $generatedSlug = $slugger->slug($job->getTitle())->lower()->toString();

                $new_recommended_candidate = new JobSeekerRecommendedJobs();
                $new_recommended_candidate->setEmployer($job->getEmployer());
                $new_recommended_candidate->setJobseeker($recommending->getJobseeker());
                $new_recommended_candidate->setJob($job);
                $template = $temps->findOneBy(["action" => "jobseeker_recommended_category"]);

                $link = $this->generateUrl('job_seeker_job_details', [
                    'slug' => $generatedSlug,
                    'uuid' => $job->getUuid(),
                ], UrlGeneratorInterface::ABSOLUTE_URL);
                $d = [
                    "name" => $recommending->getJobseeker()->getName(),
                    "email" => $recommending->getJobseeker()->getEmail(),
                    "type" => $template->getType(),
                    "content" => $template->getContent(),
                    "subject" => $template->getSubject(),
                    "header" => $template->getHeader(),
                    "cat" => "",
                    "extra" => "",
                    "otp" => "",
                    "employer" => $job->getEmployer()->getName(),
                    "interview_date" => "",
                    "platform" => "",
                    "job_title" => $job->getTitle(),
                    "link" => $link,
                    "job_id" => "#" . $job->getId(),
                    "closing_date" => $job->getApplicationClosingDate()->format("Y-m-d H:i:s"),
                    "interview_time" => "",
                ];
                $event = new SendEmailEvent($d);
                $this->eventDispatcher->dispatch($event, SendEmailEvent::class);

                $em->persist($new_recommended_candidate);
                $em->flush();
            }
        } else {
            $job->setStatus($status);
            $em->persist($job);
            $em->flush();
        }

        return $this->redirectToRoute('app_employer_jobs');
    }
    #[Route('/deleteAShortList/{shortlist}', name: 'app_employer_delete_a_shortlist', methods: ['GET', 'POST'])]
    public function deleteAShortList(
        Request $request,
        JobApplicationShortlist $shortlist,
        EntityManagerInterface $em,
    ): Response {
        // Decode the status
        $em->remove($shortlist);
        $em->flush();
        $this->addFlash('success', 'Successfully deleted');
        return $this->redirectToRoute('app_employer_autoshortlisted');
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
    #[Route('/contacts', name: 'app_employer_contacts')]
    public function contacts(Request $request, EntityManagerInterface $em, RecaptchaValidator $recaptchaValidator): Response
    {
        $contact_form = new MetierContacts();
        $form = $this->createForm(ContactFormType::class, $contact_form);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $recaptchaResponse = $request->request->get('g-recaptcha-response');
                if (null == $recaptchaResponse) {
                    return $this->redirectToRoute('app_employer_contacts', ['form_message' => 'reCAPTCHA validation failed']);
                }

                $remoteIp = $request->getClientIp();
                $verificationResult = $recaptchaValidator->verify($recaptchaResponse, $remoteIp);

                if (!$verificationResult) {
                    // Handle the error, reCAPTCHA validation failed
                    return $this->redirectToRoute('app_employer_contacts', ['form_message' => 'reCAPTCHA validation failed']);
                }

                if ($this->getUser()) {
                    $contact_form->setUser($this->getUser());
                }

                $em->persist($contact_form);
                $em->flush();
                return $this->redirectToRoute('app_employer_contacts', ['form_message' => 'success']);
            } else {
                // $errors = $this->getFormErrors();
                $errors = $this->getFormErrors($form);
                return $this->redirectToRoute('app_employer_contacts', ['form_message' => 'error']);
            }
        }

        return $this->render('employer/contacts.html.twig', [
            'site_key' => $this->getParameter('recaptcha.site_key'),
            'form' => $form->createView(),
            'form_message' => $request->get('form_message'),
        ]);
    }
    #[Route('/ps_inquiery', name: 'app_employer_ps_inquiery')]
    public function ps_inquiery(
        Request $request,
        EntityManagerInterface $em,
        MetierAdsRepository $metierAdsRepository
    ): Response {
        $contact_form = new MetierInquiry();
        $form = $this->createForm(MetierInquiryFormType::class, $contact_form);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {

                $remoteIp = $request->getClientIp();

                $em->persist($contact_form);
                $em->flush();
                return $this->redirectToRoute('app_employer_ps_inquiery', ['form_message' => 'success']);
            } else {
                // $errors = $this->getFormErrors();
                $errors = $this->getFormErrors($form);
                return $this->redirectToRoute('app_employer_ps_inquiery', ['form_message' => 'error']);
            }
        }

        return $this->render('employer/inquery.html.twig', [
            'form' => $form->createView(),
            'form_message' => $request->get('form_message'),
            'ads' => $metierAdsRepository->findActiveAds(),
        ]);
    }
    #[Route('/changeCourseStatus/{course}/{status}', name: 'app_employer_course_change_status', methods: ['GET', 'POST'])]
    public function changeCourseStatus(Request $request, EmployerCourses $course, string $status, EntityManagerInterface $em): Response
    {
        $statuses = ["posted", "closed", "deleted"];
        if (in_array($status, $statuses)) {
            $course->setStatus($status);
        } else {
            dd("The status is unknown");
        }

        $em->persist($course);
        $em->flush();
        return $this->redirectToRoute('app_employer_courses');
    }

    #[Route('/changeTenderStatus/{tender}/{status}', name: 'app_employer_tender_change_status', methods: ['GET', 'POST'])]
    public function changeTenderStatus(Request $request, EmployerTender $tender, string $status, EntityManagerInterface $em): Response
    {
        $statuses = ["posted", "closed", "deleted"];
        if (in_array($status, $statuses)) {
            $tender->setStatus($status);
        } else {
            dd("The status is unknown");
        }
        if ($status === "posted") {
            $tender->setPostingDate(new DateTime("now"));
        }
        $em->persist($tender);
        $em->flush();
        return $this->redirectToRoute('app_employer_tenders');
    }

    #[Route('/postTender/{tender}', name: 'app_employer_post_tender', defaults: ['tender' => null])]
    public function postTender(Request $request, EmployerJobs $tender = null, EntityManagerInterface $em): Response
    {
        if ($this->orderService->getActiveSubscriptionOrder($this->getUser(), 'tender')) {
            // dump($this->orderService->getActiveSubscriptionOrder($this->getUser(), 'tender'));
            // dd("yes");
        } else {
            sweetalert()->warning("Unfortunately, you cannot post a tender because you don't have an active plan or You have reached your tender post limit. Please upgrade your plan.");
            return $this->redirectToRoute('app_employer_tenders');
        }

        if (!$tender) {
            $tender = new EmployerJobs();
        }

        $tender->setOperation('tender');
        // dd("hh");
        $form = $this->createForm(TenderFormType::class, $tender);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $tender->setEmployer($this->getUser());
            $em->persist($tender);
            $em->flush();

            // Return a successful response (e.g., 200 OK) for Turbo to update the pages
            // return $this->json(['success' => true]);
            return $this->redirectToRoute('app_employer');
        } else {
            // Log or display form errors
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            // Optionally, log the errors or dump them for debugging
            // dd($errors);
            // You can also log errors to a file or monitoring system if needed
            // $this->get('logger')->error('Form errors: ' . implode(', ', $errors));
        }

        return $this->render('employer/new_tender_post.html.twig', compact('form'));
    }

    #[Route('/checkout/{package}', name: 'app_employer_purchase_checkout', methods: ['GET', 'POST'])]
    public function checkout(Request $request, MetierPackages $package, EntityManagerInterface $em): Response
    {
        $zaadForm = $this->createFormBuilder(null)
            ->add("number", NumberType::class, [
                "attr" => [
                    "class" => "form-control",
                ],
            ])->getForm();

        // halkan ku qor logic-gii back-end-ka
        return $this->render('employer/checkout.html.twig', compact('package', 'zaadForm'));
    }

    public function hasValidDigitCount($number)
    {
        // Check if the number is a string representation of a number
        if (!is_numeric($number)) {
            return false; // Not a number, return false
        }

        $numLength = strlen((string) $number);
        if ($numLength == 9 || $numLength <= 10) {
            return true;
        } else {
            return false;
        }
    }
    #[Route('/zaadPurchase/{package}', name: 'app_employer_zaad_purchase', methods: ['GET', 'POST'])]
    public function zaadPurchase(
        $waafiEndpoint,
        $merchantUid,
        $apiUserId,
        MailService $mailService,
        $apiKey,
        PaymentService $paymentService,
        Request $request,
        MetierPackages $package,
        EntityManagerInterface $em,
        MetierEmailTempsRepository $temps,
    ): JsonResponse {

        $tax_amount = $paymentService->calculateTax($package->getCost());

        // Calculate total amount including tax
        $totalAmount = $package->getCost() + $tax_amount; // Stripe expects the amount in cents

        // Ensure the total amount is cast to an integer (to avoid floating point issues)
        $totalAmount = round($totalAmount, 2);

        $telephone = $request->request->get('telephone');
        if ($this->hasValidDigitCount($telephone)) {
        } else {
            dd("There has been an error with your number");
        }
        $data = [
            "schemaVersion" => "1.0",
            "requestId" => "53232R22R",
            "timestamp" => time(), // Use current timestamp
            "channelName" => "WEB",
            "serviceName" => "API_PURCHASE",
            "serviceParams" => [
                "merchantUid" => $merchantUid,
                "apiUserId" => $apiUserId,
                "apiKey" => $apiKey,
                "paymentMethod" => "MWALLET_ACCOUNT",
                "payerInfo" => [
                    "accountNo" => "252" . $telephone,
                    "accountHolder" => "Warsame Cali Cabdi",
                ],
                "transactionInfo" => [
                    "referenceId" => "5432",
                    "invoiceId" => "5543",
                    "amount" => $totalAmount,
                    "currency" => "USD",
                    "description" => "DONATION",
                ],
            ],
        ];

        $jsonData = $this->serializer->serialize($data, 'json');

        $client = new HttpClient();

        $response = $this->client->request(
            'POST',
            $waafiEndpoint,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => $jsonData,
            ]
        );
        $user = $this->getUser();

        $statusCode = $response->getStatusCode();
        $responseData = json_decode($response->getContent(), true);

        // dd($responseData);
        if ($statusCode === 200 && $responseData['responseCode'] === '2001' && $responseData['errorCode'] === '0') {

            // create order information
            $order_type = $package->getClass();
            $order = new MetierOrder();
            $order->setCustomer($this->getUser());
            $order->setAmount($package->getCost() + $tax_amount);
            $order->setPaymentStatus("paid");
            $order->setPlan($package);
            if ($package->getCategory() === "subscription") {
                $order->setValidityPeriod($package->getDuration());
            }
            $order->setOrderDate(new DateTime("now"));
            $order->setCustomerType($package->getCategory());
            $order->setCategory("employer");
            $order->setType($order_type);
            $order->setTax($tax_amount);
            $order->setOrderUid(uniqid('ord_' . $order->getId(), true));

            $new_payment = new MetierOrderPayment();
            $new_payment->setReceivedFrom($this->getUser());
            $new_payment->setAmount($package->getCost());
            $new_payment->setPaymentCategory("waafi");
            $new_payment->setPaymentDate(new DateTime("now"));
            $new_payment->setPurchase($order);
            $em->persist($new_payment);
            $em->persist($order);
            $user = $this->getUser();

            if ($package->getType() == "employer" && $package->getCategory() == "service" && $package->getClass() == "description") {

                // dd("waa psychometric");
                $template = $temps->findOneBy(["action" => "employed_description_service_sale"]);

                $d = [
                    "name" => $order->getCustomer()->getName(),
                    "email" => $order->getCustomer()->getEmail(),
                    "type" => $template->getType(),
                    "content" => $template->getContent(),
                    "subject" => $template->getSubject(),
                    "header" => $template->getHeader(),
                    "cat" => "",
                    "extra" => "",
                    "otp" => "",
                    "employer" => $order->getCustomer()->getName(),
                    "interview_date" => "",
                    "platform" => "",
                    "job_title" => "",
                    "link" => "",
                    "job_id" => "",
                    "closing_date" => "",
                    "interview_time" => "",
                ];
                $event = new SendEmailEvent($d);
                $this->eventDispatcher->dispatch($event, SendEmailEvent::class);
            } elseif ($package->getType() == "employer" && $package->getCategory() == "service" && $package->getClass() == "description_offer") {
                $template = $temps->findOneBy(["action" => "employed_description_offer_letter_service_sale"]);

                $d = [
                    "name" => $order->getCustomer()->getName(),
                    "email" => $order->getCustomer()->getEmail(),
                    "type" => $template->getType(),
                    "content" => $template->getContent(),
                    "subject" => $template->getSubject(),
                    "header" => $template->getHeader(),
                    "cat" => "",
                    "extra" => "",
                    "otp" => "",
                    "employer" => $order->getCustomer()->getName(),
                    "interview_date" => "",
                    "platform" => "",
                    "job_title" => "",
                    "link" => "",
                    "job_id" => "",
                    "closing_date" => "",
                    "interview_time" => "",
                ];
                $event = new SendEmailEvent($d);
                $this->eventDispatcher->dispatch($event, SendEmailEvent::class);
            } elseif ($package->getType() == "employer" && $package->getCategory() == "subscription") {
                $template = $temps->findOneBy(["action" => "employer_new_subscription_plan"]);

                $d = [
                    "name" => $order->getCustomer()->getName(),
                    "email" => $order->getCustomer()->getEmail(),
                    "type" => $template->getType(),
                    "content" => $template->getContent(),
                    "subject" => $template->getSubject(),
                    "header" => $template->getHeader(),
                    "cat" => "",
                    "extra" => "",
                    "otp" => "",
                    "employer" => $order->getCustomer()->getName(),
                    "interview_date" => "",
                    "platform" => "",
                    "job_title" => "",
                    "link" => "",
                    "job_id" => "",
                    "closing_date" => "",
                    "interview_time" => "",
                ];
                $event = new SendEmailEvent($d);
                $this->eventDispatcher->dispatch($event, SendEmailEvent::class);
            }
            $em->flush();

            return new JsonResponse([
                'message' => 'Payment Successful!',
                'error_code' => $responseData['responseCode'] ?? null,
                'error_message' => $responseData['responseMsg'] ?? null,
                'redirectUrl' => $this->generateUrl('app_employer_purchase_receipt', [
                    'order' => $order->getId(),
                ]),
            ]);
        } else {

            return new JsonResponse([
                'message' => 'Payment Failed',
                'error_code' => $responseData['responseCode'] ?? null,
                'error_message' => $responseData['responseMsg'] ?? null,
                'test' => "just testing value",
                'redirectUrl' => null,
            ]);
        }

        // halkan ku qor logic-gii back-end-ka
        return $this->render('loading.html.twig', compact('package'));
    }

    #[Route('/receipt/{order}', name: 'app_employer_purchase_receipt', defaults: ["order"], methods: ['GET', 'POST'])]
    public function receipt(Request $request, MetierOrder $order, EntityManagerInterface $em): Response
    {

        $zaadForm = $this->createFormBuilder(null)
            ->add("number", NumberType::class, [
                "attr" => [
                    "class" => "form-control",
                ],
            ])->getForm();

        // halkan ku qor logic-gii back-end-ka
        return $this->render('employer/receipt.html.twig', compact('order'));
    }
    #[Route('/changePassword', name: 'app_employer_changePassword', methods: ['GET', 'POST'])]
    public function changePassword(Security $security, UserPasswordHasherInterface $passwordHasher, Request $request, MetierOrder $order, EntityManagerInterface $em): Response
    {

        $user = $this->getUser();
        $currentUser = $em->getRepository(User::class)->find($this->getUser());
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $oldPassword = $form->get('oldPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();

            if (!$passwordHasher->isPasswordValid($currentUser, $oldPassword)) {
                $this->addFlash('error', 'Old password is incorrect.');

                return $this->redirectToRoute('user_change_password');
            }

            if ($oldPassword === $newPassword) {
                $this->addFlash('error', 'New password cannot be the same as the old password.');

                return $this->redirectToRoute('user_change_password');
            }

            $encodedPassword = $passwordHasher->hashPassword($currentUser, $newPassword);
            $currentUser = $em->getRepository(User::class)->find($this->getUser());
            $currentUser->setPassword($encodedPassword);
            $entityManager = $em;
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Password changed successfully.');
        }

        $userform = $form;

        return $this->render('employer/settings.html.twig', compact('userform'));
    }

    public function getActiveOrder(User $user): ?MetierOrder
    {
        $em = $this->em;
        $qb = $em->createQueryBuilder();

        $qb->select('o')
            ->from(MetierOrder::class, 'o')
            ->where('o.customer = :user')
            ->andWhere('o.valid_from <= :now')
            ->andWhere('o.valid_to >= :now')
            // ->andWhere('o.canceled != 1')
            ->setParameter('user', $user)
            ->setParameter('now', new DateTimeImmutable());
        $qb->setMaxResults(1);
        $query = $qb->getQuery();

        $result = $query->getOneOrNullResult();
        // dd($result);
        return $result;
    }

    #[Route('/internal_staff', name: 'app_internal_staff')]
    public function operationApprovers(
        Request $request,
        EntityManagerInterface $em,
        EmployerStaffRepository $employerStaffRepository,
        UserRepository $users,
        MetierAdsRepository $metierAdsRepository
    ): Response {

        // Get the logged-in employer id
        $current_em = $this->getUser();

        // Fetch All Current Employer Staff
        $staffs = $current_em->getEmployerStaff();

        // Create the form
        $form = $this->createForm(EmployerStaffType::class, null);
        $form->handleRequest($request);

        // Handle form submission

        // // Handle form submission
        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $users->findOneBy(['email' => $email]);
            if ($user) {

                // Check if the combination already exists
                $existingStaff = $employerStaffRepository->findOneBy([
                    'jobseeker' => $user->getId(),
                    'employer' => $current_em->getId(),
                ]);

                if ($existingStaff) {
                    $this->addFlash('danger', 'This user is already assigned as a staff.');
                    return $this->redirectToRoute('app_internal_staff');
                }
                $newStaff = new EmployerStaff();

                $newStaff->setJobseeker($user);
                $newStaff->setEmployer($current_em);
                $newStaff->setDescription($form->get('description')->getData());

                $em->persist($newStaff);
                $em->flush();
                $this->addFlash('success', 'Staff added successfully!');
                return $this->redirectToRoute('app_internal_staff');
            } else {
                $this->addFlash('danger', 'User does not exist in database!');
                return $this->redirectToRoute('app_internal_staff');
            }
        }

        $deleteId = $request->query->get('delete'); // Check if deleting a user
        if ($deleteId) {
            $userToDelete = $employerStaffRepository->find($deleteId);
            if ($userToDelete) {
                $em->createQuery('DELETE FROM App\Entity\EmployerStaff es WHERE es.id = :id')
                    ->setParameter('id', $deleteId)
                    ->execute();

                /*   $em->remove($userToDelete);
                $em->flush(); */

                $this->addFlash('success', 'Staff deleted successfully.');
                return $this->redirectToRoute('app_internal_staff');
            } else {
                $this->addFlash('error', 'staff not found.');
            }
        }

        return $this->render('employer/internal_staff.html.twig', [
            'form' => $form->createView(),
            'staffs' => $staffs,
            'editId' => 1,
            'ads' => $metierAdsRepository->findActiveAds(),
        ]);
    }

    #[Route('/staff_bulk', name: 'app_internal_staff_bulk')]
    public function bulk_upload(
        Request $request,
        EntityManagerInterface $em,
        EmployerStaffRepository $employerStaffRepository,
        UserRepository $users,
        FileUploader $fileUploader
    ): Response {
        $current_em = $this->getUser();
        $staffs = $current_em->getEmployerStaff();

        $form = $this->createFormBuilder(null)
            ->add('file', FileType::class, [
                'label' => 'Choose file (Excel file)',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                            'application/vnd.ms-excel',                                          // .xls
                        ],
                        'mimeTypesMessage' => 'Please upload a valid Excel file (.xlsx or .xls)',
                    ]),
                ],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            $filename = uniqid() . '.' . $file->guessExtension();

            try {
                $file->move($this->getParameter('kernel.project_dir') . '/var/uploads', $filename);
                $filePath = $this->getParameter('kernel.project_dir') . '/var/uploads/' . $filename;

                $spreadsheet = IOFactory::load($filePath);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();

                $notFoundEmails = [];

                foreach ($rows as $index => $row) {
                    // Skip header row (assuming first row is header)
                    if ($index === 0) {
                        continue;
                    }

                    $email = trim($row[0]);
                    $description = $row[1] ?? null;

                    if (!$email) {
                        continue;
                    }

                    $user = $users->findOneBy(['email' => $email]);

                    if (!$user) {
                        $notFoundEmails[] = $email;
                        continue;
                    }

                    $existingStaff = $employerStaffRepository->findOneBy([
                        'jobseeker' => $user,
                        'employer' => $current_em,
                    ]);

                    if ($existingStaff) {
                        continue; // Skip duplicates
                    }

                    $newStaff = new EmployerStaff();
                    $newStaff->setJobseeker($user);
                    $newStaff->setEmployer($current_em);
                    $newStaff->setDescription($description);

                    $em->persist($newStaff);
                }

                $em->flush();

                if (!empty($notFoundEmails)) {
                    $this->addFlash('warning', 'These emails do not belong to any user: ' . implode(', ', $notFoundEmails));
                } else {
                    $this->addFlash('success', 'All staff were successfully uploaded.');
                }

                return $this->redirectToRoute('app_internal_staff_bulk');
            } catch (FileException $e) {
                $this->addFlash('danger', 'File upload failed.');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Error reading Excel file: ' . $e->getMessage());
            }
        }

        return $this->render('employer/internal_staff_bulk.html.twig', [
            'form' => $form->createView(),
            'staffs' => $staffs,
            'editId' => 1,
        ]);
    }
}
