<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator;

/**
 * Serializes a user ID to an array for inclusion in history API responses.
 *
 * Register your implementation in the DI container before bootstrapping:
 *   \Yii::$container->set(UserSerializerInterface::class, MyUserSerializer::class);
 *
 * The DefaultUserSerializer is registered automatically by Bootstrap and returns ['id' => $userId].
 */
interface UserSerializerInterface
{
    /**
     * Returns an array representation of the user, or null if userId is null.
     *
     * @return array<string, mixed>|null
     */
    public function serialize(?int $userId): ?array;
}
