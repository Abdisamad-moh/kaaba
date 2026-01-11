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
use App\Entity\KaabaSmsLog;
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
use App\Entity\KaabaAssessment;
use App\Entity\JobseekerDetails;
use App\Entity\KaabaApplication;
use App\Entity\KaabaScholarship;
use App\Entity\MetierAppSetting;
use App\Entity\MetierEmailTemps;
use App\Form\ChangePasswordType;
use App\Form\InterviewQFormType;
use App\Entity\KaabaIdentityType;
use App\Form\KaabaAssessmentType;
use App\Entity\InterviewQuestions;
use App\Entity\KaabaAssessmentLog;
use App\Entity\KaabaQualification;
use App\Form\CustomerAutoComplete;
use App\Repository\UserRepository;
use App\Service\ApplicationLogger;
use App\Service\TelesomSmsService;
use App\Entity\EmployerJobQuestion;
use App\Entity\KaabaApplicationLog;
use App\Form\EmailTemplateFormType;
use App\Form\SettingsBasicInfoType;
use Symfony\UX\Chartjs\Model\Chart;
use App\Entity\KaabaApplicationExam;
use App\Service\NotificationService;
use App\Service\SubscriptionService;
use App\Form\EmployerDetailsFormType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use App\Entity\KaabaApplicationStatus;
use App\Form\KaabaApplicationExamType;
use App\Form\ProductAutoCompleteField;
use Symfony\Component\Form\FormEvents;
use App\Form\EmployerAutoCompleteField;
use App\Repository\JobReportRepository;
use App\Repository\MetierAdsRepository;
use App\Entity\JobSeekerRecommendedJobs;
use App\Form\JobseekerAutoCompleteField;
use App\Form\KaabaApplicationSearchType;
use App\Repository\MetierBlogRepository;
use App\Security\AppCustomAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\EmployerJobQuestionAnswer;
use App\Repository\KaabaCourseRepository;
use App\Repository\KaabaGenderRepository;
use App\Repository\KaabaRegionRepository;
use App\Repository\MetierOrderRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpClient\Exception\TimeoutException;
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
        // Get current user
        $user = $this->getUser();

        // Get statistics with user filtering - using status IDs
        $totalApplications = $applicationRepository->countTotalApplications($user);
        $lastYearApplications = $applicationRepository->countLastYearApplications($user);

        // Count by status IDs
        $appliedApplications = $applicationRepository->countApplicationsByStatusId(1, $user); // ID 1 = Applied
        $shortlistedApplications = $applicationRepository->countApplicationsByStatusId(2, $user); // ID 2 = Shortlisted
        $rejectedApplications = $applicationRepository->countApplicationsByStatusId(3, $user); // ID 3 = Rejected
        $approvedApplications = $applicationRepository->countApplicationsByStatusId(4, $user); // ID 4 = Accepted/Approved
        $waitlistedApplications = $applicationRepository->countApplicationsByStatusId(5, $user); // ID 5 = Waitlisted

        // Get data for charts with user filtering
        $applicationsByRegion = $applicationRepository->countApplicationsByRegion($user);
        $applicationsByDistrict = $applicationRepository->countApplicationsByDistrict($user);
        $applicationsByGender = $applicationRepository->countApplicationsByGender($user);
        $applicationsByInstitute = $applicationRepository->countApplicationsByInstitute($user);
        $applicationsByScholarship = $applicationRepository->countApplicationsByScholarship($user);
        $applicationsByMonth = $applicationRepository->countApplicationsByMonth($user);

        // Get recent applications with user filtering
        $recentApplications = $applicationRepository->findRecentApplications(5, $user);

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
            'appliedApplications' => $appliedApplications,
            'approvedApplications' => $approvedApplications,
            'rejectedApplications' => $rejectedApplications,
            'shortlistedApplications' => $shortlistedApplications,
            'waitlistedApplications' => $waitlistedApplications,
            'recentApplications' => $recentApplications,
            'regionChart' => $regionChart,
            'genderChart' => $genderChart,
            'instituteChart' => $instituteChart,
            'monthlyChart' => $monthlyChart,
            'scholarshipChart' => $scholarshipChart,
            'current_user' => $user,
        ]);
    }


    #[Route('/manage-users', name: 'app_admin_accounts')]
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
                    'col_class' => 'col-4',
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
                    'col_class' => 'col-4',
                ],
                'placeholder' => 'Filter by status',
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
                    'col_class' => 'col-4',
                ],
                'placeholder' => 'Select Verification',
            ])
            ->getForm();

        $searchForm->handleRequest($request);
        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $status = $searchForm->get("status")->getData();
            $email = $searchForm->get("email")->getData();
            $is_verified = $searchForm->get("verification")->getData();
            $datatable = $accounts->filterAccounts(
                $status,
                $email,
                $is_verified
            );
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



    #[Route('/user/create', name: 'app_admin_create_user')]
    public function createUser(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = new User();

        $form = $this->createFormBuilder($user)
            ->add('name', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Full Name',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please provide a name',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Email Address',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter an email address',
                    ]),
                    new Email([
                        'message' => 'Please enter a valid email address',
                    ]),
                ],
            ])
            ->add('status', CheckboxType::class, [
                'required' => false,
                'label' => 'Active Account',
                'data' => true, // Default to active
                'attr' => [
                    'class' => 'form-check-input'
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ],
                'attr' => [
                    'autocomplete' => 'new-password',
                    'class' => 'form-control'
                ],
                'label' => 'Password',
            ])
            ->add('confirmPassword', PasswordType::class, [
                'mapped' => false,
                'label' => 'Confirm Password',
                'attr' => [
                    'class' => 'form-control'
                ],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set additional user properties
            $user->setUsername($form->get('email')->getData());
            $user->setRoles(["ROLE_USER"]); // Default role
            $user->setType("user"); // Default type
            $user->setVerified(true); // Auto-verify admin-created accounts
            $user->setOtpEnabled(false); // Disable OTP for admin-created users
            $user->setOtpAttempts(0); // Reset OTP attempts
            $user->setOtp(null); // Clear any OTP value
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'User created successfully!');
            return $this->redirectToRoute('app_admin_accounts');
        }

        return $this->render('admin/add_account.html.twig', [
            'form' => $form,
            'type' => 'User',
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












    // system users
    #[Route('/settings_system_users', name: 'app_admin_settings_users')]
    public function settings_system_users(
        Request $request,
        EntityManagerInterface $em,
        UserRepository $users,
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
        UserRepository $userRepository,
        EntityManagerInterface $em,
    ): Response {
        // Fetch all institutes for the table
        $institutes = $kaabaInstituteRepository->findAll();

        // Fetch active scholarships for the dropdown
        $activeScholarships = $kaabaScholarshipRepository->findBy(['status' => true]);
        $activeUsers = $userRepository->findBy(['status' => true]);

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
            ->add('manager', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'name',
                'label' => 'Manager',
                'placeholder' => 'Select a user',
                'choices' => $activeUsers,
                'attr' => [
                    'class' => 'form-select'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please select a User.'])
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
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $courses = $kaabaCourseRepository->findAll();
        } else {
            $user = $this->getUser();
            $instituteIds = $user->getKaabaInstitutes()->map(function ($institute) {
                return $institute->getId();
            })->toArray();

            $courses = $kaabaCourseRepository->findBy([
                'institute' => $instituteIds
            ]);
        }


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
        Request $request,
        KaabaApplicationRepository $applicationsRepository,
        KaabaInstituteRepository $instituteRepository,
        PaginatorInterface $paginator,
        KaabaCourseRepository $courseRepository,
    ): Response {
        $user = $this->getUser();

        // Institutes by role
        if ($user && in_array('ROLE_USER', $user->getRoles()) && !in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            $institutes = $instituteRepository->findBy(['manager' => $user]);
        } else {
            $institutes = $instituteRepository->findAll();
        }

        $form = $this->createForm(KaabaApplicationSearchType::class, null, [
            'method' => 'GET',
            'institutes' => $institutes,
            'request' => $request
        ]);

        $form->handleRequest($request);

        $filters = [];
        foreach ($form as $key => $field) {
            $filters[$key] = $field->getData();
        }

        // Get the query with updated parameter order
        $query = $applicationsRepository->filterApplicationsQuery(
            $filters['applicant'] ?? null,
            $filters['region'] ?? null,
            $filters['district'] ?? null,
            null,
            $filters['scholarship'] ?? null,
            $filters['institute'] ?? null,
            $filters['course'] ?? null,
            $filters['exam_result'] ?? null,
            $filters['assesment_result'] ?? null,
            $filters['disability'] ?? null,
            $filters['status'] ?? null,
            $user
        );

        $limit = $filters['limit'] ?? 100;

        // Paginate the query directly (this is more efficient)
        $applications = $paginator->paginate(
            $query, // Pass the Query object instead of array
            $request->query->getInt('page', 1),
            $limit
        );

        return $this->render('admin/kaaba_applications.html.twig', [
            'applications' => $applications,
            'searchForm' => $form->createView(),
            'total_count' => $applications->getTotalItemCount(), // Get count from paginator
            'current_limit' => $limit,
        ]);
    }




    #[Route('/kaaba-applications/export', name: 'app_admin_kaaba_applications_export')]
    public function exportApplications(
        Request $request,
        KaabaApplicationRepository $applicationsRepository,
        KaabaApplicationStatusRepository $statusRepository,
        KaabaRegionRepository $regionRepository,
        KaabaDistrictRepository $districtRepository,
        KaabaQualificationRepository $qualificationRepository,
        KaabaScholarshipRepository $scholarshipRepository,
        KaabaInstituteRepository $instituteRepository,
        KaabaCourseRepository $courseRepository,
    ): Response {

        $user = $this->getUser();

        //--------------------------------------------------------------------
        // 1) Get filters from request (Symfony form values)
        //--------------------------------------------------------------------

        $filters = $request->query->all('kaaba_application_search') ?? [];

        //--------------------------------------------------------------------
        // 2) Convert filters to Entities (as repository expects)
        //--------------------------------------------------------------------

        $status = !empty($filters['status']) ? $statusRepository->find($filters['status']) : null;

        $region = !empty($filters['region']) ? $regionRepository->find($filters['region']) : null;

        $district = !empty($filters['district']) ? $districtRepository->find($filters['district']) : null;

        $qualification = !empty($filters['qualification']) ? $qualificationRepository->find($filters['qualification']) : null;

        $scholarship = !empty($filters['scholarship']) ? $scholarshipRepository->find($filters['scholarship']) : null;

        $institute = !empty($filters['institute']) ? $instituteRepository->find($filters['institute']) : null;

        $course = !empty($filters['course']) ? $courseRepository->find($filters['course']) : null;

        //--------------------------------------------------------------------
        // 3) Applicant search — only exact ID supported in repo
        //--------------------------------------------------------------------

        $applicantEntity = null;

        if (!empty($filters['phone']) && ctype_digit($filters['phone'])) {
            $applicantEntity = $applicationsRepository->find($filters['phone']);
        }

        //--------------------------------------------------------------------
        // 4) Build query using updated parameter order (no date parameters)
        //--------------------------------------------------------------------

        $query = $applicationsRepository->filterApplicationsQuery(
            $applicantEntity,                       // 1 - applicant (first)
            $region,                                // 2 - region
            $district,                              // 3 - district
            $qualification,                         // 4 - qualification
            $scholarship,                           // 5 - scholarship
            $institute,                             // 6 - institute
            $course,                                // 7 - course
            $filters['exam_result'] ?? null,        // 8 - exam_result
            $filters['assesment_result'] ?? null,   // 9 - assesment_result
            $filters['disability'] ?? null,         // 10 - disability
            $status,                                // 11 - status (last)
            $user                                   // 12 - user
        );

        //--------------------------------------------------------------------
        // 5) Fetch ALL matching results (no pagination)
        //--------------------------------------------------------------------

        $applications = $query->getResult();

        //--------------------------------------------------------------------
        // 6) Generate Excel sheet
        //--------------------------------------------------------------------

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Column headers - Added Highschool Grade column
        $headers = [
            'No',
            'Portal ID',
            'Full Name',
            'Date of Birth',
            'Telephone',
            'Email',
            'Region',
            'District',
            'Family Members',
            'Monthly Income',
            'High School Name',
            'Highschool Grade', // New column
            'Graduation Year', // New column
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue("{$col}1", $header);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $col++;
        }

        //--------------------------------------------------------------------
        // 7) Populate data rows
        //--------------------------------------------------------------------

        $row = 2;
        $counter = 1;

        foreach ($applications as $application) {

            $sheet->setCellValue("A{$row}", $counter);
            $sheet->setCellValue("B{$row}", $application->getId());
            $sheet->setCellValue("C{$row}", $application->getFullName());

            $dob = $application->getDateOfBirth();
            $sheet->setCellValue("D{$row}", $dob ? $dob->format('Y-m-d') : '');

            $sheet->setCellValue("E{$row}", $application->getPhone());
            $sheet->setCellValue("F{$row}", $application->getEmail());

            $sheet->setCellValue("G{$row}", $application->getRegion()?->getName() ?? '');
            $sheet->setCellValue("H{$row}", $application->getDistrict()?->getName() ?? '');

            //----------------------------------------------------------------
            // Decode assessment JSON fields into readable text
            //----------------------------------------------------------------

            $assessment = $application->getAssessment();
            $familyMembers = '';
            $monthlyIncome = '';

            if ($assessment) {

                // household members
                $household = $assessment->getHousehold() ?? [];
                if (!empty($household['household_members'])) {
                    $familyMembers = match ($household['household_members']) {
                        1 => 'Less than 2',
                        2 => '2',
                        3 => '3–5',
                        4 => '6',
                        5 => '7–9',
                        6 => '10',
                        7 => '11–12',
                        8 => 'More than 12',
                        default => '',
                    };
                }

                // monthly income
                $income = $assessment->getIncome() ?? [];
                if (!empty($income['monthly_income'])) {
                    $monthlyIncome = match ($income['monthly_income']) {
                        1 => 'More than $2000',
                        2 => '$1500 – $2000',
                        3 => '$1200 – $1500',
                        4 => '$1000 – $1200',
                        5 => '$500 – $1000',
                        6 => '$300 – $500',
                        7 => '$150 – $300',
                        8 => '$65 – $150',
                        default => '',
                    };
                }
            }

            $sheet->setCellValue("I{$row}", $familyMembers);
            $sheet->setCellValue("J{$row}", $monthlyIncome);

            //----------------------------------------------------------------
            // High school name
            //----------------------------------------------------------------

            $sheet->setCellValue("K{$row}", $application->getSecondarySchool());

            //----------------------------------------------------------------
            // Highschool Grade - NEW COLUMN
            //----------------------------------------------------------------

            $sheet->setCellValue("L{$row}", $application->getSecondaryGrade() ?? '');
            $sheet->setCellValue("M{$row}", $application->getSecondaryGraduationYear() ?? '');

            $row++;
            $counter++;
        }

        //--------------------------------------------------------------------
        // 8) Auto-size columns
        //--------------------------------------------------------------------

        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        //--------------------------------------------------------------------
        // 9) Return file as downloadable response
        //--------------------------------------------------------------------

        $filename = "scholarship_export_" . date('Y-m-d_H-i-s') . ".xlsx";
        $writer = new Xlsx($spreadsheet);

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");

        return $response;
    }







    #[Route('/kaaba-applications/get-courses', name: 'app_admin_kaaba_applications_get_courses')]
    public function getCoursesByyInstitute(
        KaabaCourseRepository $courseRepository,
        Request $request
    ): Response {
        $instituteId = $request->query->get('institute_id');

        if (!$instituteId) {
            return new JsonResponse([]);
        }

        $courses = $courseRepository->findBy(['institute' => $instituteId]);

        $courseArray = [];
        foreach ($courses as $course) {
            $courseArray[] = [
                'id' => $course->getId(),
                'name' => $course->getName()
            ];
        }

        return new JsonResponse($courseArray);
    }

    #[Route('/application/{id}/send-sms', name: 'app_admin_send_sms')]
    public function sendSms(
        int $id,
        EntityManagerInterface $em,
        TelesomSmsService $smsService
    ): JsonResponse {

        /** @var KaabaApplication|null $application */
        $application = $em->getRepository(KaabaApplication::class)->find($id);

        if (!$application) {
            return new JsonResponse([
                'error' => 'Application not found'
            ], 404);
        }

        $phones = $application->getPhone();
        $fullName = $application->getFullName() ?: 'Applicant';

        if (!$phones) {
            return new JsonResponse([
                'application' => $id,
                'error' => 'No phone number found'
            ], 400);
        }

        // Customize your text message here
        $message = sprintf(
            "Hello %s, your scholarship application has been received. Thank you.",
            $fullName
        );

        try {
            $result = $smsService->sendBulk($phones, $message);

            return new JsonResponse([
                'application' => $id,
                'phones' => $result
            ]);

        } catch (TimeoutException $e) {

            return new JsonResponse([
                'application' => $id,
                'phones' => [],
                'error' => 'Gateway timeout'
            ], 504);

        } catch (\Throwable $e) {

            return new JsonResponse([
                'application' => $id,
                'phones' => [],
                'error' => 'Internal error: ' . $e->getMessage()
            ], 500);
        }
    }

    // Add this new route for AJAX course loading
    #[Route('/kaaba-applications/courses-by-institute/{instituteId}', name: 'app_admin_kaaba_applications_courses_by_institute', methods: ['GET'])]
    public function getCoursesByInstitute(int $instituteId, KaabaCourseRepository $courseRepository): JsonResponse
    {
        try {
            $courses = $courseRepository->findBy(['institute' => $instituteId], ['name' => 'ASC']);

            $courseArray = [];
            foreach ($courses as $course) {
                $courseArray[] = [
                    'id' => $course->getId(),
                    'name' => $course->getName(),
                ];
            }

            return $this->json($courseArray);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to load courses'], 500);
        }
    }

    #[Route('/soft-delete-user', name: 'app_admin_soft_delete_user', methods: ['POST'])]
    public function softDeleteUser(Request $request, EntityManagerInterface $em, UserRepository $userRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'] ?? null;

        if (!$userId) {
            return $this->json(['success' => false, 'message' => 'User ID is required'], 400);
        }

        $user = $userRepository->find($userId);
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'User not found'], 404);
        }

        try {
            // Soft delete by setting is_deleted to true and status to false
            $user->setDeleted(true);
            $user->setStatus(false);
            $em->flush();

            return $this->json([
                'success' => true,
                'message' => 'User has been deleted successfully'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to delete user: ' . $e->getMessage()
            ], 500);
        }
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
        // Check if request is AJAX
        if (!$request->isXmlHttpRequest()) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid request type.'
            ], 400);
        }

        $data = json_decode($request->getContent(), true);
        $applicationId = $data['applicationId'] ?? null;
        $statusAction = $data['statusAction'] ?? null;
        $shortlistReason = $data['shortlistReason'] ?? null; // Add this line
        $approveReason = $data['approveReason'] ?? null;
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

            // Get current user and role-based permissions
            $user = $this->getUser();
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            $isSuperAdmin = $this->isGranted('ROLE_SUPER_ADMIN');
            $isRegularUser = $this->isGranted('ROLE_USER') && !$isSuperAdmin;

            $currentStatus = $application->getStatus();
            $currentStatusName = $currentStatus ? $currentStatus->getName() : 'Applied';
            $oldStatus = $currentStatusName;

            // Define status mapping with IDs
            $statusMap = [
                'shortlisted' => ['name' => 'Shortlisted', 'id' => 2],
                'waitlisted' => ['name' => 'Waitlisted', 'id' => 5],
                'approved' => ['name' => 'Accepted', 'id' => 4],
                'rejected' => ['name' => 'Rejected', 'id' => 3]
            ];

            if (!isset($statusMap[$statusAction])) {
                return $this->json([
                    'success' => false,
                    'message' => 'Invalid status action.'
                ], 400);
            }

            // Role-based permission checks
            if ($isRegularUser) {
                $allowedFromApplied = ['shortlisted', 'waitlisted', 'rejected'];
                $currentStatusId = $currentStatus ? $currentStatus->getId() : 1;

                if ($currentStatusId !== 1 || !in_array($statusAction, $allowedFromApplied)) {
                    return $this->json([
                        'success' => false,
                        'message' => 'You can only update applications from "Applied" status to Shortlisted, Waitlisted, or Rejected.'
                    ], 403);
                }
            } elseif ($isSuperAdmin) {
                if ($statusAction === 'approved') {
                    $currentStatusId = $currentStatus ? $currentStatus->getId() : 1;
                    if (!in_array($currentStatusId, [2, 5])) {
                        return $this->json([
                            'success' => false,
                            'message' => 'You can only approve applications that are Shortlisted or Waitlisted.'
                        ], 403);
                    }
                }
            } else {
                return $this->json([
                    'success' => false,
                    'message' => 'Insufficient permissions.'
                ], 403);
            }

            $targetStatus = $statusMap[$statusAction];
            $status = $statusRepository->find($targetStatus['id']);

            if (!$status) {
                return $this->json([
                    'success' => false,
                    'message' => 'Status not found.'
                ], 404);
            }

            // Set status dates and reasons
            $now = new \DateTime();
            switch ($statusAction) {
                case 'shortlisted':
                    $application->setShortlistedDate($now);
                    $application->setWaitlistedDate(null);
                    $application->setShortlistReason($shortlistReason); // Set shortlist reason
                    break;
                case 'waitlisted':
                    $application->setWaitlistedDate($now);
                    $application->setShortlistedDate(null);
                    $application->setShortlistReason(null); // Clear shortlist reason if not shortlisting
                    break;
                case 'approved':
                    $application->setAcceptedDate($now);
                    $application->setShortlistReason(null); // Clear shortlist reason
                    $application->setApprovedReason($approveReason);
                    break;
                case 'rejected':
                    $application->setRejectedDate($now);
                    $application->setShortlistReason(null); // Clear shortlist reason
                    break;
            }

            // Clear other dates when changing status
            if ($statusAction !== 'rejected') {
                $application->setRejectedDate(null);
                $application->setRejectionReason(null); // Clear rejection reason
            }
            if ($statusAction !== 'approved') {
                $application->setAcceptedDate(null);
                $application->setApprovedReason(null); // Clear approved reason
            }

            // Update application status
            $application->setStatus($status);

            // Log the action
            $logMessage = sprintf("Status changed from '%s' to '%s'", $oldStatus, $targetStatus['name']);

            // Add reason to log if provided
            if ($statusAction === 'shortlisted' && !empty($shortlistReason)) {
                $logMessage .= ". Shortlist reason: " . $shortlistReason;
            }


            if ($statusAction === 'approved' && !empty($approveReason)) {
                $logMessage .= ". Approval reason: " . $approveReason;
            }


            $logMessage .= " by " . $user->getUserIdentifier();

            $applicationLogger->log(
                $application,
                'status_change',
                $logMessage,
                $user
            );

            $entityManager->flush();

            // Generate new status badge HTML
            $displayStatus = $targetStatus['name'] === 'Accepted' ? 'Approved' : $targetStatus['name'];
            $newStatusBadge = sprintf(
                '<span class="badge %s">%s</span>',
                match ($targetStatus['name']) {
                    'Accepted' => 'bg-success',
                    'Rejected' => 'bg-danger',
                    'Shortlisted' => 'bg-info text-white',
                    'Waitlisted' => 'bg-warning text-dark',
                    default => 'bg-secondary'
                },
                $displayStatus
            );

            return $this->json([
                'success' => true,
                'message' => "Application status updated to {$displayStatus} successfully.",
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
        ApplicationLogger $applicationLogger
    ): JsonResponse {
        // Check if request is AJAX
        if (!$request->isXmlHttpRequest()) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid request type.'
            ], 400);
        }

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

            $currentStatusId = $currentStatus->getId();
            $currentStatusName = $currentStatus->getName();

            // Get user and role
            $user = $this->getUser();
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            $isSuperAdmin = $this->isGranted('ROLE_SUPER_ADMIN');

            // Define revert rules based on role and current status
            $revertMap = [];

            if ($isSuperAdmin) {
                // Admin can revert from approved to shortlisted (default)
                if ($currentStatusId === 4) { // Approved/Accepted
                    $revertMap[4] = 2; // Default to shortlisted
                }
            }

            // Both roles can revert from shortlisted/waitlisted to applied
            $revertMap[2] = 1; // Shortlisted → Applied
            $revertMap[5] = 1; // Waitlisted → Applied

            if (!isset($revertMap[$currentStatusId])) {
                return $this->json([
                    'success' => false,
                    'message' => 'Cannot revert from current status: ' . $currentStatusName
                ], 400);
            }

            $targetStatusId = $revertMap[$currentStatusId];
            $newStatus = $statusRepository->find($targetStatusId);

            if (!$newStatus) {
                return $this->json([
                    'success' => false,
                    'message' => 'Target status not found.'
                ], 404);
            }

            // Clear status dates when reverting
            switch ($currentStatusId) {
                case 4: // Approved
                    $application->setAcceptedDate(null);
                    break;
                case 2: // Shortlisted
                    $application->setShortlistedDate(null);
                    break;
                case 5: // Waitlisted
                    $application->setWaitlistedDate(null);
                    break;
            }

            // Update application status
            $application->setStatus($newStatus);

            // Log the revert action
            $applicationLogger->log(
                $application,
                'revert',
                sprintf(
                    "Status reverted from '%s' to '%s'",
                    $currentStatusName,
                    $newStatus->getName()
                ),
                $user
            );

            $entityManager->flush();

            $newStatusDisplayName = $newStatus->getName();

            // Generate new status badge HTML
            $newStatusBadge = sprintf(
                '<span class="badge %s">%s</span>',
                match ($targetStatusId) {
                    4 => 'bg-success',
                    3 => 'bg-danger',
                    2 => 'bg-info text-white',
                    5 => 'bg-warning text-dark',
                    1 => 'bg-primary',
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

    #[Route('/find-duplicates', name: 'admin_find_duplicates', methods: ['POST', 'GET'])]
    public function findAndMarkDuplicates(EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Get all applications ordered by ID to ensure we keep the oldest one
            $applications = $entityManager->getRepository(KaabaApplication::class)
                ->findBy([], ['id' => 'ASC']);

            $duplicateGroups = [];
            $processed = [];
            $duplicatesFound = 0;
            $duplicatesUpdated = 0;

            // Group applications by full_name and phone
            foreach ($applications as $application) {
                $key = $this->generateDuplicateKey($application);

                if ($key === null) {
                    continue; // Skip if essential fields are missing
                }

                if (!isset($duplicateGroups[$key])) {
                    $duplicateGroups[$key] = [];
                }

                $duplicateGroups[$key][] = $application;
            }

            // Process duplicate groups
            foreach ($duplicateGroups as $key => $group) {
                if (count($group) > 1) {
                    $duplicatesFound += count($group) - 1; // Count all except the first one

                    // Sort by ID to ensure we keep the oldest application
                    usort($group, fn($a, $b) => $a->getId() <=> $b->getId());

                    $originalApplication = $group[0];
                    $duplicateApplications = array_slice($group, 1);

                    foreach ($duplicateApplications as $duplicateApp) {
                        // Skip if already processed or already marked as duplicate
                        if (in_array($duplicateApp->getId(), $processed) || $duplicateApp->isDuplicate()) {
                            continue;
                        }

                        // Mark as duplicate and set parent
                        $duplicateApp->setDuplicate(true);
                        $duplicateApp->setDuplicateParent($originalApplication);

                        $entityManager->persist($duplicateApp);
                        $processed[] = $duplicateApp->getId();
                        $duplicatesUpdated++;
                    }
                }
            }

            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Duplicate processing completed',
                'data' => [
                    'total_duplicates_found' => $duplicatesFound,
                    'duplicates_updated' => $duplicatesUpdated,
                    'duplicate_groups_processed' => count(array_filter($duplicateGroups, fn($group) => count($group) > 1)),
                    'already_marked_duplicates' => $duplicatesFound - $duplicatesUpdated
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Error processing duplicates: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Generate a unique key for duplicate detection based on full_name and phone
     */
    private function generateDuplicateKey(KaabaApplication $application): ?string
    {
        $fullName = $application->getFullName();
        $phone = $application->getPhone();

        // Skip if essential fields are empty
        if (empty($fullName) || empty($phone)) {
            return null;
        }

        // Normalize the data for better matching
        $normalizedName = $this->normalizeString($fullName);
        $normalizedPhone = $this->normalizePhone($phone);

        return md5($normalizedName . '|' . $normalizedPhone);
    }

    /**
     * Normalize string for duplicate comparison
     */
    private function normalizeString(string $string): string
    {
        return trim(mb_strtolower($string));
    }

    /**
     * Normalize phone number for duplicate comparison
     */
    private function normalizePhone(string $phone): string
    {
        // Remove all non-digit characters except +
        $normalized = preg_replace('/[^\d+]/', '', $phone);

        // Remove country code if it's 255 (Tanzania) and replace with 0
        if (str_starts_with($normalized, '255') && strlen($normalized) === 12) {
            $normalized = '0' . substr($normalized, 3);
        }

        // Remove leading + if present
        $normalized = ltrim($normalized, '+');

        return $normalized;
    }

    /**
     * Additional route to get duplicate statistics
     */
    #[Route('/admin/duplicate-stats', name: 'admin_duplicate_stats', methods: ['GET'])]
    public function getDuplicateStats(EntityManagerInterface $entityManager): JsonResponse
    {
        $totalApplications = $entityManager->getRepository(KaabaApplication::class)
            ->count([]);

        $duplicateApplications = $entityManager->getRepository(KaabaApplication::class)
            ->count(['is_duplicate' => true]);

        $originalApplicationsWithDuplicates = $entityManager->getRepository(KaabaApplication::class)
            ->createQueryBuilder('a')
            ->select('COUNT(DISTINCT a.id)')
            ->where('a.kaabaApplications IS NOT EMPTY')
            ->getQuery()
            ->getSingleScalarResult();

        return new JsonResponse([
            'success' => true,
            'data' => [
                'total_applications' => $totalApplications,
                'marked_as_duplicate' => $duplicateApplications,
                'original_applications_with_duplicates' => $originalApplicationsWithDuplicates,
                'unique_applications' => $totalApplications - $duplicateApplications
            ]
        ]);
    }


    private function getStatusByName(string $statusName): ?KaabaApplicationStatus
    {
        // Implement this based on your status repository
        $statusMap = [
            'Shortlisted' => 2,
            'Waitlisted' => 5,
            'Applied' => 1,
            'Rejected' => 3
        ];

        if (isset($statusMap[$statusName])) {
            return $this->getDoctrine()->getRepository(KaabaApplicationStatus::class)->find($statusMap[$statusName]);
        }

        return null;
    }

    #[Route('/kaaba-applications/revert-rejected/{id}', name: 'app_admin_kaaba_application_revert_rejected', methods: ['POST'])]
    public function revertRejectedApplication(
        KaabaApplication $application,
        Request $request,
        KaabaApplicationStatusRepository $statusRepository,
        EntityManagerInterface $entityManager,
        ApplicationLogger $applicationLogger
    ): JsonResponse {
        // Check if request is AJAX
        if (!$request->isXmlHttpRequest()) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid request type.'
            ], 400);
        }

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

            // Only allow reverting from Rejected status
            if (strtolower($currentStatusName) !== 'rejected') {
                return $this->json([
                    'success' => false,
                    'message' => 'This action is only allowed for rejected applications. Current status: ' . $currentStatusName
                ], 400);
            }

            // Find applied status
            $appliedStatus = $statusRepository->findOneBy(['name' => 'Applied']);

            if (!$appliedStatus) {
                $appliedStatus = $statusRepository->find(1); // Try by ID
            }

            if (!$appliedStatus) {
                return $this->json([
                    'success' => false,
                    'message' => 'Applied status not found.'
                ], 404);
            }

            // Get current user for logging
            $user = $this->getUser();
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            // Clear rejected date and rejection reason, then set to applied
            $application->setRejectedDate(null);
            $application->setRejectionReason(null); // Clear the rejection reason
            $application->setStatus($appliedStatus);

            // Log the action
            $applicationLogger->log(
                $application,
                'revert',
                sprintf(
                    "Rejected application reverted to '%s' status. Previous rejection reason cleared.",
                    $appliedStatus->getName()
                ),
                $user
            );

            $entityManager->persist($application);

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

    #[Route('/kaaba-applications/reject-with-reason/{id}', name: 'app_admin_kaaba_application_reject_with_reason', methods: ['POST'])]
    public function rejectWithReason(
        KaabaApplication $application,
        Request $request,
        KaabaApplicationStatusRepository $statusRepository,
        EntityManagerInterface $entityManager,
        ApplicationLogger $applicationLogger
    ): JsonResponse {
        // Check if request is AJAX
        if (!$request->isXmlHttpRequest()) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid request type.'
            ], 400);
        }

        $csrfToken = $request->request->get('_token') ?? $request->headers->get('X-CSRF-Token');

        // Validate CSRF token
        if (!$this->isCsrfTokenValid('reject_application_with_reason', $csrfToken)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid CSRF token.'
            ], 400);
        }

        try {
            $rejectionReason = $request->request->get('rejectionreason', '');

            // Find rejected status
            $rejectedStatus = $statusRepository->findOneBy(['name' => 'Rejected']);

            if (!$rejectedStatus) {
                $rejectedStatus = $statusRepository->findOneBy(['name' => 'rejected']);
            }

            if (!$rejectedStatus) {
                return $this->json([
                    'success' => false,
                    'message' => 'Rejected status not found.'
                ], 404);
            }

            // Get current user for logging
            $user = $this->getUser();
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            // Set rejected date, rejection reason, and status
            $application->setRejectedDate(new \DateTime());
            $application->setRejectionReason($rejectionReason);
            $application->setStatus($rejectedStatus);
            // return $this->json([
            //         'success' => false,
            //         'message' => $rejectionReason
            //     ], 401);
            // dd($rejectionReason);
            $entityManager->persist($application);

            // Log the action
            $logMessage = "Application rejected";
            if (!empty($rejectionReason)) {
                $logMessage .= ". Reason: " . $rejectionReason;
            }

            $applicationLogger->log(
                $application,
                'status_change',
                $logMessage,
                $user
            );

            $entityManager->flush();

            // Generate new status badge HTML
            $newStatusBadge = '<span class="badge bg-danger">Rejected</span>';

            return $this->json([
                'success' => true,
                'message' => "Application rejected successfully.",
                'newStatusBadge' => $newStatusBadge,
                'newStatus' => 'Rejected'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error rejecting application: ' . $e->getMessage()
            ], 500);
        }
    }




    #[Route('/kaaba-application/{uuid}', name: 'app_admin_kaaba_application_view')]
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

    #[Route('/kaaba-applications/edit-status-reason', name: 'app_admin_kaaba_application_edit_status_reason', methods: ['POST'])]
    public function editStatusReason(
        Request $request,
        KaabaApplicationRepository $applicationRepository,
        EntityManagerInterface $entityManager,
        ApplicationLogger $applicationLogger
    ): JsonResponse {
        // Check if request is AJAX
        if (!$request->isXmlHttpRequest()) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid request type.'
            ], 400);
        }

        $data = json_decode($request->getContent(), true);

        // Check if JSON decoding was successful
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid JSON data: ' . json_last_error_msg()
            ], 400);
        }

        $applicationId = $data['applicationId'] ?? null;
        $reasonType = $data['reasonType'] ?? null; // 'shortlist', 'approve', or 'reject'
        $reason = $data['reason'] ?? null;
        $csrfToken = $data['_token'] ?? null;

        // Validate required fields
        if (!$applicationId || !$reasonType) {
            return $this->json([
                'success' => false,
                'message' => 'Missing required parameters.'
            ], 400);
        }

        // Validate CSRF token
        if (!$this->isCsrfTokenValid('edit_status_reason', $csrfToken)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid CSRF token.'
            ], 400);
        }

        // Validate reason type
        $validReasonTypes = ['shortlist', 'approve', 'reject'];
        if (!in_array($reasonType, $validReasonTypes)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid reason type.'
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

            // Check if application has the correct status for the reason type
            $currentStatus = $application->getStatus();
            if (!$currentStatus) {
                return $this->json([
                    'success' => false,
                    'message' => 'Application has no status.'
                ], 400);
            }

            $currentStatusName = strtolower($currentStatus->getName());

            // Map reason types to expected status names (using your actual DB values)
            $statusMap = [
                'shortlist' => 'shortlisted',
                'approve' => 'approved',  // Changed from 'accepted' to 'approved'
                'reject' => 'rejected'
            ];

            // Debug: log the status check
            // error_log("Current status: $currentStatusName, Expected: " . $statusMap[$reasonType]);

            if ($currentStatusName !== $statusMap[$reasonType]) {
                return $this->json([
                    'success' => false,
                    'message' => "Can only edit {$reasonType} reason for {$statusMap[$reasonType]} applications. Current status is: {$currentStatusName}"
                ], 400);
            }

            // Get current user
            $user = $this->getUser();
            if (!$user) {
                return $this->json([
                    'success' => false,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            // Update the appropriate reason field
            $oldReason = null;
            $logAction = '';

            switch ($reasonType) {
                case 'shortlist':
                    $oldReason = $application->getShortlistReason();
                    $application->setShortlistReason($reason);
                    $logAction = 'shortlist_reason_updated';
                    break;
                case 'approve':
                    $oldReason = $application->getApprovedReason();
                    $application->setApprovedReason($reason);
                    $logAction = 'approval_reason_updated';
                    break;
                case 'reject':
                    $oldReason = $application->getRejectionReason();
                    $application->setRejectionReason($reason);
                    $logAction = 'rejection_reason_updated';
                    break;
            }

            // Log the action
            $logMessage = sprintf(
                "%s reason updated from '%s' to '%s' by %s",
                ucfirst($reasonType),
                $oldReason ?? 'Empty',
                $reason ?? 'Empty',
                $user->getUserIdentifier()
            );

            $applicationLogger->log(
                $application,
                $logAction,
                $logMessage,
                $user
            );

            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => ucfirst($reasonType) . ' reason updated successfully.'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error updating ' . $reasonType . ' reason: ' . $e->getMessage()
            ], 500);
        }
    }
    // #[Route('/kaaba-applications/edit-rejection-reason', name: 'app_admin_kaaba_application_edit_rejection_reason', methods: ['POST'])]
    // public function editRejectionReason(
    //     Request $request,
    //     KaabaApplicationRepository $applicationRepository,
    //     EntityManagerInterface $entityManager,
    //     ApplicationLogger $applicationLogger
    // ): JsonResponse {
    //     // Check if request is AJAX
    //     if (!$request->isXmlHttpRequest()) {
    //         return $this->json([
    //             'success' => false,
    //             'message' => 'Invalid request type.'
    //         ], 400);
    //     }



    //     $data = json_decode($request->getContent(), true);

    //     // Check if JSON decoding was successful
    //     if (json_last_error() !== JSON_ERROR_NONE) {
    //         return $this->json([
    //             'success' => false,
    //             'message' => 'Invalid JSON data: ' . json_last_error_msg()
    //         ], 400);
    //     }

    //     $applicationId = $data['applicationId'] ?? null;
    //     $rejectionReason = $data['rejectionReason'] ?? null;
    //     $csrfToken = $data['_token'] ?? null;

    //     // Validate required fields
    //     if (!$applicationId) {
    //         return $this->json([
    //             'success' => false,
    //             'message' => 'Missing application ID.'
    //         ], 400);
    //     }

    //     // Validate CSRF token
    //     if (!$this->isCsrfTokenValid('edit_rejection_reason', $csrfToken)) {
    //         return $this->json([
    //             'success' => false,
    //             'message' => 'Invalid CSRF token.'
    //         ], 400);
    //     }

    //     // Rest of your existing code remains the same...
    //     try {
    //         $application = $applicationRepository->find($applicationId);

    //         if (!$application) {
    //             return $this->json([
    //                 'success' => false,
    //                 'message' => 'Application not found.'
    //             ], 404);
    //         }

    //         // Check if application is actually rejected
    //         $currentStatus = $application->getStatus();
    //         if (!$currentStatus || $currentStatus->getName() !== 'rejected') {
    //             return $this->json([
    //                 'success' => false,
    //                 'message' => 'Can only edit rejection reason for rejected applications.'
    //             ], 400);
    //         }

    //         // Get current user
    //         $user = $this->getUser();
    //         if (!$user) {
    //             return $this->json([
    //                 'success' => false,
    //                 'message' => 'User not authenticated.'
    //             ], 401);
    //         }

    //         $oldReason = $application->getRejectionReason();
    //         $application->setRejectionReason($rejectionReason);

    //         // Log the action
    //         $logMessage = sprintf(
    //             "Rejection reason updated from '%s' to '%s' by %s",
    //             $oldReason ?? 'Empty',
    //             $rejectionReason ?? 'Empty',
    //             $user->getUserIdentifier()
    //         );

    //         $applicationLogger->log(
    //             $application,
    //             'rejection_reason_updated',
    //             $logMessage,
    //             $user
    //         );

    //         $entityManager->flush();

    //         return $this->json([
    //             'success' => true,
    //             'message' => 'Rejection reason updated successfully.'
    //         ]);

    //     } catch (\Exception $e) {
    //         return $this->json([
    //             'success' => false,
    //             'message' => 'Error updating rejection reason: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    #[Route('/accountSettings', name: 'app_employer_account_settings')]
    public function accountSettings(
        FileUploader $fileUploader,
        UserPasswordHasherInterface $passwordHasher,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $users,
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


        return $this->render('employer/account_settings.html.twig', [
            'employer' => $this->getUser(),
            'form' => $form,
            'statusForm' => $statusForm,
        ]);
    }



    #[Route('/application/{uuid}/assessment/result', name: 'app_assessment_result')]
    public function result(KaabaApplication $application): Response
    {
        return $this->render('admin/assessment_result.html.twig', [
            'application' => $application,
            'assessment' => $application->getAssessment(),
        ]);
    }

    #[Route('/application/{uuid}/assessment', name: 'app_assessment')]
    public function assess(
        KaabaApplication $application,
        Request $request,
        EntityManagerInterface $em
    ): Response {

        // Load or create assessment
        $assessment = $application->getAssessment();

        if (!$assessment) {
            $assessment = new KaabaAssessment();
            $assessment->setApplication($application);
            $em->persist($assessment);
        }

        // Create form with the ENTITY — not arrays
        $form = $this->createForm(KaabaAssessmentType::class, $assessment);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData(); // this returns ENTITY + mapped:false fields

            // Extract values manually from $request because fields are mapped:false
            $motivationData = [
                'employment_status' => $form->get('employment_status')->getData(),
                'training_enrolment' => $form->get('training_enrolment')->getData(),
                'reason_for_applying' => $form->get('reason_for_applying')->getData(),
                'career_aspirations' => $form->get('career_aspirations')->getData(),
                'community_participation' => $form->get('community_participation')->getData(),
            ];

            $householdData = [
                'housing_situation' => $form->get('housing_situation')->getData(),
                'household_members' => $form->get('household_members')->getData(),
                'decision_maker' => $form->get('decision_maker')->getData(),
                'dependency_ratio' => $form->get('dependency_ratio')->getData(),
                'household_circumstances' => $form->get('household_circumstances')->getData(),
            ];

            $incomeData = [
                'breadwinner_occupation' => $form->get('breadwinner_occupation')->getData(),
                'income_sources' => $form->get('income_sources')->getData(),
                'monthly_income' => $form->get('monthly_income')->getData(),
                'household_savings' => $form->get('household_savings')->getData(),
                'financial_vulnerability' => $form->get('financial_vulnerability')->getData(),
            ];

            // Save into entity
            $assessment->setMotivation($motivationData);
            $assessment->setHousehold($householdData);
            $assessment->setIncome($incomeData);

            // SCORE CALCULATION
            $totalScore =
                array_sum($motivationData) +
                array_sum($householdData) +
                array_sum($incomeData);

            $assessment->setTotalScore($totalScore);

            if ($totalScore >= 60) {
                $assessment->setRecommendedStatus("APPROVED");
            } elseif ($totalScore >= 30) {
                $assessment->setRecommendedStatus("WAITLIST");
            } else {
                $assessment->setRecommendedStatus("SHORTLIST");
            }

            if ($assessment->getTotalScore() < 60) {
                $application->setAssesmentResult('Rejection (Failed Interview)');
            } elseif ($assessment->getTotalScore() >= 60) {
                $application->setAssesmentResult('Passed Interview (Exam)');
            } else {
                $application->setAssesmentResult('Approved (Passed Interview)');
            }

            $em->flush();
            $this->addFlash('success', 'Assessment created successfully.');

            return $this->redirectToRoute('app_admin_kaaba_applications');
        }

        return $this->render('admin/assessment_form.html.twig', [
            'application' => $application,
            'form' => $form->createView(),
            'assessment' => $assessment
        ]);
    }


    #[Route('/application/{uuid}/assessment/edit', name: 'app_assessment_edit')]
    public function editAssessment(
        KaabaApplication $application,
        Request $request,
        EntityManagerInterface $em
    ): Response {

        $assessment = $application->getAssessment();

        if (!$assessment) {
            $assessment = new KaabaAssessment();
            $assessment->setApplication($application);
            $em->persist($assessment);
        }

        // Create form
        $form = $this->createForm(KaabaAssessmentType::class, $assessment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // dd($form->getData());
            /**
             * Extract submitted values (mapped = false)
             */
            $submittedMotivation = [
                'employment_status' => $form->get('employment_status')->getData(),
                'training_enrolment' => $form->get('training_enrolment')->getData(),
                'reason_for_applying' => $form->get('reason_for_applying')->getData(),
                'career_aspirations' => $form->get('career_aspirations')->getData(),
                'community_participation' => $form->get('community_participation')->getData(),
            ];

            $submittedHousehold = [
                'housing_situation' => $form->get('housing_situation')->getData(),
                'household_members' => $form->get('household_members')->getData(),
                'decision_maker' => $form->get('decision_maker')->getData(),
                'dependency_ratio' => $form->get('dependency_ratio')->getData(),
                'household_circumstances' => $form->get('household_circumstances')->getData(),
            ];

            $submittedIncome = [
                'breadwinner_occupation' => $form->get('breadwinner_occupation')->getData(),
                'income_sources' => $form->get('income_sources')->getData(),
                'monthly_income' => $form->get('monthly_income')->getData(),
                'household_savings' => $form->get('household_savings')->getData(),
                'financial_vulnerability' => $form->get('financial_vulnerability')->getData(),
            ];

            /**
             * LOG CHANGES BEFORE SAVING
             */
            $changes = [];

            // Compare motivation
            foreach ($submittedMotivation as $key => $value) {
                $old = $assessment->getMotivation()[$key] ?? null;
                if ($old !== $value) {
                    $changes[] = "$key changed from $old to $value";
                }
            }

            // Compare household
            foreach ($submittedHousehold as $key => $value) {
                $old = $assessment->getHousehold()[$key] ?? null;
                if ($old !== $value) {
                    $changes[] = "$key changed from $old to $value";
                }
            }

            // Compare income
            foreach ($submittedIncome as $key => $value) {
                $old = $assessment->getIncome()[$key] ?? null;
                if ($old !== $value) {
                    $changes[] = "$key changed from $old to $value";
                }
            }

            if ($assessment->getInterviewerName() !== $form->get('interviewerName')->getData()) {
                $old = $assessment->getInterviewerName();
                $new = $form->get('interviewerName')->getData();
                $changes[] = "interviewerName changed from \"$old\" to \"$new\"";
            }

            if (!empty($changes)) {
                $log = new KaabaAssessmentLog();
                $log->setAssessment($assessment);
                $log->setAction("Assessment Updated");
                $log->setNote(implode("\n", $changes));
                $log->setUser($this->getUser());

                $assessment->addAssessmentLog($log);
                $em->persist($log);
            }

            // Save new values
            $assessment->setMotivation($submittedMotivation);
            $assessment->setHousehold($submittedHousehold);
            $assessment->setIncome($submittedIncome);

            // Recalculate
            $assessment->calculateTotalScore();

            if ($assessment->getTotalScore() >= 60) {
                $assessment->setRecommendedStatus('APPROVED');
            } elseif ($assessment->getTotalScore() >= 30) {
                $assessment->setRecommendedStatus('WAITLIST');
            } else {
                $assessment->setRecommendedStatus('SHORTLIST');
            }
            if ($assessment->getTotalScore() < 60) {
                $application->setAssesmentResult('Rejection (Failed Interview)');
            } elseif ($assessment->getTotalScore() >= 60) {
                $application->setAssesmentResult('Passed Interview (Exam)');
            } else {
                $application->setAssesmentResult('Approved (Passed Interview)');
            }

            $em->flush();
            $this->addFlash('success', 'Assessment updated successfully.');
            return $this->redirectToRoute('app_assessment_edit', [
                'uuid' => $application->getUuid()
            ]);
        }

        return $this->render('admin/assessment_edit.html.twig', [
            'application' => $application,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/application/{uuid}/exam', name: 'app_application_exam')]
    public function exam(
        KaabaApplication $application,
        Request $request,
        FileUploader $fileUploader,
        EntityManagerInterface $em
    ) {
        $exam = $application->getExam();

        if (!$exam) {
            $exam = new KaabaApplicationExam();
            $exam->setApplication($application);
            $em->persist($exam);
        }

        $form = $this->createForm(KaabaApplicationExamType::class, $exam);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile|null $attachment */
            $attachment = $form->get('attachment')->getData();


            $examResult = $form->get('examResult')->getData();
            $application->setExamResult($examResult);

            if ($attachment) {
                $fileName = $fileUploader->upload(
                    $attachment,
                    $this->getParameter('application_exam_attachments')
                );
                $exam->setAttachment($fileName);
            }

            $em->flush();

            $this->addFlash('success', 'Exam result saved successfully.');

            return $this->redirectToRoute('app_application_exam', [
                'uuid' => $application->getUuid()
            ]);
        }

        return $this->render('admin/application_exam_form.html.twig', [
            'application' => $application,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/bulk-sms', name: 'app_admin_bulk_sms', methods: ['GET', 'POST'])]
    public function bulkSmsForm(
        Request $request,
        EntityManagerInterface $em
    ): Response {

        $user = $this->getUser();

        // =========================
        // INSTITUTES BY ROLE
        // =========================
        if (
            $user
            && in_array('ROLE_USER', $user->getRoles(), true)
            && !in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)
        ) {
            // Only institutes managed by this user
            $institutes = $em->getRepository(KaabaInstitute::class)
                ->findBy(['manager' => $user]);
        } else {
            // Admin / Super admin
            $institutes = $em->getRepository(KaabaInstitute::class)->findAll();
        }

        $statuses = $em->getRepository(KaabaApplicationStatus::class)->findAll();

        // =========================
        // PREFILL (EDIT SUPPORT)
        // =========================
        $prefillStatuses = $request->query->get('s')
            ? json_decode($request->query->get('s'), true)
            : [];

        $prefillInstitutes = $request->query->get('i')
            ? json_decode($request->query->get('i'), true)
            : [];

        $prefillMessage = $request->query->get('m') ?? '';

        // =========================
        // HANDLE POST
        // =========================
        if ($request->isMethod('POST')) {

            $statusIds = $request->request->all('status');
            $instituteIds = $request->request->all('institute');
            $message = $request->request->get('message');

            $qb = $em->getRepository(KaabaApplication::class)
                ->createQueryBuilder('a')
                ->join('a.institute', 'i');

            // ---- STATUS FILTER
            if (!empty($statusIds)) {
                $qb->andWhere('a.status IN (:st)')
                    ->setParameter('st', $statusIds);
            }

            // ---- INSTITUTE FILTER (ROLE-AWARE)
            if (!empty($instituteIds)) {
                // User selected institutes → trust but still safe
                $qb->andWhere('i.id IN (:ins)')
                    ->setParameter('ins', $instituteIds);
            } else {
                // No institute selected
                // If ROLE_USER → restrict to managed institutes
                if (
                    $user
                    && in_array('ROLE_USER', $user->getRoles(), true)
                    && !in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)
                ) {
                    $managedInstituteIds = array_map(
                        fn($inst) => $inst->getId(),
                        $institutes
                    );

                    // Safety: if user manages nothing, return empty
                    if (empty($managedInstituteIds)) {
                        $apps = [];
                    } else {
                        $qb->andWhere('i.id IN (:managedIns)')
                            ->setParameter('managedIns', $managedInstituteIds);
                    }
                }
            }

            $apps = $qb->getQuery()->getResult();

            return $this->render('admin/bulk_sms_confirm.html.twig', [
                'count' => count($apps),
                'message' => $message,
                'statuses' => $statusIds,
                'institutes' => $instituteIds,
            ]);
        }

        // =========================
        // RENDER FORM
        // =========================
        return $this->render('admin/bulk_sms_form.html.twig', [
            'statuses' => $statuses,
            'institutes' => $institutes,
            'selectedStatuses' => $prefillStatuses,
            'selectedInstitutes' => $prefillInstitutes,
            'prefillMessage' => $prefillMessage,
        ]);
    }


    #[Route('/bulk-sms/individual', name: 'app_admin_bulk_sms_individual', methods: ['GET', 'POST'])]
    public function individual_sms(
        Request $request,
        TelesomSmsService $smsService,
        EntityManagerInterface $em
    ): Response {

        if ($request->isMethod('POST')) {

            $phonesRaw = $request->request->get('phones');
            $messageTemplate = $request->request->get('message');

            $phones = array_filter(array_map('trim', explode(',', $phonesRaw)));

            $results = [];
            $sentCount = 0;

            foreach ($phones as $index => $phone) {

                if (!$phone) {
                    continue;
                }

                // Fake name for testing
                $name = 'Test User ' . ($index + 1);

                $message = str_replace('{{name}}', $name, $messageTemplate);

                usleep(400000); // prevent gateway blocking

                $sendResults = $smsService->sendBulk($phone, $message);

                $status = $sendResults[0]['status'] ?? 'unknown';
                $response = $sendResults[0]['body'] ?? 'no response';

                $results[] = [
                    'phone' => $phone,
                    'name' => $name,
                    'status' => $status,
                    'response' => $response,
                ];

                // OPTIONAL: Log it
                $log = new KaabaSmsLog();
                $log->setReceiverName($name);
                $log->setCreatedBy($this->getUser());
                $log->setPhoneNumber($phone);
                $log->setMessage($message);
                $log->setMessageStatus($status);
                $log->setGatewayResponse($response);
                $log->setFilteredStatuses([]);
                $log->setFilteredInstitutes([]);

                $em->persist($log);

                $sentCount++;
            }

            $em->flush();

            return $this->render('admin/bulk_sms_test_result.html.twig', [
                'results' => $results,
                'sentCount' => $sentCount,
            ]);
        }

        return $this->render('admin/bulk_sms_test_form.html.twig');
    }


    #[Route('/bulk-sms/send', name: 'app_admin_bulk_sms_send', methods: ['POST'])]
    public function sendBulkSms(
        Request $request,
        EntityManagerInterface $em,
        TelesomSmsService $smsService
    ): Response {

        dd("Debugging point");
        $statusIds = json_decode($request->request->get('statuses'), true);
        $instituteIds = json_decode($request->request->get('institutes'), true);
        $messageTemplate = $request->request->get('message');

        $qb = $em->getRepository(KaabaApplication::class)->createQueryBuilder('a');

        if (!empty($statusIds)) {
            $qb->andWhere('a.status IN (:st)')->setParameter('st', $statusIds);
        }

        if (!empty($instituteIds)) {
            $qb->andWhere('a.institute IN (:ins)')->setParameter('ins', $instituteIds);
        }

        $apps = $qb->getQuery()->getResult();
        $sentCount = 0;

        foreach ($apps as $app) {

            $name = $app->getFullName() ?? "Applicant";
            $phone = $app->getPhone();

            if (!$phone) {
                continue;
            }

            $message = str_replace('{{name}}', $name, $messageTemplate);
            usleep(400000); // prevent Telesom gateway block

            $sendResults = $smsService->sendBulk($phone, $message);
            $status = $sendResults[0]['status'] ?? 'unknown';
            $response = $sendResults[0]['body'] ?? 'no response';

            // Save SMS Log
            $log = new KaabaSmsLog();
             $log->setCreatedBy($this->getUser());
            $log->setApplication($app);
            $log->setReceiverName($name);
            $log->setPhoneNumber($phone);
            $log->setMessage($message);
            $log->setFilteredStatuses($statusIds);
            $log->setFilteredInstitutes($instituteIds);
            $log->setMessageStatus($status);
            $log->setGatewayResponse($response);

            $em->persist($log);
            $sentCount++;
        }

        $em->flush();

        // SUCCESS FLASH
        $this->addFlash('success', sprintf(
            "%d SMS messages have been successfully sent and logged.",
            $sentCount
        ));

        // REDIRECT DIRECTLY TO LOGS PAGE
        return $this->redirectToRoute('app_admin_sms_logs');
    }
    #[Route('/bulk-sms/send/debug', name: 'app_admin_bulk_sms_send', methods: ['POST'])]
    public function sendBulkSms_debug(
        Request $request,
        EntityManagerInterface $em,
        TelesomSmsService $smsService
    ): Response {

        $statusIds = json_decode($request->request->get('statuses'), true);
        $instituteIds = json_decode($request->request->get('institutes'), true);
        $messageTemplate = $request->request->get('message');

        $qb = $em->getRepository(KaabaApplication::class)->createQueryBuilder('a');

        if (!empty($statusIds)) {
            $qb->andWhere('a.status IN (:st)')->setParameter('st', $statusIds);
        }

        if (!empty($instituteIds)) {
            $qb->andWhere('a.institute IN (:ins)')->setParameter('ins', $instituteIds);
        }

        $apps = $qb->getQuery()->getResult();
        $sentCount = 0;

        foreach ($apps as $app) {

            $name = $app->getFullName() ?? "Applicant";
            $phone = $app->getPhone();

            if (!$phone) {
                continue;
            }

            $message = str_replace('{{name}}', $name, $messageTemplate);
            usleep(400000); // prevent Telesom gateway block

            $sendResults = $smsService->sendBulk('063407225', $message);
            $status = $sendResults[0]['status'] ?? 'unknown';
            $response = $sendResults[0]['body'] ?? 'no response';

            // Save SMS Log
            $log = new KaabaSmsLog();
             $log->setCreatedBy($this->getUser());
            $log->setApplication($app);
            $log->setReceiverName($name);
            $log->setPhoneNumber($phone);
            $log->setMessage($message);
            $log->setFilteredStatuses($statusIds);
            $log->setFilteredInstitutes($instituteIds);
            $log->setMessageStatus($status);
            $log->setGatewayResponse($response);

            $em->persist($log);
            $sentCount++;
        }

        $em->flush();

        // SUCCESS FLASH
        $this->addFlash('success', sprintf(
            "%d SMS messages have been successfully sent and logged.",
            $sentCount
        ));

        // REDIRECT DIRECTLY TO LOGS PAGE
        return $this->redirectToRoute('app_admin_sms_logs');
    }


    #[Route('/sms-logs', name: 'app_admin_sms_logs')]
    public function smsLogs(EntityManagerInterface $em): Response
    {
        $logs = $em->getRepository(KaabaSmsLog::class)
            ->findBy([], ['id' => 'DESC']);

        return $this->render('admin/sms_logs.html.twig', [
            'logs' => $logs
        ]);
    }








}

