<?php

use Bundle\CoreBundle\Application\Kernel;


class AppKernel extends Kernel
{
    public function registerBundles(): array
    {
        $bundles = [

            //own bundles

//            new \Bundle\ClientBundle\ClientBundle(),
//            new \Bundle\UserBundle\UserBundle(),
            new \Bundle\RoleBundle\RoleBundle(),
            new \Bundle\ProfileBundle\ProfileBundle(),
            new \Bundle\ClientBundle\ClientBundle(),
            new \Bundle\ProductBundle\ProductBundle(),
        ];

        return array_merge(parent::registerBundles(), $bundles);
    }
}
