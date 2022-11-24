<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/article')]
class ArticleController extends AbstractController
{
    #[Route('/', name: 'app_article_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em, PaginatorInterface $paginator, Request $request,ArticleRepository $articleRepository): Response
    {
       $db= $em->createQueryBuilder();
       $db->select('n')->from('App:Article', 'n');
       $query = $db->getQuery();
    
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            10 /*limit per page*/
        );

        return $this->render('article/index.html.twig', [
            'articles' => $pagination,'pagination'=>$pagination
        ]);
    }

    #[Route('/new', name: 'app_article_new', methods: ['GET', 'POST'])]
    public function new(EntityManagerInterface $em,Request $request, ArticleRepository $articleRepository): Response
    {
        
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        // $user = $this->getDoctrine()
        //      ->getRepository('YourBundle:User')
        //      ->findOneBy(array('email' => $email));
        $message = '';

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $request->files->get('article')['picture'];
            $title_request = $request->get('article')['title'];
            $user = $this->getDoctrine()
            ->getRepository('App\Entity\Article')
            ->findOneBy(array('title' => $title_request));
            if($user){
           $message = 'Article already exists inside our database';
           
            }else{

            $uploads_directory = $this->getParameter('uploads_directory');

            $filename = md5(uniqid()) . '.' . $file->guessExtension();

            $file->move(
                $uploads_directory, $filename
            );
           $article->setPicture($filename);
        };
            $articleRepository->save($article, true);

            return $this->redirectToRoute('app_article_index', ['message'=>$message], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('article/new.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_article_show', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]

    public function show(Article $article): Response
    {
        
        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_article_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, ArticleRepository $articleRepository): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $articleRepository->save($article, true);

            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('article/edit.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_article_delete', methods: ['POST'])]
    public function delete(Request $request, Article $article, ArticleRepository $articleRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->request->get('_token'))) {
            $articleRepository->remove($article, true);
        }

        return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
    }
}
