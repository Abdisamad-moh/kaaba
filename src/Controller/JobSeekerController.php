<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use DateTimeImmutable;
use Stripe\StripeClient;
use App\Entity\JobReport;
use App\Entity\MetierOrder;
use App\Entity\EmployerJobs;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Entity\MetierGender;
use App\Entity\MetierSkills;
use App\Event\MyCustomEvent;
use App\Service\MailService;
use Stripe\Checkout\Session;
use App\Entity\MetierCareers;
use App\Entity\MetierCountry;
use App\Entity\MetierJobType;
use App\Event\SendEmailEvent;
use App\Form\MealPlannerForm;
use App\Service\FileUploader;
use App\Service\OrderService;
use App\Entity\EmployerTender;
use App\Entity\JobApplication;
use App\Entity\MetierPackages;
use App\Entity\EmployerCourses;
use App\Entity\JobSeekerResume;
use App\Entity\MetierDownloads;
use App\Form\JobSeekerInfoType;
use App\Model\ResumeStatusEnum;
use App\Service\PaymentService;
use Symfony\Component\Uid\Uuid;
use App\Entity\CourseApplication;
use App\Entity\JobSeekerJobAlert;
use App\Entity\JobSeekerLanguage;
use App\Entity\JobSeekerSavedJob;
use App\Entity\MetierJobCategory;
use App\Entity\MetierProfileView;
use App\Entity\TenderApplication;
use App\Form\JobSeekerResumeType;
use App\Entity\JobSeekerEducation;
use App\Entity\MetierOrderPayment;
use App\Entity\MetierServiceOrder;
use App\Form\JobSeekerDetailsType;
use App\Entity\JobSeekerExperience;
use App\Form\CityAutocompleteField;
use App\Form\EmailTemplateFormType;
use App\Entity\JobSeekerCertificate;
use App\Form\StateAutoCompleteField;
use App\Service\NotificationService;
use App\Form\CareerAutoCompleteField;
use App\Form\JobSeekerEducationsType;
use App\Form\JobSeekerExperienceType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use App\Form\CountryAutoCompleteField;
use App\Form\JobSeekerCertificateType;
use Symfony\Component\Form\FormEvents;
use App\Form\JobSeekerPsReportFormType;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\EmployerJobQuestionAnswer;
use App\Entity\EmployerStaff;
use Symfony\Component\Form\FormInterface;
use Flasher\Prime\Storage\StorageInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\JobApplicationQuestionAnswer;
use App\Entity\MetierDownloadable;
use App\Repository\MetierPackagesRepository;
use Symfony\Component\HttpClient\HttpClient;
use App\Repository\MetierDownloadsRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\MetierEmailTempsRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\JobSeekerJobAlertRepository;
use App\Repository\MetierNotificationRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Form\DataTransformer\CityToNameTransformer;
use Symfony\Component\Validator\Constraints\IsTrue;
use App\Form\DataTransformer\StateToNameTransformer;
use App\Repository\EmployerStaffRepository;
use App\Repository\MetierDownloadableRepository;
use App\Repository\UserRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard\Number;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File as HttpFoundationFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/jobseeker')]
class JobSeekerController extends AbstractController
{
    private UrlGeneratorInterface $generator;
    private $serializer;
    private $params;
    private $stripeGateway;
    private $em;
    private $eventDispatcher;

    public function __construct(
        $stripSK,
        EventDispatcherInterface $eventDispatcher,
        EntityManagerInterface $em,
        ParameterBagInterface $params,
        private HttpClientInterface $client,
        UrlGeneratorInterface $generator,
        SerializerInterface $serializer
    ) {
        $this->generator = $generator;
        $this->serializer = $serializer;
        $this->params = $params;
        $this->em = $em;
        $this->stripeGateway = new StripeClient($stripSK);

        $this->eventDispatcher = $eventDispatcher;
    }


    #[Route('/', name: 'app_job_seeker')]
    public function index(): Response
    {

        return $this->redirectToRoute('app_home');
    }

