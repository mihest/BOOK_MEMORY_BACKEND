<?php

namespace App\Command;

use App\WebSocket\ChatServer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'websocket-command',
    description: 'example command',
)]
class WebSocketServerCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("Starting WebSocket server...");

        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new ChatServer()
                )
            ),
            8099
        );

        $output->writeln("WebSocket server started on ws://localhost:8099");

        $server->run();

        return Command::SUCCESS;
    }
}