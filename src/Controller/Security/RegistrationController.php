<?php

namespace App\Controller\Security;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\LoginFormAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\HttpFoundation\JsonResponse;


class RegistrationController extends AbstractController
{
    /**
     * @Route("/register", name="app_register", methods="POST")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $data = json_decode($request->getContent(), true);
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->submit($data);

        if(!$form->isValid())
        {
            $errors =[];
            foreach ($form->getErrors(true) as $error) 
            {
                $errors[] = $error->getMessage();
            }
            return JsonResponse::create(
                [
                    'error' => 'Bad request',
                    'errorDescription' => "Validation failed with the following message ".json_encode($errors)
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = $form->getData();
        $user->setUsername($data['username']);
        $user->setFirstname(
            array_key_exists('firstname', $data) ? $data['firstname'] : ''
        );
        $user->setLastname(
            array_key_exists('lastname', $data) ? $data['lastname'] : ''
        );
        //var_dump($user->getPlainPassword());
        $user->setPassword(
            $passwordEncoder->encodePassword(
                $user,
                $form->get('plainPassword')->getData()
            )
        );

        try
        {
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
        }
        catch(\Exception $ex)
        {
            return JsonResponse::create(
                [
                    'error' => 'An SQL Exception occured',
                    'errorDescription' => $ex->getMessage()
                ],
                Response::HTTP_BAD_REQUEST
            );
        }


        return JsonResponse::create(
            [],
            Response::HTTP_CREATED
        );
        

    }
}
