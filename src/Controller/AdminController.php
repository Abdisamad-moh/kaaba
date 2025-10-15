<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use App\Form\CvForm;
use DateTimeImmutable;
use App\Entity\MetierAds;
use App\Form\AdsFormType;
use App\Form\JobFormType;
use App\Entity\MetierBlog;
use App\Form\BlogFormType;
use App\Entity\KaabaCourse;
use App\Entity\KaabaGender;
use App\Entity\KaabaRegion;
use App\Entity\MetierOrder;
use App\Form\OrderFormType;
use App\Entity\EmployerJobs;
use App\Entity\MetierSkills;
use App\Model\JobStatusEnum;
use App\Service\MailService;
use App\Entity\KaabaDistrict;
use App\Entity\MetierCareers;
use App\Event\SendEmailEvent;
use App\Form\JobQuestionType;
use App\Service\FileUploader;
use App\Entity\KaabaInstitute;
use App\Entity\MetierContacts;
use App\Form\JobFormTypeShort;
use App\Entity\EmployerDetails;
use App\Entity\JobseekerDetails;
use App\Entity\KaabaApplication;
use App\Entity\KaabaScholarship;
use App\Entity\MetierAppSetting;
use App\Entity\MetierEmailTemps;
use App\Form\InterviewQFormType;
use App\Entity\KaabaIdentityType;
use App\Entity\InterviewQuestions;
use App\Entity\KaabaQualification;
use App\Form\CustomerAutoComplete;
use App\Repository\UserRepository;
use App\Service\ApplicationLogger;
use App\Entity\EmployerJobQuestion;
use App\Entity\KaabaApplicationLog;
use App\Form\EmailTemplateFormType;
use App\Form\SettingsBasicInfoType;
use Symfony\UX\Chartjs\Model\Chart;
use App\Service\NotificationService;
use App\Service\SubscriptionService;
use App\Form\EmployerDetailsFormType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use App\Entity\KaabaApplicationStatus;
use App\Form\ProductAutoCompleteField;
use Symfony\Component\Form\FormEvents;
use App\Form\EmployerAutoCompleteField;
use App\Repository\JobReportRepository;
use App\Repository\MetierAdsRepository;
use App\Entity\JobSeekerRecommendedJobs;
use App\Form\JobseekerAutoCompleteField;
use App\Repository\MetierBlogRepository;
use App\Security\AppCustomAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\EmployerJobQuestionAnswer;
use App\Repository\KaabaCourseRepository;
use App\Repository\KaabaGenderRepository;
use App\Repository\KaabaRegionRepository;
use App\Repository\MetierOrderRepository;
use App\Repository\EmployerJobsRepository;
use App\Repository\KaabaDistrictRepository;
use App\Repository\MetierJobTypeRepository;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\JobApplicationRepository;
use App\Repository\KaabaInstituteRepository;
use App\Repository\MetierContactsRepository;
use App\Repository\MetierPackagesRepository;
use App\Repository\EmployerDetailsRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\KaabaApplicationRepository;
use App\Repository\KaabaScholarshipRepository;
use App\Repository\MetierEmailTempsRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\JobSeekerJobAlertRepository;
use App\Repository\KaabaIdentityTypeRepository;
use App\Repository\InterviewQuestionsRepository;
use App\Repository\KaabaQualificationRepository;
use App\Repository\MetierSubscriptionRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use App\Repository\KaabaApplicationStatusRepository;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

#[Route('/admin')]
class AdminController extends AbstractController
{

    private $eventDispatcher;

    private $em;


    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        EntityManagerInterface $em,

    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->em = $em;
    }


    #[Route('/', name: 'app_admin')]