    #[Route('/notifications', name: 'app_notifications_list')]
    public function notifications(MetierNotificationRepository $notificationRepository): Response
    {

        $nots = $notificationRepository->findLatestByUser($this->getUser());

        return $this->render('job_seeker/notifications.html.twig', [
            'nots' => $nots,
        ]);
    }
    #[Route('/jobalert', name: 'app_jobalert_set')]
    public function jobalert(
        JobSeekerJobAlertRepository $alerts,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        $user = $this->getUser();
        $activeSubscription = $userRepository->findActiveSubscription($user);
        $alert = $alerts->findOneBy(['jobseeker' => $user]);
        if (!$alert) {
            $alert = new JobSeekerJobAlert();
            $alert->setJobseeker($user);
        }
        $form = $this->createFormBuilder($alert)
            ->add('jobcategory', EntityType::class, [
                'class' => MetierJobCategory::class,
                'choice_label' => 'name',
                'multiple' => true,
                'autocomplete' => true,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('phone', NumberType::class, [
                'attr' => [
                    'class' => 'form-control'
                ]
            ])

            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            if(!$activeSubscription){
                $this->addFlash('danger', "This feature is not part of your plan. Please click here to upgrade now.");
            return $this->redirectToRoute('app_jobalert_set');
            }
            $em->persist($alert);
            $em->flush();
            $this->addFlash('success', "Successfully updated job alert");
            return $this->redirectToRoute('app_home');
        }

        return $this->render('job_seeker/jobalert.html.twig', [
            'form' => $form,
            'active' => $activeSubscription,
        ]);
    }
    #[Route('/profile', name: 'app_job_seeker_profile')]
    public function profile(
        Request $request,
        FileUploader $fileUploader,
        EntityManagerInterface $em,
        NotificationService $notificationService,
        UserRepository $userRepository,
        EmployerStaffRepository $staffs,
    ): Response {
        $user = $userRepository->find($this->getUser());

            // Check if user has job alerts set up
            if ($user && $this->isGranted('ROLE_JOBSEEKER')) {
            if ($user->getJobalerts()->isEmpty()) {
                // notyf()->warning('Your account may not have been restored.');
                // dd("yes");
                notyf()
                ->duration(10000)
                ->ripple(true)
                ->addWarning(
                    'Reminder! <a href="'.$this->generateUrl('app_jobalert_set').'" 
                    style="color: black; text-decoration: underline;">Click here</a> 
                    to choose and receive job alerts for new opportunities that match your preferences, along with interview updates via SMS and email.',
                    ['escapeMarkup' => false]
                );
                // $notyf->using('notyf')->addFlash(
                //     'warning', // or 'error', 'success', 'info'
                //     'Please <a href="'.$this->generateUrl('app_jobalert_set').'" 
                //     style="color: white; text-decoration: underline;">create job alerts</a> 
                //     to get notified about new jobs matching your preferences.',
                //     ['escapeMarkup' => false]
                // );
            }
            }
        $staff = $staffs->findOneBy(['jobseeker' => $user]);
        $countPrivateJobs = 0;

        if ($staff) {
            $employer = $staff->getEmployer();
            // dump($employer->getPrivateEmployerJobs());
            $countPrivateJobs = count($employer->getPrivateEmployerJobs());
        }
        // dump($staff);
        // dd($this->getUser()->getEmployerStaff());
        $job_seeker_details = $this->getUser()->getJobseekerDetails();
        $infoForm = $this->createFormBuilder($job_seeker_details)
            ->add('dob', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('name', TextType::class, [
                'required' => true,
                'mapped' => false,
                'data' => $this->getUser()->getName()
            ])
            ->add('phone')
            ->add('whatsappPhone')
            ->add('location')
            ->add('country', CountryAutoCompleteField::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-select country_input'
                ],
            ])
            ->add('state', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-select state_input'
                ]
            ])
            ->add('city', TextType::class, [
                'attr' => [
                    'class' => 'form-select city_input'
                ],
            ])
            ->add('gender', EntityType::class, [
                'placeholder' => 'Select Gender',
                'class' => MetierGender::class,
                'choice_label' => 'name',

            ])
            ->add('zipCode', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ]);

        $infoForm->get('city')->addModelTransformer(new CityToNameTransformer($em));
        $infoForm->get('state')->addModelTransformer(new StateToNameTransformer($em));

        $infoForm = $infoForm->getForm();

        $infoForm->handleRequest($request);

        if ($infoForm->isSubmitted() && $infoForm->isValid()) {
            // dd($request);
            $user = $this->getUser();

            $em->persist($user);
            $em->persist($job_seeker_details);
            $em->flush();
            // return $this->redirectToRoute('app_job_seeker', ['infoForm' => $infoForm]);
            // return $this->redirect($request->getUri(), 303);
        }

        // $about_work_details = $this->getUser()->getJobseekerDetails();
        $aboutWorkForm = $this->createFormBuilder()
        ->add('profession', CareerAutoCompleteField::class, [
            'data' => $this->getUser()->getJobseekerDetails() ? $this->getUser()->getJobseekerDetails()->getProfession() : null
        ])
        ->add('experience', TextType::class, [
            'required' => false,
            'data' => $this->getUser()->getJobseekerDetails() ? $this->getUser()->getJobseekerDetails()->getExperience() : null
        ])
        ->add('salary', NumberType::class, [
            'required' => false,
            'data' => $this->getUser()->getJobseekerDetails() ? $this->getUser()->getJobseekerDetails()->getSalary() : null
        ]);
    

        $aboutWorkForm = $aboutWorkForm->getForm();

        $aboutWorkForm->handleRequest($request);

        if ($aboutWorkForm->isSubmitted() && $aboutWorkForm->isValid()) {
            $form_data = $aboutWorkForm->getData();

            $user_details = $this->getUser()->getJobseekerDetails();
            $user_details->setExperience($form_data['experience']);
            $user_details->setProfession($form_data['profession']);
            $user_details->setSalary($form_data['salary']);

            $em->persist($user_details);
            $em->flush();

            $em->refresh($user_details);
            // return $this->redirectToRoute('app_job_seeker', ['infoForm' => $infoForm]);
            return $this->redirect($request->getUri(), 303);
        }



        $saved_jobs = $em->getRepository(JobSeekerSavedJob::class)
            ->createQueryBuilder('s')
            ->join('s.job', 'j')
            ->where('s.jobSeeker = :jobSeeker')
            ->setParameter('jobSeeker', $this->getUser())
            ->select('sum(j.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $profile_resume = $this->getUser()->getJobSeekerResume() ?? new JobSeekerResume();
        $resume_form = $this->createForm(JobSeekerResumeType::class, $profile_resume);

        $user_skills = $this->getUser()->getJobseekerResume()?->getSkills()->getValues() ?? [];
        $job_seeker_skills = array_map(function ($user_skill) {
            return ['value' => $user_skill->getId(), 'text' => $user_skill->getName()];
        }, $user_skills);

        // $user_career_skills = 

        $user_career_skills = $profile_resume?->getJobTitle()?->getSkills()->getValues() ?? [];

        $user_career_skills = array_map(function ($skill) {
            return ['value' => $skill->getId(), 'text' => $skill->getName()];
        }, $user_career_skills);

        $psReportForm = $this->createForm(JobSeekerPsReportFormType::class, $profile_resume, [
            'action' => $this->generateUrl('app_job_seeker_profile'),
            'method' => 'POST', // or 'GET' depending on your requirements
        ]);

        $resume_form->handleRequest($request);
        $psReportForm->handleRequest($request);

        if ($resume_form->isSubmitted() && $resume_form->isValid()) {

            if ($profile_resume->getId() == null) $profile_resume->setJobSeeker($this->getUser());

            $skills_array = explode(',', $resume_form->get('skills')->getData());

            $skills_objects = [];
            foreach ($skills_array as $skill) {
                if (is_numeric($skill)) {
                    $skill = $em->getRepository(MetierSkills::class)->find($skill);
                    if ($skill) $skills_objects[] = $skill;
                } else {
                    $skill_obj = new MetierSkills();
                    $skill_obj->setName($skill);
                    $skill_obj->setCustom(true);
                    $em->persist($skill_obj);
                    $skills_objects[] = $skill_obj;
                }
            }

            $em->flush();

            $profile_resume->getSkills()->clear();

            foreach ($skills_objects as $skill) {
                $profile_resume->addSkill($skill);
            }

            $em->persist($profile_resume);
            $em->flush();

            return $this->redirectToRoute('app_job_seeker_profile');
        }
        if ($psReportForm->isSubmitted()) {
            if ($psReportForm->isValid()) {
                if ($profile_resume->getId() == null) $profile_resume->setJobSeeker($this->getUser());
                $sreportFile = $psReportForm->get('s_report')->getData();
                if ($sreportFile !== $profile_resume->getSreport()) {
                    $originalFilename = $fileUploader->upload($sreportFile, $this->getParameter('ps_report_directory'));
                    $profile_resume->setSreport($originalFilename);
                }
                $em->persist($profile_resume);
                $em->flush();
            } else {
                $this->addFlash('error', "Can not upload file");
            }
        }

        $profile_percentage = $this->profile_percentage($em);
        $showAlert = $request->getSession()->get('profile_notification_shown', false);

        if ($profile_percentage['percentage'] < 90 && $request->getSession()->get('profile_notification_shown', false) == false) {
            $request->getSession()->set('profile_notification_shown', true);
            $showAlert = true;
            // dd('yeey');
        } else {
            $showAlert = false;
        }

        $job_seeker_details = $this->getUser()->getJobseekerDetails();
        $careerStatusForm = $this->createFormBuilder($job_seeker_details)
            ->add('careerStatus', ChoiceType::class, [
                'placeholder' => 'Choose career status',
                'choices' => ResumeStatusEnum::cases(),
                'choice_label' => fn(ResumeStatusEnum $status) => $status->value,
                'choice_value' => fn(?ResumeStatusEnum $status) => $status?->value,
                'attr' => [
                    'class' => 'form-select'
                ]
            ]);

        $careerStatusForm = $careerStatusForm->getForm();
        // $careerStatusForm->handleRequest($request);

        // dd($showAlert);
        return $this->render('job_seeker/index.html.twig', [
            'controller_name' => 'JobSeekerController',
            'infoForm' => $infoForm,
            'aboutWorkForm' => $aboutWorkForm,
            'saved_jobs' => $saved_jobs,
            'resume_form' => $resume_form,
            'profile_percentage' => $profile_percentage,
            'psReportForm' => $psReportForm,
            'job_seeker_skills' => $job_seeker_skills,
            'user_career_skills' => $user_career_skills,
            'showAlert' => $showAlert,
            'careerStatusForm' => $careerStatusForm,
            'countPrivateJobs' => $countPrivateJobs
        ]);
    }

    #[Route('/fetch_skills', name: 'fetch_careers')]
    public function fetchOptions(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $searchTerm = $request->query->get('search', '');
        $careerId = $request->query->get('career', null);
        $limit = $request->query->getInt('limit', 20);

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

    #[Route('/save_about_work_form', name: 'save_about_work_form')]
    public function save_about_work_form(Request $request, EntityManagerInterface $em)
    {
        $details = $this->getUser()->getJobseekerDetails();
        $profession = $em->getRepository(MetierCareers::class)->find($request->get('form')['profession']);

        $details->setExperience($request->get('form')['experience']);
        $details->setSalary($request->get('form')['salary']);
        $details->setProfession($profession);
        $em->persist($details);
        $em->flush();

        $em->refresh($details);

        return $this->redirectToRoute('app_job_seeker_profile');
    }

    #[Route('/save_resume_headline_form', name: 'save_resume_headline_form')]
    public function save_resume_headline_form(Request $request, EntityManagerInterface $em)
    {
        $details = $this->getUser()->getJobseekerDetails();

        $details->setResumeHeadline($request->get('resume_headline'));
        $em->persist($details);
        $em->flush();

        $em->refresh($details);

        $url = $this->generateUrl('app_job_seeker_profile', [], UrlGeneratorInterface::ABSOLUTE_URL) . '#resume_title';
        return new RedirectResponse($url);
    }

    #[Route('/save_about_me_form', name: 'save_about_me_form')]
    public function save_about_me_form(Request $request, EntityManagerInterface $em)
    {
        $details = $this->getUser()->getJobseekerDetails();
        $about_me = $request->get('about_me');

        $details->setAboutMe($about_me);
        $em->persist($details);
        $em->flush();

        $em->refresh($details);

        return $this->redirectToRoute('app_job_seeker_profile');
    }

    #[Route('/add_job_seeker_experience', name: 'add_job_seeker_experience')]
    public function add_job_seeker_experience(Request $request, EntityManagerInterface $em)
    {
        $experience = $request->get('experience');
        $experience = $experience == null ? new JobSeekerExperience() : $em->getRepository(JobSeekerExperience::class)->find($experience);

        $form = $this->createFormBuilder($experience)
            ->add('current', ChoiceType::class, [
                'choices' => [
                    'Yes' => true,
                    'No' => false
                ],
                'expanded' => true,
                'multiple' => false,
                'attr' => [
                    'class' => 'radio-button'
                ]
            ])
            ->add('jobType', EntityType::class, [
                'class' => MetierJobType::class,
                'placeholder' => 'Select',
                'choice_label' => 'name',
                'multiple' => false,
                'autocomplete' => true
            ])
            ->add('experienceYears', NumberType::class, [])
            ->add('experienceMonths', NumberType::class, [])
            ->add('currentCompany', TextType::class, [])
            ->add('joinedYear', NumberType::class, [])
            ->add('joinedMonth', NumberType::class, [])
            ->add('salary', NumberType::class, [])
            ->add('noticePeriod', TextType::class, []);

        $form = $form->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $experience->setJobSeeker($this->getUser());
            $em->persist($experience);
            $em->flush();

            // return $this->render('/');
        }

        return $this->render('job_seeker/add-experience.html.twig', compact('form'));
    }

    #[Route('/job_details/{slug}/{uuid}', name: 'job_seeker_job_details')]
    public function job_details(
        Request $request, 
        SluggerInterface $slugger, 
        EntityManagerInterface $em, 
        EmployerJobs $job, 
        RequestStack $requestStack,
        $slug)
    {
        $generatedSlug = $slugger->slug($job->getTitle())->lower()->toString();
        $referer = $requestStack->getCurrentRequest()->headers->get('referer');

        // If the provided slug doesn't match the generated one, redirect to the correct URL
        if ($slug !== $generatedSlug) {
            return $this->redirectToRoute('job_seeker_job_details', [
                'slug' => $generatedSlug,
                'uuid' => $job->getUuid(),
            ], 301);  // 301 for permanent redirection
        }

        $saved_jobs = $em->getRepository(JobSeekerSavedJob::class)
            ->createQueryBuilder('s')
            ->join('s.job', 'j')
            ->where('s.jobSeeker = :jobSeeker')
            ->setParameter('jobSeeker', $this->getUser())
            ->select('j.id')
            ->getQuery()
            ->getResult();

        $qb = $em->getRepository(EmployerJobs::class)->createQueryBuilder('j');
        $similar_jobs = $qb
            ->andWhere('j.status = :status')
            ->setParameter('status', 'posted')
            ->andWhere('j.application_closing_date >= :dateNow') // Filter based on expiry
            ->setParameter('dateNow', new DateTime())
            ->leftJoin('j.job_category', 'c')
            ->leftJoin('j.industry', 'i')
            ->leftJoin('j.city', 'ct')
            ->andWhere('j.id != :thisJob')
            ->setParameter('thisJob', $job->getId())
            ->andWhere(
                $qb->expr()->orX(
                    'j.title LIKE :title',
                    'c.id = :categoryId',  // Match category
                    // 'i.id = :industryId',  // Match industry
                    'j.employer = :employer',
                    'ct.id = :cityId'      // Match city
                )
            )
            ->setParameter('categoryId', $job->getJobCategory()->getId())
            // ->setParameter('industryId', $job->getIndustry()->getId())
            ->setParameter('employer', $job->getEmployer())
            ->setParameter('cityId', $job->getCity()->getId())
            ->setParameter('title', '%' . $job->getTitle() . '%')
            ->setMaxResults(12)
            ->orderBy('j.id', 'DESC')
            ->getQuery()
            ->getResult();

        $saved_jobs = array_column($saved_jobs, 'id');
        $profile_percentage = $this->profile_percentage($em);
        if ($profile_percentage['percentage'] < 90 and $this->getUser()) {
            // sweetalert()->warning('Please complete your profile info to be abe to apply for this job', [], 'Warning');
        }

        $job_application = $em->getRepository(JobApplication::class)->findOneBy(['jobSeeker' => $this->getUser(), 'job' => $job]);
        $job_report = $em->getRepository(JobReport::class)->findOneBy(['job' => $job, 'reportedBy' => $this->getUser()]);


        if ($job->getIsPrivate()) {
            $user = $this->getUser();


            if (!$user) {
                $this->addFlash('success', 'You must be logged in to view this private job.');
                return new RedirectResponse($referer);
            }

            $employer = $job->getEmployer();
            $existingStaff = $em->getRepository(EmployerStaff::class)->findOneBy([
                'jobseeker' => $user->getId(),
                'employer'  => $employer->getId(),
            ]);

            if (!$existingStaff) {
                $this->addFlash('success', 'You are not authorized to view this private job.');
                return new RedirectResponse($referer);
            }
        }
        return $this->render('job_seeker/job-details.html.twig', compact('job', 'similar_jobs', 'job_application', 'saved_jobs', 'profile_percentage', 'job_report'));
    }

    public function profile_percentage(EntityManagerInterface $em): array
    {
        $profile_percentage = 0;
        $details = [];
    
        if ($this->getUser() && $this->isGranted('ROLE_JOBSEEKER')) {
            $job_seeker_details = $this->getUser()->getJobseekerDetails();
            $profile_resume = $this->getUser()->getJobSeekerResume();
    
            $profile_percentage = 15;
    
            // Check if $job_seeker_details is not null before accessing its methods
            if ($job_seeker_details) {
                if ($job_seeker_details->getCv()) {
                    $profile_percentage += 10.62;
                } else {
                    array_push($details, 'Upload your updated CV/Resume.');
                }
    
                if ($em->getRepository(JobSeekerExperience::class)->findOneBy(['jobSeeker' => $this->getUser()])) {
                    $profile_percentage += 10.62;
                } else {
                    array_push($details, 'Provide at least one work experience record, even if it is an internship.');
                }
    
                if ($em->getRepository(JobSeekerLanguage::class)->findOneBy(['jobSeeker' => $this->getUser()])) {
                    $profile_percentage += 10.62;
                } else {
                    array_push($details, 'List all languages you speak, including any in which you have proficiency.');
                }
    
                if ($job_seeker_details->getContactType()) {
                    $profile_percentage += 10.62;
                } else {
                    array_push($details, 'Indicate how you would like to be contacted.');
                }
            } else {
                array_push($details, 'Please complete your job seeker details.');
            }
    
            // Check if profile_resume is not null before accessing its methods
            if ($profile_resume) {
                if ($profile_resume->getId() && $profile_resume->isWillingToRelocate() !== null) {
                    $profile_percentage += 10.62;
                } else {
                    array_push($details, 'Specify if you are willing to relocate.');
                }
    
                $profile_resume_message = '';
    
                if ($profile_resume->getId() && $profile_resume->getExperience() != null) {
                    $profile_percentage += 10.62;
                } else {
                    $profile_resume_message = 'Fill out all mandatory information in the “Profile Resume” section.';
                }
    
                if ($profile_resume->getId() && count($profile_resume->getSkills()) > 0) {
                    $profile_percentage += 10.62;
                } else {
                    $profile_resume_message = 'Fill out all mandatory information in the “Profile Resume” section.';
                }
    
                if ($profile_resume->getId() && $profile_resume->getJobTitle() != null) {
                    $profile_percentage += 10.62;
                } else {
                    $profile_resume_message = 'Fill out all mandatory information in the “Profile Resume” section.';
                }
    
                if ($profile_resume_message != null) {
                    array_push($details, $profile_resume_message);
                }
            } else {
                array_push($details, 'Please complete your profile resume.');
            }
        }
    
        return ['percentage' => $profile_percentage, 'details' => $details];
    }
    
    #[Route('/tender_details/{slug}/{uuid}', name: 'job_seeker_tender_details')]
    public function tender_details(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, $slug, EmployerTender $tender)
    {
        $generatedSlug = $slugger->slug($tender->getTitle())->lower()->toString();

        // If the provided slug doesn't match the generated one, redirect to the correct URL
        if ($slug !== $generatedSlug) {
            return $this->redirectToRoute('job_seeker_tender_details', [
                'slug' => $generatedSlug,
                'uuid' => $tender->getUuid(),
            ], 301);  // 301 for permanent redirection
        }
        $saved_jobs = [];

        $tender_application = $em->getRepository(TenderApplication::class)->findOneBy(['applicant' => $this->getUser(), 'tender' => $tender]);

        $countries = $em->getRepository(MetierCountry::class)->createQueryBuilder('c')
            ->select('c.id', 'c.name')->getQuery()->getResult();

        return $this->render('job_seeker/tender-details.html.twig', compact('tender', 'tender_application', 'saved_jobs'));
    }

    #[Route('/course_details/{slug}/{uuid}', name: 'job_seeker_course_details')]
    public function course_details(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, $slug, EmployerCourses $course)
    {
        $generatedSlug = $slugger->slug($course->getTitle())->lower()->toString();

        // If the provided slug doesn't match the generated one, redirect to the correct URL
        if ($slug !== $generatedSlug) {
            return $this->redirectToRoute('job_seeker_course_details', [
                'slug' => $generatedSlug,
                'uuid' => $course->getUuid(),
            ], 301);  // 301 for permanent redirection
        }

        $saved_jobs = [];

        $course_application = $em->getRepository(CourseApplication::class)->findOneBy(['applicant' => $this->getUser(), 'course' => $course]);

        $countries = $em->getRepository(MetierCountry::class)->createQueryBuilder('c')
            ->select('c.id', 'c.name')->getQuery()->getResult();

        return $this->render('job_seeker/course-details.html.twig', compact('course', 'course_application', 'saved_jobs'));
    }

    #[Route('/apply_job/{slug}/{uuid}', name: 'job_seeker_apply_job')]
    public function apply_job(Request $request, SluggerInterface $slugger, EntityManagerInterface $em, EmployerJobs $job, $slug)
    {
        $generatedSlug = $slugger->slug($job->getTitle())->lower()->toString();

        // If the provided slug doesn't match the generated one, redirect to the correct URL
        if ($slug !== $generatedSlug) {
            return $this->redirectToRoute('job_seeker_apply_job', [
                'slug' => $generatedSlug,
                'uuid' => $job->getUuid(),
            ], 301);  // 301 for permanent redirection
        }

        // Redirect User to job details if profile is not complete
        $profile_percentage = $this->profile_percentage($em);
        if ($profile_percentage['percentage'] < 90) {
            return $this->redirectToRoute('job_seeker_job_details', [
                'slug' => $generatedSlug,
                'uuid' => $job->getUuid(),
            ]);
        }

        $job_application = $em->getRepository(JobApplication::class)->findOneBy(['jobSeeker' => $this->getUser(), 'job' => $job]);
        $app_answers = $em->getRepository(JobApplicationQuestionAnswer::class)->findBy(['application' => $job_application]);
        $answers = [];
        foreach ($app_answers as $answer) {
            array_push($answers, $answer->getAnswer()->getId());
        }

        $job_report = $em->getRepository(JobReport::class)->findOneBy(['job' => $job, 'reportedBy' => $this->getUser()]);

        $saved_jobs = $em->getRepository(JobSeekerSavedJob::class)
            ->createQueryBuilder('s')
            ->join('s.job', 'j')
            ->where('s.jobSeeker = :jobSeeker')
            ->setParameter('jobSeeker', $this->getUser())
            ->select('j.id')
            ->getQuery()
            ->getResult();

        $qb = $em->getRepository(EmployerJobs::class)->createQueryBuilder('j');
        $similar_jobs = $qb
            ->andWhere('j.status = :status')
            ->setParameter('status', 'posted')
            ->andWhere('j.application_closing_date >= :dateNow') // Filter based on expiry
            ->setParameter('dateNow', new DateTime())
            ->leftJoin('j.job_category', 'c')
            ->leftJoin('j.industry', 'i')
            ->leftJoin('j.city', 'ct')
            ->andWhere('j.id != :thisJob')
            ->setParameter('thisJob', $job->getId())
            ->andWhere(
                $qb->expr()->orX(
                    'j.title LIKE :title',
                    'c.id = :categoryId',  // Match category
                    'i.id = :industryId',  // Match industry
                    'ct.id = :cityId'      // Match city
                )
            )
            ->setParameter('categoryId', $job->getJobCategory()->getId())
            ->setParameter('industryId', $job->getIndustry()->getId())
            ->setParameter('cityId', $job->getCity()->getId())
            ->setParameter('title', '%' . $job->getTitle() . '%')
            ->setMaxResults(12)
            ->orderBy('j.id', 'DESC')
            ->getQuery()
            ->getResult();

        $saved_jobs = array_column($saved_jobs, 'id');

        return $this->render('job_seeker/apply-job.html.twig', compact('job', 'job_application', 'answers', 'job_report', 'similar_jobs', 'saved_jobs'));
    }
    #[Route('/apply_tender/{slug}/{uuid}', name: 'job_seeker_apply_tender')]
    public function apply_tender(Request $request, FileUploader $fileUploader, EntityManagerInterface $em, SluggerInterface $slugger, $slug, EmployerTender $tender)
    {
        $generatedSlug = $slugger->slug($tender->getTitle())->lower()->toString();

        // If the provided slug doesn't match the generated one, redirect to the correct URL
        if ($slug !== $generatedSlug) {
            return $this->redirectToRoute('job_seeker_apply_tender', [
                'slug' => $generatedSlug,
                'uuid' => $tender->getUuid(),
            ], 301);  // 301 for permanent redirection
        }
        $tender_application = $em->getRepository(TenderApplication::class)->findOneBy(['applicant' => $this->getUser(), 'tender' => $tender]);

        // $job_report = $em->getRepository(JobReport::class)->findOneBy(['job' => $job, 'reportedBy' => $this->getUser()]);

        $countries = $em->getRepository(MetierCountry::class)->createQueryBuilder('c')
            ->select('c.id', 'c.name')->getQuery()->getResult();

        $new_tender_application = new TenderApplication();

        $form = $this->createFormBuilder($new_tender_application)
            ->add('companyName', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter company name'
                ]
            ])
            ->add('country', EntityType::class, [
                'class' => MetierCountry::class,
                'placeholder' => 'Select Country',
                'choice_label' => 'name',
                'multiple' => false,
                'autocomplete' => true,
                'attr' => [
                    'class' => 'form-select form-select-lg',
                    'data-job-details-target' => 'tenderDetailCountry'
                ]
            ])
            ->add('description', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])
            ->add('companyEmail', EmailType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter company email'
                ]
            ])
            ->add('companyPhone', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter company phone'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please enter a password',
                    ]),
                ]
            ])
            ->add('attachment', FileType::class, [
                'required' => false,
                // 'data_class' => null,
                'attr' => [
                    'class' => 'form-control'
                ],
                'error_bubbling' => false,  // Ensure errors are shown in the form field itself
                'constraints' => [
                    new File([
                        'mimeTypes' => [
                            'application/pdf',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid PDF/Word document.',
                        'maxSize' => '2024k',
                        'maxSizeMessage' => 'The maximum allowed file size is 2MB.',
                    ])
                ],
            ]);;

        $form = $form->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $attachment = $form->get('attachment')->getData();

            if ($attachment) {
                $originalFilename = $fileUploader->upload($attachment, $this->getParameter('logo_directory'));
                $new_tender_application->setAttachment($originalFilename);
            }

            $new_tender_application->setTender($tender);
            $new_tender_application->setApplicant($this->getUser());
            $em->persist($new_tender_application);
            $em->flush();

            return $this->redirectToRoute('job_seeker_tender_details', ['slug' => $slugger->slug($tender->getTitle())->lower()->toString(), 'uuid' => $tender->getUuid()]);
        } // dd($form->getErrors(true));

        return $this->render('job_seeker/apply-tender.html.twig', compact('tender', 'tender_application', 'form', 'countries'));
    }

    #[Route('/apply_course/{uuid}', name: 'job_seeker_apply_course')]
    public function apply_course(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, EmployerCourses $course)
    {
        $course_application = new CourseApplication();
        $course_application->setApplicant($this->getUser());
        $course_application->setCourse($course);

        $em->persist($course_application);
        $em->flush();

        return $this->redirectToRoute('job_seeker_course_details', ['slug' => $slugger->slug($course->getTitle())->lower()->toString(), 'uuid' => $course->getUuid()]);
    }

    #[Route('/upload_cv', name: 'jobseeker_upload_cv')]
    public function function(Request $request, EntityManagerInterface $em, SluggerInterface $slugger)
    {
        if ($request->files->get('cv')) {
            $file = $request->files->get('cv');

            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

            try {
                $file->move(
                    $this->getParameter('logo_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                // Handle the exception
            }

            $user_detail = $this->getUser()->getJobseekerDetails();
            $user_detail->setCv($newFilename);
            $em->persist($user_detail);
            $em->flush();
        }

        return $this->redirectToRoute('app_job_seeker_profile');
    }

    #[Route('/apply_job_save_answers/{job}', name: 'apply_job_save_answers')]
    public function job_application_answers(Request $request, SluggerInterface $slugger, EntityManagerInterface $em, EmployerJobs $job)
    {
        $job_questions = $job->getEmployerJobQuestions();

        foreach ($job_questions as $question) {
            if ($request->get($question->getId())) {
                $selected_answer = $em->getRepository(EmployerJobQuestionAnswer::class)->find($request->get($question->getId()));
                $answer = new JobApplicationQuestionAnswer();
                $answer->setQuestion($question);
                $answer->setAnswer($selected_answer);

                $application = $em->getRepository(JobApplication::class)->findOneBy(['jobSeeker' => $this->getUser(), 'job' => $job]);
                $answer->setApplication($application);
                $em->persist($answer);
                $em->flush();
            }
        }

        return $this->redirectToRoute('job_seeker_apply_job', ['slug' => $slugger->slug($job->getTitle())->lower()->toString(), 'uuid' => $job->getUuid()]);
    }

    #[Route('/job_seeker_save_job/{job}', name: 'job_seeker_save_job', methods: ['POST', 'PATCH'])]
    public function save_job(Request $request, EntityManagerInterface $em, EmployerJobs $job)
    {
        $back = $request->headers->get('referer');

        $already_saved = $em->getRepository(JobSeekerSavedJob::class)->findOneBy(['job' => $job, 'jobSeeker' => $this->getUser()]);

        if ($already_saved) :
            $em->remove($already_saved);
            $em->flush();
        else :
            $saved_job = new JobSeekerSavedJob();
            $saved_job->setJob($job);
            $saved_job->setJobSeeker($this->getUser());
            $em->persist($saved_job);
            $em->flush();
        endif;

        if ($request->isMethod('PATCH')) {
            return new JsonResponse(['success' => true, 'saved' => $already_saved ? false : true, 'job' => $job->getId()]);
        }

        return new RedirectResponse($back);
    }


    #[Route('/save_job_seeker_status', name: 'save_job_seeker_status', methods: ['POST'])]
    public function save_job_seeker_status(Request $request, EntityManagerInterface $em)
    {

        $job_seeker_details = $this->getUser()->getJobseekerDetails();
        $careerStatusForm = $this->createFormBuilder($job_seeker_details)
            ->add('careerStatus', ChoiceType::class, [
                'placeholder' => 'Choose career status',
                'choices' => ResumeStatusEnum::cases(),
                'choice_label' => fn(ResumeStatusEnum $status) => $status->value,
                'choice_value' => fn(?ResumeStatusEnum $status) => $status?->value,
                'attr' => [
                    'class' => 'form-select'
                ]
            ]);

        $careerStatusForm = $careerStatusForm->getForm();
        $careerStatusForm->handleRequest($request);

        if ($careerStatusForm->isSubmitted() && $careerStatusForm->isValid()) {

            $em->persist($job_seeker_details);
            $em->flush();

            return $this->redirectToRoute('app_job_seeker_profile');
        }

        return $this->redirectToRoute('app_job_seeker_profile');
    }
    #[Route('/save_job_seeker_contact_type', name: 'save_job_seeker_contact_type', methods: ['POST'])]
    public function save_job_seeker_contact_type(Request $request, EntityManagerInterface $em)
    {
        $details = $this->getUser()->getJobseekerDetails();
        $details->setContactType($request->get('prefered_contact_type'));
        $em->persist($details);
        $em->flush();

        // $this->addFlash('success', 'Status Updated Successfully');

        return $this->redirectToRoute('app_job_seeker_profile');
    }

    #[Route('/jobseeker_education_form/{education?}', name: 'job_seeker_education_form')]
    public function job_seeker_education_form(Request $request, EntityManagerInterface $em, JobSeekerEducation $education = null)
    {
        $education = $education ? $education : new JobSeekerEducation();
        $errors = [];

        if ($education->getId() == null) $education->setJobSeeker($this->getUser());

        $form = $this->createForm(JobSeekerEducationsType::class, $education);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {

            if ($form->isValid()) {
                $em->persist($education);
                $em->flush();

                if ($request->isXmlHttpRequest()) {
                    return $this->json(['success' => true], Response::HTTP_OK);
                }

                return $this->redirectToRoute('app_job_seeker_profile');
            } else {
                $errors = $this->getFormErrors($form);
            }
        }

        return $this->render('job_seeker/education-form.html.twig', compact('form', 'education', 'errors'));
    }

    #[Route('/jobseeker_experience_form/{experience?}', name: 'job_seeker_experience_form')]
    public function job_seeker_experience_form(Request $request, EntityManagerInterface $em, JobSeekerExperience $experience = null): Response
    {
        $experience = $experience ? $experience : new JobSeekerExperience();
        $errors = [];

        if ($experience->getId() == null) $experience->setJobSeeker($this->getUser());

        $form = $this->createForm(JobSeekerExperienceType::class, $experience);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // dd($request);
                $em->persist($experience);
                $em->flush();

                if ($request->isXmlHttpRequest()) {
                    return $this->json(['success' => true], Response::HTTP_OK);
                }

                return $this->redirectToRoute('app_job_seeker_profile');
            } else {
                // $errors = $this->getFormErrors();
                $errors = $this->getFormErrors($form);
            }
        }

        return $this->render('job_seeker/work-experience-form.html.twig', compact('experience', 'form', 'errors'));
    }

    #[Route('/jobseeker_certificate_form/{certificate?}', name: 'job_seeker_certificate_form')]
    public function job_seeker_certificate_form(Request $request, EntityManagerInterface $em, FileUploader $file_uploader, JobSeekerCertificate $certificate = null)
    {
        $certificate = $certificate ? $certificate : new JobSeekerCertificate();
        $errors = [];

        if ($certificate->getId() == null) {
            $certificate->setJobSeeker($this->getUser());
            // $certificate->setFile(null);
        }

        $form = $this->createForm(JobSeekerCertificateType::class, $certificate);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $avatarFile = $form->get('file')->getData();
                // dd($request);
                // this condition is needed because the 'brochure' field is not required
                if ($avatarFile) {
                    $originalFilename = $file_uploader->upload($avatarFile, $this->getParameter('logo_directory') . '/certifications');

                    $certificate->setFile($originalFilename);
                }

                $em->persist($certificate);
                $em->flush();

                if ($request->isXmlHttpRequest()) {
                    return $this->json(['success' => true], Response::HTTP_OK);
                }

                return $this->redirectToRoute('app_job_seeker_profile');
            } else {
                // $errors = $this->getFormErrors();
                $errors = $this->getFormErrors($form);
            }
        }

        return $this->render('job_seeker/certificate-form.html.twig', compact('certificate', 'form', 'errors'));
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
    public function getActiveOrder(User $user): ?MetierOrder
    {
        $em = $this->em;
        $qb = $em->createQueryBuilder();

        $qb->select('o')
            ->from(MetierOrder::class, 'o')
            ->where('o.customer = :user')
            ->andWhere('o.valid_from <= :now')
            ->andWhere('o.valid_to >= :now')
            ->setParameter('user', $user)
            ->setParameter('now', new DateTimeImmutable());

        $qb->setMaxResults(1);
        $query = $qb->getQuery();
        $result = $query->getOneOrNullResult();
        // dd($result);
        return $result;
    }

    #[Route('/settings', name: 'job_seeker_settings')]
    public function job_seeker_settings(
        Request $request,
        EntityManagerInterface $em,
        FileUploader $file_uploader,
        MetierDownloadsRepository $downloads
    ) {
        $profileForm = $this->createFormBuilder();
        $profileForm
            ->add('image', FileType::class, [
                'label' => 'Choose Image (image file)',
                'mapped' => false,
                'required' => false,
            ]);

        $profileForm = $profileForm->getForm();
        $profileForm->handleRequest($request);

        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $user = $this->getUser();
            $avatarFile = $profileForm->get('image')->getData();
            // dd($request);
            // this condition is needed because the 'brochure' field is not required
            if ($avatarFile) {
                $originalFilename = $file_uploader->uploadImage($avatarFile, $this->getParameter('logo_directory') . '/profile');

                $user->setProfile($originalFilename);
            }

            $em->persist($user);
            $em->flush();
            //add a flash message
            return $this->redirectToRoute('job_seeker_settings');
        }

        $job_seeker_details = $this->getUser()->getJobSeekerDetails();

        $infoForm = $this->createFormBuilder($job_seeker_details)
            ->add('dob', null, [
                'widget' => 'single_text',
            ])
            ->add('name', TextType::class, [
                'required' => true,
                'mapped' => false,
                'data' => $this->getUser()->getName()
            ])
            ->add('phone')
            ->add('whatsappPhone')
            ->add('location')
            ->add('country', CountryAutoCompleteField::class, [
                'choice_label' => 'name',
                'required' => true,
                'attr' => [
                    'class' => 'form-select country_input'
                ],
            ])
            ->add('state', TextType::class, [
                'attr' => [
                    'class' => 'form-control state_input',
                ],
                'required' => false
            ])
            ->add('city', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-select city_input'
                ],
            ])
            ->add('gender', EntityType::class, [
                'class' => MetierGender::class,
                'choice_label' => 'name',
            ])
            ->add('zipCode', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ]);

        $infoForm->get('city')->addModelTransformer(new CityToNameTransformer($em));
        $infoForm->get('state')->addModelTransformer(new StateToNameTransformer($em));

        $infoForm = $infoForm->getForm();

        $infoForm->handleRequest($request);

        if ($infoForm->isSubmitted() && $infoForm->isValid()) {
            // dd($request);
            $user = $this->getUser();

            $em->persist($user);
            $em->persist($job_seeker_details);
            $em->flush();
            // return $this->redirectToRoute('app_job_seeker', ['infoForm' => $infoForm]);
            // return $this->redirect($request->getUri(), 303);
        }

        $activePlan = $this->getActiveOrder($this->getUser());


        // downloads
        $downloads = $downloads->findBy(['client' => $this->getUser()]);


        return $this->render('job_seeker/settings.html.twig', compact('downloads', 'infoForm', 'profileForm', 'activePlan'));
    }

    #[Route('/job_seeker_detail_form', name: 'job_seeker_detail_form')]
    public function job_seeker_detail_form(Request $request, EntityManagerInterface $em)
    {
        $form = $this->createForm(JobSeekerDetailsType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            dd($request);
            $em->persist($this->getUser()->getJobSeekerDetails());
            $em->flush();
        }

        return $this->render('job_seeker/detail-form.html.twig', compact('form'));
    }

    #[Route('/messages', name: 'app_job_seeker_messages')]
    public function messages(): Response
    {
        return $this->render('job_seeker/messages.html.twig');
    }

    #[Route('/report_job/{job}', name: 'report_job', methods: ['POST'])]
    public function report_job(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, EmployerJobs $job)
    {
        if (!$request->get('description')) {
            $this->addFlash('error', 'Please describe why you want to report this job');
            return $this->redirectToRoute('job_seeker_apply_job', ['slug' => $slugger->slug($job->getTitle())->lower()->toString(), 'uuid' => $job->getUuid()]);
        }

        $report = new JobReport();
        $report->setJob($job);
        $report->setReportedBy($this->getUser());
        $report->setDescription($request->get('description'));
        $em->persist($report);
        $em->flush();

        $url = $this->generateUrl('job_seeker_apply_job', ['slug' => $slugger->slug($job->getTitle())->lower()->toString(), 'uuid' => $job->getUuid()], UrlGeneratorInterface::ABSOLUTE_URL);

        // Append the hash fragment
        $urlWithFragment = $url . '#description';

        return new RedirectResponse($urlWithFragment);
    }


    #[Route('/serveFiles/{filename}/{type}', name: 'profile_serve_employer_image', defaults: ['type' => null])]
    public function serveFiles(string $filename, string $type = null, Request $request): Response
    {
        // Check user authentication
        dd('dead');
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $imagePath = $this->getParameter('employer_profile_images_directory') . '/' . $filename;
        // Path to the image
        if ($type === "product") {
            $imagePath = $this->getParameter('product_images_directory') . '/' . $filename;
        }
        if ($type === "sreport") {
            $imagePath = $this->getParameter('ps_report_directory') . '/' . $filename;
        }

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
    #[Route('/downloadPurchasedFile/{filename}/{type}', name: 'profile_serve_employer_image', defaults: ['type' => null])]
    public function downloadPurchasedFile(string $filename, string $type = null, Request $request): Response
    {
        // Check user authentication
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');


        $imagePath = $this->getParameter('employer_profile_images_directory') . '/' . $filename;
        // Path to the image
        if ($type === "product") {
            $imagePath = $this->getParameter('product_images_directory') . '/' . $filename;
        }
        if ($type === "sreport") {
            $imagePath = $this->getParameter('ps_report_directory') . '/' . $filename;
        }

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

    #[Route('/downloadFile/{filename}/{type}', name: 'jobseeker_download_file', defaults: ['type' => null])]
    public function downloadFile(string $filename, string $type = null, Request $request): Response
    {
        // Check user authentication
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Determine the image path based on the file type
        $imagePath = match ($type) {
            'product' => $this->getParameter('product_images_directory'),
            'sreport' => $this->getParameter('ps_report_directory'),
            default => $this->getParameter('employer_profile_images_directory'),
        };

        // Construct the full file path
        $filePath = $imagePath . '/' . $filename;

        // Check if the file exists
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File not found.');
        }

        // Set appropriate headers for file download
        $response = new Response();
        $response->headers->set('Content-Type', mime_content_type($filePath));
        $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($filePath) . '"');

        // Read   
        //  the file contents and return the response
        $contents = file_get_contents($filePath);
        $response->setContent($contents);

        return $response;
    }



    #[Route('/pricing', name: 'app_packages_pricing')]
    public function pricing(
        MetierPackagesRepository  $packages,
        Request $request,
        EntityManagerInterface $em,
    ): Response {

        $qb = $em->createQueryBuilder();

        $qb->select('o')
            ->from(MetierOrder::class, 'o')
            ->where('o.customer = :user')
            ->andWhere('o.valid_from <= :now')
            ->andWhere('o.valid_to >= :now')
            ->setParameter('user', $this->getUser())
            ->setParameter('now', new DateTimeImmutable());

        $qb->setMaxResults(1);
        $query = $qb->getQuery();
        $result = $query->getOneOrNullResult();

        $activePlan = $result;
        return $this->render('job_seeker/pricing.html.twig', [
            'packages' => $packages->findBy(['status' => true, 'type' => "jobseeker", 'category' => "subscription"]),
            'activeplan' => $activePlan,
            'employer' => $this->getUser(),
        ]);
    }
    #[Route('/checkout/{package}', name: 'app_jobseeker_purchase_checkout', methods: ['GET', 'POST'])]
    public function checkout(
        Request $request, 
        MetierPackages $package, 
        EntityManagerInterface $em,
        MetierDownloadableRepository $metierDownloadableRepository
        ): Response
    {
         $user = $this->getUser();

    // If not logged in or wrong role
    if (!$user) {
        // dd("ss");
        $session = $request->getSession();
        $session->set('redirect_after_auth', $request->getUri());


        // dd($session->get('redirect_after_auth'));
        // redirect to login or register
        return $this->redirectToRoute('app_login'); 
    }
        $class = $package->getClass();
        $package_type = $package->getCategory();
        $all_categories = ["resume", "resume2", "cover"];
        
        if (in_array($class, $all_categories) && $package_type === "product") {
            // check if its subscription or product
            // $class is in the array $all_categories
           
            $hasDownloadables = $metierDownloadableRepository->getValidDownloadable($this->getUser(), $class);

            if($hasDownloadables){
                return $this->redirectToRoute('app_jobseeker_downloadable_purchase_checkout', [
                    "package" => $package->getId(),
                    "file" => $hasDownloadables->getId(),
                ]);
            }
        } 
       
        // dd("just wait");
        $zaadForm = $this->createFormBuilder(null)
            ->add("number", NumberType::class, [
                "attr" => [
                    "class" => "form-control",
                ]
            ])
            ->getForm();

        // halkan ku qor logic-gii back-end-ka
        return $this->render('job_seeker/checkout.html.twig', compact('package', 'zaadForm'));
    }
    #[Route('/jobseekerOfferPurchase/{package}', name: 'app_jobseeker_offer_purchase_checkout', methods: ['GET', 'POST'])]
    public function jobseekerOfferPurchase(
        Request $request,
        MetierPackages $package,
        EntityManagerInterface $em,
        MetierEmailTempsRepository $temps,
        NotificationService $notificationService,
        OrderService $orderService
    ): Response {

        // dd(new DateTime("now"));
        // check if the current package has a offer
        if (!$package->isOffer()) {
            dd("h");
        }
        $form = $this->createFormBuilder(null)

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
            ->getForm();
        $form->handleRequest($request);
        $now = new \DateTime();



        // Add one month to the current date
        $futureDate = $now->add(new \DateInterval('P1M'));
        $formattedDate = $futureDate->format('d-m-Y');

        if ($form->isSubmitted() && $form->isValid()) {

            $order = new MetierOrder();
            $order->setCustomer($this->getUser());
            $order->setAmount(0);
            $order->setValidityPeriod($package->getDuration());
            $order->setPlan($package);
            $order->setCategory("jobseeker");
            $order->setPaymentStatus("free");
            $order->setOrderDate(new DateTime("now"));
            $order->setCustomerType("jobseeker");
            $order->setCustomerType("Subscription");
            $order->setOrderUid(uniqid('ord_' . $order->getId(), true));

            // dd("waa psychometric");
            $template = $temps->findOneBy(["action" => "jobseeker_free_trail_start"]);
            // Create a DateTime object for the current date and time


            // Format the date as needed (e.g., YYYY-MM-DD)

            echo $formattedDate; // Outputs the future date in the format YYYY-MM-DD

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
                "employer" => "",
                "interview_date" => "",
                "platform" => "",
                "job_title" => "",
                "link" => "",
                "job_id" => "",
                "closing_date" => $formattedDate,
                "interview_time" => "",
            ];
            $event = new SendEmailEvent($d);
            $this->eventDispatcher->dispatch($event, SendEmailEvent::class);
            $em->persist($order);

            $notification = $notificationService->createNotification(
                type: "danger",
                message: "Subscription started successfully! Enjoy your free month until " . $formattedDate,
                user: $order->getCustomer(),
                routeName: "",
                routeParams: []
            );

            $downloadable =  $orderService->createDownloadables('free', $order->getCustomer(), $order,  $order->getPlan()->getDuration());


            $em->persist($notification);
            $em->persist($order);

            $em->flush();
            $this->addFlash('success', "Subscription started successfully! Enjoy your free month until " . $formattedDate);
            return $this->redirectToRoute('app_home');
        }

        $type = "f";
        // halkan ku qor logic-gii back-end-ka
        return $this->render('job_seeker/checkout_offer.html.twig', compact('package', 'form', 'formattedDate', 'type'));
    }

    #[Route('/purchaseDownloadableItem/{package}/{file}', name: 'app_jobseeker_downloadable_purchase_checkout', methods: ['GET', 'POST'])]
    public function purchaseDownloadableItem(
        Request $request,
        MetierPackages $package,
        EntityManagerInterface $em,
        MetierDownloadable $file,
        MetierEmailTempsRepository $temps,
        NotificationService $notificationService,
        OrderService $orderService,
        MetierDownloadableRepository $metierDownloadableRepository
    ): Response {

        // dd(new DateTime("now"));
        // check if the current package has a offer
        $class = $package->getClass();
        $package_type = $package->getCategory();
        $all_categories = ["resume", "resume2", "cover"];
        if (!$package->isOffer() XOR !$file) {
           
            $hasDownloadables = $metierDownloadableRepository->getValidDownloadable($this->getUser(), $class);
            if(!$hasDownloadables){
                $this->addFlash('danger', "Sorry there has been an error");
                return $this->redirectToRoute('app_packages_pricing');
            }
        }
        // dd("yes");
        $form = $this->createFormBuilder(null)

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
            ->getForm();
        $form->handleRequest($request);
        $now = new \DateTime();



        // Add one month to the current date
        $futureDate = $now->add(new \DateInterval('P1M'));
        $formattedDate = $futureDate->format('d-m-Y');

        if ($form->isSubmitted() && $form->isValid()) {

            $order = new MetierOrder();
            $order->setCustomer($this->getUser());
            $order->setAmount(0);
            $order->setValidityPeriod(0);
            $order->setPlan($package);
            $order->setCategory("jobseeker");
            $order->setPaymentStatus("paid");
            $order->setOrderDate(new DateTime("now"));
            $order->setCustomerType("jobseeker");
            $order->setCustomerType("Product");
            $order->setOrderUid(uniqid('ord_' . $order->getId(), true));

            // dd("waa psychometric");

            $new_file_download = new MetierDownloads();
            $new_file_download->setClient($this->getUser());
            $new_file_download->setPurchase($order);
            $new_file_download->setDate(new DateTime("now"));
            $new_file_download->setFile($package->getFile());
            $new_file_download->setDownloadable(true);
            $new_file_download->setDescription($package->getName());
            $em->persist($new_file_download);


            // now update the downloadable
            $file->setHasDownloaded(true);
            $em->persist($order);
            $em->persist($file);

            $notification = $notificationService->createNotification(
                type: "danger",
                message: "Successfully downloaded a file ",
                user: $order->getCustomer(),
                routeName: "",
                routeParams: []
            );

            // $downloadable =  $orderService->createDownloadables('free', $order->getCustomer(), $order,  $order->getPlan()->getDuration());

            $em->persist($notification);

            $em->flush();
            if ($order && $new_file_download && $file) {
                $this->addFlash('success', "Successfully downloaded a file ");
                return $this->redirectToRoute('app_job_seeker_purchase_receipt', [
                    'order' => $order->getId()
                ]);

               
            }
           
        }

        $type = "d";
        // halkan ku qor logic-gii back-end-ka
        return $this->render('job_seeker/checkout_offer.html.twig', compact('package', 'form', 'formattedDate','type'));
    }


    function hasValidDigitCount($number)
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

    #[Route('/receipt/{order}', name: 'app_job_seeker_purchase_receipt', defaults: ["order"], methods: ['GET', 'POST'])]
    public function receipt(
        Request $request,
        MetierOrder $order,
        EntityManagerInterface $em,
        MetierEmailTempsRepository $temps,
    ): Response {


        // if($order)
        // $form = $this->createFormBuilder(MetierServiceOrder::class)
        //     ->add("number", NumberType::class, [
        //         "attr" => [
        //             "class" => "form-control",
        //         ]
        //     ])->getForm();

        // halkan ku qor logic-gii back-end-ka
        return $this->render('job_seeker/receipt.html.twig', compact('order'));
    }
    #[Route('/psychometricTest/{order}', name: 'app_job_seeker_psychometric_test', methods: ['GET', 'POST'], requirements: ["order" => ".+"])]
    public function psychometricTest(
        Request $request,
        MetierOrder $order,
        EntityManagerInterface $em,
        MetierEmailTempsRepository $temps,
    ): Response {


        $customer = $order->getCustomer();
        $currentUser = $this->getUser(); // Replace with your user retrieval method
        $customer = $order->getCustomer();

        if ($currentUser->getId() === $customer->getId()) {

            // The current user is equal to the order's customer
            // Perform your desired actions here
        } else {
            sweetalert()->error("Please make payment first");
            return $this->redirectToRoute('app_home');
        }
        // if($order)
        // $form = $this->createFormBuilder(MetierServiceOrder::class)
        //     ->add("number", NumberType::class, [
        //         "attr" => [
        //             "class" => "form-control",
        //         ]
        //     ])->getForm();

        // halkan ku qor logic-gii back-end-ka
        return $this->render('job_seeker/take_test.html.twig', compact('order'));
    }
    #[Route('/psychometric_finding_your_course/{order}', name: 'app_job_seeker_psychometric_finding_your_course', methods: ['GET', 'POST'], requirements: ["order" => ".+"])]
    public function psychometric_finding_your_course(
        Request $request,
        MetierOrder $order,
        EntityManagerInterface $em,
        MetierEmailTempsRepository $temps,
    ): Response {


        $customer = $order->getCustomer();
        $currentUser = $this->getUser(); // Replace with your user retrieval method
        $customer = $order->getCustomer();

        if ($currentUser->getId() === $customer->getId()) {

            // The current user is equal to the order's customer
            // Perform your desired actions here
        } else {
            sweetalert()->error("Please make payment first");
            return $this->redirectToRoute('app_home');
        }
        // if($order)
        // $form = $this->createFormBuilder(MetierServiceOrder::class)
        //     ->add("number", NumberType::class, [
        //         "attr" => [
        //             "class" => "form-control",
        //         ]
        //     ])->getForm();

        // halkan ku qor logic-gii back-end-ka
        return $this->render('job_seeker/take_test_find_your_course.html.twig', compact('order'));
    }


    #[Route('/checkOutStripe/{package}', name: 'app_jobseeker_checkout_package', methods: ['GET', 'POST'])]
    public function checkOutStripe($stripSK, Request $request, MetierPackages $package, EntityManagerInterface $em, PaymentService $paymentService): RedirectResponse
    {

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
                    'name' => "Job Seeker " . $package->getName() . " Plan",
                ],
            ],
            'quantity' => 1
        ];
        $successBaseUrl = $this->generator->generate("app_jobseeker_stripe_success", [
            'package' => $package->getId()
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        $successUrl = $successBaseUrl . '?id_sessions={CHECKOUT_SESSION_ID}';

        \Stripe\Stripe::setApiKey($stripSK);



        $checkout_session = Session::create([
            'customer_email' => $this->getUser()->getEmail(),
            // 'payment_method_types' => ['card', 'link'], 
            // 'payment_method_configuration' => 'pmc_1OomAKBX5ty6EGlHf0BmL2Zw',
            'line_items' => $products,
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $this->generator->generate("app_jobseeker_stripe_error", [
                'package' => $package->getId(),
                'id_sessions' => '{CHECKOUT_SESSION_ID}',
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('app_jobseeker_stripe_error', [
                'package' => $package->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return new RedirectResponse($checkout_session->url);
    }

    //   #[Route('/checkOutStripe/{package}', name: 'app_jobseeker_checkout_package', methods: ['GET', 'POST'])]
    // public function checkOutStripe($stripSK, Request $request, MetierPackages $package, EntityManagerInterface $em): RedirectResponse
    // {
    //     if (!$package) {
    //         dd("Package is missing");
    //     }

    //     $products = [];
    //     $products[] = [
    //         'price_data' => [
    //             'currency' => 'usd',
    //             'unit_amount' => ($package->getCost() * 100),
    //             'product_data' => [
    //                 'name' => "Job Seeker " . $package->getName() . " Plan",
    //             ],
    //         ],
    //         'quantity' => 1
    //     ];

    //     \Stripe\Stripe::setApiKey($stripSK);

    //     $checkout_session = \Stripe\Checkout\Session::create([
    //         'customer_email' => $this->getUser()->getEmail(),
    //         'payment_method_types' => ['card'],
    //         'line_items' => [
    //             $products
    //         ],
    //         'mode' => 'payment',
    //         'success_url' => $this->generateUrl('app_jobseeker_stripe_success', [
    //             'package' => $package->getId(),
    //             'id_sessions' => '{{CHECKOUT_SESSION_ID}}',
    //         ], UrlGeneratorInterface::ABSOLUTE_URL),
    //         'cancel_url' => $this->generateUrl('app_jobseeker_stripe_error', [
    //             'package' => $package->getId(),
    //         ], UrlGeneratorInterface::ABSOLUTE_URL),
    //         'automatic_tax' => [
    //             'enabled' => true,
    //         ],
    //     ]);

    //     return new RedirectResponse($checkout_session->url);
    // }


    #[Route('/stripOnSuccess/{package}', name: 'app_jobseeker_stripe_success', methods: ['GET', 'POST'])]
    public function stripOnSuccess(
        $stripSK,
        Request $request,
        MetierPackages $package,
        EntityManagerInterface $em,
        MailService $mailService,
        MetierEmailTempsRepository $temps,
    ): Response {
        $session_id = $request->query->get('id_sessions');
        $customer_data  = $this->stripeGateway->checkout->sessions->retrieve(
            $session_id,
            []
        );



        $total_amount =  $customer_data['amount_total'];

        // dump($customer_data); 

        // dd("");

        // create order information
        $order = new MetierOrder();
        $order->setCustomer($this->getUser());
        $order->setAmount($total_amount / 100);
        $order->setValidityPeriod($package->getDuration());
        $order->setPlan($package);
        $order->setCategory("jobseeker");
        $order->setPaymentStatus("paid");
        $order->setOrderDate(new DateTime("now"));
        $order->setCustomerType("employer");
        $order->setCustomerType("Subscription");
        $order->setOrderUid(uniqid('ord_' . $order->getId(), true));

        $new_payment = new MetierOrderPayment();
        $new_payment->setReceivedFrom($this->getUser());

        $new_payment->setPaymentCategory("stripe");
        $new_payment->setPaymentDate(new DateTime("now"));
        $new_payment->setPurchase($order);
        $new_payment->setAmount($total_amount);
        $em->persist($new_payment);
        $em->persist($order);
        $em->flush();

        $user = $this->getUser();

        if ($package->getType() == "jobseeker" && $package->getCategory() == "product" && $package->getClass() == "file") {
            # code...
            $new_file_download = new MetierDownloads();
            $new_file_download->setClient($this->getUser());
            $new_file_download->setPurchase($order);
            $new_file_download->setDate(new DateTime("now"));
            $new_file_download->setDownloadable(true);
            $new_file_download->setFile($package->getFile());
            $new_file_download->setDescription($package->getName());
            $order->setCustomerType("Product");
            $em->persist($new_file_download);

            // dd("waa file cashiib");
        } elseif ($package->getType() == "jobseeker" && $package->getCategory() == "service" && $package->getClass() == "psychometric") {
            $order->setCustomerType("Service");
            // dd("waa psychometric");
            $template = $temps->findOneBy(["action" => "jobseeker_purchase_psychometric"]);

            $link = $this->generateUrl('app_job_seeker_psychometric_test', [
                'order' => $order->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL);
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
                "employer" => "",
                "interview_date" => "",
                "platform" => "",
                "job_title" => "",
                "link" => $link,
                "job_id" => "",
                "closing_date" => "",
                "interview_time" => "",
            ];
            $event = new SendEmailEvent($d);
            $this->eventDispatcher->dispatch($event, SendEmailEvent::class);
        }
        elseif ($package->getType() == "jobseeker" && $package->getCategory() == "service" && $package->getClass() == "psychometric_2") {

                // dd("waa psychometric");

                $template = $temps->findOneBy(["action" => "jobseeker_purchase_psychometric_2"]);

                $link = $this->generateUrl('app_job_seeker_psychometric_finding_your_course', [
                    'order' => $order->getId()
                ], UrlGeneratorInterface::ABSOLUTE_URL);
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
                    "employer" => "",
                    "interview_date" => "",
                    "platform" => "",
                    "job_title" => "",
                    "link" => $link,
                    "job_id" => "",
                    "closing_date" => "",
                    "interview_time" => "",
                ];
                $event = new SendEmailEvent($d);
                $this->eventDispatcher->dispatch($event, SendEmailEvent::class);
            }
        
        elseif ($package->getType() == "jobseeker" && $package->getCategory() == "service" && $package->getClass() == "tailored") {
            $order->setCustomerType("Subscription");
        } elseif ($package->getType() == "jobseeker" && $package->getCategory() == "subscription") {

            $order->setCustomerType("Subscription");
            // dd("waa psychometric");
            $template = $temps->findOneBy(["action" => "jobseeker_new_subscription_plan"]);


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
                "employer" => "",
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
        } elseif ($package->getType() == "jobseeker" && $package->getCategory() == "service" && $package->getClass() == "tailored") {

            // dd("waa tailored");
            $template = $temps->findOneBy(["action" => "jobseeker_purchase_tailored"]);

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
                "employer" => "",
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
        } elseif ($package->getType() == "jobseeker" && $package->getCategory() == "service" && $package->getClass() == "branding") {

            // dd("waa branding");
            $template = $temps->findOneBy(["action" => "jobseeker_purchase_branding"]);

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
                "employer" => "",
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
        } elseif ($package->getType() == "jobseeker" && $package->getCategory() == "product" && $package->getClass() == "resume") {

            $new_file_download = new MetierDownloads();
            $new_file_download->setClient($this->getUser());
            $new_file_download->setPurchase($order);
            $new_file_download->setDate(new DateTime("now"));
            $new_file_download->setFile($package->getFile());
            $new_file_download->setDownloadable(false);
            $new_file_download->setDescription($package->getName());
            $em->persist($new_file_download);
        } elseif ($package->getType() == "jobseeker" && $package->getCategory() == "product" && $package->getClass() == "cover") {

            $new_file_download = new MetierDownloads();
            $new_file_download->setClient($this->getUser());
            $new_file_download->setPurchase($order);
            $new_file_download->setDate(new DateTime("now"));
            $new_file_download->setFile($package->getFile());
            $new_file_download->setDownloadable(false);
            $new_file_download->setDescription($package->getName());
            $em->persist($new_file_download);
        } elseif ($package->getType() == "jobseeker" && $package->getCategory() == "product" && $package->getClass() == "resume2") {

            $new_file_download = new MetierDownloads();
            $new_file_download->setClient($this->getUser());
            $new_file_download->setPurchase($order);
            $new_file_download->setDate(new DateTime("now"));
            $new_file_download->setFile($package->getFile());
            $new_file_download->setDownloadable(false);
            $new_file_download->setDescription($package->getName());
            $em->persist($new_file_download);
        }
        $em->flush();

        if ($order && $new_payment) {
            return $this->redirectToRoute('app_job_seeker_purchase_receipt', [
                'order' => $order->getId()
            ]);
        }
        // Use the Stripe API to retrieve the payment intent details
        // $stripe = new \Stripe\Stripe($stripSK);

        // halkan ku qor logic-gii back-end-ka
        return $this->render('employer/stripe_success.html.twig', compact('package'));
    }

    #[Route('/zaadPurchase/{package}', name: 'app_jobseeker_zaad_purchase', methods: ['GET', 'POST'])]
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

        // Calculate the tax
        $tax = $paymentService->calculateTax($package->getCost());

        // Calculate total amount including tax
        $totalAmount = $package->getCost() + $tax; // Stripe expects the amount in cents

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
                    "accountHolder" => "Warsame Cali Cabdi"
                ],
                "transactionInfo" => [
                    "referenceId" => "5432",
                    "invoiceId" => "5543",
                    "amount" => $totalAmount,
                    "currency" => "USD",
                    "description" => "DONATION"
                ]
            ]
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

        // calculate tax 
        $tax_amount = $paymentService->calculateTax($package->getCost());
        // dd($responseData);
        if ($statusCode === 200 && $responseData['responseCode'] === '2001' && $responseData['errorCode'] === '0') {


            // create order information
            $order_type = $package->getClass();
            $order = new MetierOrder();
            $order->setCustomer($this->getUser());
            $order->setAmount($package->getCost());
            $order->setPaymentStatus("paid");
            $order->setPlan($package);
            // Check duration category and set validity period accordingly
    if ($package->getDurationCategory() === 'weekly') {
        $order->setValidityPeriod($package->getDuration(), 'weekly');
    } else {
        $order->setValidityPeriod($package->getDuration());
    }
            $order->setOrderDate(new DateTime("now"));
            $order->setCustomerType("Product");
            $order->setCategory("jobseeker");
            $order->setType($order_type);
            $order->setTax($tax_amount);
            $order->setOrderUid(uniqid('ord_' . $order->getId(), true));

            $new_payment = new MetierOrderPayment();
            $new_payment->setReceivedFrom($this->getUser());
            $new_payment->setAmount($package->getCost());
            $new_payment->setPaymentCategory("waafi");
            $new_payment->setPaymentDate(new DateTime("now"));
            $new_payment->setPurchase($order);

            if($package->getCategory() == "subscription"){
                $order->setCustomerType(customer_type: 'subscription');
                $order->setType('subscription');
            }
            $em->persist($new_payment);
            $em->persist($order);
            $em->flush();
            $user = $this->getUser();

            if ($package->getType() == "jobseeker" && $package->getCategory() == "product" && $package->getClass() == "file") {
                # code...
                $new_file_download = new MetierDownloads();
                $new_file_download->setClient($this->getUser());
                $new_file_download->setPurchase($order);
                $new_file_download->setDate(new DateTime("now"));
                $new_file_download->setDownloadable(true);
                $new_file_download->setFile($package->getFile());
                $new_file_download->setDescription($package->getName());
                $em->persist($new_file_download);


                // dd("waa file cashiib");
            } elseif ($package->getType() == "jobseeker" && $package->getCategory() == "service" && $package->getClass() == "psychometric") {

                // dd("waa psychometric");

                $template = $temps->findOneBy(["action" => "jobseeker_purchase_psychometric"]);

                $link = $this->generateUrl('app_job_seeker_psychometric_test', [
                    'order' => $order->getId()
                ], UrlGeneratorInterface::ABSOLUTE_URL);
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
                    "employer" => "",
                    "interview_date" => "",
                    "platform" => "",
                    "job_title" => "",
                    "link" => $link,
                    "job_id" => "",
                    "closing_date" => "",
                    "interview_time" => "",
                ];
                $event = new SendEmailEvent($d);
                $this->eventDispatcher->dispatch($event, SendEmailEvent::class);
            }
            elseif ($package->getType() == "jobseeker" && $package->getCategory() == "service" && $package->getClass() == "psychometric_2") {

                // dd("waa psychometric");

                $template = $temps->findOneBy(["action" => "jobseeker_purchase_psychometric_2"]);

                $link = $this->generateUrl('app_job_seeker_psychometric_finding_your_course', [
                    'order' => $order->getId()
                ], UrlGeneratorInterface::ABSOLUTE_URL);
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
                    "employer" => "",
                    "interview_date" => "",
                    "platform" => "",
                    "job_title" => "",
                    "link" => $link,
                    "job_id" => "",
                    "closing_date" => "",
                    "interview_time" => "",
                ];
                $event = new SendEmailEvent($d);
                $this->eventDispatcher->dispatch($event, SendEmailEvent::class);
            }
            
            elseif ($package->getType() == "jobseeker" && $package->getCategory() == "service" && $package->getClass() == "tailored") {
            } elseif ($package->getType() == "jobseeker" && $package->getCategory() == "subscription") {

                // dd("waa psychometric");
                $template = $temps->findOneBy(["action" => "jobseeker_new_subscription_plan"]);
                

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
                    "employer" => "",
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
            } elseif ($package->getType() == "jobseeker" && $package->getCategory() == "service" && $package->getClass() == "tailored") {

                // dd("waa tailored");
                $template = $temps->findOneBy(["action" => "jobseeker_purchase_tailored"]);

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
                    "employer" => "",
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
            } elseif ($package->getType() == "jobseeker" && $package->getCategory() == "service" && $package->getClass() == "branding") {

                // dd("waa branding");
                $template = $temps->findOneBy(["action" => "jobseeker_purchase_branding"]);

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
                    "employer" => "",
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
            } elseif ($package->getType() == "jobseeker" && $package->getCategory() == "product" && $package->getClass() == "resume") {

                $new_file_download = new MetierDownloads();
                $new_file_download->setClient($this->getUser());
                $new_file_download->setPurchase($order);
                $new_file_download->setDate(new DateTime("now"));
                $new_file_download->setFile($package->getFile());
                $new_file_download->setDownloadable(false);
                $new_file_download->setDescription($package->getName());
                $em->persist($new_file_download);
            } elseif ($package->getType() == "jobseeker" && $package->getCategory() == "product" && $package->getClass() == "cover") {

                $new_file_download = new MetierDownloads();
                $new_file_download->setClient($this->getUser());
                $new_file_download->setPurchase($order);
                $new_file_download->setDate(new DateTime("now"));
                $new_file_download->setFile($package->getFile());
                $new_file_download->setDownloadable(false);
                $new_file_download->setDescription($package->getName());
                $em->persist($new_file_download);
            } elseif ($package->getType() == "jobseeker" && $package->getCategory() == "product" && $package->getClass() == "resume2") {

                $new_file_download = new MetierDownloads();
                $new_file_download->setClient($this->getUser());
                $new_file_download->setPurchase($order);
                $new_file_download->setDate(new DateTime("now"));
                $new_file_download->setFile($package->getFile());
                $new_file_download->setDownloadable(false);
                $new_file_download->setDescription($package->getName());
                $em->persist($new_file_download);
            }
            $em->flush();

            return new JsonResponse([
                'message' => 'Payment Successful!',
                'error_code' => $responseData['responseCode'] ?? null,
                'error_message' => $responseData['responseMsg'] ?? null,
                'redirectUrl' => $this->generateUrl('app_job_seeker_purchase_receipt', [
                    'order' => $order->getId()
                ]),
            ]);
        } else {

            return new JsonResponse([
                'message' => 'Payment Failed',
                'error_code' => $responseData['responseCode'] ?? null,
                'error_message' => $responseData['responseMsg'] ?? null,
                'test' => "just testing value",
                'redirectUrl' => null
            ]);
        }


        // halkan ku qor logic-gii back-end-ka
        return $this->render('loading.html.twig', compact('package'));
    }
    #[Route('/edahabPurchase/{package}', name: 'app_jobseeker_edahab_purchase', methods: ['GET', 'POST'])]
    public function edahabPurchase(
        $eDahabAPIKey,
        $eDahabAgentCode,
        $eDahabSecretKey,
        MailService $mailService,
        $apiKey,
        PaymentService $paymentService,
        Request $request,
        MetierPackages $package,
        EntityManagerInterface $em,
        MetierEmailTempsRepository $temps,
    ): JsonResponse {

        // Calculate the tax
        $tax = $paymentService->calculateTax($package->getCost());

        // Calculate total amount including tax
        $totalAmount = $package->getCost() + $tax; // Stripe expects the amount in cents

        // Ensure the total amount is cast to an integer (to avoid floating point issues)
        $totalAmount = round($totalAmount, 2);

        $telephone = $request->request->get('telephone');
        if ($this->hasValidDigitCount($telephone)) {
        } else {
            dd("There has been an error with your number");
        }



        // $response = $this->client->request(
        //     'POST',
        //     $waafiEndpoint,
        //     [
        //         'headers' => [
        //             'Content-Type' => 'application/json',
        //         ],
        //         'body' => $jsonData,
        //     ]
        // );
        $user = $this->getUser();

        // $statusCode = $response->getStatusCode();
        // $responseData = json_decode($response->getContent(), true);

        // calculate tax 
        $tax_amount = $paymentService->calculateTax($package->getCost());


        // $request_param = array("apiKey" => "JluXcyzS687xujZfAputkeMFUoeBp8LeQ7rOqrbBu", "edahabNumber" => $telephone, "amount" => 100, "agentCode" => "722008", "returnUrl" => "website.com", "currency" => "SLSH");


        // Prepare the request parameters
        $requestParams = [
            "apiKey" => $eDahabAPIKey,
            "edahabNumber" => $telephone,
            "amount" => $totalAmount,
            "agentCode" => $eDahabAgentCode,
            "returnUrl" => "http://localhost:8000",
            "currency" => "USD"
        ];

        // Encode it into a JSON string without escaping slashes
        $json = json_encode($requestParams, JSON_UNESCAPED_SLASHES);

        // Create the hash by concatenating the JSON and secret key, then hash it
        $secretKey = $eDahabSecretKey;
        $hashed = hash('SHA256', $json . $secretKey);

        // Build the full URL with the hash as a query parameter
        $url = "https://edahab.net/api/api/IssueInvoice?hash=" . $hashed;

        // Make the POST request with the JSON payload
        $response = $this->client->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => $json, // Send the JSON-encoded data as the body
        ]);

        // Get the response content from the API
        $result = $response->toArray(); // Automatically converts JSON response to array


        if (
            isset($result['InvoiceStatus']) && $result['InvoiceStatus'] === 'Paid' &&
            isset($result['StatusCode']) && $result['StatusCode'] === 0 &&
            !empty($result['InvoiceId'])
        ) {


            // create order information
            $order_type = $package->getClass();
            $order = new MetierOrder();
            $order->setCustomer($this->getUser());
            $order->setAmount($package->getCost());
            $order->setPaymentStatus("paid");
            $order->setPlan($package);
            $order->setValidityPeriod($package->getDuration());
            $order->setOrderDate(new DateTime("now"));
            $order->setCustomerType("Product");
            $order->setCategory("jobseeker");
            $order->setType($order_type);
            $order->setTax($tax_amount);
            $order->setOrderUid(uniqid('ord_' . $order->getId(), true));

            $new_payment = new MetierOrderPayment();
            $new_payment->setReceivedFrom($this->getUser());
            $new_payment->setAmount($package->getCost());
            $new_payment->setPaymentCategory("Edahab");
            $new_payment->setPaymentDate(new DateTime("now"));
            $new_payment->setPurchase($order);
            $em->persist($new_payment);
            $em->persist($order);
            $em->flush();
            $user = $this->getUser();

            if ($package->getType() == "jobseeker" && $package->getCategory() == "product" && $package->getClass() == "file") {
                # code...
                $new_file_download = new MetierDownloads();
                $new_file_download->setClient($this->getUser());
                $new_file_download->setPurchase($order);
                $new_file_download->setDate(new DateTime("now"));
                $new_file_download->setDownloadable(true);
                $new_file_download->setFile($package->getFile());
                $new_file_download->setDescription($package->getName());
                $em->persist($new_file_download);


                // dd("waa file cashiib");
            } elseif ($package->getType() == "jobseeker" && $package->getCategory() == "service" && $package->getClass() == "psychometric") {

                // dd("waa psychometric");

                $template = $temps->findOneBy(["action" => "jobseeker_purchase_psychometric"]);

                $link = $this->generateUrl('app_job_seeker_psychometric_test', [
                    'order' => $order->getId()
                ], UrlGeneratorInterface::ABSOLUTE_URL);
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
                    "employer" => "",
                    "interview_date" => "",
                    "platform" => "",
                    "job_title" => "",
                    "link" => $link,
                    "job_id" => "",
                    "closing_date" => "",
                    "interview_time" => "",
                ];
                $event = new SendEmailEvent($d);
                $this->eventDispatcher->dispatch($event, SendEmailEvent::class);
            }
            elseif ($package->getType() == "jobseeker" && $package->getCategory() == "service" && $package->getClass() == "psychometric_2") {

                // dd("waa psychometric");

                $template = $temps->findOneBy(["action" => "jobseeker_purchase_psychometric_2"]);

                $link = $this->generateUrl('app_job_seeker_psychometric_finding_your_course', [
                    'order' => $order->getId()
                ], UrlGeneratorInterface::ABSOLUTE_URL);
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
                    "employer" => "",
                    "interview_date" => "",
                    "platform" => "",
                    "job_title" => "",
                    "link" => $link,
                    "job_id" => "",
                    "closing_date" => "",
                    "interview_time" => "",
                ];
                $event = new SendEmailEvent($d);
                $this->eventDispatcher->dispatch($event, SendEmailEvent::class);
            }
            
            elseif ($package->getType() == "jobseeker" && $package->getCategory() == "subscription") {

                // dd("waa psychometric");
                $template = $temps->findOneBy(["action" => "jobseeker_new_subscription_plan"]);


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
                    "employer" => "",
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
            } elseif ($package->getType() == "jobseeker" && $package->getCategory() == "service" && $package->getClass() == "tailored") {

                // dd("waa tailored");
                $template = $temps->findOneBy(["action" => "jobseeker_purchase_tailored"]);

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
                    "employer" => "",
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
            } elseif ($package->getType() == "jobseeker" && $package->getCategory() == "service" && $package->getClass() == "branding") {

                // dd("waa branding");
                $template = $temps->findOneBy(["action" => "jobseeker_purchase_branding"]);

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
                    "employer" => "",
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
            } elseif ($package->getType() == "jobseeker" && $package->getCategory() == "product" && $package->getClass() == "resume") {

                $new_file_download = new MetierDownloads();
                $new_file_download->setClient($this->getUser());
                $new_file_download->setPurchase($order);
                $new_file_download->setDate(new DateTime("now"));
                $new_file_download->setFile($package->getFile());
                $new_file_download->setDownloadable(false);
                $new_file_download->setDescription($package->getName());
                $em->persist($new_file_download);
            } elseif ($package->getType() == "jobseeker" && $package->getCategory() == "product" && $package->getClass() == "cover") {

                $new_file_download = new MetierDownloads();
                $new_file_download->setClient($this->getUser());
                $new_file_download->setPurchase($order);
                $new_file_download->setDate(new DateTime("now"));
                $new_file_download->setFile($package->getFile());
                $new_file_download->setDownloadable(false);
                $new_file_download->setDescription($package->getName());
                $em->persist($new_file_download);
            } elseif ($package->getType() == "jobseeker" && $package->getCategory() == "product" && $package->getClass() == "resume2") {

                $new_file_download = new MetierDownloads();
                $new_file_download->setClient($this->getUser());
                $new_file_download->setPurchase($order);
                $new_file_download->setDate(new DateTime("now"));
                $new_file_download->setFile($package->getFile());
                $new_file_download->setDownloadable(false);
                $new_file_download->setDescription($package->getName());
                $em->persist($new_file_download);
            }
            $em->flush();

            return new JsonResponse([
                'message' => 'Payment Successful!',
                'error_code' => $responseData['responseCode'] ?? null,
                'error_message' => $responseData['responseMsg'] ?? null,
                'redirectUrl' => $this->generateUrl('app_job_seeker_purchase_receipt', [
                    'order' => $order->getId()
                ], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
        } else {

            return new JsonResponse([
                'message' => 'Payment Failed',
                'error_code' => $responseData['responseCode'] ?? null,
                'error_message' => $responseData['responseMsg'] ?? null,
                'test' => "just testing value",
                'redirectUrl' => null
            ]);
        }


        // halkan ku qor logic-gii back-end-ka
        return $this->render('loading.html.twig', compact('package'));
    }
    #[Route('/stripOnError/{package}', name: 'app_jobseeker_stripe_error', methods: ['GET', 'POST'])]
    public function stripOnError(Request $request, MetierPackages $package, EntityManagerInterface $em): Response
    {

        // halkan ku qor logic-gii back-end-ka
        return $this->render('employer/stripe_error.html.twig', compact('package'));
    }

    #[Route('/jobs/{type}', name: 'jobseeker_jobs')]
    public function jobs_list(Request $request, EntityManagerInterface $em, $type = null)
    {

        $applications = [];
        $saved_jobs = [];
        $interviews = [];

        if ($type == 'shortlisted')
            $applications = $em->getRepository(JobApplication::class)->findBy(['status' => 'shortlisted', 'jobSeeker' => $this->getUser()]);

        if ($type == 'rejected')
            $applications = $em->getRepository(JobApplication::class)->findBy(['status' => 'rejected', 'jobSeeker' => $this->getUser()]);

        if ($type == 'saved')
            $saved_jobs = $this->getUser()->getJobSeekerSavedJobs();

        if ($type == 'interview_scheduled')
            $interviews = $this->getUser()->getJobApplicationInterviews();

        if ($type == 'applied')
            $applications = $em->getRepository(JobApplication::class)->findBy(['jobSeeker' => $this->getUser()]);


        return $this->render('job_seeker/jobseeker-jobs.html.twig', compact('interviews', 'saved_jobs', 'applications', 'type'));
    }

    #[Route('/reports', name: 'jobseeker_reports')]
    public function jobseeker_reports(
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        EmployerStaffRepository $staffs,
    ): Response {
        // Get current jobseeker
        $user = $userRepository->find($this->getUser());

        // Get staff info to find employer
        $staff = $staffs->findOneBy(['jobseeker' => $user]);
        $privateJobs = [];

        if ($staff && $staff->getEmployer()) {
            $privateJobs = $staff->getEmployer()->getPrivateEmployerJobs();
        }

        // Employer views for reports
        $employers = $em->getRepository(MetierProfileView::class)
            ->createQueryBuilder('m')
            ->select('e.id as employerId, e.name as employerName, MAX(m.createdAt) as lastViewedAt, COUNT(m.id) as viewCount')
            ->join('m.employer', 'e')
            ->where('m.jobseeker = :jobSeekerId')
            ->setParameter('jobSeekerId', $user)
            ->groupBy('e.id')
            ->orderBy('lastViewedAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('job_seeker/jobseeker-reports.html.twig', [
            'employers' => $employers,
            'privateJobs' => $privateJobs,
        ]);
    }


    #[Route('/profile_toggle', name: 'profile_toggle')]
    public function profile_toggle(Request $request, EntityManagerInterface $em)
    {

        // Check if the request is AJAX and if the CSRF token is valid
        if ($this->isCsrfTokenValid('profile_toggle', $request->headers->get('X-CSRF-Token'))) {
            $data = json_decode($request->getContent(), true);
            $field = $data['field'];
            $value = $data['value'];

            // Get the currently logged-in user
            // $user = $this->getUser();
            $resume = $this->getUser()->getJobSeekerResume();
            // Update the appropriate field based on the field name
            if ($field === 'resumeVisibility') {
                $resume->setResumeVisible($value);
            } elseif ($field === 'publicProfile') {
                $resume->setPublicProfile($value);
            } elseif ($field === 'otpVerification') {
                $user = $this->getUser();
                $user->setOtpEnabled($value);
                $em->persist($user);
                $em->flush();
            } else {
                return new JsonResponse(['success' => false, 'message' => 'Invalid field'], 400);
            }

            // Save changes to the database
            $em->persist($resume);
            $em->flush();

            // Return success response
            return new JsonResponse(['success' => true]);
        }

        // Return error response if the request is invalid
        return new JsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
    }

    #[Route('/fetch_job_title', name: 'fetch_job_title')]
    public function fetch_job_title(Request $request, EntityManagerInterface $em)
    {
        $search = $request->get('search');

        $job_titles = $em->getRepository(MetierCareers::class)->createQueryBuilder('t')
            ->select('t.name as text');

        if ($search)
            $job_titles
                ->where('t.name LIKE :q')
                ->setParameter('q', '%' . $search . '%');

        $job_titles = $job_titles->setMaxResults(80)
            ->getQuery()
            ->getArrayResult();

        $job_titles = array_map(function ($option) {
            return ['value' => $option['text'], 'text' => $option['text']];
        }, $job_titles);

        return $this->json([$job_titles, 200]);
    }

    #[Route('change_password', name: 'jobseeker_change_password')]
    public function change_password(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher, TokenStorageInterface $tokenStorageInterface)
    {
        $form = $this->createFormBuilder()
            ->add('oldPassword', PasswordType::class, [
                'label' => 'Previous Password',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Enter your previous password',
                    'class' => 'form-control'
                ],
            ])
            ->add('newPassword', PasswordType::class, [
                'label' => 'New Password',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Enter your new password',
                    'class' => 'form-control'
                ],
            ])
            ->add('confirmPassword', PasswordType::class, [
                'label' => 'Confirm New Password',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Confirm your new password',
                    'class' => 'form-control'
                ],
            ])
            ->add('stay_logged_in', CheckboxType::class, [
                'label' => 'Stay logged in, after password change',
                'required' => false,
                'attr' => [
                    // 'class' => 'form-check'
                ]
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($hasher) {
                $form = $event->getForm();
                $newPassword = $form->get('newPassword')->getData();
                $confirmPassword = $form->get('confirmPassword')->getData();
                
                if(!$hasher->isPasswordValid($this->getUser(), $form->get('oldPassword')->getData())) {
                    $form->get('oldPassword')->addError(new FormError('Old password is incorrect.'));
                }

                if ($newPassword !== $confirmPassword) {
                    // Add a custom error message for password mismatch
                    $form->get('confirmPassword')->addError(new FormError('Passwords do not match.'));
                }
            });

        $form = $form->getForm();
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $user = $this->getUser();
                $newEncodedPassword = $hasher->hashPassword($user, $form->get('newPassword')->getData());
                $user->setPassword($newEncodedPassword);
                $em->persist($user);
                $em->flush();

                // Check if the user does NOT want to stay logged in
                if (!$form->get('stay_logged_in')->getData()) {
                    // Log the user out by clearing the session and token
                    $tokenStorageInterface->setToken(null);
                    $request->getSession()->invalidate();

                    // Redirect to the login page after logout
                    return $this->redirectToRoute('app_login');
                }

                // Add a success message and redirect to another route (e.g., account page)
                sweetalert()->success( 'Password changed successfully.');
                return $this->redirectToRoute('app_home');
        }

        return $this->render('job_seeker/change-password.html.twig', compact('form'));
    }


    // Internal Jobs Route


}