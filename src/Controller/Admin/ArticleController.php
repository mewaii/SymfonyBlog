<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Form\ConfirmationType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/article", name="admin_article_")
 */
class ArticleController extends AbstractController
{
    /**
     * @Route("/", name="list")
     */
    public function index(ArticleRepository $repository)
    {
        return $this->render('admin/article/index.html.twig', [
            'articles' => $repository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="add")
     */
    public function add(Request $request, EntityManagerInterface $em)
    {
        $form = $this->createForm(ArticleType::class);
        /**
         * handleRequest permet au formulaire de récupérer les données POST et de procéder à la validation
         */
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // getData() permet de récupérer les données de formulaire elle retourne par défaut un tableau des champs du formulaire ou il retourne un objet de la classe a laquelle il est lié
            /** @var Article $article */
            $article = $form->getData();

            $em->persist($article);
            $em->flush();

            $this->addFlash('success', 'L\'article a été crée');
            return $this->redirectToRoute('admin_article_edit', [
                'id' => $article->getId()
            ]);
        }
        return $this->render('admin/article/add.html.twig',[
            'article_form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{id}/edit", name="edit")
     */
    public function edit(Article $article, Request $request, EntityManagerInterface $em)
    {
        // on peut préremplir un form en passant un 2e argument à createForm, on passe un tableau associatif ou un objet si le form liè à une classe
       $form = $this->createForm(ArticleType::class, $article);
       // Le form va directement modifier l'objet
       $form->handleRequest($request);

       if ($form->isSubmitted() && $form->isValid()) {
           // on a pas besoin d'appeler $form->getData(), l'objet $article est directement modifié par le form
           // on a pas besoin d'appeler $em->persist(), Doctrine connait déja cet objet (il existe en bdd) il sera automiquement mise à jour
           $em->flush();
           $this->addFlash('success', 'Article mis à jour');
       }

       return $this->render('admin/article/edit.html.twig', [
           'article' => $article,
           'article_form' => $form->createView(),
       ]);
    }

    /**
     * @Route("/supression{id}", name="delete")
     */
    public function delete(Article $article, Request $request, EntityManagerInterface $em)
    {
        $form = $this->createForm(ConfirmationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $em->remove($article);
            $em->flush();

            $this->addFlash('info', sprintf('L\'article "%s" a été supprimée.', $article->getTitle()));
            return $this->redirectToRoute('admin_article_list');
        }

        return $this->render('admin/article/delete.html.twig', [
            'delete_form' => $form->createView(),
            'article' => $article,
        ]);
    }
    /**
     * @Route("/{id}/publish/{token}", name="publish")
     * le param token servira à verifier que l'action a bien été demandée par l'admin connecté (protection contre les attaques CSRF)
     */
    public function publish(Article $article, string $token, EntityManagerInterface $em)
    {
        /**
         * On doit nommer les jeton CSRF Symfony va comparer le jeton qu'il a enregistrer en session avec ce que l'on a récupérer dans l'adresse
         */
        if ($this->isCsrfTokenValid('article-publish', $token) === false) {
            $this->addFlash('danger', 'Le jeton est invalide.');
            return $this->redirectToRoute('admin_article_edit', [
               'id' => $article->getId(),
            ]);
        }

        $article->setPublishedAt(new \DateTime());
        $em->flush();

        $this->addFlash('success', 'L\'article a été publié');
        return $this->redirectToRoute('admin_article_edit', [
            'id' => $article->getId(),
        ]);
    }
}
