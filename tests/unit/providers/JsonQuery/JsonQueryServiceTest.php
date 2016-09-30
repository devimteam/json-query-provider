<?php

use Devim\Provider\JsonQueryServiceProvider\JsonQueryService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

require_once __DIR__ . '/_fixtures/entities/BarEntity.php';

class JsonQueryServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    public function testJsonQueryService()
    {
        $qb = $this->em->createQueryBuilder();

        $qb->from('BarEntity', 'f');

        $jsonQueryService = new JsonQueryService();

        $jsonQueryService->apply($qb, json_decode('{"name": "test"}', true));
    }

    protected function setUp()
    {
        $this->markTestSkipped('incomplete');

        $paths = [__DIR__ . '/_fixtures/entities'];
        $isDevMode = true;

        $config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode);

        $this->em = EntityManager::create(['driver' => 'pdo_pgsql'], $config);
    }


    protected function tearDown()
    {
    }
}
