<?php

namespace Bigfork\SilverstripeSentryHandler;

use Sentry\ClientBuilder;
use Sentry\Integration\EnvironmentIntegration;
use Sentry\Integration\FrameContextifierIntegration;
use Sentry\Integration\ModulesIntegration;
use Sentry\Integration\RequestIntegration;
use Sentry\Integration\TransactionIntegration;
use Sentry\SentrySdk;
use Sentry\State\Hub;
use Sentry\State\HubInterface;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Factory;

class SentryHubFactory implements Factory
{
    public function create($service, array $params = []): HubInterface
    {
        $options = $this->prepareOptions($params['options'] ?? []);
        $client = ClientBuilder::create($options)->getClient();
        $hub = new Hub($client);

        SentrySdk::setCurrentHub($hub);
        return SentrySdk::getCurrentHub();
    }

    protected function prepareOptions(array $options): array
    {
        $options['dsn'] = $options['dsn'] ?? Environment::getEnv('SENTRY_DSN');

        // Set environment if not present
        if (!array_key_exists('environment', $options)) {
            $options['environment'] = Director::get_environment_type();
        }

        // Configure integrations. default_integrations are disabled by default as the default error "listener"
        // integrations cause errors to be logged twice
        if (!array_key_exists('default_integrations', $options)) {
            $options['default_integrations'] = false;
            $options['integrations'] = [
                new EnvironmentIntegration(),
                new FrameContextifierIntegration(),
                new ModulesIntegration(),
                new RequestIntegration(),
                new TransactionIntegration(),
            ];
        }

        // Include BASE_PATH as a prefix, so it's removed from file paths
        if (!array_key_exists('prefixes', $options)) {
            $options['prefixes'] = array_filter([
                ...explode(PATH_SEPARATOR, get_include_path() ?: ''),
                ...explode(PATH_SEPARATOR, BASE_PATH)
            ]);
        }

        // Stop vendor from being marked as "in app"
        if (!array_key_exists('in_app_exclude', $options)) {
            $options['in_app_exclude'] = [
                BASE_PATH . '/vendor'
            ];
        }

        return $options;
    }
}
