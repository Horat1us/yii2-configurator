<?php

declare(strict_types=1);

namespace Horat1us\Yii\Configurator;

class DefaultUserSerializer implements UserSerializerInterface
{
    public function serialize(?int $userId): ?array
    {
        return $userId !== null ? ['id' => $userId] : null;
    }
}
