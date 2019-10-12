<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Core\Encoder;

use Symfony\Component\Security\Core\Exception\LogicException;

/**
 * A generic encoder factory implementation.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class EncoderFactory implements EncoderFactoryInterface
{
    private $encoders;

    public function __construct(array $encoders)
    {
        $this->encoders = $encoders;
    }

    /**
     * {@inheritdoc}
     */
    public function getEncoder($user)
    {
        $encoderKey = null;

        if ($user instanceof EncoderAwareInterface && (null !== $encoderName = $user->getEncoderName())) {
            if (!\array_key_exists($encoderName, $this->encoders)) {
                throw new \RuntimeException(sprintf('The encoder "%s" was not configured.', $encoderName));
            }

            $encoderKey = $encoderName;
        } else {
            foreach ($this->encoders as $class => $encoder) {
                if ((\is_object($user) && $user instanceof $class) || (!\is_object($user) && (is_subclass_of($user, $class) || $user == $class))) {
                    $encoderKey = $class;
                    break;
                }
            }
        }

        if (null === $encoderKey) {
            throw new \RuntimeException(sprintf('No encoder has been configured for account "%s".', \is_object($user) ? \get_class($user) : $user));
        }

        if (!$this->encoders[$encoderKey] instanceof PasswordEncoderInterface) {
            $this->encoders[$encoderKey] = $this->createEncoder($this->encoders[$encoderKey]);
        }

        return $this->encoders[$encoderKey];
    }

    /**
     * Creates the actual encoder instance.
     *
     * @throws \InvalidArgumentException
     */
    private function createEncoder(array $config): PasswordEncoderInterface
    {
        if (isset($config['algorithm'])) {
            $config = $this->getEncoderConfigFromAlgorithm($config);
        }
        if (!isset($config['class'])) {
            throw new \InvalidArgumentException(sprintf('"class" must be set in %s.', json_encode($config)));
        }
        if (!isset($config['arguments'])) {
            throw new \InvalidArgumentException(sprintf('"arguments" must be set in %s.', json_encode($config)));
        }

        $reflection = new \ReflectionClass($config['class']);

        return $reflection->newInstanceArgs($config['arguments']);
    }

    private function getEncoderConfigFromAlgorithm(array $config): array
    {
        if ('auto' === $config['algorithm']) {
            $encoderChain = [];
            // "plaintext" is not listed as any leaked hashes could then be used to authenticate directly
            foreach ([SodiumPasswordEncoder::isSupported() ? 'sodium' : 'native', 'pbkdf2', $config['hash_algorithm']] as $algo) {
                $config['algorithm'] = $algo;
                $encoderChain[] = $this->createEncoder($config);
            }

            return [
                'class' => MigratingPasswordEncoder::class,
                'arguments' => $encoderChain,
            ];
        }

        switch ($config['algorithm']) {
            case 'plaintext':
                return [
                    'class' => PlaintextPasswordEncoder::class,
                    'arguments' => [$config['ignore_case']],
                ];

            case 'pbkdf2':
                return [
                    'class' => Pbkdf2PasswordEncoder::class,
                    'arguments' => [
                        $config['hash_algorithm'],
                        $config['encode_as_base64'],
                        $config['iterations'],
                        $config['key_length'],
                    ],
                ];

            case 'bcrypt':
                return [
                    'class' => NativePasswordEncoder::class,
                    'arguments' => [
                        $config['time_cost'] ?? null,
                        (($config['memory_cost'] ?? 0) << 10) ?: null,
                        $config['cost'] ?? null,
                        \PASSWORD_BCRYPT,
                    ],
                ];

            case 'native':
                return [
                    'class' => NativePasswordEncoder::class,
                    'arguments' => [
                        $config['time_cost'] ?? null,
                        (($config['memory_cost'] ?? 0) << 10) ?: null,
                        $config['cost'] ?? null,
                    ],
                ];

            case 'sodium':
                return [
                    'class' => SodiumPasswordEncoder::class,
                    'arguments' => [
                        $config['time_cost'] ?? null,
                        (($config['memory_cost'] ?? 0) << 10) ?: null,
                    ],
                ];

            case 'argon2i':
                if (SodiumPasswordEncoder::isSupported() && !\defined('SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13')) {
                    return [
                        'class' => SodiumPasswordEncoder::class,
                        'arguments' => [
                            $config['time_cost'] ?? null,
                            (($config['memory_cost'] ?? 0) << 10) ?: null,
                        ],
                    ];
                }

                if (!\defined('PASSWORD_ARGON2I')) {
                    throw new LogicException('Algorithm "argon2i" is not available. You should either install the sodium extension, upgrade to PHP 7.2+ or use a different encoder.');
                }

                return [
                    'class' => NativePasswordEncoder::class,
                    'arguments' => [
                        $config['time_cost'] ?? null,
                        (($config['memory_cost'] ?? 0) << 10) ?: null,
                        $config['cost'] ?? null,
                        \PASSWORD_ARGON2I,
                    ],
                ];
            case 'argon2id':
                if (($hasSodium = SodiumPasswordEncoder::isSupported()) && \defined('SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13')) {
                    return [
                        'class' => SodiumPasswordEncoder::class,
                        'arguments' => [
                            $config['time_cost'] ?? null,
                            (($config['memory_cost'] ?? 0) << 10) ?: null,
                        ],
                    ];
                }

                if (!\defined('PASSWORD_ARGON2ID')) {
                    throw new LogicException(sprintf('Algorithm "argon2id" is not available. You should either %s sodium extension, upgrade to PHP 7.3+ or use a different encoder.', $hasSodium ? 'upgrade your' : 'install the'));
                }

                return [
                    'class' => NativePasswordEncoder::class,
                    'arguments' => [
                        $config['time_cost'] ?? null,
                        (($config['memory_cost'] ?? 0) << 10) ?: null,
                        $config['cost'] ?? null,
                        \PASSWORD_ARGON2ID,
                    ],
                ];
        }

        return [
            'class' => MessageDigestPasswordEncoder::class,
            'arguments' => [
                $config['algorithm'],
                $config['encode_as_base64'],
                $config['iterations'],
            ],
        ];
    }
}