public function index(
    KaabaApplicationRepository $applicationRepository,
    ChartBuilderInterface $chartBuilder
): Response {
    // Get statistics
    $totalApplications = $applicationRepository->countTotalApplications();
    $lastYearApplications = $applicationRepository->countLastYearApplications();
    $pendingApplications = $applicationRepository->countApplicationsByStatus('Pending');
     $appliedApplications = $applicationRepository->countApplicationsByStatus('applied');
    $acceptedApplications = $applicationRepository->countApplicationsByStatus('accepted');
    $rejectedApplications = $applicationRepository->countApplicationsByStatus('rejected');
    $shortlistedApplications = $applicationRepository->countApplicationsByStatus('shortlisted');
    // Get data for charts
    $applicationsByRegion = $applicationRepository->countApplicationsByRegion();
    $applicationsByDistrict = $applicationRepository->countApplicationsByDistrict();
    $applicationsByGender = $applicationRepository->countApplicationsByGender();
    $applicationsByInstitute = $applicationRepository->countApplicationsByInstitute();
    $applicationsByScholarship = $applicationRepository->countApplicationsByScholarship();
    $applicationsByMonth = $applicationRepository->countApplicationsByMonth();

    // Get recent applications
    $recentApplications = $applicationRepository->findRecentApplications(5);

    // Define colors for charts
    $backgroundColors = [
        'rgba(75, 192, 192, 0.8)',
        'rgba(255, 99, 132, 0.8)',
        'rgba(54, 162, 235, 0.8)',
        'rgba(255, 206, 86, 0.8)',
        'rgba(153, 102, 255, 0.8)',
        'rgba(255, 159, 64, 0.8)',
        'rgba(199, 199, 199, 0.8)',
        'rgba(83, 102, 255, 0.8)',
        'rgba(40, 159, 64, 0.8)',
    ];

    $borderColors = [
        'rgba(75, 192, 192, 1)',
        'rgba(255, 99, 132, 1)',
        'rgba(54, 162, 235, 1)',
        'rgba(255, 206, 86, 1)',
        'rgba(153, 102, 255, 1)',
        'rgba(255, 159, 64, 1)',
        'rgba(199, 199, 199, 1)',
        'rgba(83, 102, 255, 1)',
        'rgba(40, 159, 64, 1)',
    ];

    // Chart 1: Applications by Region (Bar Chart)
    $regionLabels = array_column($applicationsByRegion, 'region_name');
    $regionData = array_column($applicationsByRegion, 'application_count');

    $regionChart = $chartBuilder->createChart(Chart::TYPE_BAR);
    $regionChart->setData([
        'labels' => $regionLabels,
        'datasets' => [
            [
                'label' => 'Applications by Region',
                'backgroundColor' => array_slice($backgroundColors, 0, count($regionLabels)),
                'borderColor' => array_slice($borderColors, 0, count($regionLabels)),
                'borderWidth' => 1,
                'data' => $regionData,
            ],
        ],
    ]);
    $regionChart->setOptions([
        'responsive' => true,
        'maintainAspectRatio' => false,
        'plugins' => [
            'legend' => [
                'display' => false,
            ],
        ],
        'scales' => [
            'y' => [
                'beginAtZero' => true,
            ],
        ],
    ]);

    // Chart 2: Applications by Gender (Pie Chart)
    $genderLabels = array_column($applicationsByGender, 'gender_name');
    $genderData = array_column($applicationsByGender, 'application_count');

    $genderChart = $chartBuilder->createChart(Chart::TYPE_PIE);
    $genderChart->setData([
        'labels' => $genderLabels,
        'datasets' => [
            [
                'label' => 'Applications by Gender',
                'backgroundColor' => array_slice($backgroundColors, 0, count($genderLabels)),
                'borderColor' => array_slice($borderColors, 0, count($genderLabels)),
                'borderWidth' => 1,
                'data' => $genderData,
            ],
        ],
    ]);
    $genderChart->setOptions([
        'responsive' => true,
        'maintainAspectRatio' => false,
        'plugins' => [
            'legend' => [
                'position' => 'right',
            ],
        ],
    ]);

    // Chart 3: Applications by Institute (Doughnut Chart)
    $instituteLabels = array_column($applicationsByInstitute, 'institute_name');
    $instituteData = array_column($applicationsByInstitute, 'application_count');

    $instituteChart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
    $instituteChart->setData([
        'labels' => array_slice($instituteLabels, 0, 8), // Limit to top 8
        'datasets' => [
            [
                'label' => 'Applications by Institute',
                'backgroundColor' => array_slice($backgroundColors, 0, min(8, count($instituteLabels))),
                'borderColor' => array_slice($borderColors, 0, min(8, count($instituteLabels))),
                'borderWidth' => 1,
                'data' => array_slice($instituteData, 0, 8),
            ],
        ],
    ]);
    $instituteChart->setOptions([
        'responsive' => true,
        'maintainAspectRatio' => false,
        'plugins' => [
            'legend' => [
                'position' => 'right',
            ],
        ],
    ]);

    // Chart 4: Applications by Month (Line Chart)
    $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $monthData = array_fill(0, 12, 0);
    
    foreach ($applicationsByMonth as $monthDataPoint) {
        $monthIndex = $monthDataPoint['month'] - 1;
        $monthData[$monthIndex] = $monthDataPoint['application_count'];
    }

    $monthlyChart = $chartBuilder->createChart(Chart::TYPE_LINE);
    $monthlyChart->setData([
        'labels' => $monthNames,
        'datasets' => [
            [
                'label' => 'Applications by Month',
                'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                'borderColor' => 'rgba(54, 162, 235, 1)',
                'borderWidth' => 2,
                'data' => $monthData,
                'tension' => 0.4,
                'fill' => true,
            ],
        ],
    ]);
    $monthlyChart->setOptions([
        'responsive' => true,
        'maintainAspectRatio' => false,
        'plugins' => [
            'legend' => [
                'display' => false,
            ],
        ],
        'scales' => [
            'y' => [
                'beginAtZero' => true,
            ],
        ],
    ]);

    // Chart 5: Applications by Scholarship (Horizontal Bar Chart)
    $scholarshipLabels = array_column($applicationsByScholarship, 'scholarship_title');
    $scholarshipData = array_column($applicationsByScholarship, 'application_count');

    $scholarshipChart = $chartBuilder->createChart(Chart::TYPE_BAR);
    $scholarshipChart->setData([
        'labels' => array_slice($scholarshipLabels, 0, 6), // Limit to top 6
        'datasets' => [
            [
                'label' => 'Applications by Scholarship',
                'backgroundColor' => array_slice($backgroundColors, 0, min(6, count($scholarshipLabels))),
                'borderColor' => array_slice($borderColors, 0, min(6, count($scholarshipLabels))),
                'borderWidth' => 1,
                'data' => array_slice($scholarshipData, 0, 6),
            ],
        ],
    ]);
    $scholarshipChart->setOptions([
        'indexAxis' => 'y',
        'responsive' => true,
        'maintainAspectRatio' => false,
        'plugins' => [
            'legend' => [
                'display' => false,
            ],
        ],
    ]);

    return $this->render('admin/index.html.twig', [
        'totalApplications' => $totalApplications,
        'lastYearApplications' => $lastYearApplications,
         'appliedApplications' => $appliedApplications, // Changed from pendingApplications
        'acceptedApplications' => $acceptedApplications, // Changed from approvedApplications
        'rejectedApplications' => $rejectedApplications,
        'shortlistedApplications' => $shortlistedApplications,
        'recentApplications' => $recentApplications,
        'regionChart' => $regionChart,
        'genderChart' => $genderChart,
        'instituteChart' => $instituteChart,
        'monthlyChart' => $monthlyChart,
        'scholarshipChart' => $scholarshipChart,
    ]);
}
    #[Route('/packages', name: 'app_admin_packages')]
    public function packages(
        Request $request,
        EntityManagerInterface $em,
        MetierSubscriptionRepository $packages
    ): Response {
        $packages = $packages->findAll();
        return $this->render('admin/packages.html.twig', compact('packages'));
    }
    #[Route('/contacts', name: 'app_admin_contacts')]
    public function contacts(
        Request $request,
        EntityManagerInterface $em,
        MetierContactsRepository $packages
    ): Response {
        $contacts = $packages->findAll();
        return $this->render('admin/contacts_list.html.twig', compact('contacts'));
    }
    #[Route('/reports', name: 'app_admin_reports')]
    public function reports(
        Request $request,
        EntityManagerInterface $em,
        JobReportRepository $packages
    ): Response {
        $reports = $packages->findAll();
        return $this->render('admin/reports.html.twig', compact('reports'));
    }
    #[Route('/add_package', name: 'app_admin_add_package')]
    public function add_package(
        Request $request,
        EntityManagerInterface $em,
        MetierSubscriptionRepository $packages
    ): Response {

        $packages = $packages->findAll();
        return $this->render('admin/packages_new.html.twig', compact('packages'));
    }
    #[Route('/emailTemps', name: 'app_admin_email_temps')]
    public function emailTemps(
        Request $request,
        EntityManagerInterface $em,
        MetierEmailTempsRepository $temps
    ): Response {


        $applications = $temps->findAll();
        return $this->render('admin/emailTemps.html.twig', compact('applications'));
    }

    #[Route('/accounts', name: 'app_admin_accounts')]
    public function accounts(

        Request $request,
        EntityManagerInterface $em,
        UserRepository $accounts,
        PaginatorInterface $paginator,
    ): Response {
        $searchForm = $this->createFormBuilder(null)
            ->add('email', EmailType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'col_class' => 'col-3',
                    'placeholder' => 'Filter By Email'
                ],
            ])
            ->add('status', ChoiceType::class, [
                'required' => false,
                'mapped' => false,
                'choices' => [
                    'Active' => true,
                    'Inactive' => false,
                ],
                'attr' => [
                    'class' => 'form-control required',
                    'col_class' => 'col-3',
                ],
                'placeholder' => 'Filter by status',
            ])
            ->add('type', ChoiceType::class, [
                'required' => false,
                'mapped' => false,
                'choices' => [
                    'Employer' => 'employer',
                    'Job Seeker' => 'jobseeker',
                ],
                'attr' => [
                    'class' => 'form-control required',
                    'col_class' => 'col-3',
                ],
                'placeholder' => 'Select Account Type',
            ])
            ->add('verification', ChoiceType::class, [
                'required' => false,
                'mapped' => false,
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
                'attr' => [
                    'class' => 'form-control required',
                    'col_class' => 'col-3',
                ],
                'placeholder' => 'Select Verification',
            ])
            ->getForm();

        $searchForm->handleRequest($request);
        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $status = $searchForm->get("status")->getData();
            $email = $searchForm->get("email")->getData();
            $type = $searchForm->get("type")->getData();
            $is_verified = $searchForm->get("verification")->getData();
            $datatable = $accounts->filterAccounts(
                $type,
                $status,
                $email,
                $is_verified
            );
            // $total = $cashierCollectionRepository->search($student, $faculty, $department, $term, $semester, $year, 'total');
            $count = count($datatable);
            $accounts = $paginator->paginate(
                $datatable,
                $request->query->get('page', 1),
                100
            );
        } else {
            $datatable = $accounts->filterAccounts(
                null,
                null,
                null,
                null
            );



            // $total = $cashierCollectionRepository->search($student, $faculty, $department, $term, $semester, $year, 'total');
            $count = count($datatable);
            $accounts = $paginator->paginate(
                $datatable,
                $request->query->get('page', 1),
                100
            );
        }

        return $this->render('admin/accounts.html.twig', compact(
            'accounts',
            'searchForm',
            'count'
        ));
    }


    #[Route('/jobs', name: 'app_admin_jobs')]
    public function jobs(
        EmployerJobsRepository $jobs,
        PaginatorInterface $paginator,
        Request $request,
    ): Response {

        $searchForm = $this->createFormBuilder(null)
            ->add('employer', EmployerAutoCompleteField::class, [
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'col_class' => 'col-3',
                    'placeholder' => 'Filter By Employer'
                ],
            ])
            ->add('status', ChoiceType::class, [
                'required' => false,
                'mapped' => false,
                'choices' => [
                    'Active' => 'posted',
                    'Closed' => 'closed',
                    'Drafted' => 'draft',
                    'Deleted' => 'deleted',
                    'Paused' => 'paused',
                ],
                'attr' => [
                    'class' => 'form-control required',
                    'col_class' => 'col-3',
                ],
                'placeholder' => 'Filter by status',
            ])
            // ->add('type', ChoiceType::class, [
            //     'required' => false,
            //     'mapped' => false,
            //     'choices'  => [
            //         'Employer' => 'employer',
            //         'Job Seeker' => 'jobseeker',
            //     ],
            //     'attr' => [
            //         'class' => 'form-control required',
            //         'col_class' => 'col-3',
            //     ],
            //     'placeholder' => 'Select Account Type',
            // ])
            // ->add('verification', ChoiceType::class, [
            //     'required' => false,
            //     'mapped' => false,
            //     'choices'  => [
            //         'Yes' =>  true,
            //         'No' => false,
            //     ],
            //     'attr' => [
            //         'class' => 'form-control required',
            //         'col_class' => 'col-3',
            //     ],
            //     'placeholder' => 'Select Verification',
            // ])
            // ->add('joined_date', DateType::class, [
            //     'required' => false,
            //     'mapped' => false,
            //     'widget' => 'single_text',
            //     'data' => new \DateTime(),
            //     'attr' => [
            //         'class' => 'form-control required',
            //         'col_class' => 'col-3',
            //     ],
            //     'placeholder' => 'Select project status'
            // ])
            ->getForm();

        $searchForm->handleRequest($request);
        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $status = $searchForm->get("status")->getData();
            $employer = $searchForm->get("employer")->getData();
            // $email = $searchForm->get("email")->getData();
            // $type = $searchForm->get("type")->getData();
            // $is_verified = $searchForm->get("verification")->getData();
            $datatable = $jobs->filterAccounts(
                $employer,
                $status,
            );
            // $total = $cashierCollectionRepository->search($student, $faculty, $department, $term, $semester, $year, 'total');
            $count = count($datatable);
            $jobs = $paginator->paginate(
                $datatable,
                $request->query->get('page', 1),
                100
            );
        } else {
            $datatable = $jobs->filterAccounts(
                null,
                null,
                null,
                null
            );



            // $total = $cashierCollectionRepository->search($student, $faculty, $department, $term, $semester, $year, 'total');
            $count = count($datatable);
            $jobs = $paginator->paginate(
                $datatable,
                $request->query->get('page', 1),
                100
            );
        }
        return $this->render('admin/jobs.html.twig', compact('jobs', 'searchForm'));
    }

    #[Route('/orders', name: 'app_admin_metier_orders')]
    public function metierOrders(
        MetierOrderRepository $orders,
        PaginatorInterface $paginator,
        Request $request,
    ): Response {
        // Create the search form
        $searchForm = $this->createFormBuilder(null)
            ->add('customer', CustomerAutoComplete::class, [
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'col_class' => 'col-3',
                    'placeholder' => 'Filter By Customer',
                ],

            ])
            ->add('status', ChoiceType::class, [
                'required' => false,
                'mapped' => false,
                'choices' => [
                    'Paid' => 'paid',
                    'Pending' => 'pending',
                    'Canceled' => 'canceled',
                ],
                'attr' => [
                    'class' => 'form-control',
                    'col_class' => 'col-3',
                    'placeholder' => 'Filter By Status',
                ],
                'placeholder' => 'Filter By Status',
            ])
            ->add('type', ChoiceType::class, [
                'required' => false,
                'mapped' => false,
                'choices' => [
                    'Psychometric' => 'psychometric',
                    'Proffessional Resume' => 'resume',
                    'Resume' => 'resume2',
                    'Services' => 'service',
                ],
                'attr' => [
                    'class' => 'form-control',
                    'col_class' => 'col-3',

                ],
                'placeholder' => 'Filter By Type',
            ])
            ->add('product', ProductAutoCompleteField::class, [
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'col_class' => 'col-3',
                    'placeholder' => 'Filter By Product',
                ],
            ])
            ->getForm();


        $searchForm->handleRequest($request);

        // Filter orders based on form submission
        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $customer = $searchForm->get('customer')->getData();
            $status = $searchForm->get('status')->getData();
            $type = $searchForm->get('type')->getData();
            $product = $searchForm->get('product')->getData();

            $datatable = $orders->filterOrders($customer, $status, $type, $product);
        } else {
            $datatable = $orders->filterOrders(null, null, null, null);
        }

        // Paginate the results
        $orders = $paginator->paginate(
            $datatable,
            $request->query->get('page', 1),
            100 // Items per page
        );

        return $this->render('admin/orders.html.twig', [
            'orders' => $orders,
            'searchForm' => $searchForm->createView(),
        ]);
    }

    #[Route('/companySettings/{company}', name: 'app_admin_company_settings')]
    public function companySettings(
        FileUploader $fileUploader,
        User $company,
        MetierPackagesRepository $packages,
        UserPasswordHasherInterface $passwordHasher,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $users,
        EmployerDetailsRepository $employerDetails,
    ): Response {
        $currentUser = $company;




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
                ])
            ],
        ])->getForm();
        $logoForm->handleRequest($request);
        if ($logoForm->isSubmitted() && $logoForm->isValid()) {


            $imageFile = $logoForm->get('logo')->getData();


            if ($imageFile) {

                try {

                    $employerDetails = $employerDetails->findOneBy(['employer' => $currentUser]);
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
                        $new_employer_details->setEmployer($currentUser);

                        $fileName = $fileUploader->uploadImage($imageFile, $this->getParameter('employer_profile_images_directory'));

                        $new_employer_details->setLogo($fileName);
                        $em->persist($new_employer_details);

                        $em->flush();

                        dd('now stop it please');
                    }
                    $em->flush();
                } catch (\Exception $e) {

                    dump($imageFile);
                    dd('in catch');
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
            return $this->redirectToRoute('app_admin_company_settings');
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
                    'class' => 'form-control'
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

                'attr' => ['class' => 'form-control']
            ])->getForm();
        $statusForm->handleRequest($request);
        if ($statusForm->isSubmitted() && $statusForm->isValid()) {
            $currentUser->setStatus($statusForm->get('status')->getData());
            $em->persist($currentUser);
            $em->flush();
            sweetalert()->success("Updated account successfully");
        }

        $activePlan = $this->getActiveOrder($company);

        $services = $packages->findBy(['status' => true, 'type' => "employer", "category" => "service"]);

        return $this->render('employer/company_settings.html.twig', [
            'employer' => $company,
            'logoForm' => $logoForm,
            'userDetails' => $detailsForm,
            'statusForm' => $statusForm,
            'services' => $services,
        ]);
    }

    #[Route('/cvCenter', name: 'app_admin_cvCenter')]
    public function cvCenter(
        JobApplicationRepository $applications,
        Request $request,
        PaginatorInterface $paginator,
        UserRepository $jobseekers
    ): Response {

        $form = $this->createForm(CvForm::class);
        $form->handleRequest($request);



        // dd($em->getRepository(BudgetEntry::class)->findBy($criteria));
        // $form = $this->createForm(PurchaseFormType::class,$order);
        //     $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // dd($form->getData());
            $experience = $form->get("experience")->getData();
            $education = $form->get("education")->getData();
            $city = $form->get("city")->getData();
            $country = $form->get("country")->getData();
            $state = $form->get("states")->getData();
            $jobtitle = $form->get("jobtitle")->getData();

            $fields = [
                $form->get("experience")->getData(),
                $form->get("education")->getData(),
                $form->get("city")->getData(),
                $form->get("country")->getData(),
                $form->get("states")->getData(),
                $form->get("jobtitle")->getData()
            ];

            // Check if all fields are null
            if (array_filter($fields, fn($value) => $value !== null)) {
                // At least one field is not null
                // Do something here if one or more fields are set
                $datatable = $jobseekers->searchJobseekers($jobtitle, $country, $state, $city, $experience, $education);

                // dd($datatable);
                $cvs = $paginator->paginate(
                    $datatable,
                    $request->query->get('page', 1),
                    50
                );
            } else {
                // All fields are null
                // Handle the case when all fields are empty
                $cvs = [];
            }

            // dump($experience);
            // dd($education);


        } else {
            $cvs = [];
        }

        return $this->render('employer/cvs.html.twig', compact('cvs', 'form'));
    }

    #[Route('/candidates', name: 'app_admin_candidates')]
    public function candidates(
        JobApplicationRepository $applications,
        Request $request,
        PaginatorInterface $paginator,
    ): Response {

        $searchForm = $this->createFormBuilder(null)
            ->add('employer', EmployerAutoCompleteField::class, [
                'required' => true,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'col_class' => 'col-3',
                    'placeholder' => 'Filter By Employer'
                ],
            ])
            ->add('jobseeker', JobseekerAutoCompleteField::class, [
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'col_class' => 'col-3',
                    'placeholder' => 'Filter By Job Seeker'
                ],
            ])
            ->add('jobseeker', JobseekerAutoCompleteField::class, [
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'col_class' => 'col-3',
                    'placeholder' => 'Filter By Job Seeker'
                ],
            ])
            // ->add('status', ChoiceType::class, [
            //     'required' => false,
            //     'mapped' => false,
            //     'choices'  => [
            //         'Active' => true,
            //         'Inactive' => false,
            //     ],
            //     'attr' => [
            //         'class' => 'form-control required',
            //         'col_class' => 'col-3',
            //     ],
            //     'placeholder' => 'Filter by status',
            // ])
            ->add('type', ChoiceType::class, [
                'required' => false,
                'mapped' => false,
                'choices' => [
                    'Hired' => 'hired',
                    'Selected' => 'selected',
                    'Applied' => 'applied',
                    'Interview Scheduled' => 'interview scheduled',
                    'Rejected' => 'rejected',
                ],
                'attr' => [
                    'class' => 'form-control required',
                    'col_class' => 'col-3',
                ],
                'placeholder' => 'Select Type',
            ])

            ->getForm();

        $searchForm->handleRequest($request);
        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            // $status = $searchForm->get("status")->getData();
            $employer = $searchForm->get("employer")->getData();
            $type = $searchForm->get("type")->getData();
            $jobseeker = $searchForm->get("jobseeker")->getData();
            $datatable = $applications->filterApplications(
                $employer,
                $jobseeker,
                $type,
            );
            // $total = $cashierCollectionRepository->search($student, $faculty, $department, $term, $semester, $year, 'total');
            $count = count($datatable);
            $accounts = $paginator->paginate(
                $datatable,
                $request->query->get('page', 1),
                100
            );
        } else {
            $datatable = $applications->filterApplications(
                null,
                null,
                null,
            );



            // $total = $cashierCollectionRepository->search($student, $faculty, $department, $term, $semester, $year, 'total');
            $count = count($datatable);
            $accounts = $paginator->paginate(
                $datatable,
                $request->query->get('page', 1),
                100
            );
        }

        return $this->render('admin/candidates.html.twig', [
            'applications' => $accounts,
            'searchForm' => $searchForm,
        ]);
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

    #[Route('/postJob/{job}', name: 'app_admin_create_job', defaults: ['job' => null], methods: ['GET', 'POST'])]
    public function postJob(Request $request, EmployerJobsRepository $jobs, EmployerJobs $job = null, EmployerDetailsRepository $details, EntityManagerInterface $em): Response
    {

        // dump($jobs->findMatchingJobSeekers($job));
        // dd("yes");
        // check if the employer details are full
        // $emp_details = $details->findOneBy(['employer' => $this->getUser()]);

        // if ($emp_details) {
        // } else {

        //     sweetalert()->warning("In order to be able to post and access other features, please click Settings and complete all required fields.");
        //     return $this->redirectToRoute('app_admin_company_settings');
        // }

        // // Check if all required fields are filled
        // if (
        //     // empty($emp_details->getHeading()) ||
        //     // empty($emp_details->getDescription()) ||
        //     empty($emp_details->getLogo()) ||
        //     empty($emp_details->getIndustry()) ||
        //     empty($emp_details->getCountry()) ||
        //     empty($emp_details->getCity()) ||
        //     empty($emp_details->getAddress()) ||
        //     empty($emp_details->getPhone())
        // ) {
        //     sweetalert()->error("In order to be able to post and access other features, please click Settings and complete all required fields.");
        //     return $this->redirectToRoute('app_admin_company_settings');
        // }

        if (!$job) {
            $job = new EmployerJobs();
            $job->setStatus("draft");
            $job->setApplicationClosingDate((new DateTime())->modify('+30 days'));
        }

        // dd("hh");
        $job->setOperation('job');
        $form = $this->createForm(JobFormType::class, $job);
        $form->add('employer', EmployerAutoCompleteField::class, [
            'required' => true,
            'placeholder' => 'Choose Employer',
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $career = $form->get('title')->getData();
            // dd($career);
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
                    if ($skill)
                        $skills_objects[] = $skill;
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
                    if ($skill)
                        $skills_objects[] = $skill;
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
            // $job->setEmployer($this->getUser());

            $em->persist($job);
            $em->flush();
            // Return a successful response (e.g., 200 OK) for Turbo to update the pages
            // return $this->json(['success' => true]);
            if ($job->getStatus() === "draft") {
                return $this->redirectToRoute('app_admin_review_job', [
                    'job' => $job->getId()
                ]);
            }

            return $this->redirectToRoute('app_admin_jobs');
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


        return $this->render('admin/new_job_post.html.twig', compact('form', 'question', 'questionForm', 'job', 'required_skills', 'preferred_skills', 'career_skills'));
    }

    #[Route('/jobReview/{job}', name: 'app_admin_review_job', methods: ['GET', 'POST'])]
    public function jobReview(
        Request $request,
        EmployerJobs $job,
        EntityManagerInterface $em,
        MetierEmailTempsRepository $temps,
    ): Response {
        // if ($job->getStatus() !== "draft") {
        //     return false;
        // }

        foreach ($job->getEmployerJobQuestions() as $question) {
            $question->addEmployerJobQuestionAnswer(new EmployerJobQuestionAnswer());
        }
        // dd("hh");
        // $job->setOperation('job');
        $form = $this->createForm(JobFormTypeShort::class, $job);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // dd($form->getData());

            // $job->setEmployer($this->getUser());
            $em->persist($job);
            $em->flush();


            // Return a successful response (e.g., 200 OK) for Turbo to update the pages
            // return $this->json(['success' => true]);
            $this->addFlash('success', 'The job has been posted successfully.');
            return $this->redirectToRoute('app_admin_jobs');
        }
        $question = new EmployerJobQuestion();
        $questionForm = $this->createForm(JobQuestionType::class, $question);
        $questionForm->handleRequest($request);
        return $this->render('employer/new_job_post_review.html.twig', compact('form', 'question', 'questionForm', 'job'));
    }

    #[Route('/employer', name: 'app_admin_register_employer')]
    public function employer_register(
        Request $request,
        MailService $mailService,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        UserAuthenticatorInterface $userAuthenticator,
        AppCustomAuthenticator $authenticator,
        MetierEmailTempsRepository $temps,
        NotificationService $notificationService,
        SubscriptionService $subscriptionService
    ): Response {
        $user = new User();
        $user->settype("employer");
        $user->setVerified(true);
        $user->setStatus(true);
        $user->setRoles(["ROLE_EMPLOYER"]);
        $form = $this->createFormBuilder($user)
            ->add('name', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Company Name',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please provide a company name',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Company Email ID',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter an email address',
                    ]),
                    new Email([
                        'message' => 'Please enter a valid email address',
                    ]),
                ],
            ])

            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
                'attr' => [
                    'autocomplete' => 'new-password',
                    'class' => 'form-control '
                ],
            ])
            ->add('confirmPassword', PasswordType::class, [
                // 'type' => PasswordType::class,
                'invalid_message' => 'Passwords must match.',
                // Optional: Set a different label for the confirm field
                'label' => 'Confirm Password',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control '
                ],
            ])->add('agreeTerms', CheckboxType::class, [
                    'mapped' => false,
                    'constraints' => [
                        new IsTrue([
                            'message' => 'You should agree to our terms.',
                        ]),
                    ],
                    'attr' => [
                        'class' => 'form-check-input'
                    ],
                ])->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            $remoteIp = $request->getClientIp();
            // TODO : Handle if recapcha is null, which comes when there is no internet mostly


            $user->setUsername($form->get('email')->getData());
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $otp = $mailService->generateOtp();
            $notification = $notificationService->createNotification("success", "Welcome to Metier Quest, Your Account has been created", $user, "", []);
            $user->setOtp($otp);
            $user->setOtpExpiration((new \DateTime())->modify('+10 minutes'));
            $user->setVerified(false);

            $new_subscription = $subscriptionService->createSixMonthsSubscription($user);

            if (!$new_subscription) {
                $this->addFlash('danger', 'There has been an Error');
                return $this->redirectToRoute('app_admin_register_employer');
            }
            $entityManager->persist($user);
            $entityManager->persist($new_subscription);
            $entityManager->persist($notification);
            $entityManager->flush();

            // dd($user->getOtp($otp));
            $temps = $entityManager->getRepository(MetierEmailTemps::class);
            $employer_registration_message = $temps->findOneBy(["action" => "reg_msg_employer"]) ?? new MetierEmailTemps();
            //send welcome message
            $registration_message_data = [
                "name" => "",
                "email" => $user->getEmail(),
                "type" => $employer_registration_message->getType(),
                "content" => $employer_registration_message->getContent(),
                "subject" => $employer_registration_message->getSubject(),
                "header" => $employer_registration_message->getHeader(),
                "cat" => "",
                "extra" => "",
                "otp" => "",
                "employer" => $user->getName(),
                "interview_date" => "",
                "platform" => "",
                "job_title" => "",
                "link" => "",
                "job_id" => "",
                "closing_date" => "",
                "interview_time" => "",
            ];
            $event = new SendEmailEvent($registration_message_data);
            $this->eventDispatcher->dispatch($event, SendEmailEvent::class);
            // send the otp
            $employer_otp_template = $temps->findOneBy(["action" => "employer_reg_otp"]) ?? new MetierEmailTemps();
            $otp_email_data = [
                "name" => "",
                "email" => $user->getEmail(),
                "type" => $employer_otp_template->getType(),
                "content" => $employer_otp_template->getContent(),
                "subject" => $employer_otp_template->getSubject(),
                "header" => $employer_otp_template->getHeader(),
                "cat" => "",
                "extra" => "",
                "otp" => $otp,
                "employer" => $user->getName(),
                "interview_date" => "",
                "platform" => "",
                "job_title" => "",
                "link" => "",
                "job_id" => "",
                "closing_date" => "",
                "interview_time" => "",
            ];


            // 
            $event = new SendEmailEvent($otp_email_data);
            $this->eventDispatcher->dispatch($event, SendEmailEvent::class);

            // dd($user->getOtp());
            $this->addFlash('success', 'New employer has been registred successfully, please verify the email');
            //redirect to a different page

            // Authenticate the user

            // Prepare credentials for authentication (assuming email as username)

            // Create an authentication token
            // Create an authentication token
            // $userAuthenticator->authenticateUser(
            //     $user,
            //     $authenticator,
            //     $request
            // );+


            //  u samee 6 months subscription


            // $session = $request->getSession();
            // $session->set('otp_verification_email', $user->getEmail());

            return $this->redirectToRoute('app_admin_register_employer');
        }

        return $this->render('admin/add_account.html.twig', [
            'form' => $form,
            'error' => $request->get('error'),
            'type' => 'Employer',
        ]);
    }

    #[Route('/changeUser/{email}', methods: ["POST", "GET"], name: 'app_admin_change_user_status', options: ['expose' => true])]
    public function changeUser(
        string $email,
        RequestStack $requestStack,
        Request $request,
        EntityManagerInterface $em,
        NotificationService $notificationService
    ): Response {

        $status = $request->query->get('status');
        $status = strtolower($request->query->get('status')); // Convert to lowercase

        if (
            !in_array($status, [
                'Activate',
                'activate',
                'Disable',
                'disable',
            ])
        ) {
            throw new \InvalidArgumentException('There has been an error');
        }

        $account = $em->getRepository(User::class)->findOneBy(['username' => $email]);

        if (!$account) {
            throw $this->createNotFoundException('Account not found');
        }
        // $new_quot = new PurchaseQuotation();
        // $new_quot->setPo($order);
        // $orderItems = $order->getPurchaseOrderItems();
        $referer = $requestStack->getCurrentRequest()->headers->get('referer');


        // Get the action URL dynamically
        $actionUrl = $this->generateUrl(
            'app_admin_change_user_status',
            [
                'email' => $account->getEmail(),
            ]
        );
        $actionUrl .= '?status=' . strtolower($status);
        $form = $this->createFormBuilder(null, ['action' => $actionUrl]);


        $form = $form->getForm();

        $form->handleRequest($request);


        // dd($em->getRepository(BudgetEntry::class)->findBy($criteria));
        // $form = $this->createForm(PurchaseFormType::class,$order);
        //     $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // $account->setStatus($status);
            $account->setStatus($status === 'activate');
            $em->persist($account);
            $em->flush();

            $messages = $status === 'activate' ? "User Activated Successfully" : "User Disabled Successfully";

            sweetalert()->success($messages);
            // return $this->redirectToRoute('app_procurement_edit_po', [
            return new RedirectResponse($referer);
            //     'order' => $approval->getPo()->getId(),
            // ]);

        }

        // Set confirmation message based on action
        $msg = [
            "msg" => $status === 'activate'
                ? "Are you sure you want to activate this user?"
                : "Are you sure you want to disable this user?",
            "class" => $status === 'activate' ? "alert alert-success" : "alert alert-warning"
        ];

        return $this->render('admin/form.html.twig', [
            'form' => $form,
            'message' => $msg['msg'],
            'status' => $status,
        ]);
    }
    #[Route('/verifyUser/{email}', methods: ["POST", "GET"], name: 'app_admin_verify_user', options: ['expose' => true])]
    public function verifyUser(
        string $email,
        RequestStack $requestStack,
        Request $request,
        EntityManagerInterface $em,
        NotificationService $notificationService
    ): Response {

        $status = $request->query->get('status');
        $status = strtolower($request->query->get('status')); // Convert to lowercase

        if (
            !in_array($status, [
                'verify',
                'unverify',
            ])
        ) {
            throw new \InvalidArgumentException('There has been an error');
        }

        $account = $em->getRepository(User::class)->findOneBy(['username' => $email]);

        if (!$account) {
            throw $this->createNotFoundException('Account not found');
        }
        // $new_quot = new PurchaseQuotation();
        // $new_quot->setPo($order);
        // $orderItems = $order->getPurchaseOrderItems();
        $referer = $requestStack->getCurrentRequest()->headers->get('referer');


        // Get the action URL dynamically
        $actionUrl = $this->generateUrl(
            'app_admin_verify_user',
            [
                'email' => $account->getEmail(),
            ]
        );
        $actionUrl .= '?status=' . strtolower($status);
        $form = $this->createFormBuilder(null, ['action' => $actionUrl]);


        $form = $form->getForm();

        $form->handleRequest($request);


        // dd($em->getRepository(BudgetEntry::class)->findBy($criteria));
        // $form = $this->createForm(PurchaseFormType::class,$order);
        //     $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // $account->setStatus($status);
            $account->setVerified($status === 'verify');
            $em->persist($account);
            $em->flush();

            $messages = $status === 'activate' ? "Account Verfied Successfully" : "Account Unverivied Successfully";

            sweetalert()->success($messages);
            // return $this->redirectToRoute('app_procurement_edit_po', [
            return new RedirectResponse($referer);
            //     'order' => $approval->getPo()->getId(),
            // ]);

        }

        // Set confirmation message based on action
        $msg = [
            "msg" => $status === 'verify'
                ? "Are you sure you want to verify this account?"
                : "Are you sure you want to unverify this account?",
            "class" => $status === 'verify' ? "alert alert-success" : "alert alert-warning"
        ];

        return $this->render('admin/form.html.twig', [
            'form' => $form,
            'message' => $msg['msg'],
            'status' => $status,
        ]);
    }


    #[Route('/changeJobStatus/{job}/{encodedStatus}/{re}', name: 'app_admin_job_change_status', defaults: ["re" => null], methods: ['GET', 'POST'])]
    public function changeJobStatus(
        Request $request,
        EmployerJobs $job,
        string $re = null,
        string $encodedStatus,
        EntityManagerInterface $em,
        MetierEmailTempsRepository $temps,
        JobSeekerJobAlertRepository $alerts,
        SluggerInterface $slugger,
        JobApplicationRepository $applications,
        RequestStack $requestStack,
    ): Response {

        // Decode the status
        $statusEnum = JobStatusEnum::fromEncoded($encodedStatus);



        if ($statusEnum === null) {
            dd("The status is unknown");
        }

        $referer = $requestStack->getCurrentRequest()->headers->get('referer');

        $status = $statusEnum->value;

        // dd($status);

        if ($statusEnum === JobStatusEnum::POSTED && $re !== "repost") {



            // now get those who got same category
            $job_category = $job->getJobCategory();

            $recommends = $alerts->findJobSeekersByMatchingJobCategory($job_category);



            // dump($recommends);

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



            if (!$template)
                return new RedirectResponse($referer);

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
        } elseif ($statusEnum === JobStatusEnum::POSTED && $re === "repost") {

            // TODO: check repost functionality
            $job->setStatus('posted');
            $job->setCreatedAt(new DateTime("now"));
            $job->setRepost(true);
            $em->persist($job);
            $em->flush();
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
                return new RedirectResponse($referer);
            }


            $template = $temps->findOneBy(["action" => "jobseeker_application_closed"]);
            //get all the applicants of this job
            $applicants = $applications->findBy(['job' => $job->getId()]);





            if (in_array($statusEnum, JobStatusEnum::cases())) {
                $job->setStatus($status);
            } else {
                dd("The status is unknown");
            }

            foreach ($applicants as $applicant) {
                if (!$template)
                    return new RedirectResponse($referer);

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




        return new RedirectResponse($referer);
    }

    #[Route('/jobseeker', name: 'app_admin_jobseeker_register')]
    public function jobseeker(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        MailService $mailService,
        NotificationService $notificationService
    ): Response {
        $user = new User();
        $user->settype("jobseeker");
        $user->setVerified(false);
        $user->setOtpEnabled(false);
        $user->setOtpExpiration((new \DateTime())->modify('+10 minutes'));
        $user->setStatus(true);
        $user->setRoles(["ROLE_JOBSEEKER"]);
        $form = $this->createFormBuilder($user)
            ->add('firstName', TextType::class, [
                'required' => true,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'First Name',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a first name',
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Your first name should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 255,
                    ]),
                ],

            ])
            ->add('middleName', TextType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Middle Name',
            ])
            ->add('lastName', TextType::class, [
                'required' => true,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Last Name',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a last name',
                    ]),
                    new Length([
                        'min' => 2,
                        'minMessage' => 'Your last name should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 255,
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Email ID',
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'You should agree to our terms.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-check-input'
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ])
            ->add('confirmPassword', PasswordType::class, [
                // 'type' => PasswordType::class,
                'invalid_message' => 'Passwords must match.',
                // Optional: Set a different label for the confirm field
                'label' => 'Confirm Password',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control'
                ],

            ])
            ->add('dob', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date of Birth',
                'data' => new DateTime(),
                'mapped' => false,
                'constraints' => [
                    new NotBlank(), // Ensure the field is not empty,
                    new Callback(function ($birthdate, ExecutionContextInterface $context) {
                        if ($birthdate) {
                            $eighteenYearsAgo = (new DateTime())->modify('-18 years');
                            if ($birthdate > $eighteenYearsAgo) {
                                $context->buildViolation('You must be 18 years old or older to register.')
                                    ->atPath('dob') // This targets the 'dob' field
                                    ->addViolation();
                            }
                        }
                    }),

                ],
                'attr' => [
                    'class' => 'form-control'
                ],
            ])
            ->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $plainPassword = $form->get('plainPassword')->getData();
                $confirmPassword = $form->get('confirmPassword')->getData();

                // Perform custom password validation logic
                if ($plainPassword !== $confirmPassword) {
                    $form->get('confirmPassword')->addError(new FormError('Passwords must match.'));
                }
            })
            ->getForm();
        // $form->add('recaptcha', EWZRecaptchaV3Type::class, array(
        //     'action_name' => 'contact',
        //     'constraints' => array(
        //         new IsTrueV3()
        //     )
        // ));
        $errors = [];
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $remoteIp = $request->getClientIp();


            $birthdate = $form->get('dob')->getData();

            $new_jobseeker_details = new JobseekerDetails();
            $new_jobseeker_details->setDob($birthdate);
            $new_jobseeker_details->setJobseeker($user);
            $new_jobseeker_details->setFirstName($form->get('firstName')->getData());
            $new_jobseeker_details->setMiddleName($form->get('middleName')->getData());
            $new_jobseeker_details->setLastName($form->get('lastName')->getData());

            $user->setUsername($form->get('email')->getData());
            $user->setName(ucfirst($new_jobseeker_details->getFirstName()) . ' ' . ucfirst($new_jobseeker_details->getMiddleName()) . ' ' . ucfirst($new_jobseeker_details->getLastName()));
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $registration_otp = $mailService->generateOtp();


            $temps = $entityManager->getRepository(MetierEmailTemps::class);
            $jobseeker_registration_message = $temps->findOneBy(["action" => "reg_msg_jobseeker"]) ?? new MetierEmailTemps();
            //send welcome message
            $jobseeker_registration_message_data = [
                "name" => $user->getName(),
                "email" => $user->getEmail(),
                "type" => $jobseeker_registration_message->getType(),
                "content" => $jobseeker_registration_message->getContent(),
                "subject" => $jobseeker_registration_message->getSubject(),
                "header" => $jobseeker_registration_message->getHeader(),
                "cat" => "",
                "extra" => "",
                "otp" => "",
                "employer" => "",
                "interview_date" => "",
                "platform" => "",
                "job_title" => "",
                "link" => "",
                "job_id" => "",
                "closing_date" => "",
                "interview_time" => "",
            ];
            $event = new SendEmailEvent($jobseeker_registration_message_data);
            $this->eventDispatcher->dispatch($event, SendEmailEvent::class);
            // send the otp
            $jobseeker_otp_template = $temps->findOneBy(["action" => "jobseeker_reg_otp"]) ?? new MetierEmailTemps();
            $o = random_int(100000, 999999);
            $user->setOtp($o);
            $user->setVerified(false);
            $notification = $notificationService->createNotification(type: "success", message: "Welcome to Metier Quest, Your Account has been created", user: $user, routeName: "", routeParams: []);

            $entityManager->persist($new_jobseeker_details);
            $entityManager->persist($user);
            $entityManager->persist($notification);
            $entityManager->flush();
            $otp_email_data = [
                "name" => $user->getName(),
                "email" => $user->getEmail(),
                "type" => $jobseeker_otp_template->getType(),
                "content" => $jobseeker_otp_template->getContent(),
                "subject" => $jobseeker_otp_template->getSubject(),
                "header" => $jobseeker_otp_template->getHeader(),
                "cat" => "",
                "extra" => "",
                "otp" => $o,
                "employer" => "",
                "interview_date" => "",
                "platform" => "",
                "job_title" => "",
                "link" => "",
                "job_id" => "",
                "closing_date" => "",
                "interview_time" => "",
            ];

            // dd($o);
            $event = new SendEmailEvent($otp_email_data);
            $this->eventDispatcher->dispatch($event, SendEmailEvent::class);

            $this->addFlash('success', 'New Jobseeker has been registred successfully');
            //auto user login and then redirect to the verify_email
            // $userAuthenticator->authenticateUser(
            //     $user,
            //     $authenticator,
            //     $request
            // );
            return $this->redirectToRoute('app_admin_jobseeker_register');
        }

        // $recaptchaResponse = $request->request->get('g-recaptcha-response');
        // dd($recaptchaResponse);

        return $this->render('admin/add_account.html.twig', [
            'form' => $form,
            'error' => $request->get('error'),
            'type' => 'Job Seeker',
        ]);
    }
    #[Route('/emailTemp/{temp}', name: 'app_admin_email_temp', defaults: ['temp' => null], methods: ['POST', 'GET'])]
    public function emailTemp(
        Request $request,
        EntityManagerInterface $em,
        MetierEmailTemps $temp = null,
        MetierSubscriptionRepository $packages
    ): Response {
        if (!$temp) {
            $temp = new MetierEmailTemps();
        }

        $form = $this->createForm(EmailTemplateFormType::class, $temp);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($temp);
            $em->flush();
        }

        $packages = $packages->findAll();
        return $this->render('admin/emailTemp.html.twig', compact('form'));
    }


    #[Route('/ads', name: 'app_admin_ads')]
    public function ads(
        Request $request,
        EntityManagerInterface $em,
        MetierAdsRepository $packages
    ): Response {
        $ads = $packages->findAll();
        return $this->render('admin/ads.html.twig', compact('ads'));
    }

    #[Route('/addAdd/{ad}', name: 'app_admin_ad_temp', defaults: ['ad' => null], methods: ['POST', 'GET'])]
    public function addAdd(
        Request $request,
        EntityManagerInterface $em,
        MetierAds $ad = null,
        MetierAdsRepository $packages,
        FileUploader $fileUploader,
    ): Response {
        $isNew = !$ad; // Determine if this is a new ad

        if ($isNew) {
            $ad = new MetierAds();
        }

        $form = $this->createForm(AdsFormType::class, $ad);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $originalFilename = $fileUploader->upload($imageFile, $this->getParameter('employer_profile_images_directory'));
                $ad->setImage($originalFilename);
            }

            $em->persist($ad);
            $em->flush();

            // Different flash messages based on new vs existing ad
            if ($isNew) {
                $this->addFlash('success', 'Successfully created the ad');
            } else {
                $this->addFlash('success', 'Successfully updated the ad');
            }

            return $this->redirectToRoute('app_admin_ads'); // Add your redirect route
        }

        $packages = $packages->findAll();
        return $this->render('admin/ad.html.twig', [
            'form' => $form->createView(),
            'ad' => $ad,
        ]);
    }
    #[Route('/addOrderSubscription/{type}/{order}', name: 'app_admin_add_order', defaults: ['order' => null], methods: ['POST', 'GET'])]
    public function addOrderSubscription(
        Request $request,
        EntityManagerInterface $em,
        string $type,
        MetierOrder $order = null,
        MetierOrderRepository $orders,
    ): Response {
        $isNew = !$order; // Determine if this is a new order

        if ($isNew) {
            $order = new MetierOrder();
            $order->setCategory($type);
        } else {
            $type = $order->getCategory();
        }

        if (!$type) {
            $this->addFlash('danger', 'Order type must be specified');

            return $this->redirectToRoute('app_admin_metier_orders');
        }

        $form = $this->createForm(OrderFormType::class, $order, [
            'type' => $type,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order->setOrderDate(new DateTime("now"));
            $order->setCustomerType("subscription");
            $order->setCategory($type);
            $order->setType("subscription");
            $order->setTax(0);
            $order->setOrderUid(uniqid('ord_' . $order->getId(), true));

            $em->persist($order);
            $em->flush();

            // Different flash messages based on new vs existing order
            if ($isNew) {
                $this->addFlash('success', 'Successfully created the order');
            } else {
                $this->addFlash('success', 'Successfully updated the order');
            }

            return $this->redirectToRoute('app_admin_metier_orders');
        }

        return $this->render('admin/add_order.html.twig', [
            'form' => $form->createView(),
            'order' => $order,
        ]);
    }


    #[Route('/cancelPlan/{plan}', methods: ["POST", "GET"], name: 'app_admin_cancel_plan', options: ['expose' => true])]
    public function cancelPlanAdmin(
        MetierOrder $plan,
        RequestStack $requestStack,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $referer = $requestStack->getCurrentRequest()->headers->get('referer');

        $plan->setCanceled(true);
        $em->persist($plan);
        $em->flush();

        sweetalert()->success("Successfully updated");
        return new RedirectResponse($referer);
    }


    #[Route('/deleteAdd/{ad}', name: 'app_admin_ad_delete', defaults: ['ad' => null], methods: ['POST', 'GET'])]
    public function deleteAdd(
        EntityManagerInterface $em,
        MetierAds $ad = null,
    ): Response {

        if (!$ad) {
            $ad = new MetierAds();
        }


        // delete entity
        $em->remove($ad);
        $em->flush();

        $this->addFlash('success', 'Successfully deleted the ad');
        //redirect to a different page
        return $this->redirectToRoute('app_admin_ads');

        return $this->render('admin/ads.html.twig');
    }
    #[Route('/deleteAccount/{account}', name: 'app_admin_account_delete', defaults: ['account' => null], methods: ['POST', 'GET'])]
    public function deleteAccount(
        EntityManagerInterface $em,
        User $account,
    ): Response {

        if (!$account) {
            dd("Sorry no account");
        }


        // delete entity
        $account->isDeleted(true);
        $em->persist($account);
        $em->flush();

        $this->addFlash('success', 'Successfully Deleted an Account');
        //redirect to a different page
        return $this->redirectToRoute('app_admin_accounts');

        return $this->render('admin/ads.html.twig');
    }



    #[Route('/addBlog/{blog}', name: 'app_admin_add_blog', defaults: ['blog' => null], methods: ['POST', 'GET'])]
    public function addBlog(
        Request $request,
        EntityManagerInterface $em,
        MetierBlog $blog = null,
        FileUploader $fileUploader,
        SluggerInterface $slugger
    ): Response {
        if (!$blog) {
            $blog = new MetierBlog();
        }

        $form = $this->createForm(BlogFormType::class, $blog);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($imageFile) {
                $originalFilename = $fileUploader->upload($imageFile, $this->getParameter('blogs_directory'));
                $blog->setImage($originalFilename);
            }
            $em->persist($blog);
            $em->flush();
        }

        return $this->render('admin/addBlog.html.twig', compact('form', 'blog'));
    }
    #[Route('/deleteBlog/{blog}', name: 'app_admin_delete_blog', defaults: ['blog' => null], methods: ['POST', 'GET'])]
    public function deleteBlog(
        Request $request,
        EntityManagerInterface $em,
        MetierBlog $blog = null,
        FileUploader $fileUploader,
        SluggerInterface $slugger
    ): Response {
        if (!$blog) {
            $blog = new MetierBlog();
        }


        $em->remove($blog);

        $em->flush();

        $this->addFlash('success', 'Successfully deleted a blog post');
        //redirect to a different page
        return $this->redirectToRoute('app_admin_blogs');

    }

    // Questions and Answers
    #[Route('/iqas', name: 'app_admin_qas')]
    public function iqas(
        Request $request,
        EntityManagerInterface $em,
        PaginatorInterface $paginator,
        InterviewQuestionsRepository $qs
    ): Response {
        $datatable = $qs->findAll();

        // dd($datatable);
        $cvs = $paginator->paginate(
            $datatable,
            $request->query->get('page', 1),
            100
        );
        $blogs = $cvs;
        return $this->render('admin/qs.html.twig', compact('blogs'));
    }

    #[Route('/addQa/{q}', name: 'app_admin_add_qa', defaults: ['q' => null], methods: ['POST', 'GET'])]
    public function addQa(
        Request $request,
        EntityManagerInterface $em,
        InterviewQuestions $q = null,
        FileUploader $fileUploader,
        SluggerInterface $slugger
    ): Response {
        if (!$q) {
            $q = new InterviewQuestions();
        }
        $form = $this->createForm(InterviewQFormType::class, $q);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($q);
            $em->flush();
            $this->addFlash('success', 'Successfully added interview quesiton');
        }

        return $this->render('admin/addQA.html.twig', compact('form', 'q'));
    }


    #[Route('/editQa/{q}', name: 'app_admin_edit_qa', defaults: ['q' => null], methods: ['POST', 'GET'])]
    public function editQa(
        Request $request,
        EntityManagerInterface $em,
        InterviewQuestions $q = null,
        FileUploader $fileUploader,
        SluggerInterface $slugger
    ): Response {
        if (!$q) {
            $q = new InterviewQuestions();
        }
        $form = $this->createForm(InterviewQFormType::class, $q);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($q);
            $em->flush();
            $this->addFlash('success', 'Successfully Updated the Interview Question');
        }


        return $this->render('admin/editQA.html.twig', compact('form', 'q'));
    }
    #[Route('/deleteQa/{q}', name: 'app_admin_delete_qa', defaults: ['q' => null], methods: ['POST', 'GET'])]
    public function deleteQa(
        Request $request,
        EntityManagerInterface $em,
        InterviewQuestions $q = null,
        FileUploader $fileUploader,
        SluggerInterface $slugger
    ): Response {
        if (!$q) {
            $q = new InterviewQuestions();
        }
        $em->remove($q);

        $em->flush();

        $this->addFlash('success', 'Successfully deleted the interview question');
        //redirect to a different page
        return $this->redirectToRoute('app_admin_qas');
    }


    // Blogs
    #[Route('/blogs', name: 'app_admin_blogs')]
    public function blogs(
        Request $request,
        EntityManagerInterface $em,
        MetierBlogRepository $blogs,
    ): Response {
        $blogs = $blogs->findAll();
        return $this->render('admin/blogs.html.twig', compact('blogs'));
    }
    // app settings
    #[Route('/settings_basic_info', name: 'app_admin_settings', defaults: ['app' => 1])]
    public function settings_basic_info(
        Request $request,
        MetierAppSetting $app,
        EntityManagerInterface $em,
        MetierBlogRepository $blogs,
        FileUploader $fileUploader,

    ): Response {
        $form = $this->createForm(SettingsBasicInfoType::class, $app);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $emp1 = $form->getData();
            $avatarFile = $form->get('avatar')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($avatarFile) {
                $originalFilename = $fileUploader->upload($avatarFile, $this->getParameter('logo_directory'));

                $emp1->setLogo($originalFilename);
            }
            $em->persist($app);
            $em->flush();
            //add a flash message
            $this->addFlash('success', 'New employee has been registred successfully');
            //redirect to a different page
            return $this->redirectToRoute('app_admin_settings');
        }
        $blogs = $blogs->findAll();
        return $this->render('admin/settings.html.twig', compact('blogs', 'app', 'form'));
    }
    // system users
    #[Route('/settings_system_users', name: 'app_admin_settings_users')]
    public function settings_system_users(
        Request $request,
        EntityManagerInterface $em,
        UserRepository $users,
        MetierBlogRepository $blogs,
        FileUploader $fileUploader,

    ): Response {
        // $form = $this->createForm(SettingsBasicInfoType::class, $app);
        // $form->handleRequest($request);

        // if ($form->isSubmitted() && $form->isValid()) {
        //     $emp1 = $form->getData();
        //     $avatarFile = $form->get('avatar')->getData();

        //     // this condition is needed because the 'brochure' field is not required
        //     // so the PDF file must be processed only when a file is uploaded
        //     if ($avatarFile) {
        //         $originalFilename = $fileUploader->upload($avatarFile, $this->getParameter('logo_directory'));

        //         $emp1->setLogo($originalFilename);
        //     }
        //     $em->persist($app);
        //     $em->flush();
        //     //add a flash message
        //     $this->addFlash('success', 'New employee has been registred successfully');
        //     //redirect to a different page
        //     return $this->redirectToRoute('app_admin_settings');
        // }
        $users = $users->searchAdmins();
        return $this->render('admin/settings_users.html.twig', compact('users'));
    }

    #[Route('/kaaba-identity-types', name: 'app_admin_kaaba_identity_types')]
    public function kaabaIdentityTypes(
        Request $request,
        KaabaIdentityTypeRepository $kaabaIdentityTypeRepository,
        EntityManagerInterface $em,
    ): Response {
        // Fetch all identity types for the table
        $identityTypes = $kaabaIdentityTypeRepository->findAll();

        // Check if editing or creating a new identity type
        $editId = $request->query->get('edit');
        $showForm = $editId || $request->query->get('create');

        $identityType = new KaabaIdentityType();
        if ($editId) {
            $identityType = $kaabaIdentityTypeRepository->find($editId);
            if (!$identityType) {
                throw $this->createNotFoundException('Identity type not found.');
            }
        }

        // Create the form using FormBuilder
        $form = $this->createFormBuilder($identityType)
            ->add('name', TextType::class, [
                'label' => 'Identity Type Name',
                'attr' => ['class' => 'form-control']
            ])
            ->getForm();

        $form->handleRequest($request);

        // Handle form submission
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($identityType);
            $em->flush();

            $this->addFlash('success', $editId ? 'Identity type updated successfully.' : 'Identity type created successfully.');
            return $this->redirectToRoute('app_admin_kaaba_identity_types');
        }

        // Handle delete request
        $deleteId = $request->query->get('delete');
        if ($deleteId) {
            $identityTypeToDelete = $kaabaIdentityTypeRepository->find($deleteId);
            if ($identityTypeToDelete) {
                $em->remove($identityTypeToDelete);
                $em->flush();

                $this->addFlash('success', 'Identity type deleted successfully.');
                return $this->redirectToRoute('app_admin_kaaba_identity_types');
            } else {
                $this->addFlash('error', 'Identity type not found.');
            }
        }

        return $this->render('admin/kaaba_identity_types.html.twig', [
            'identityTypes' => $identityTypes,
            'form' => $form->createView(),
            'editId' => $editId,
            'showForm' => $showForm,
        ]);
    }


    #[Route('/kaaba-qualifications', name: 'app_admin_kaaba_qualifications')]
    public function kaabaQualifications(
        Request $request,
        KaabaQualificationRepository $kaabaQualificationRepository,
        EntityManagerInterface $em,
    ): Response {
        // Fetch all qualifications for the table
        $qualifications = $kaabaQualificationRepository->findAll();

        // Check if editing or creating a new qualification
        $editUuid = $request->query->get('edit');
        $showForm = $editUuid || $request->query->get('create');

        $qualification = new KaabaQualification();
        if ($editUuid) {
            $qualification = $kaabaQualificationRepository->findOneBy(['uuid' => $editUuid]);
            if (!$qualification) {
                throw $this->createNotFoundException('Qualification not found.');
            }
        }

        // Create the form using FormBuilder
        $form = $this->createFormBuilder($qualification)
            ->add('name', TextType::class, [
                'label' => 'Qualification Name',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter qualification name']
            ])
            ->getForm();

        $form->handleRequest($request);

        // Handle form submission
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($qualification);
            $em->flush();

            $this->addFlash('success', $editUuid ? 'Qualification updated successfully.' : 'Qualification created successfully.');
            return $this->redirectToRoute('app_admin_kaaba_qualifications');
        }

        // Handle delete request
        $deleteUuid = $request->query->get('delete');
        if ($deleteUuid) {
            $qualificationToDelete = $kaabaQualificationRepository->findOneBy(['uuid' => $deleteUuid]);
            if ($qualificationToDelete) {
                // Check if there are any applications using this qualification
                $applicationsCount = $qualificationToDelete->getKaabaApplications()->count();

                if ($applicationsCount > 0) {
                    $this->addFlash('error', "Cannot delete this qualification. It is being used by $applicationsCount application(s).");
                    return $this->redirectToRoute('app_admin_kaaba_qualifications');
                }

                $em->remove($qualificationToDelete);
                $em->flush();

                $this->addFlash('success', 'Qualification deleted successfully.');
                return $this->redirectToRoute('app_admin_kaaba_qualifications');
            } else {
                $this->addFlash('error', 'Qualification not found.');
            }
        }

        return $this->render('admin/kaaba_qualifications.html.twig', [
            'qualifications' => $qualifications,
            'form' => $form->createView(),
            'editUuid' => $editUuid,
            'showForm' => $showForm,
        ]);
    }

 #[Route('/kaaba-institutes', name: 'app_admin_kaaba_institutes')]
