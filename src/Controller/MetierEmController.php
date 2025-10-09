<?php

namespace App\Controller;

use App\Entity\User;
use DateTimeImmutable;
use App\Entity\MetierBlog;
use App\Entity\MetierOrder;
use App\Entity\MetierCountry;
use App\Form\ContactFormType;
use App\Entity\MetierContacts;
use App\Service\RecaptchaValidator;
use App\Repository\MetierAdsRepository;
use App\Repository\MetierBlogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use App\Repository\EmployerTenderRepository;
use App\Repository\MetierPackagesRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Route('/metier/em')]
class MetierEmController extends AbstractController
{
    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    #[Route('/', name: 'app_metier_em')]
    public function index(
        MetierAdsRepository $metierAdsRepository,
    ): Response
    {
        return $this->render('metier_em/index.html.twig', [
            'controller_name' => 'MetierEmController',
            'ads' => $metierAdsRepository->findActiveAds(),
        ]);
    }
    #[Route('/pr_services', name: 'app_metier_em_services')]
    public function services(
        MetierAdsRepository $metierAdsRepository,
        MetierPackagesRepository $packages,
        ): Response
    {
        $products = $packages->findBy(['status' => true, 'type' => "employer", 'category' => "service"]);
        return $this->render('metier_em/services.html.twig', [
            'controller_name' => 'MetierEmController',
            'ads' => $metierAdsRepository->findActiveAds(),
            'packages' => $products,
        ]);
    }
    #[Route('/em_blogs', name: 'app_metier_em_blogs')]
    public function em_blogs(MetierBlogRepository $blogs,): Response
    {
        $blogs = $blogs->findBy(["blog_type" => 1]);
        return $this->render('home/blogs.html.twig', [
            'blogs' => $blogs,
            'controller_name' => 'MetierEmController',
        ]);
    }
    #[Route('/read-post/{blog}', name: 'app_home_read_em_post')]
    public function read_post(
        MetierBlogRepository $blogs,
        EntityManagerInterface $em,
        MetierBlog $blog
    ): Response {

        $similar_blogs = $em->getRepository(MetierBlog::class)->findBy(['category' => $blog->getCategory()]);
        $controller_name = 'MetierEmController';
        return $this->render('home/blog.html.twig', compact('blog', 'similar_blogs', 'controller_name'));
    }
    #[Route('/faq', name: 'app_home_em_faq')]
    public function faq(
        MetierBlogRepository $blogs, Request $request, EntityManagerInterface $em, RecaptchaValidator $recaptchaValidator
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

                if($this->getUser()) $contact_form->setUser($this->getUser());

                $em->persist($contact_form);
                $em->flush();
                return $this->redirectToRoute('app_home_faq', ['form_message' => 'success']);

            } else {
                // $errors = $this->getFormErrors();
                $errors = $this->getFormErrors($form);
                return $this->redirectToRoute('app_home_faq', ['form_message' => 'error']);
            }
        }
        return $this->render('metier_em/faqs.html.twig', [
            'site_key' => $this->getParameter('recaptcha.site_key'),
            'form' => $form->createView(),
            'form_message' => $request->get('form_message'),
            'controller_name' => 'MetierEmController',
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
    #[Route('/tenders', name: 'app_em_tenders')]
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
            'controller_name' => 'MetierEmController',
            // 'tenders' => $tenders,
            'countries' => $countries,
        ]);
    }
    
    #[Route('/contacts', name: 'app_metier_em_contacts')]
    public function contacts(): Response
    {
        return $this->redirectToRoute('app_home_contacts');
        // return $this->render('home/contacts.html.twig', [
        //     'controller_name' => 'MetierEmController',
        // ]);
    }

    #[Route('/subscriptions', name: 'app_metier_em_subscriptions')]
    public function subscriptions(
        MetierPackagesRepository  $packages,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        throw new NotFoundHttpException('The resource you are looking for does not exist.');
        
        return $this->render('metier_em/subscriptions.html.twig', [
            'packages' => $packages->findBy(['status' => true, 'type' => "employer", 'category' => "subscription"]),
            'employer' => $this->getUser(),
            'controller_name' => 'MetierEmController',
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

        $query = $qb->getQuery();

        $result = $query->getOneOrNullResult();
        // dd($result);
        return $result;
    }
}
