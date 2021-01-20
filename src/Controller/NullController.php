<?php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NullController
{
    public function indexAction(): Response
    {
        throw new NotFoundHttpException();
    }
}
