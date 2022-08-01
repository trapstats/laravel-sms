<?php

namespace Trapstats\Sms\Transport;

use Aws\Sns\Exception\SnsException;
use Aws\Sns\SnsClient;
use Trapstats\Sms\Contracts\TransportContract;
use Trapstats\Sms\SentMessage;
use Trapstats\Sms\Sms;

class SnsTransport implements TransportContract
{
    /**
     * Create a new Sns transport instance.
     *
     * @param  \Aws\Sns\SnsClient  $client
     * @param  array  $options
     */
    public function __construct(
        protected SnsClient $client,
        protected array $options
    ) {
        //...
    }

    /**
     * @inheritDoc
     */
    public function send(Sms $message): ?SentMessage
    {
        $from = $message->getFrom() ?: $this->options['from'];

        foreach ($message->getTo() as $to) {
            try {
                $this->client->publish([
                    'Message' => $message->getContent(),
                    'PhoneNumber' => $to,
                    'MessageAttributes' => [
                        'AWS.SNS.SMS.SMSType' => [
                            'DataType' => 'String',
                            'StringValue' => 'Transactional' // $message->getDeliveryType(),
                        ],
                        ...$from
                            ? [
                                'AWS.MM.SMS.OriginationNumber' => [
                                    'DataType' => 'String',
                                    'StringValue' => $from,
                                ]
                            ]
                            : []
                    ]
                ]);
            } catch (SnsException) {
                return null;
            }
        }

        return new SentMessage($message);
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return 'sns';
    }
}