public function kaabaInstitutes(
    Request $request,
    KaabaInstituteRepository $kaabaInstituteRepository,
    KaabaScholarshipRepository $kaabaScholarshipRepository,
    EntityManagerInterface $em,
): Response {
    // Fetch all institutes for the table
    $institutes = $kaabaInstituteRepository->findAll();

    // Fetch active scholarships for the dropdown
    $activeScholarships = $kaabaScholarshipRepository->findBy(['status' => true]);

    // Check if editing or creating a new institute
    $editUuid = $request->query->get('edit');
    $showForm = $editUuid || $request->query->get('create');

    $institute = new KaabaInstitute();
    if ($editUuid) {
        $institute = $kaabaInstituteRepository->findOneBy(['uuid' => $editUuid]);
        if (!$institute) {
            throw $this->createNotFoundException('Institute not found.');
        }
    }

    // Create the form using FormBuilder
    $form = $this->createFormBuilder($institute)
        ->add('scholarship', EntityType::class, [
            'class' => KaabaScholarship::class,
            'choice_label' => 'title',
            'label' => 'Scholarship',
            'placeholder' => 'Select a scholarship',
            'choices' => $activeScholarships,
            'attr' => [
                'class' => 'form-select'
            ],
            'constraints' => [
                new NotBlank(['message' => 'Please select a scholarship.'])
            ]
        ])
        ->add('name', TextType::class, [
            'label' => 'Institute Name',
            'attr' => [
                'class' => 'form-control',
                'placeholder' => 'Enter institute name'
            ],
            'constraints' => [
                new NotBlank(['message' => 'Institute name is required.']),
                new Length([
                    'max' => 255,
                    'maxMessage' => 'Institute name cannot be longer than 255 characters.'
                ])
            ]
        ])
        ->getForm();

    $form->handleRequest($request);

    // Handle form submission
    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($institute);
        $em->flush();

        $this->addFlash('success', $editUuid ? 'Institute updated successfully.' : 'Institute created successfully.');
        return $this->redirectToRoute('app_admin_kaaba_institutes');
    }

    // Handle delete request
  // Handle delete request
