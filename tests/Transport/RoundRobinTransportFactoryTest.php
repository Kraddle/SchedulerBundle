<?php

declare(strict_types=1);

namespace Tests\SchedulerBundle\Transport;

use PHPUnit\Framework\TestCase;
use SchedulerBundle\SchedulePolicy\SchedulePolicyOrchestrator;
use SchedulerBundle\Transport\Dsn;
use SchedulerBundle\Transport\RoundRobinTransport;
use SchedulerBundle\Transport\RoundRobinTransportFactory;
use SchedulerBundle\Transport\TransportInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @author Guillaume Loulier <contact@guillaumeloulier.fr>
 */
final class RoundRobinTransportFactoryTest extends TestCase
{
    public function testFactoryCanSupportTransport(): void
    {
        $factory = new RoundRobinTransportFactory([]);

        self::assertFalse($factory->support('test://'));
        self::assertTrue($factory->support('roundrobin://'));
        self::assertTrue($factory->support('rr://'));
    }

    /**
     * @dataProvider provideDsn
     */
    public function testFactoryCanCreateTransport(string $dsn): void
    {
        $serializer = $this->createMock(SerializerInterface::class);

        $factory = new RoundRobinTransportFactory([]);
        $transport = $factory->createTransport(Dsn::fromString($dsn), [], $serializer, new SchedulePolicyOrchestrator([]));

        self::assertInstanceOf(TransportInterface::class, $transport);
        self::assertInstanceOf(RoundRobinTransport::class, $transport);
    }

    public function provideDsn(): \Generator
    {
        yield ['roundrobin://(memory://first_in_first_out && memory://last_in_first_out)'];
        yield ['rr://(memory://first_in_first_out && memory://last_in_first_out)'];
    }
}
