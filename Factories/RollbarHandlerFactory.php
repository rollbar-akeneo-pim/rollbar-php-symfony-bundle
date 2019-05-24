<?php

namespace Rollbar\Symfony\RollbarBundle\Factories;

use Pim\Bundle\UserBundle\Entity\User;
use Pim\Bundle\UserBundle\Normalizer\UserNormalizer;
use Psr\Log\LogLevel;
use Rollbar\Monolog\Handler\RollbarHandler;
use Rollbar\Rollbar;
use Rollbar\Symfony\RollbarBundle\DependencyInjection\RollbarExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RollbarHandlerFactory
 *
 * @package Rollbar\Symfony\RollbarBundle\Factories
 */
class RollbarHandlerFactory
{
    /**
     * RollbarHandlerFactory constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $config = $container->getParameter(RollbarExtension::ALIAS . '.config');

        if (isset($_ENV['ROLLBAR_TEST_TOKEN']) && $_ENV['ROLLBAR_TEST_TOKEN']) {
            $config['access_token'] = $_ENV['ROLLBAR_TEST_TOKEN'];
        }

        if (!empty($config['person_fn']) && is_callable($config['person_fn'])) {
            $config['person'] = null;
        } else {
            
            if (empty($config['person'])) {
                
                $config['person_fn'] = function() use ($container) {
                    
                    try {
                        $token = $container->get('security.token_storage')->getToken();
                        if ($token) {
                            /** @var User $user */
                            $user = $token->getUser();

                            /** @var UserNormalizer $serializer */
                            $normalizer = $container->get('pim_user.normalizer.user');
                            $person = $normalizer->normalize($user, 'array');

                            $person['id'] = $user->getId();
                            $person['username'] = $user->getUsername();
                            $person['email'] = $user->getEmail();

                            return $person;

                        }
                    } catch (\Throwable $exception) {
                        // Ignore
                    }
                };
                
            }
            
        }

        Rollbar::init($config, false, false, false);
    }

    /**
     * Create RollbarHandler
     *
     * @return RollbarHandler
     */
    public function createRollbarHandler()
    {
        return new RollbarHandler(Rollbar::logger(), LogLevel::ERROR);
    }
}
