<?php

namespace Bundle\ApiBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Bundle\CoreBundle\Controller\BaseController;

class DefaultController extends BaseController
{
    public function indexAction(Request $request): Response
    {
        return $this->json([
            'status' => self::STATUS_SUCCESS,
            'msg' => 'mensaje ddd',
        ]);
    }
}
