<?php declare(strict_types=1);

namespace Test\Ecotone\Lite\Unit;

use Ecotone\Lite\EcotoneLiteApplication;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\QueryBus;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Lite\Fixture\AddMoney;

class EcotoneLiteApplicationTest extends TestCase
{
    public function test_running_ecotone_lite_application()
    {
        $ecotoneLite = EcotoneLiteApplication::boostrap(
            configurationVariables: ["currentExchange" => 2],
            configuration: ServiceConfiguration::createWithDefaults()
                                ->withNamespaces(["Test\Ecotone\Lite\Fixture"]),
            pathToRootCatalog: __DIR__ . "/../../../"
        );

        /** @var CommandBus $commandBus */
        $commandBus = $ecotoneLite->getGatewayByName(CommandBus::class);
        /** @var QueryBus $queryBus */
        $queryBus = $ecotoneLite->getGatewayByName(QueryBus::class);

        $personId = 100;
        $commandBus->send(new AddMoney($personId, 1));

        $this->assertEquals(
            2,
            $queryBus->sendWithRouting("person.getMoney", $personId)
        );
    }
}