$deleteUuid = $request->query->get('delete');
if ($deleteUuid) {
    $instituteToDelete = $kaabaInstituteRepository->findOneBy(['uuid' => $deleteUuid]);
    if ($instituteToDelete) {
        // Check if there are any applications using this institute
        $applicationsCount = $instituteToDelete->getKaabaApplications()->count();
        
        // NEW: Check if there are any courses using this institute
        $coursesCount = $instituteToDelete->getKaabaCourses()->count();

        if ($applicationsCount > 0) {
            $this->addFlash('error', "Cannot delete this institute. It is being used by $applicationsCount application(s).");
            return $this->redirectToRoute('app_admin_kaaba_institutes');
        }
        
        // NEW: Check for courses
        if ($coursesCount > 0) {
            $this->addFlash('error', "Cannot delete this institute. It has $coursesCount course(s) associated with it. Please delete or reassign the courses first.");
            return $this->redirectToRoute('app_admin_kaaba_institutes');
        }

        $em->remove($instituteToDelete);
        $em->flush();

        $this->addFlash('success', 'Institute deleted successfully.');
        return $this->redirectToRoute('app_admin_kaaba_institutes');
    } else {
        $this->addFlash('error', 'Institute not found.');
    }
}


    return $this->render('admin/kaaba_institutes.html.twig', [
        'institutes' => $institutes,
        'activeScholarships' => $activeScholarships,
        'form' => $form->createView(),
        'editUuid' => $editUuid,
        'showForm' => $showForm,
    ]);
}

    #[Route('/kaaba-regions', name: 'app_admin_kaaba_regions')]
    public function kaabaRegions(
        Request $request,
        KaabaRegionRepository $kaabaRegionRepository,
        EntityManagerInterface $em,
    ): Response {
        // Fetch all regions for the table
        $regions = $kaabaRegionRepository->findAll();

        // Check if editing or creating a new region
        $editUuid = $request->query->get('edit');
        $showForm = $editUuid || $request->query->get('create');

        $region = new KaabaRegion();
        if ($editUuid) {
            $region = $kaabaRegionRepository->findOneBy(['uuid' => $editUuid]);
            if (!$region) {
                throw $this->createNotFoundException('Region not found.');
            }
        }

        // Create the form using FormBuilder
        $form = $this->createFormBuilder($region)
            ->add('name', TextType::class, [
                'label' => 'Region Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter region name'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Region name is required.']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Region name cannot be longer than 255 characters.'
                    ])
                ]
            ])
            ->getForm();

        $form->handleRequest($request);

        // Handle form submission
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($region);
            $em->flush();

            $this->addFlash('success', $editUuid ? 'Region updated successfully.' : 'Region created successfully.');
            return $this->redirectToRoute('app_admin_kaaba_regions');
        }

        // Handle delete request
        $deleteUuid = $request->query->get('delete');
        if ($deleteUuid) {
            $regionToDelete = $kaabaRegionRepository->findOneBy(['uuid' => $deleteUuid]);
            if ($regionToDelete) {
                // Check if there are any applications using this region as primary or secondary region
                $primaryApplicationsCount = $regionToDelete->getKaabaApplications()->count();
                $secondaryApplicationsCount = $regionToDelete->getKaabaApplicationsSchools()->count();
                $totalApplicationsCount = $primaryApplicationsCount + $secondaryApplicationsCount;

                if ($totalApplicationsCount > 0) {
                    $this->addFlash('error', "Cannot delete this region. It is being used by $totalApplicationsCount application(s) as primary or secondary region.");
                    return $this->redirectToRoute('app_admin_kaaba_regions');
                }

                $em->remove($regionToDelete);
                $em->flush();

                $this->addFlash('success', 'Region deleted successfully.');
                return $this->redirectToRoute('app_admin_kaaba_regions');
            } else {
                $this->addFlash('error', 'Region not found.');
            }
        }

        return $this->render('admin/kaaba_regions.html.twig', [
            'regions' => $regions,
            'form' => $form->createView(),
            'editUuid' => $editUuid,
            'showForm' => $showForm,
        ]);
    }

    #[Route('/kaaba-scholarships', name: 'app_admin_kaaba_scholarships')]
    public function kaabaScholarships(
        Request $request,
        KaabaScholarshipRepository $kaabaScholarshipRepository,
        EntityManagerInterface $em,
    ): Response {
        // Fetch all scholarships for the table
        $scholarships = $kaabaScholarshipRepository->findAll();

        // Check if editing or creating a new scholarship
        $editUuid = $request->query->get('edit');
        $showForm = $editUuid || $request->query->get('create');

        $scholarship = new KaabaScholarship();
        if ($editUuid) {
            $scholarship = $kaabaScholarshipRepository->findOneBy(['uuid' => $editUuid]);
            if (!$scholarship) {
                throw $this->createNotFoundException('Scholarship not found.');
            }
        }

        // Create the form using FormBuilder
        $form = $this->createFormBuilder($scholarship)
            ->add('title', TextType::class, [
                'label' => 'Scholarship Title',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter scholarship title'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Scholarship title is required.']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Scholarship title cannot be longer than 255 characters.'
                    ])
                ]
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Scholarship Content',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter scholarship description and details',
                    'rows' => 6
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Scholarship content is required.']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Scholarship content cannot be longer than 255 characters.'
                    ])
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Active' => true,
                    'Inactive' => false,
                ],
                'attr' => ['class' => 'form-select'],
                'expanded' => false,
                'multiple' => false,
                'constraints' => [
                    new NotNull(['message' => 'Status is required.'])
                ]
            ])
            ->add('closing_date', DateType::class, [
                'label' => 'Closing Date',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Closing date is required.']),
                    new GreaterThanOrEqual([
                        'value' => 'today',
                        'message' => 'Closing date must be today or in the future.'
                    ])
                ]
            ])
            ->getForm();

        $form->handleRequest($request);

        // Handle form submission
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($scholarship);
            $em->flush();

            $this->addFlash('success', $editUuid ? 'Scholarship updated successfully.' : 'Scholarship created successfully.');
            return $this->redirectToRoute('app_admin_kaaba_scholarships');
        }

        // Handle status toggle request
        $toggleUuid = $request->query->get('toggle');
        if ($toggleUuid) {
            $scholarshipToToggle = $kaabaScholarshipRepository->findOneBy(['uuid' => $toggleUuid]);
            if ($scholarshipToToggle) {
                $newStatus = !$scholarshipToToggle->isStatus();
                $scholarshipToToggle->setStatus($newStatus);
                $em->flush();

                $this->addFlash('success', "Scholarship " . ($newStatus ? 'activated' : 'deactivated') . " successfully.");
                return $this->redirectToRoute('app_admin_kaaba_scholarships');
            } else {
                $this->addFlash('error', 'Scholarship not found.');
            }
        }

        // Handle delete request
        $deleteUuid = $request->query->get('delete');
        if ($deleteUuid) {
            $scholarshipToDelete = $kaabaScholarshipRepository->findOneBy(['uuid' => $deleteUuid]);
            if ($scholarshipToDelete) {
                $em->remove($scholarshipToDelete);
                $em->flush();

                $this->addFlash('success', 'Scholarship deleted successfully.');
                return $this->redirectToRoute('app_admin_kaaba_scholarships');
            } else {
                $this->addFlash('error', 'Scholarship not found.');
            }
        }

        return $this->render('admin/kaaba_scholarships.html.twig', [
            'scholarships' => $scholarships,
            'form' => $form->createView(),
            'editUuid' => $editUuid,
            'showForm' => $showForm,
        ]);
    }

    #[Route('/kaaba-districts', name: 'app_admin_kaaba_districts')]
    public function kaabaDistricts(
        Request $request,
        KaabaDistrictRepository $kaabaDistrictRepository,
        KaabaRegionRepository $kaabaRegionRepository,
        EntityManagerInterface $em,
    ): Response {
        // Fetch all districts for the table
        $districts = $kaabaDistrictRepository->findAll();

        // Check if editing or creating a new district
        $editUuid = $request->query->get('edit');
        $showForm = $editUuid || $request->query->get('create');

        $district = new KaabaDistrict();
        if ($editUuid) {
            $district = $kaabaDistrictRepository->findOneBy(['uuid' => $editUuid]);
            if (!$district) {
                throw $this->createNotFoundException('District not found.');
            }
        }

        // Fetch all regions for the dropdown
        $regions = $kaabaRegionRepository->findAll();

        // Create the form using FormBuilder
        $form = $this->createFormBuilder($district)
            ->add('name', TextType::class, [
                'label' => 'District Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter district name'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'District name is required.']),
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'District name cannot be longer than 255 characters.'
                    ])
                ]
            ])
            ->add('region', EntityType::class, [
                'label' => 'Region',
                'class' => KaabaRegion::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a region',
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new NotNull(['message' => 'Region is required.'])
                ]
            ])
            ->getForm();

        $form->handleRequest($request);

        // Handle form submission
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($district);
            $em->flush();

            $this->addFlash('success', $editUuid ? 'District updated successfully.' : 'District created successfully.');
            return $this->redirectToRoute('app_admin_kaaba_districts');
        }

        // Handle delete request
        $deleteUuid = $request->query->get('delete');
        if ($deleteUuid) {
            $districtToDelete = $kaabaDistrictRepository->findOneBy(['uuid' => $deleteUuid]);
            if ($districtToDelete) {
                // Check if there are any applications using this district
                $applicationsCount = $districtToDelete->getKaabaApplications()->count();

                if ($applicationsCount > 0) {
                    $this->addFlash('error', "Cannot delete this district. It is being used by $applicationsCount application(s).");
                    return $this->redirectToRoute('app_admin_kaaba_districts');
                }

                $em->remove($districtToDelete);
                $em->flush();

                $this->addFlash('success', 'District deleted successfully.');
                return $this->redirectToRoute('app_admin_kaaba_districts');
            } else {
                $this->addFlash('error', 'District not found.');
            }
        }

        return $this->render('admin/kaaba_districts.html.twig', [
            'districts' => $districts,
            'form' => $form->createView(),
            'editUuid' => $editUuid,
            'showForm' => $showForm,
        ]);
    }

