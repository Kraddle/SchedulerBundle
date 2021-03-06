<?php

declare(strict_types=1);

namespace SchedulerBundle\Task;

use SchedulerBundle\Exception\InvalidArgumentException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Guillaume Loulier <contact@guillaumeloulier.fr>
 */
final class HttpTask extends AbstractTask
{
    public function __construct(string $name, string $url, string $method = 'GET', array $clientOptions = [])
    {
        $this->validateClientOptions($clientOptions);
        $this->defineOptions([
            'url' => $url,
            'method' => $method,
            'client_options' => $clientOptions,
        ], [
            'url' => ['string'],
            'method' => ['string'],
            'client_options' => ['array', 'string[]'],
        ]);

        parent::__construct($name);
    }

    public function getUrl(): string
    {
        return $this->options['url'];
    }

    public function setUrl(string $url): TaskInterface
    {
        $this->options['url'] = $url;

        return $this;
    }

    public function getMethod(): string
    {
        return $this->options['method'];
    }

    public function setMethod(string $method): TaskInterface
    {
        $this->options['method'] = $method;

        return $this;
    }

    /**
     * @return array<string,mixed>
     */
    public function getClientOptions(): array
    {
        return $this->options['client_options'];
    }

    /**
     * @param array<string,mixed> $clientOptions
     */
    public function setClientOptions(array $clientOptions): TaskInterface
    {
        $this->options['client_options'] = $clientOptions;

        return $this;
    }

    /**
     * @param array<string,mixed> $clientOptions
     */
    private function validateClientOptions(array $clientOptions = []): void
    {
        if (empty($clientOptions)) {
            return;
        }

        \array_walk($clientOptions, function ($_, $key): void {
            if (!\array_key_exists($key, HttpClientInterface::OPTIONS_DEFAULTS)) {
                throw new InvalidArgumentException(\sprintf('The following option: "%s" is not supported', $key));
            }
        });
    }
}
