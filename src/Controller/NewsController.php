<?php

namespace App\Controller;

use App\Entity\News;
use App\Form\NewsType;
use App\Helpers\BlameableEntityTrait;
use App\Helpers\ControllerHelper;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class NewsController extends FOSRestController
{
    use ControllerHelper;
    use BlameableEntityTrait;

    /**
     * @Rest\Get("/api/news", name="get_all_news")
     *
     * @param Request $request
     * @return Response
     */
    public function allAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $news = $em->getRepository('App:News')->findAll();

        if (!$news) {
            throw new NotFoundHttpException('News not found');
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $news,
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', 2)
        );

        return new Response($this->serialize($pagination, 'json', $this->defaultNewsAttributes()), Response::HTTP_OK);
    }

    /**
     * @Rest\Get("/api/news/{id}", name="get_news")
     *
     * @param Request $request
     * @param         $id
     * @return Response
     */
    public function getAction(Request $request, $id)
    {
        $news = $this->getDoctrine()->getRepository('App:News')->find($id);

        if (!$news instanceof News) {
            throw new NotFoundHttpException('News not found');
        }

        return new Response($this->serialize($news, 'json', $this->defaultNewsAttributes()), Response::HTTP_OK);
    }

    /**
     * @Rest\Post("/api/news", name="new_news")
     *
     * @param Request $request
     * @return Response|View
     */
    public function newAction(Request $request)
    {
        $user = $this->getUser();
        if (null === $user) {
            throw new UnauthorizedHttpException('You need to be authorized');
        }
        $news = new News();
        $form = $this->createForm(NewsType::class, $news, [
            'method' => 'POST',
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $news->setCreatedBy($user);
            $news->setUpdatedBy($user);
            $em->persist($news);
            $em->flush();

            $response = new Response($this->serialize($news, 'json', $this->defaultNewsAttributes()), Response::HTTP_CREATED);

            return $response;
        }

        return View::create($form, 400);
    }

    /**
     * @Rest\Put("/api/news/{id}", name="edit_news")
     *
     * @param Request $request
     * @param News    $news
     * @return Response|View
     */
    public function editAction(Request $request, News $news)
    {
        $user = $this->getUser();
        if (null === $user) {
            throw new UnauthorizedHttpException('You need to be authorized');
        }
        $form = $this->createForm(NewsType::class, $news, [
            'method' => 'PUT',
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $news->setCreatedBy($user);
            $news->setUpdatedBy($user);
            $em->persist($news);
            $em->flush();

            $response = new Response($this->serialize($news, 'json', $this->defaultNewsAttributes()), Response::HTTP_CREATED);

            return $response;
        }

        return View::create($form, 400);
    }

    /**
     * @Rest\Delete("/api/news/{id}", name="delete_news")
     *
     * @param Request $request
     * @param News    $news
     * @return Response
     */
    public function deleteAction(Request $request, News $news)
    {
        if (!$news instanceof News) {
            throw new NotFoundHttpException('News not found');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($news);
        $em->flush();

        return new Response('', Response::HTTP_ACCEPTED);
    }

    private function defaultNewsAttributes()
    {
        return [
            'id',
            'title',
            'description',
            'createdAt' => [
                'timestamp'
            ],
            'createdBy' => [
                'id', 'username', 'email',
            ]];
    }
}