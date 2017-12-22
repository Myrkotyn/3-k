<?php

namespace App\Controller;

use App\Entity\News;
use App\Event\AppEvents;
use App\Event\NewNewsEvent;
use App\EventListener\NewNewsListener;
use App\EventListener\NewsBlameableEvent;
use App\Form\NewsType;
use App\Security\NewsVoter;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Swagger\Annotations as SWG;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class NewsController
 *
 * @Rest\Route("/news")
 */
class NewsController extends FOSRestController
{
    /**
     * @Rest\Get("", name="get_all_news")
     *
     * @param Request $request
     * @return Response
     *
     * @SWG\Response(
     *     response="200",
     *     description="return list of news"
     * )
     * @SWG\Parameter(
     *     name="page",
     *     in="query",
     *     default="1",
     *     type="integer",
     *     description="The field used for choosing page on pagination"
     * )
     * @SWG\Parameter(
     *     name="limit",
     *     in="query",
     *     default="2",
     *     type="integer",
     *     description="The field used for limit items per page"
     * )
     * @SWG\Parameter(
     *     name="Authorization",
     *     in="header",
     *     type="string",
     *     description="JWT token for authentication",
     *     required=true
     * )
     * @SWG\Tag(name="news")
     */
    public function getAllAction(Request $request)
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

        return new Response($this->get('serializer')->serialize($pagination, 'json', ['groups' => ['default']]));
    }

    /**
     * @Rest\Get("/{id}", name="get_news")
     *
     * @param Request $request
     * @param News    $news
     * @return Response
     *
     * @SWG\Tag(name="news")
     * @SWG\Response(
     *     response="200",
     *     description="return news by id"
     * )
     * @SWG\Parameter(
     *     name="Authorization",
     *     in="header",
     *     type="string",
     *     description="JWT token for authentication",
     *     required=true
     * )
     */
    public function getAction(Request $request, News $news)
    {
        if (!$news instanceof News) {
            throw new NotFoundHttpException('News not found');
        }

        return new Response($this->get('serializer')->serialize($news, 'json', ['groups' => ['default']]), Response::HTTP_OK);
    }

    /**
     * @Rest\Post("", name="new_news")
     *
     * @param Request $request
     * @return Response|View
     *
     * @SWG\Tag(name="news")
     * @SWG\Parameter(
     *     name="news",
     *     type="json",
     *     required=true,
     *     in="body",
     *     description="json object of news",
     *     @SWG\Schema(
     *          type="object",
     *          @SWG\Property(
     *              property="news",
     *              type="object",
     *              @SWG\Property(
     *                  property="title",
     *                  type="string",
     *              ),
     *              @SWG\Property(
     *                  property="description",
     *                  type="string"
     *              )
     *          ),
     *      )
     * )
     * @SWG\Parameter(
     *     name="Authorization",
     *     in="header",
     *     type="string",
     *     description="JWT token for authentication",
     *     required=true
     * )
     * @SWG\Response(
     *     response="201",
     *     description="created a new post of news"
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Expired JWT Token | JWT Token not found | Invalid JWT Token",
     * )
     */
    public function createAction(Request $request)
    {
        $user = $this->getUser();
        $news = new News();
        $form = $this->createForm(NewsType::class, $news, [
            'method' => Request::METHOD_POST,
        ]);

        $form->handleRequest($request);
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $this->get('event_dispatcher')->dispatch(NewNewsEvent::NAME, new NewNewsEvent($news, $user));

            $em->persist($news);
            $em->flush();

            $response = new Response($this->get('serializer')->serialize($news, 'json', ['groups' => ['default']]), Response::HTTP_CREATED);

            return $response;
        }

        return View::create($form, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @Rest\Put("/{id}", name="edit_news")
     *
     * @param Request $request
     * @param News    $news
     * @return Response|View
     *
     * @SWG\Tag(name="news")
     * @SWG\Parameter(
     *     name="news",
     *     type="json",
     *     required=true,
     *     in="body",
     *     description="json object of news",
     *     @SWG\Schema(
     *          type="object",
     *          @SWG\Property(
     *              property="news",
     *              type="object",
     *              @SWG\Property(
     *                  property="title",
     *                  type="string",
     *              ),
     *              @SWG\Property(
     *                  property="description",
     *                  type="string"
     *              )
     *          ),
     *      )
     * )
     * @SWG\Parameter(
     *     name="Authorization",
     *     in="header",
     *     type="string",
     *     description="JWT token for authentication",
     *     required=true
     * )
     * @SWG\Response(
     *     response="201",
     *     description="create a new post of news"
     * )
     * @SWG\Response(
     *     response=401,
     *     description="Expired JWT Token | JWT Token not found | Invalid JWT Token",
     * )
     */
    public function updateAction(Request $request, News $news)
    {
        $user = $this->getUser();
        $this->denyAccessUnlessGranted(NewsVoter::EDIT, $news);
        $form = $this->createForm(NewsType::class, $news, [
            'method' => Request::METHOD_PUT,
        ]);

        $form->handleRequest($request);
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $news->setUpdatedBy($user);
            $em->persist($news);
            $em->flush();

            return new Response($this->get('serializer')->serialize($news, 'json', ['groups' => ['default']]), Response::HTTP_OK);
        }

        return View::create($form, 400);
    }

    /**
     * @Rest\Delete("/{id}", name="delete_news")
     *
     * @param Request $request
     * @param News    $news
     * @return Response
     *
     * @SWG\Response(
     *     response="202",
     *     description="deleted news by id"
     * )
     * @SWG\Response(
     *     response="404",
     *     description="Not found"
     * )
     * @SWG\Parameter(
     *     name="Authorization",
     *     in="header",
     *     type="string",
     *     description="JWT token for authentication",
     *     required=true
     * )
     * @SWG\Tag(name="news")
     */
    public function deleteAction(Request $request, News $news)
    {
        if (!$news instanceof News) {
            throw new NotFoundHttpException('News not found');
        }
        $this->denyAccessUnlessGranted(NewsVoter::DELETE, $news);

        $em = $this->getDoctrine()->getManager();
        $em->remove($news);
        $em->flush();

        return new JsonResponse($this->get('serializer')->serialize($eventName, 'json'), Response::HTTP_NO_CONTENT);
    }
}