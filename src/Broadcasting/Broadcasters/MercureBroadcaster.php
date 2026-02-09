<?php declare(strict_types=1);

namespace Duijker\LaravelMercureBroadcaster\Broadcasting\Broadcasters;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Support\Stringable;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class MercureBroadcaster implements Broadcaster
{
    protected HubInterface $hub;

    public function __construct(HubInterface $hub)
    {
        $this->hub = $hub;
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function auth($request)
    {
        // Mercure does its own implementation of authorization with jwt's
        // You can add targets to Channel class to specify your audience
    }

    /**
     * Return the valid authentication response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  mixed $result
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
    {
        // Mercure does its own implementation of authorization with jwt's
        // You can add targets to Channel class to specify your audience
    }

    /**
     * Topic name from broadcastAs or channel name instead.
     *
     * @param  string $event
     * @param  string|Channel $channel
     * @return string
     */
    public function topic($event, string|Channel $channel): string
    {
        throw_unless(
            is_scalar($event),
            'TypeError',
            'broadcastAs() should return a scalar value.'
        );

        return str($event)
            ->whenContains(
                "\\", # channel name used when event namespace given "no broadcastAs() implemented"
                fn(Stringable $event) => $event->substrReplace($channel)
            )->toString();
    }


    /**
     * Broadcast the given event.
     *
     * @param  array $channels
     * @param  string $event
     * @param  array $payload
     * @return void
     */
    public function broadcast(array $channels, $event, mixed $payload = [])
    {
        $data = collect($payload)
            ->forget(['socket', 'event'])
            ->toJson();

        foreach ($channels as $channel) {
            $this->hub->publish(new Update(
                $this->topic($event, $channel),
                $data,
                $channel instanceof PrivateChannel
            ));
        }
    }
}
