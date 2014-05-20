<?php

namespace EventStore;

use EventStore\Exception\ConcurrencyException;
use EventStore\Exception\TransportException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

/**
 * Class Connection
 * @package EventStore
 */
class Connection implements ConnectionInterface
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var Reader[]
     */
    private $readers;

    /**
     * @var array
     */
    private static $defaultOptions = [
        'base_url' => 'http://127.0.0.1:2113/'
    ];

    /**
     * Constructor
     *
     * @param ClientInterface $client
     */
    protected function __construct(ClientInterface $client)
    {
        $this->client = $client;
        $this->readers = [
            'forward' => new ForwardReader($client),
            'backward' => new BackwardReader($client),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function appendToStream($stream, $expectedVersion, array $events)
    {
        $eventsArray = [];

        foreach ($events as $event) {
            $eventsArray[] = $event->toArray();
        }

        $response = $this
           ->client
           ->post('/streams/'.$stream, [
                'body' => Stream::factory(json_encode($eventsArray)),
                'headers' => [
                    'Content-type'       => 'application/vnd.eventstore.events+json',
                    'ES-ExpectedVersion' => $expectedVersion
                ],
                'exceptions' => false
           ])
        ;

        if ($response->getStatusCode() == 400) {
            throw new ConcurrencyException($response->getReasonPhrase());
        }

        if ($response->getStatusCode() != 201) {
            throw new TransportException($response->getReasonPhrase());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function readStreamEventsForward($stream, $start, $count, $resolveLinkTos)
    {
        return $this->readers['forward']->readStreamEvents($stream, $start, $count, $resolveLinkTos);
    }

    /**
     * {@inheritdoc}
     */
    public function readStreamEventsBackward($stream, $start, $count, $resolveLinkTos)
    {
        return $this->readers['backward']->readStreamEvents($stream, $start, $count, $resolveLinkTos);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteStream($stream, $hardDelete = false)
    {
        $headers = [
            'Content-type' => 'application/json',
        ];

        if ($hardDelete) {
            $headers['ES-HardDelete'] = 'true';
        }

        $this
            ->client
            ->delete('/streams/'.$stream, [
                'headers' => $headers
            ])
        ;
    }

    /**
     * @param  array      $options
     * @return Connection
     */
    public static function create(array $options = [])
    {
        $options = array_merge(self::$defaultOptions, $options);

        if (!isset($options['client'])) {
            $client = new Client([
                'base_url' => $options['base_url'],
                'exceptions' => false,
            ]);

            $options['client'] = $client;
        }

        return new self($options['client']);
    }
}
