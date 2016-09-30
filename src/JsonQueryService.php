<?php

namespace Devim\Provider\JsonQueryServiceProvider;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\QueryBuilder;

/**
 * Class JsonQueryService.
 */
class JsonQueryService
{
    const DATETIME_FORMAT = \DateTime::RFC3339;

    /**
     * @var array
     */
    protected static $scalarMap = [
        '$eq' => 'eq',
        '$neq' => 'neq',
        '$lt' => 'lt',
        '$gt' => 'gt',
        '$lte' => 'lte',
        '$gte' => 'gte',
        '$in' => 'in',
        '$notIn' => 'notIn',
        '$out' => 'notIn',
        '$like' => 'like',
        '$notLike' => 'notLike'
    ];

    /**
     * @var array
     */
    protected static $logicMap = [
        '$and' => 'andX',
        '$or' => 'orX',
    ];

    /**
     * @var string
     */
    private $rootAlias;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var ClassMetadata
     */
    private $entityMetadata;

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $query
     *
     * @return QueryBuilder
     * @throws \InvalidArgumentException
     *
     * @throws \RuntimeException
     */
    public function apply(QueryBuilder $queryBuilder, array $query) : QueryBuilder
    {
        $this->queryBuilder = $queryBuilder;
        $this->entityMetadata = $this->queryBuilder->getEntityManager()->getClassMetadata($this->getEntityClassName());
        $this->rootAlias = $this->getRootAlias();

        if (0 !== count($query)) {
            $expr = $this->queryBuilder->expr()->andX();

            foreach ($query as $field => $value) {
                $expr->add(
                    $this->walkQuery($field, $value)
                );
            }

            $this->queryBuilder->andWhere($expr);
        }

        return $this->queryBuilder;
    }

    /**
     * @return string
     */
    private function getEntityClassName() : string
    {
        $rootEntities = $this->queryBuilder->getRootEntities();

        if (!isset($rootEntities[0])) {
            // todo: add exception
        }

        return $rootEntities[0];
    }

    /**
     * @return string
     */
    private function getRootAlias() : string
    {
        $rootAliases = $this->queryBuilder->getRootAliases();

        return isset($rootAliases[0]) ? $rootAliases[0] . '.' : '';
    }

    /**
     * @param string $field
     * @param mixed $value
     *
     * @return Composite
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    protected function walkQuery(string $field, $value) : Composite
    {
        if (array_key_exists($field, self::$logicMap)) {
            return $this->visitLogic($field, $value);
        }

        return $this->visitScalar($field, $value);
    }

    /**
     * @param string $type
     * @param array $queries
     *
     * @return Composite
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    protected function visitLogic(string $type, array $queries) : Composite
    {
        $method = self::$logicMap[$type];

        /** @var Expr\Composite $expr */
        $expr = $this->queryBuilder->expr()->$method();

        foreach ($queries as $query) {
            foreach ($query as $field => $value) {
                $expr->add(
                    $this->walkQuery($field, $value)
                );
            }
        }

        return $expr;
    }

    /**
     * @param string $field
     * @param mixed $types
     *
     * @return Composite
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    protected function visitScalar(string $field, $types) : Composite
    {
        if (!is_array($types)) {
            $types = ['$eq' => $types];
        }

        $fieldName = $this->rootAlias . $field;
        $fieldType = $this->entityMetadata->getTypeOfField($field);

        $andX = $this->queryBuilder->expr()->andX();

        foreach ($types as $type => $value) {
            if (!isset(self::$scalarMap[$type])) {
                throw new \RuntimeException(sprintf('Unsupported type "%s"', $type));
            }

            $method = self::$scalarMap[$type];

            if ($method === 'like' || $method === 'notLike') {
                $andX->add($this->queryBuilder->expr()->$method(
                    $fieldName,
                    $this->queryBuilder->expr()->literal($value)
                ));
            } else {
                if (in_array($fieldType, [Type::DATETIME, Type::DATE, Type::DATETIMETZ], true) &&
                    ($datetime = \DateTime::createFromFormat(self::DATETIME_FORMAT, $value)) !== false
                ) {
                    $value = $datetime;
                }

                $paramName = uniqid(':param_');

                $this->queryBuilder->setParameter($paramName, $value);

                $andX->add($this->queryBuilder->expr()->$method(
                    $fieldName,
                    $paramName
                ));
            }
        }

        return $andX;
    }
}