#[Route('/kaaba-courses', name: 'app_admin_kaaba_courses')]
public function kaabaCourses(
    Request $request,
    KaabaCourseRepository $kaabaCourseRepository,
    KaabaInstituteRepository $kaabaInstituteRepository,
    EntityManagerInterface $em,
): Response {
    // Fetch all courses for the table
    $courses = $kaabaCourseRepository->findAll();

    // Fetch all institutes for the dropdown
    $institutes = $kaabaInstituteRepository->findAll();

    // Check if editing or creating a new course
    $editUuid = $request->query->get('edit');
    $showForm = $editUuid || $request->query->get('create');

    $course = new KaabaCourse();
    if ($editUuid) {
        $course = $kaabaCourseRepository->findOneBy(['uuid' => $editUuid]);
        if (!$course) {
            throw $this->createNotFoundException('Course not found.');
        }
    }

    // Create the form using FormBuilder
    $form = $this->createFormBuilder($course)
        ->add('institute', EntityType::class, [
            'class' => KaabaInstitute::class,
            'choice_label' => 'name',
            'label' => 'Institute',
            'placeholder' => 'Select an institute',
            'choices' => $institutes,
            'attr' => [
                'class' => 'form-select'
            ],
            'constraints' => [
                new NotBlank(['message' => 'Please select an institute.'])
            ]
        ])
        ->add('name', TextType::class, [
            'label' => 'Course Name',
            'attr' => [
                'class' => 'form-control', 
                'placeholder' => 'Enter course name'
            ],
            'constraints' => [
                new NotBlank(['message' => 'Course name is required.']),
                new Length([
                    'max' => 255,
                    'maxMessage' => 'Course name cannot be longer than 255 characters.'
                ])
            ]
        ])
        ->getForm();

    $form->handleRequest($request);

    // Handle form submission
    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($course);
        $em->flush();

        $this->addFlash('success', $editUuid ? 'Course updated successfully.' : 'Course created successfully.');
        return $this->redirectToRoute('app_admin_kaaba_courses');
    }

    // Handle delete request - IMPROVED VERSION
    $deleteUuid = $request->query->get('delete');
    if ($deleteUuid) {
        $courseToDelete = $kaabaCourseRepository->findOneBy(['uuid' => $deleteUuid]);
        if ($courseToDelete) {
            // More robust check for applications
            $applicationsCount = $courseToDelete->getKaabaApplications()->count();
            
            if ($applicationsCount > 0) {
                $this->addFlash('error', "Cannot delete course \"{$courseToDelete->getName()}\". It is being used by $applicationsCount application(s). Please reassign or delete those applications first.");
                return $this->redirectToRoute('app_admin_kaaba_courses');
            }

            try {
                $em->remove($courseToDelete);
                $em->flush();
                $this->addFlash('success', 'Course deleted successfully.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Cannot delete course. It is still being referenced by other records.');
            }
            
            return $this->redirectToRoute('app_admin_kaaba_courses');
        } else {
            $this->addFlash('error', 'Course not found.');
        }
    }
    return $this->render('admin/kaaba_courses.html.twig', [
        'courses' => $courses,
        'institutes' => $institutes,
        'form' => $form->createView(),
        'editUuid' => $editUuid,
        'showForm' => $showForm,
    ]);
}

