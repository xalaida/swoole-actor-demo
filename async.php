<?php

use Swoole\Coroutine;
use Swoole\Http\Server;

$tickets = 2;

function buyTicket(): bool
{
    global $tickets;

    echo "Checking tickets..." . PHP_EOL;

    if ($tickets > 0) {
        // sleep(5); // Bad example, blocking the event loop
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

$server = new Server('127.0.0.1', 1337);

$server->on('request', function ($request, $response) use (&$tickets) {
    $sold = buyTicket();
    $response->header('Content-Type', 'application/json; charset=utf-8');
    $response->end(json_encode([
        'sold' => $sold,
        'tickets_remainig' => $tickets,
    ]));
});

$server->start();
