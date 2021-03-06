<?php namespace Ollyxar\WebSockets;

/**
 * Class Master
 * @package Ollyxar\WebSockets
 */
class Master
{
    private $workers = [];
    private $clients = [];
    private $connector;

    /**
     * Master constructor.
     *
     * @param $workers
     * @param $connector
     */
    public function __construct($workers, $connector)
    {
        $this->clients = $this->workers = $workers;
        $this->connector = $connector;
    }

    /**
     * Dispatch messaging
     *
     * @return void
     */
    public function dispatch(): void
    {
        while (true) {
            $read = $this->clients;
            $read[] = $this->connector;

            @stream_select($read, $write, $except, null);

            foreach ($read as $client) {
                if ($client === $this->connector) {
                    $client = @stream_socket_accept($client);
                }

                $data = Frame::decode($client);

                if (!$data) {
                    unset($this->clients[(int)$client]);
                    fclose($client);
                    continue;
                }

                foreach ($this->workers as $worker) {
                    if ($worker !== $client) {
                        fwrite($worker, Frame::encode($data['payload'], $data['opcode']));
                    }
                }
            }
        }
    }
}