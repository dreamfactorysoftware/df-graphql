<?php namespace DreamFactory\Core\GraphQL;

use Illuminate\Support\Facades\Facade;

class LumenServiceProvider extends ServiceProvider
{
    /**
     * Get the active router.
     *
     * @return Router
     */
    protected function getRouter()
    {
        return property_exists($this->app, 'router') ? $this->app->router : $this->app;
    }

    /**
     * Bootstrap publishes
     *
     * @return void
     */
    protected function bootPublishes()
    {
        $configPath = __DIR__ . '/../config';
        $viewsPath = __DIR__.'/../resources/views';
        $this->mergeConfigFrom($configPath . '/config.php', 'graphql');
        $this->loadViewsFrom($viewsPath, 'graphql');
    }

    /**
     * Bootstrap router
     *
     * @return void
     */
    protected function bootRouter()
    {
        if ($this->app['config']->get('graphql.routes')) {
            $router = $this->getRouter();
            include __DIR__.'/../routes/graphql.php';
        }
    }

    /**
     * Register facade
     *
     * @return void
     */
    public function registerGraphQL()
    {
        static $registered = false;
        // Check if facades are activated
        if (Facade::getFacadeApplication() == $this->app && !$registered) {
            class_alias(\DreamFactory\Core\GraphQL\Facades\GraphQL::class, 'GraphQL');
            $registered = true;
        }

        parent::registerGraphQL();
    }
}
