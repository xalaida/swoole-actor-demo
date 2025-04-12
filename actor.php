<?php

use Swoole\Coroutine\Channel;
use Swoole\Coroutine;
use Swoole\Http\Server;

use function Swoole\Coroutine\go;

$tickets = 2;

function buyTicket(): bool
{
    global $tickets;

    echo "Checking tickets..." . PHP_EOL;

    if ($tickets > 0) {
        Coroutine::sleep(5); // Simulate a delay
        $tickets -= 1;
        echo "Ticket sold. Remaining tickets: " . $tickets . PHP_EOL;
        return true;
    } else {
        echo "Sold out. Remaining tickets: " . $tickets . PHP_EOL;
        if ($tickets < 0) {
            echo "Wait...what? How did we get here?" . PHP_EOL;
        }
        return false;
    }
}

class TicketActor {
    private Channel $channel;

    public function __construct()
    {
        $this->channel = new Channel(100);
    }

    public function buyTicket(Channel $responseChannel): void
    {
        $this->channel->push([
            'responseChannel' => $responseChannel,
        ]);
    }

    public function start() {
        go(function () {
            echo "Starting ticket actor..." . PHP_EOL;

            while (true) {
                echo "Selling tickets..." . PHP_EOL;

                $message = $this->channel->pop(10);

                if (false === $message) {
                    continue;
                }

                $responseChannel = $message['responseChannel'];

                $sold = buyTicket();

                $responseChannel->push([
                    'sold' => $sold,
                    'remaining_tickets' => $GLOBALS['tickets'],
                ]);
            }
        });
    }
}

$ticketActor = new TicketActor();

$server = new Server('127.0.0.1', 1337);

$server->on('request', function ($request, $response) use ($ticketActor) {
    $responseChannel = new Channel(1);

    $ticketActor->buyTicket($responseChannel);

    $responseFromChannel = $responseChannel->pop(10);

    $response->header('Content-Type', 'application/json; charset=utf-8');
    $response->end(json_encode($responseFromChannel));
});

$server->on('start', function () use ($ticketActor) {
    $ticketActor->start();
});

// Start coroutine event loop
// We cannot run multiple event loops in Swoole
// So instead of using Swoole\Coroutine\run, we just start the server that does this for us
$server->start();
