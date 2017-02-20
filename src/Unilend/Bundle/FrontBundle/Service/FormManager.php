<?php


namespace Unilend\Bundle\FrontBundle\Service;


class FormManager
{

    /**
     * @param object $dbObject
     * @param object $formObject
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getModifiedContent($dbObject, $formObject)
    {
        if (get_class($dbObject) !== get_class($formObject)) {
            throw new \Exception('The objects to be compared are not of the same class');
        }

        $differences = [];
        $object      = new \ReflectionObject($dbObject);

        foreach ($object->getMethods() as $method) {
            if (
                substr($method->name, 0, 3) === 'get'
                && $method->invoke($dbObject) != $method->invoke($formObject)
            ) {
                $differences[] = str_replace('get', '', $method->name);
            }
        }

        return $differences;
    }
}
