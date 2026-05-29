<?php

namespace Bindto;

use Bindto\Mapper\MapperStrategy;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Binder
{
    /** @var ValidatorInterface */
    protected $validator;
    /** @var MapperInterface */
    protected $mapper;
    /** @var array */
    protected $defaultGroups;

    public function __construct(ValidatorInterface $validator, MapperInterface $mapper, $addDefaultGroups = ['Default'])
    {
        $this->validator = $validator;
        $this->mapper = $mapper;
        if (!is_array($addDefaultGroups)) {
            $addDefaultGroups = [$addDefaultGroups];
        }
        $this->defaultGroups = $addDefaultGroups;
    }

    public static function createDefaultBinder()
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
        $mapper = MapperStrategy::createDefaultMapperStrategy();

        return new self($validator, $mapper);
    }

    public static function createSimpleProductionBinder()
    {
        $builder = Validation::createValidatorBuilder();
        $builder->setTranslationDomain('validators');
        $builder->addObjectInitializers([]);
        $builder->enableAttributeMapping();
        $validator = $builder->getValidator();

        $mapper = MapperStrategy::createDefaultMapperStrategy();

        return new self($validator, $mapper);
    }

    /**
     * @param mixed $request
     * @param mixed $object
     * @param string[] $validationGroups Groups to apply validation to
     *
     * @return BindResult
     *
     * @throws \Exception
     */
    public function bind($request, $object, array $validationGroups = [], array $metadata = [])
    {
        if (!is_object($object)) {
            $object = new $object();
        }

        $newObject = $this->mapper->map($request, $object, $metadata);

        $groups = $this->defaultGroups;

        if (method_exists($request, 'getMethod')) {
            $groups[] = $request->getMethod();
        }

        $groups = array_merge($groups, $validationGroups);

        $issues = $this->validator->validate($object, null, $groups);

        return $this->createBindResultFromFilledObject($issues, $newObject, $metadata);
    }

    protected function createBindResultFromFilledObject($issues, $object, $metadata=[])
    {
        return new BindResult($object, $issues, $metadata);
    }
}
