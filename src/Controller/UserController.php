<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Helpers\ControllerHelper;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class UserController extends FOSRestController
{
    use ControllerHelper;

    /**
     * @Rest\Get("/api/user", name="get_all_users")
     *
     * @param Request $request
     * @return Response
     */
    public function allAction(Request $request)
    {
        $users = $this->getDoctrine()->getRepository('App:User')->findAll();

        if ($users === null) {
            return new Response("", Response::HTTP_NOT_FOUND);
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $users,
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', 5)
        );

        return new Response($this->serialize($pagination, 'json', ['id', 'username', 'email']), Response::HTTP_OK);
    }

    /**
     * @param int $id
     * @Rest\Get("/api/user/{id}", name="get_user")
     *
     * @return Response
     */
    public function getAction(int $id)
    {
        $user = $this->getDoctrine()->getRepository('App:User')->find($id);

        if (!$user instanceof User) {
            throw new NotFoundHttpException('User not found');
        }

        return new Response($this->serialize($user, 'json', ['id', 'username', 'email']), Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @Rest\Post("/api/register", name="register")
     *
     * @return Response|static|View
     */
    public function registerAction(Request $request)
    {
        $apiUser = new User();
        $form = $this->createForm(UserType::class, $apiUser);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $apiUser = $form->getData();
            $apiUser->setEnabled(true);
            $em->persist($apiUser);
            $em->flush();

            $response = new Response($this->serialize($apiUser, 'json', ['id', 'username', 'email']), Response::HTTP_CREATED);

            return $response;
        }

        return View::create($form, 400);
    }

    /**
     * @param Request $request
     *
     * @return Response|View
     * @throws JWTEncodeFailureException
     * @Rest\Post("/api/login", name="login")
     */
    public function loginAction(Request $request)
    {
        $form = $this->createForm(UserType::class);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $apiUser = $form->getData();

            $user = $this->getDoctrine()
                ->getRepository('App:User')
                ->findOneBy(['email' => $apiUser->email]);

            if (!$user) {
                throw $this->createNotFoundException();
            }

            $isValid = $this->get('security.password_encoder')
                ->isPasswordValid($user, $apiUser->getPlainPassword());

            if (!$isValid) {
                throw new BadCredentialsException();
            }

            $token = $this->getToken($user);

            return new Response($this->serialize(['token' => $token], 'json'), Response::HTTP_OK);
        }

        return View::create($form, 400);
    }

    /**
     * @param Request $request
     * @param User    $user
     * @Rest\Put("/api/user/{id}", name="edit_user")
     *
     * @return View|Response
     */
    public function editAction(Request $request, User $user)
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $apiUser = $form->getData();

            $user = $this->getDoctrine()
                ->getRepository('App:User')
                ->findOneBy(['email' => $apiUser->email]);

            if (!$user) {
                throw $this->createNotFoundException();
            }
        }

        return View::create($form, 400);
    }

    /**
     * @Rest\Delete("/api/user/{id}", name="delete_user")
     *
     * @param Request $request
     * @param User    $user
     * @return Response
     */
    public function deleteAction(Request $request, User $user)
    {
        if (!$user instanceof User) {
            throw new NotFoundHttpException('User not found');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($user);
        $em->flush();

        return new Response('', Response::HTTP_ACCEPTED);
    }

    /**
     * Returns token for user.
     *
     * @param User $user
     *
     * @return array
     * @throws JWTEncodeFailureException
     */
    public function getToken(User $user)
    {
        return $this->container->get('lexik_jwt_authentication.encoder')
            ->encode([
                'username' => $user->getUsername(),
                'exp'      => $this->getTokenExpiryDateTime(),
            ]);
    }

    /**
     * Returns token expiration datetime.
     *
     * @return string Unixtmestamp
     */
    private function getTokenExpiryDateTime()
    {
        $tokenTtl = $this->container->getParameter('lexik_jwt_authentication.token_ttl');
        $now = new \DateTime();
        $now->add(new \DateInterval('PT' . $tokenTtl . 'S'));

        return $now->format('U');
    }
}