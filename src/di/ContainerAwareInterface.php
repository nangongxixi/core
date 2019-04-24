<?php

namespace j\di;

/**
 * ContainerAwareInterface should be implemented by classes that depends on a Container.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface ContainerAwareInterface
{
    /**
     * Sets the container.
     *
     * @param Container|null $container A ContainerInterface instance or null
     */
    public function setContainer($container = null);

    /**
     * @return boolean
     */
    public function hasDi();

    /**
     * @param $name
     * @return mixed
     */
    public function getService($name);

    /**
     * @param $name
     * @return mixed
     */
    public function hasService($name);

}