#[Route('/kaaba-applications', name: 'app_admin_kaaba_applications')]
public function kaabaApplications(
    KaabaApplicationRepository $applicationsRepository,
    KaabaApplicationStatusRepository $statusRepository,
    KaabaRegionRepository $regionRepository,
    KaabaDistrictRepository $districtRepository,
    KaabaQualificationRepository $qualificationRepository,
    KaabaGenderRepository $genderRepository,
    KaabaScholarshipRepository $scholarshipRepository,
    Request $request,
    PaginatorInterface $paginator,
): Response {

    // Fetch filter options
    $statuses = $statusRepository->findAll();
    $regions = $regionRepository->findAll();
    $districts = $districtRepository->findAll();
    $qualifications = $qualificationRepository->findAll();
    $genders = $genderRepository->findAll();
    $scholarships = $scholarshipRepository->findAll();

  $searchForm = $this->createFormBuilder(null)
        ->add('status', EntityType::class, [
            'class' => KaabaApplicationStatus::class,
            'choice_label' => 'name',
            'required' => false,
            'mapped' => false,
            'label' => 'Status',
            'attr' => [
                'class' => 'form-control',
                'col_class' => 'col-md-3',
                'placeholder' => 'Filter by Status'
            ],
        ])
        ->add('scholarship', EntityType::class, [
            'class' => KaabaScholarship::class,
            'choice_label' => 'title',
            'required' => false,
            'mapped' => false,
            'label' => 'Scholarship',
            'attr' => [
                'class' => 'form-control',
                'col_class' => 'col-md-3',
                'placeholder' => 'Filter by Scholarship'
            ],
        ])
        ->add('from_date', DateType::class, [
            'required' => false,
            'mapped' => false,
            'widget' => 'single_text',
            'label' => 'From Date',
            'attr' => [
                'class' => 'form-control',
                'col_class' => 'col-md-3',
                'placeholder' => 'From Date'
            ],
        ])
        ->add('to_date', DateType::class, [
            'required' => false,
            'mapped' => false,
            'widget' => 'single_text',
            'label' => 'To Date',
            'attr' => [
                'class' => 'form-control',
                'col_class' => 'col-md-3',
                'placeholder' => 'To Date'
            ],
        ])
        ->add('phone', TextType::class, [
            'required' => false,
            'mapped' => false,
            'label' => 'Phone Number',
            'attr' => [
                'class' => 'form-control',
                'col_class' => 'col-md-3',
                'placeholder' => 'Filter by Phone'
            ],
        ])
        ->add('region', EntityType::class, [
            'class' => KaabaRegion::class,
            'choice_label' => 'name',
            'required' => false,
            'mapped' => false,
            'label' => 'Region',
            'attr' => [
                'class' => 'form-control',
                'col_class' => 'col-md-3',
                'placeholder' => 'Filter by Region'
            ],
        ])
        ->add('district', EntityType::class, [
            'class' => KaabaDistrict::class,
            'choice_label' => 'name',
            'required' => false,
            'mapped' => false,
            'label' => 'District',
            'attr' => [
                'class' => 'form-control',
                'col_class' => 'col-md-3',
                'placeholder' => 'Filter by District'
            ],
        ])
        ->add('qualification', EntityType::class, [
            'class' => KaabaQualification::class,
            'choice_label' => 'name',
            'required' => false,
            'mapped' => false,
            'label' => 'Qualification',
            'attr' => [
                'class' => 'form-control',
                'col_class' => 'col-md-3',
                'placeholder' => 'Filter by Qualification'
            ],
        ])
        ->add('gender', EntityType::class, [
            'class' => KaabaGender::class,
            'choice_label' => 'name',
            'required' => false,
            'mapped' => false,
            'label' => 'Gender',
            'attr' => [
                'class' => 'form-control',
                'col_class' => 'col-md-3',
                'placeholder' => 'Filter by Gender'
            ],
        ])
        ->getForm();

    $searchForm->handleRequest($request);

    if ($searchForm->isSubmitted() && $searchForm->isValid()) {
        $status = $searchForm->get("status")->getData();
        $scholarship = $searchForm->get("scholarship")->getData();
        $fromDate = $searchForm->get("from_date")->getData();
        $toDate = $searchForm->get("to_date")->getData();
        $phone = $searchForm->get("phone")->getData();
        $region = $searchForm->get("region")->getData();
        $district = $searchForm->get("district")->getData();
        $qualification = $searchForm->get("qualification")->getData();
        $gender = $searchForm->get("gender")->getData();

        $datatable = $applicationsRepository->filterApplications(
            $status,
            $fromDate,
            $toDate,
            $phone,
            $region,
            $district,
            $qualification,
            $gender,
            $scholarship
        );

        $count = count($datatable);
        $applications = $paginator->paginate(
            $datatable,
            $request->query->get('page', 1),
            20
        );
    } else {
        $datatable = $applicationsRepository->filterApplications();
        $count = count($datatable);
        $applications = $paginator->paginate(
            $datatable,
            $request->query->get('page', 1),
            20
        );
    }

    return $this->render('admin/kaaba_applications.html.twig', [
        'applications' => $applications,
        'searchForm' => $searchForm->createView(),
        'total_count' => $count,
    ]);
}


