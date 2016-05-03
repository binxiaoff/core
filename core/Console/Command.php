<?php
namespace Unilend\core\Console;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class Command extends ContainerAwareCommand
{
    public function __construct($name = null)
    {
        parent::__construct($name);
    }

    public function loadData($object, $params = array())
    {
        return $this->get('unilend.service.entity_manager')->getRepository($object, $params);
    }

    /**
     * @deprecated Each lib will be declared as a service.
     * @param string $library
     * @param array $params
     * @param bool $instanciate
     * @return bool|object
     */
    public function loadLib($library, $params = array(), $instanciate = true)
    {
        return \Unilend\core\Loader::loadLib($library, $params, $instanciate);
    }

    public function get($service)
    {
        return $this->getContainer()->get($service);
    }
}
