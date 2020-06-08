<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Form\CommentFormType;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class ConferenceController extends AbstractController
{
    /**
     * @var Environment $twig
     */
    private $twig;

    /**
     * @var EntityManagerInterface $entityManager
     */
    private $entityManager;

    /**
     * @param Environment $twig
     */
    public function __construct(Environment $twig, EntityManagerInterface $entityManager)
    {
        $this->twig = $twig;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/", name="homepage")
     *
     * @param ConferenceRepository $conferenceRepository
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function index(ConferenceRepository $conferenceRepository)
    {
        return new Response(
            $this->twig->render('conference/index.html.twig', [
                'conferences' => $conferenceRepository->findAll(),
            ])
        );
    }

    /**
     * @Route("/conference/{slug}", name="conference")
     *
     * @param Request $request
     * @param Conference $conference
     * @param CommentRepository $commentRepository
     * @param SpamChecker $spamChecker
     * @param LoggerInterface $logger
     * @param string $photoDir
     * @return Response
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function show(Request $request, Conference $conference, CommentRepository $commentRepository,
                         SpamChecker $spamChecker, LoggerInterface $logger, string $photoDir) {
        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $comment->setConference($conference);

            if($photo = $form['photo']->getData()) {
                $filename = bin2hex(random_bytes(6)) . '.' . $photo->guessExtension();

                try {
                    $photo->move($photoDir, $filename);
                } catch (FileException $e) {
                    // Give up
                    $logger->critical(sprintf('File %s could not be uploaded', $filename));
                }

                $comment->setPhotoFilename($filename);
            }

            $this->entityManager->persist($comment);

            $context = [
                'user_ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('user-agent'),
                'referrer' => $request->headers->get('referrer'),
                'permalink' => $request->getUri(),
            ];

            if (2 === $spamChecker->getSpamScore($comment, $context)) {
                $logger->critical(sprintf('Spam', $context));
                throw new \RuntimeException('SPAM!!!');
            }

            $this->entityManager->flush();

            return $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
        }

        $offset = max(0, $request->query->getInt('offset', 0));
        $paginator = $commentRepository->getCommentPaginator($conference, $offset);

        return new Response(
            $this->twig->render('conference/show.html.twig', [
                'conference' => $conference,
                'comments' => $paginator,
                'previous' => $offset - CommentRepository::PAGINATOR_PER_PAGE,
                'next' => min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE),
                'comment_form' => $form->createView(),
            ])
        );
    }
}