#[Route('/logs/{uuid}', name: 'app_admin_kaaba_application_logs', methods: ['GET', 'POST'])]
public function viewLogs(
    KaabaApplication $application,
    Request $request,
    EntityManagerInterface $em
): Response {
    // Get logs with user data
    $logs = $em->createQueryBuilder()
        ->select('l', 'u')
        ->from(KaabaApplicationLog::class, 'l')
        ->leftJoin('l.user', 'u')
        ->where('l.application = :application')
        ->setParameter('application', $application)
        ->orderBy('l.created_at', 'DESC')
        ->getQuery()
        ->getResult();

    $template = $request->isXmlHttpRequest()
        ? 'admin/_logs.html.twig'
        : 'admin/_logs.html.twig';

    return $this->render($template, [
        'application' => $application,
        'logs' => $logs
    ]);
}


#[Route('/kaaba-applications/update-status', name: 'app_admin_kaaba_application_update_status', methods: ['POST'])]
public function updateApplicationStatus(
    Request $request,
    KaabaApplicationRepository $applicationRepository,
    KaabaApplicationStatusRepository $statusRepository,
    EntityManagerInterface $entityManager,
    ApplicationLogger $applicationLogger
): JsonResponse {
    $data = json_decode($request->getContent(), true);
    $applicationId = $data['applicationId'] ?? null;
    $statusAction = $data['statusAction'] ?? null;
    $csrfToken = $data['_token'] ?? null;

    // Validate CSRF token
    if (!$this->isCsrfTokenValid('update_application_status', $csrfToken)) {
        return $this->json([
            'success' => false,
            'message' => 'Invalid CSRF token.'
        ], 400);
    }

    if (!$applicationId || !$statusAction) {
        return $this->json([
            'success' => false,
            'message' => 'Missing required parameters.'
        ], 400);
    }

    try {
        $application = $applicationRepository->find($applicationId);
        
        if (!$application) {
            return $this->json([
                'success' => false,
                'message' => 'Application not found.'
            ], 404);
        }

        // Get the old status for logging BEFORE changing it
        $oldStatus = $application->getStatus() ? $application->getStatus()->getName() : 'None';

        // Map status actions to status names
        $statusMap = [
            'shortlisted' => 'Shortlisted',
            'accepted' => 'Accepted', 
            'rejected' => 'Rejected'
        ];

        if (!isset($statusMap[$statusAction])) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid status action.'
            ], 400);
        }

        $statusName = $statusMap[$statusAction];
        $status = $statusRepository->findOneBy(['name' => $statusName]);
        
        // If not found, try case-insensitive
        if (!$status) {
            $allStatuses = $statusRepository->findAll();
            foreach ($allStatuses as $s) {
                if (strtolower($s->getName()) === strtolower($statusName)) {
                    $status = $s;
                    break;
                }
            }
        }

        if (!$status) {
            return $this->json([
                'success' => false,
                'message' => 'Status not found.'
            ], 404);
        }

        // Set status dates
        $now = new \DateTime();
        switch ($statusAction) {
            case 'shortlisted':
                $application->setShortlistedDate($now);
                break;
            case 'accepted':
                $application->setAcceptedDate($now);
                break;
            case 'rejected':
                $application->setRejectedDate($now);
                break;
        }

        // Update application status
        $application->setStatus($status);
        
          // ✅ FIX: Use the generic log() method instead of logStatusChange()
        $applicationLogger->log(
            $application,
            'status_change', // This will trigger the status_change case in your switch statement
            sprintf("Status changed from '%s' to '%s' via admin panel", $oldStatus, $statusName),
            $this->getUser() // current admin user
        );


        $entityManager->flush();

        // Generate new status badge HTML
        $newStatusBadge = sprintf(
            '<span class="badge %s">%s</span>',
            match($statusName) {
                'Accepted' => 'bg-success',
                'Rejected' => 'bg-danger',
                'Shortlisted' => 'bg-info text-white',
                default => 'bg-secondary'
            },
            $statusName
        );

        return $this->json([
            'success' => true,
            'message' => "Application status updated to {$statusName} successfully.",
            'newStatusBadge' => $newStatusBadge
        ]);

    } catch (\Exception $e) {
        return $this->json([
            'success' => false,
            'message' => 'Error updating application status: ' . $e->getMessage()
        ], 500);
    }
}

