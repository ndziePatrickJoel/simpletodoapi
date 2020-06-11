<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;



class BasicAuthenticator extends AbstractGuardAuthenticator
{
    private $em;
    private $logger;
    private $passwordEncoder;
    private $userProvider;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger, 
    UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning false will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request)
    {
        return ($request->headers->has('php-auth-user') && $request->headers->has('php-auth-pw'));
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     */
    public function getCredentials(Request $request)
    {
        return [
            'username' => $request->headers->get('php-auth-user'),
            'password' => $request->headers->get('php-auth-pw')
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $username = $credentials['username'];
        $password = $credentials['password'];

        if (null === $username || null === $password) {
            return;
        }

        // if a User object, checkCredentials() is called

        $user =  $this->em->getRepository(User::class)
            ->findOneBy(['username' => $username]);

        
        if(!$user)
        {
            return;
        }
        
        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        // check credentials - e.g. make sure the password is valid
        // no credential check is needed in this case

        // return true to cause authentication success

        $password = $credentials['password'];

        return $this->passwordEncoder->isPasswordValid($user, $password) || $this->userProvider->authentify($credentials['username'], $credentials['password']);
       
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())

            // or to translate this message
            // $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    /**
     * Called when authentication is needed, but it's not sent
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = [
            // you might translate this message
            'message' => 'Authentication Required'
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe()
    {
        return true;
    }
}

?>