#[Route('/kaaba-applications/revert-status/{id}', name: 'app_admin_kaaba_application_revert_status', methods: ['POST'])]
public function revertApplicationStatus(
    KaabaApplication $application,
    Request $request,
    KaabaApplicationStatusRepository $statusRepository,
    EntityManagerInterface $entityManager,
    ApplicationLogger $applicationLogger // Add this
): JsonResponse {
    $csrfToken = $request->request->get('_token') ?? $request->headers->get('X-CSRF-Token');

    // Validate CSRF token
    if (!$this->isCsrfTokenValid('revert_application_status', $csrfToken)) {
        return $this->json([
            'success' => false,
            'message' => 'Invalid CSRF token.'
        ], 400);
    }

    try {
        $currentStatus = $application->getStatus();
        
        if (!$currentStatus) {
            return $this->json([
                'success' => false,
                'message' => 'Application has no current status.'
            ], 400);
        }

        $currentStatusName = $currentStatus->getName();

        // Define allowed reverts with case-insensitive matching
        $allowedReverts = [
            'accepted' => 'applied', // or 'shortlisted' depending on your flow
            'shortlisted' => 'applied'
        ];

        // Normalize the current status name to lowercase for comparison
        $normalizedCurrentStatus = strtolower($currentStatusName);

        if (!isset($allowedReverts[$normalizedCurrentStatus])) {
            return $this->json([
                'success' => false,
                'message' => 'Cannot revert from current status: ' . $currentStatusName . '. Allowed reverts: from accepted or shortlisted to applied.'
            ], 400);
        }

        $targetStatusName = $allowedReverts[$normalizedCurrentStatus];
        
        // Find the target status - try both exact match and case-insensitive
        $newStatus = $statusRepository->findOneBy(['name' => $targetStatusName]);
        
        // If not found with exact case, try case-insensitive search
        if (!$newStatus) {
            $allStatuses = $statusRepository->findAll();
            foreach ($allStatuses as $status) {
                if (strtolower($status->getName()) === $targetStatusName) {
                    $newStatus = $status;
                    break;
                }
            }
        }

        if (!$newStatus) {
            return $this->json([
                'success' => false,
                'message' => 'Target status not found: ' . $targetStatusName
            ], 404);
        }

        // Clear status dates when reverting
        switch ($normalizedCurrentStatus) {
            case 'accepted':
                $application->setAcceptedDate(null);
                break;
            case 'shortlisted':
                $application->setShortlistedDate(null);
                break;
        }

        // Update application status
        $application->setStatus($newStatus);
        
        // ✅ ADD LOGGING HERE
        $applicationLogger->log(
            $application,
            'revert',
            sprintf(
                "Status reverted from '%s' to '%s'", 
                $currentStatusName, 
                $newStatus->getName()
            ),
            $this->getUser() // current admin user
        );

        $entityManager->flush();

        $newStatusDisplayName = $newStatus->getName();
        
        // Generate new status badge HTML
        $newStatusBadge = sprintf(
            '<span class="badge %s">%s</span>',
            match(strtolower($newStatusDisplayName)) {
                'accepted' => 'bg-success',
                'rejected' => 'bg-danger',
                'shortlisted' => 'bg-info',
                'applied' => 'bg-primary',
                default => 'bg-secondary'
            },
            $newStatusDisplayName
        );

        return $this->json([
            'success' => true,
            'message' => "Application status reverted to {$newStatusDisplayName} successfully.",
            'newStatusBadge' => $newStatusBadge,
            'newStatus' => $newStatusDisplayName
        ]);

    } catch (\Exception $e) {
        return $this->json([
            'success' => false,
            'message' => 'Error reverting application status: ' . $e->getMessage()
        ], 500);
    }
}

#[Route('/kaaba-applications/revert-rejected/{id}', name: 'app_admin_kaaba_application_revert_rejected', methods: ['POST'])]
public function revertRejectedApplication(
    KaabaApplication $application,
    Request $request,
    KaabaApplicationStatusRepository $statusRepository,
    EntityManagerInterface $entityManager,
    ApplicationLogger $applicationLogger // Add this
): JsonResponse {
    $csrfToken = $request->request->get('_token') ?? $request->headers->get('X-CSRF-Token');

    // Validate CSRF token
    if (!$this->isCsrfTokenValid('revert_rejected_application', $csrfToken)) {
        return $this->json([
            'success' => false,
            'message' => 'Invalid CSRF token.'
        ], 400);
    }

    try {
        $currentStatus = $application->getStatus();
        
        if (!$currentStatus) {
            return $this->json([
                'success' => false,
                'message' => 'Application has no current status.'
            ], 400);
        }

        $currentStatusName = $currentStatus->getName();

        // Only allow reverting from Rejected status (case-insensitive)
        if (strtolower($currentStatusName) !== 'rejected') {
            return $this->json([
                'success' => false,
                'message' => 'This action is only allowed for rejected applications. Current status: ' . $currentStatusName
            ], 400);
        }

        // Find applied status - try both exact match and case-insensitive
        $appliedStatus = $statusRepository->findOneBy(['name' => 'applied']);
        
        // If not found with exact case, try case-insensitive search
        if (!$appliedStatus) {
            $allStatuses = $statusRepository->findAll();
            foreach ($allStatuses as $status) {
                if (strtolower($status->getName()) === 'applied') {
                    $appliedStatus = $status;
                    break;
                }
            }
        }
        
        if (!$appliedStatus) {
            return $this->json([
                'success' => false,
                'message' => 'Applied status not found.'
            ], 404);
        }

        // Clear rejected date and set to applied
        $application->setRejectedDate(null);
        $application->setStatus($appliedStatus);
        
        // ✅ ADD LOGGING HERE
        $applicationLogger->log(
            $application,
            'revert',
            sprintf(
                "Rejected application reverted to '%s' status", 
                $appliedStatus->getName()
            ),
            $this->getUser() // current admin user
        );

        $entityManager->flush();

        // Generate new status badge HTML
        $newStatusBadge = '<span class="badge bg-primary">Applied</span>';

        return $this->json([
            'success' => true,
            'message' => "Application status reverted to Applied successfully.",
            'newStatusBadge' => $newStatusBadge,
            'newStatus' => 'Applied'
        ]);

    } catch (\Exception $e) {
        return $this->json([
            'success' => false,
            'message' => 'Error reverting rejected application: ' . $e->getMessage()
        ], 500);
    }
}


#[Route('/admin/kaaba-application/{uuid}', name: 'app_admin_kaaba_application_view')]
public function kaabaApplicationView(
    string $uuid,
    KaabaApplicationRepository $applicationRepository,
    ParameterBagInterface $params
): Response {
    $application = $applicationRepository->findOneBy(['uuid' => $uuid]);
    
    if (!$application) {
        throw $this->createNotFoundException('Application not found.');
    }

    // Get the attachments directory from parameters
    $attachmentsDir = $params->get('application_attachments');
    
    return $this->render('admin/kaaba_application_view.html.twig', [
        'application' => $application,
        'attachments_dir' => $attachmentsDir,
    ]);
}

#[Route('/application-attachments/{filename}', name: 'app_application_attachments')]
    public function serveApplicationAttachment(string $filename): Response
    {
        // Security check - validate filename
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $filename)) {
            throw new NotFoundHttpException('Invalid filename.');
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        $filePath = $projectDir . '/var/uploads/application_attachments/' . $filename;

        // Check if file exists
        if (!file_exists($filePath)) {
            throw new NotFoundHttpException('File not found.');
        }

        // Create response with appropriate headers
        $response = new BinaryFileResponse($filePath);
        
        // Set appropriate content type based on file extension
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        if (isset($mimeTypes[$extension])) {
            $response->headers->set('Content-Type', $mimeTypes[$extension]);
        }

        // Force download or display in browser
        $response->headers->set('Content-Disposition', 'inline; filename="' . $filename . '"');

        return $response;
    }

    
}

